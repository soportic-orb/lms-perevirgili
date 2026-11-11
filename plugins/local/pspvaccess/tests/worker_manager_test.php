<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_pspvaccess;

use local_pspvaccess\tests\access_testcase;

/**
 * Tests of the synchronisation of the workers cohort.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(worker_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observer::class)]
final class worker_manager_test extends access_testcase {
    public function test_sync_adds_and_removes_with_an_audit_trail(): void {
        global $DB;

        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        // Created before the rule existed, so the creation observer did nothing.
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@example.com']);
        $DB->set_field('user', 'email', 'anna@pspv.example', ['id' => $user->id]);
        $user->email = 'anna@pspv.example';
        $admin = get_admin();

        $this->assertSame('added', worker_manager::sync_user($user, worker_manager::ORIGIN_MANUAL, $admin->id));
        $this->assertTrue($this->in_cohort($user->id));
        $this->assertNull(worker_manager::sync_user($user, worker_manager::ORIGIN_LOGIN));

        $user->email = 'anna@gmail.com';
        $this->assertSame('removed', worker_manager::sync_user($user, worker_manager::ORIGIN_TASK));
        $this->assertFalse($this->in_cohort($user->id));
        $this->assertFalse($DB->record_exists('local_pspvaccess_member', ['userid' => $user->id]));

        $audit = array_values($DB->get_records('local_pspvaccess_audit', ['userid' => $user->id], 'id'));
        $this->assertCount(2, $audit);
        $this->assertSame(['added', 'domain', 'manual', $admin->id], [
            $audit[0]->action, $audit[0]->reason, $audit[0]->origin, $audit[0]->actorid,
        ]);
        $this->assertSame(['removed', 'none', 'task', null], [
            $audit[1]->action, $audit[1]->reason, $audit[1]->origin, $audit[1]->actorid,
        ]);
    }

    public function test_members_added_by_hand_are_left_alone(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        $trainee = $this->getDataGenerator()->create_user(['email' => 'trainee@gmail.com']);
        cohort_add_member(local\structure::cohort_id(), $trainee->id);

        $this->assertNull(worker_manager::sync_user($trainee, worker_manager::ORIGIN_TASK));
        worker_manager::resync_all();
        $this->assertTrue($this->in_cohort($trainee->id));
        $this->assertSame(0, $DB->count_records('local_pspvaccess_audit'));
    }

    public function test_nothing_changes_while_no_rule_is_configured(): void {
        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        $user = $this->getDataGenerator()->create_user(['email' => 'anna@pspv.example']);
        $this->assertTrue($this->in_cohort($user->id));

        // Clearing the settings by mistake must not empty the cohort.
        set_config('emaildomains', '', 'local_pspvaccess');
        $this->assertSame(['checked' => 0, 'added' => 0, 'removed' => 0], worker_manager::resync_all());
        $this->assertTrue($this->in_cohort($user->id));
    }

    public function test_observers_follow_account_creation_profile_changes_and_logins(): void {
        global $DB;

        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        $generator = $this->getDataGenerator();

        // On creation.
        $worker = $generator->create_user(['email' => 'anna@pspv.example']);
        $external = $generator->create_user(['email' => 'eric@example.com']);
        $this->assertTrue($this->in_cohort($worker->id));
        $this->assertFalse($this->in_cohort($external->id));

        // On a profile change.
        $worker->email = 'anna@example.com';
        \core\user::update_user($worker, false);
        $this->assertFalse($this->in_cohort($worker->id));

        // On login.
        $DB->set_field('user', 'email', 'eric@pspv.example', ['id' => $external->id]);
        $this->assertFalse($this->in_cohort($external->id));
        \core\event\user_loggedin::create([
            'userid' => $external->id,
            'objectid' => $external->id,
            'other' => ['username' => $external->username],
        ])->trigger();
        $this->assertTrue($this->in_cohort($external->id));
        $this->assertSame('login', $DB->get_field('local_pspvaccess_audit', 'origin', ['userid' => $external->id]));
    }

    public function test_resync_all_finds_everybody_a_rule_could_affect(): void {
        global $DB;

        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $bydomain = $generator->create_user(['email' => 'anna@pspv.example']);
        $bylist = $generator->create_user(['email' => 'pere@gmail.com']);
        $bytenant = $generator->create_user(['email' => 'eva@outlook.com']);
        $nobody = $generator->create_user(['email' => 'eric@example.com']);

        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        set_config('useemployeelist', 1, 'local_pspvaccess');
        $DB->insert_record('local_pspvaccess_employee', ['email' => 'pere@gmail.com', 'timecreated' => 1, 'timemodified' => 1]);
        $this->link_login($bytenant->id, $this->create_worker_issuer());

        $counts = worker_manager::resync_all();
        $this->assertSame(['checked' => 3, 'added' => 3, 'removed' => 0], $counts);
        foreach ([$bydomain, $bylist, $bytenant] as $user) {
            $this->assertTrue($this->in_cohort($user->id));
        }
        $this->assertFalse($this->in_cohort($nobody->id));

        // The employee leaves the list: the next run removes them.
        $DB->delete_records('local_pspvaccess_employee');
        $counts = worker_manager::resync_all();
        $this->assertSame(1, $counts['removed']);
        $this->assertFalse($this->in_cohort($bylist->id));
        $this->assertTrue($this->in_cohort($bydomain->id));
    }

    public function test_scheduled_task_reports_what_it_did(): void {
        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        $this->getDataGenerator()->create_user(['email' => 'anna@pspv.example']);
        $this->expectOutputRegex('/1 checked, 0 added, 0 removed/');
        (new task\resync_workers())->execute();
    }
}

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

namespace local_pspvaccess\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_pspvaccess\local\structure;
use local_pspvaccess\worker_manager;

/**
 * Tests of the privacy provider.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * Creates a worker with data in every table, and an administrator who caused an audit line.
     *
     * @return \stdClass[] The worker and the administrator.
     */
    protected function create_worker(): array {
        global $DB;

        $this->resetAfterTest();
        structure::ensure();
        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        set_config('useemployeelist', 1, 'local_pspvaccess');
        $DB->insert_record('local_pspvaccess_employee', [
            'email' => 'anna@pspv.example', 'department' => 'Geriatrics', 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $admin = get_admin();
        $this->setUser($admin);
        $worker = $this->getDataGenerator()->create_user(['email' => 'anna@pspv.example']);
        worker_manager::record_tenant_login($worker->id);
        return [$worker, $admin];
    }

    public function test_export(): void {
        [$worker, $admin] = $this->create_worker();
        $system = \core\context\system::instance();

        $contextlist = provider::get_contexts_for_userid($worker->id);
        $this->assertSame([$system->id], array_map('intval', $contextlist->get_contextids()));
        $this->assertCount(1, provider::get_contexts_for_userid($admin->id));
        $this->assertCount(0, provider::get_contexts_for_userid($this->getDataGenerator()->create_user()->id));

        $userlist = new \core_privacy\local\request\userlist($system, 'local_pspvaccess');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$worker->id, $admin->id], $userlist->get_userids());

        $this->export_context_data_for_user($worker->id, $system, 'local_pspvaccess');
        $writer = writer::with_context($system);
        $path = [get_string('pluginname', 'local_pspvaccess')];
        $member = $writer->get_data(array_merge($path, [get_string('privacy:path:member', 'local_pspvaccess')]));
        $this->assertSame('domain,list', $member->reason);
        $audit = $writer->get_data(array_merge($path, [get_string('privacy:path:audit', 'local_pspvaccess')]));
        $this->assertCount(1, $audit->changes);
        $this->assertSame('added', $audit->changes[0]->action);
        $employee = $writer->get_data(array_merge($path, [get_string('privacy:path:employee', 'local_pspvaccess')]));
        $this->assertSame('Geriatrics', $employee->department);

        provider::export_user_preferences($worker->id);
        $this->assertNotEmpty((array) $writer->get_user_preferences('local_pspvaccess'));
    }

    public function test_delete_one_user_keeps_what_they_did_to_others(): void {
        global $DB;

        [$worker, $admin] = $this->create_worker();
        $system = \core\context\system::instance();

        // The administrator is erased: the line about the worker stays, without the actor.
        provider::delete_data_for_user(new approved_contextlist($admin, 'local_pspvaccess', [$system->id]));
        $this->assertSame(1, $DB->count_records('local_pspvaccess_audit', ['userid' => $worker->id]));
        $this->assertNull($DB->get_field('local_pspvaccess_audit', 'actorid', ['userid' => $worker->id]));

        $userlist = new \core_privacy\local\request\approved_userlist($system, 'local_pspvaccess', [$worker->id]);
        provider::delete_data_for_users($userlist);
        $this->assertSame(0, $DB->count_records('local_pspvaccess_audit'));
        $this->assertSame(0, $DB->count_records('local_pspvaccess_member'));
        $this->assertSame(0, $DB->count_records('local_pspvaccess_employee'));
    }

    public function test_delete_all(): void {
        global $DB;

        $this->create_worker();
        provider::delete_data_for_all_users_in_context(\core\context\system::instance());
        foreach (['member', 'audit', 'employee'] as $table) {
            $this->assertSame(0, $DB->count_records('local_pspvaccess_' . $table));
        }
    }
}

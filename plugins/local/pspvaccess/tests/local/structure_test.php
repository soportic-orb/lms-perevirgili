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

namespace local_pspvaccess\local;

use local_pspvaccess\tests\access_testcase;

/**
 * Tests of the access model: who sees which category.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(structure::class)]
final class structure_test extends access_testcase {
    /**
     * Whether a user can see a category and the courses in it.
     *
     * @param string $idnumber Category idnumber.
     * @param \stdClass $user
     * @return bool
     */
    protected function sees(string $idnumber, \stdClass $user): bool {
        return \core_course_category::get(structure::category_id($idnumber), IGNORE_MISSING, false, $user) !== null;
    }

    public function test_ensure_is_idempotent(): void {
        global $DB;

        structure::ensure();
        structure::ensure();
        $this->assertSame(1, $DB->count_records('cohort', ['idnumber' => structure::COHORT]));
        $this->assertSame(1, $DB->count_records('course_categories', ['idnumber' => structure::CATEGORY_INTERNAL]));
        $this->assertSame(1, $DB->count_records('course_categories', ['idnumber' => structure::CATEGORY_EXTERNAL]));
        foreach ([structure::ROLE_WORKER, structure::ROLE_TRAINING_MANAGER, structure::ROLE_SALES_MANAGER] as $shortname) {
            $this->assertSame(1, $DB->count_records('role', ['shortname' => $shortname]));
        }
    }

    public function test_only_workers_see_the_internal_category(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        set_config('emaildomains', 'pspv.example', 'local_pspvaccess');
        $generator = $this->getDataGenerator();
        $worker = $generator->create_user(['email' => 'anna@pspv.example']);
        $external = $generator->create_user(['email' => 'eric@example.com']);
        $guest = guest_user();

        $this->assertTrue($this->sees(structure::CATEGORY_INTERNAL, $worker));
        $this->assertFalse($this->sees(structure::CATEGORY_INTERNAL, $external));
        $this->assertFalse($this->sees(structure::CATEGORY_INTERNAL, $guest));

        foreach ([$worker, $external, $guest] as $user) {
            $this->assertTrue($this->sees(structure::CATEGORY_EXTERNAL, $user));
        }

        // The courses of the internal category are not listed for those who cannot see it.
        $course = $generator->create_course(['category' => structure::category_id(structure::CATEGORY_INTERNAL)]);
        $this->setUser($external);
        $this->assertFalse(\core_course_category::can_view_course_info($course));
        $this->setUser($worker);
        $this->assertTrue(\core_course_category::can_view_course_info($course));

        // A worker who stops being one loses sight of it.
        $worker->email = 'anna@gmail.com';
        $DB->set_field('user', 'email', $worker->email, ['id' => $worker->id]);
        \local_pspvaccess\worker_manager::sync_user($worker, \local_pspvaccess\worker_manager::ORIGIN_TASK);
        $this->assertFalse($this->sees(structure::CATEGORY_INTERNAL, $worker));
    }

    public function test_cohort_members_added_by_hand_see_the_internal_category(): void {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $trainee = $this->getDataGenerator()->create_user();
        $this->assertFalse($this->sees(structure::CATEGORY_INTERNAL, $trainee));

        cohort_add_member(structure::cohort_id(), $trainee->id);
        $this->assertTrue($this->sees(structure::CATEGORY_INTERNAL, $trainee));

        cohort_remove_member(structure::cohort_id(), $trainee->id);
        $this->assertFalse($this->sees(structure::CATEGORY_INTERNAL, $trainee));
    }

    public function test_managers_see_the_internal_category_and_cannot_configure_the_site(): void {
        global $DB;

        $system = \core\context\system::instance();
        $generator = $this->getDataGenerator();
        $manager = $generator->create_user();
        $sales = $generator->create_user();
        role_assign($DB->get_field('role', 'id', ['shortname' => structure::ROLE_TRAINING_MANAGER]), $manager->id, $system->id);
        role_assign($DB->get_field('role', 'id', ['shortname' => structure::ROLE_SALES_MANAGER]), $sales->id, $system->id);

        $this->assertTrue($this->sees(structure::CATEGORY_INTERNAL, $manager));
        $this->assertTrue(has_capability('moodle/course:create', $system, $manager));
        $this->assertTrue(has_capability('moodle/user:create', $system, $manager));
        $this->assertFalse(has_capability('moodle/site:config', $system, $manager));
        $this->assertTrue(has_capability('local/pspvaccess:viewaudit', $system, $manager));
        $this->assertFalse(has_capability('local/pspvaccess:manageemployees', $system, $manager));

        // The sales manager gets its capabilities with the shop; meanwhile it is an ordinary user.
        $this->assertFalse($this->sees(structure::CATEGORY_INTERNAL, $sales));
        $this->assertFalse(has_capability('moodle/course:create', $system, $sales));
    }
}

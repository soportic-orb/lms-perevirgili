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

namespace local_pspvaccredit\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_pspvaccredit\accreditation;
use local_pspvaccredit\attendance;

/**
 * Tests of the privacy provider.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends \advanced_testcase {
    public function test_attendance_and_records_are_exported_and_deleted_by_course(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator();
        $courses = [];
        $sessions = [];
        foreach ([0, 1] as $i) {
            $courses[$i] = $generator->create_course(['customfield_pspv_accredited' => 1, 'customfield_pspv_minattendance' => 50]);
            $sessions[$i] = attendance::save_session((object) [
                'courseid' => $courses[$i]->id, 'name' => 'Workshop', 'sessiondate' => time(), 'duration' => 60,
            ]);
        }
        $user = $generator->create_user();
        $other = $generator->create_user();
        foreach ($courses as $i => $course) {
            $generator->enrol_user($user->id, $course->id, 'student');
            $generator->enrol_user($other->id, $course->id, 'student');
            attendance::mark((int) $sessions[$i]->id, [$user->id, $other->id], [$user->id, $other->id], 2);
            accreditation::sync_course((int) $course->id);
        }
        $contexts = array_map(fn($course) => \core\context\course::instance($course->id), $courses);

        $contextids = array_map('intval', provider::get_contexts_for_userid((int) $user->id)->get_contextids());
        $this->assertEqualsCanonicalizing([$contexts[0]->id, $contexts[1]->id], $contextids);
        $userlist = new \core_privacy\local\request\userlist($contexts[0], 'local_pspvaccredit');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$user->id, $other->id], $userlist->get_userids());

        $approved = new approved_contextlist($user, 'local_pspvaccredit', [$contexts[0]->id]);
        provider::export_user_data($approved);
        $data = writer::with_context($contexts[0])->get_data([get_string('pluginname', 'local_pspvaccredit')]);
        $this->assertEquals(100, $data->record->attendance);
        $this->assertSame('Workshop', $data->attended[0]->session);

        // Deleting the data of a user in a course leaves the rest alone.
        provider::delete_data_for_user($approved);
        $this->assertNull(accreditation::record((int) $courses[0]->id, (int) $user->id));
        $this->assertNotNull(accreditation::record((int) $courses[0]->id, (int) $other->id));
        $this->assertNotNull(accreditation::record((int) $courses[1]->id, (int) $user->id));
        $this->assertEquals([$other->id], attendance::present((int) $sessions[0]->id));
        $this->assertCount(2, attendance::present((int) $sessions[1]->id));

        $approvedusers = new \core_privacy\local\request\approved_userlist($contexts[1], 'local_pspvaccredit', [$other->id]);
        provider::delete_data_for_users($approvedusers);
        $this->assertEquals([$user->id], attendance::present((int) $sessions[1]->id));
        $this->assertNull(accreditation::record((int) $courses[1]->id, (int) $other->id));

        provider::delete_data_for_all_users_in_context($contexts[1]);
        $this->assertSame(0, $DB->count_records('local_pspvaccredit_record', ['courseid' => $courses[1]->id]));
        $this->assertSame([], attendance::present((int) $sessions[1]->id));
        $this->assertSame(1, $DB->count_records('local_pspvaccredit_record'));
    }
}

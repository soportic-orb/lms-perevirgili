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

namespace local_pspvaccredit;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->libdir . '/gradelib.php');

/**
 * Tests of the course settings, the attendance, the criteria and the register of who passed.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(accreditation::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(attendance::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(course_settings::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(evaluator::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(local\setup::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(task\sync_accreditations::class)]
final class accreditation_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * Creates an accredited course.
     *
     * @param array $fields Accreditation fields, without the "pspv_" prefix.
     * @param array $course Other fields of the course.
     * @return \stdClass
     */
    protected function accredited_course(array $fields = [], array $course = []): \stdClass {
        $fields += ['accredited' => 1, 'filecode' => '09/0001-MD', 'credits' => 2.4, 'hours' => 20];
        foreach ($fields as $name => $value) {
            $course['customfield_pspv_' . $name] = $value;
        }
        return $this->getDataGenerator()->create_course($course);
    }

    /**
     * Enrols new students in a course.
     *
     * @param \stdClass $course
     * @param int $count
     * @return \stdClass[]
     */
    protected function students(\stdClass $course, int $count): array {
        $students = [];
        for ($i = 0; $i < $count; $i++) {
            $students[] = $this->getDataGenerator()->create_and_enrol($course, 'student');
        }
        return $students;
    }

    /**
     * Creates a session.
     *
     * @param \stdClass $course
     * @param int $minutes
     * @return \stdClass
     */
    protected function session(\stdClass $course, int $minutes): \stdClass {
        return attendance::save_session((object) [
            'courseid' => $course->id, 'name' => 'Session', 'sessiondate' => time(), 'duration' => $minutes,
        ]);
    }

    /**
     * Gives a student a course grade.
     *
     * @param \stdClass $course
     * @param \stdClass $student
     * @param float $grade Out of 100.
     */
    protected function grade(\stdClass $course, \stdClass $student, float $grade): void {
        $params = ['courseid' => $course->id, 'itemtype' => 'manual', 'itemname' => 'Exam'];
        if (!\grade_item::fetch($params)) {
            $this->getDataGenerator()->create_grade_item($params + ['grademax' => 100]);
        }
        \grade_item::fetch($params)->update_final_grade($student->id, $grade);
    }

    public function test_fields_are_created_once_and_read_from_the_course(): void {
        // The installation created them; running it again changes nothing.
        $this->assertSame([], local\setup::ensure_fields());
        $shortnames = array_map(
            fn($field) => $field->get('shortname'),
            \core_course\customfield\course_handler::create()->get_fields(),
        );
        $this->assertEmpty(array_diff(array_keys(local\setup::fields()), $shortnames));

        $from = make_timestamp(2027, 1, 1);
        $course = $this->accredited_course(['accreditedfrom' => $from, 'minattendance' => 80, 'mingrade' => 50]);
        $settings = course_settings::for_course((int) $course->id);
        $this->assertTrue($settings->accredited);
        $this->assertSame('09/0001-MD', $settings->filecode);
        $this->assertSame([2.4, 20.0, 80.0, 50.0], [
            $settings->credits, $settings->hours, $settings->minattendance, $settings->mingrade,
        ]);
        $this->assertSame($from, $settings->accreditedfrom);
        $this->assertSame(0, $settings->accrediteduntil);
        $this->assertTrue($settings->has_own_criteria());

        $plain = course_settings::for_course((int) $this->getDataGenerator()->create_course()->id);
        $this->assertFalse($plain->accredited);
        $this->assertNull($plain->minattendance);
        $this->assertNull($plain->credits);
        $this->assertFalse($plain->has_own_criteria());

        $this->assertSame([(int) $course->id], course_settings::accredited_course_ids());
    }

    public function test_attendance_is_counted_over_the_time_of_the_sessions(): void {
        $course = $this->accredited_course();
        [$always, $half, $never] = $this->students($course, 3);
        $this->assertSame([[], false], attendance::percentages((int) $course->id));

        $long = $this->session($course, 180);
        $short = $this->session($course, 60);
        $all = [$always->id, $half->id, $never->id];
        attendance::mark((int) $long->id, $all, [$always->id, $half->id], 2);
        attendance::mark((int) $short->id, $all, [$always->id], 2);

        [$percentages, $hassessions] = attendance::percentages((int) $course->id);
        $this->assertTrue($hassessions);
        $this->assertSame(100.0, $percentages[$always->id]);
        $this->assertSame(75.0, $percentages[$half->id]);
        $this->assertArrayNotHasKey($never->id, $percentages);

        // Taking the list again corrects it, and only for the students it was taken for.
        attendance::mark((int) $long->id, [$half->id], [], 2);
        $this->assertEqualsCanonicalizing([$always->id], attendance::present((int) $long->id));

        // Sessions without a duration count the same as each other.
        $other = $this->accredited_course();
        [$student] = $this->students($other, 1);
        $first = $this->session($other, 0);
        $this->session($other, 0);
        attendance::mark((int) $first->id, [$student->id], [$student->id], 2);
        $this->assertSame(50.0, attendance::percentages((int) $other->id)[0][$student->id]);

        attendance::delete_session((int) $first->id);
        $this->assertCount(1, attendance::sessions((int) $other->id));
        $this->assertSame([], attendance::percentages((int) $other->id)[0]);
    }

    public function test_a_student_passes_when_every_criterion_of_the_course_is_met(): void {
        $course = $this->accredited_course(['minattendance' => 80, 'mingrade' => 50]);
        [$good, $absent, $failed, $ungraded] = $this->students($course, 4);
        $session = $this->session($course, 60);
        attendance::mark((int) $session->id, [$good->id, $absent->id, $failed->id, $ungraded->id], [
            $good->id, $failed->id, $ungraded->id,
        ], 2);
        $this->grade($course, $good, 50);
        $this->grade($course, $absent, 90);
        $this->grade($course, $failed, 49);

        $evaluator = new evaluator(get_course($course->id));
        $this->assertTrue($evaluator->has_criteria());
        $result = $evaluator->evaluate((int) $good->id);
        $this->assertTrue($result->passed);
        $this->assertSame([100.0, 50.0, null], [$result->attendance, $result->grade, $result->completed]);
        $this->assertSame(['attendance'], $evaluator->evaluate((int) $absent->id)->pending);
        $this->assertSame(['grade'], $evaluator->evaluate((int) $failed->id)->pending);
        $this->assertSame(['grade'], $evaluator->evaluate((int) $ungraded->id)->pending);

        // A minimum attendance cannot be met while the course has no sessions.
        $nosessions = $this->accredited_course(['minattendance' => 80]);
        [$student] = $this->students($nosessions, 1);
        $result = (new evaluator($nosessions))->evaluate((int) $student->id);
        $this->assertNull($result->attendance);
        $this->assertSame(['attendance'], $result->pending);

        // A course without criteria passes nobody.
        $open = $this->accredited_course();
        [$student] = $this->students($open, 1);
        $evaluator = new evaluator($open);
        $this->assertFalse($evaluator->has_criteria());
        $this->assertSame(['nocriteria'], $evaluator->evaluate((int) $student->id)->pending);
        $this->assertSame(0, accreditation::sync_course((int) $open->id));
    }

    public function test_who_passes_is_recorded_with_a_copy_of_the_accreditation_data(): void {
        global $DB;

        $course = $this->accredited_course(['mingrade' => 50], ['fullname' => 'Wound care & more']);
        [$good, $failed] = $this->students($course, 2);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->grade($course, $good, 75);
        $this->grade($course, $failed, 10);
        $events = $this->redirectEvents();

        $this->assertSame(1, accreditation::sync_course((int) $course->id));
        // Teachers are not students, and running it again records nobody twice.
        $this->assertSame(0, accreditation::sync_course((int) $course->id));

        $record = accreditation::record((int) $course->id, (int) $good->id);
        $this->assertSame('Wound care & more', $record->coursename);
        $this->assertSame('09/0001-MD', $record->filecode);
        $this->assertEquals([2.4, 20, 75, 0], [$record->credits, $record->hours, $record->grade, $record->awardedby]);
        $this->assertNull($record->attendance);
        $this->assertNull(accreditation::record((int) $course->id, (int) $failed->id));
        $this->assertNull(accreditation::record((int) $course->id, (int) $teacher->id));
        $awarded = array_filter($events->get_events(), fn($event) => $event instanceof event\accreditation_awarded);
        $this->assertCount(1, $awarded);
        $this->assertEquals($good->id, reset($awarded)->relateduserid);

        // Who passed is in the group that gives access to the diploma.
        $groupid = accreditation::group((int) $course->id, false);
        $this->assertNotSame(0, $groupid);
        $this->assertTrue(groups_is_member($groupid, $good->id));
        $this->assertFalse(groups_is_member($groupid, $failed->id));

        // Changing the course later does not change what was recorded.
        $handler = \core_course\customfield\course_handler::create();
        $handler->instance_form_save((object) ['id' => $course->id, 'customfield_pspv_filecode' => 'CHANGED']);
        $this->assertSame('09/0001-MD', accreditation::record((int) $course->id, (int) $good->id)->filecode);

        // A manager can record somebody by hand, and delete a record.
        $manual = accreditation::award(
            get_course($course->id),
            (int) $failed->id,
            course_settings::for_course((int) $course->id),
            (new evaluator(get_course($course->id)))->evaluate((int) $failed->id),
            (int) $teacher->id,
        );
        $this->assertEquals($teacher->id, $manual->awardedby);
        $this->assertSame('CHANGED', $manual->filecode);
        $this->assertTrue(groups_is_member($groupid, $failed->id));

        accreditation::revoke((int) $manual->id);
        $this->assertNull(accreditation::record((int) $course->id, (int) $failed->id));
        $this->assertFalse(groups_is_member($groupid, $failed->id));
        $this->assertCount(1, $DB->get_records('local_pspvaccredit_record'));

        // Courses that are not accredited record nobody.
        $plain = $this->getDataGenerator()->create_course(['customfield_pspv_mingrade' => 0]);
        $student = $this->getDataGenerator()->create_and_enrol($plain, 'student');
        $this->grade($plain, $student, 100);
        $this->assertSame(0, accreditation::sync_course((int) $plain->id));
    }

    public function test_completing_the_course_and_the_nightly_task_record_who_passes(): void {
        global $CFG;
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_role.php');

        $CFG->enablecompletion = 1;
        $course = $this->accredited_course([], ['enablecompletion' => 1]);
        [$done, $pending] = $this->students($course, 2);
        // The course is completed when the teacher says so.
        $criterion = new \completion_criteria_role();
        $config = (object) ['id' => $course->id, 'criteria_role' => [3 => 3]];
        $criterion->update_config($config);

        $evaluator = new evaluator(get_course($course->id));
        $this->assertTrue($evaluator->has_criteria());
        $this->assertSame(['completion'], $evaluator->evaluate((int) $done->id)->pending);

        // The observer records the student as soon as they complete the course.
        (new \completion_completion(['userid' => $done->id, 'course' => $course->id]))->mark_complete();
        $this->assertNotNull(accreditation::record((int) $course->id, (int) $done->id));
        $this->assertNull(accreditation::record((int) $course->id, (int) $pending->id));

        // The task catches what no event announced, such as a grade.
        $graded = $this->accredited_course(['mingrade' => 50]);
        [$student] = $this->students($graded, 1);
        $this->grade($graded, $student, 60);
        ob_start();
        (new task\sync_accreditations())->execute();
        $output = ob_get_clean();
        $this->assertStringContainsString("Course {$graded->id}: 1 new accreditation records.", $output);
        $this->assertNotNull(accreditation::record((int) $graded->id, (int) $student->id));
    }

    public function test_reports_list_who_passed_with_the_identity_document(): void {
        $generator = $this->getDataGenerator();
        $course = $this->accredited_course(['mingrade' => 50], ['fullname' => 'Falls']);
        $student = $generator->create_and_enrol($course, 'student', [
            'firstname' => 'Bea', 'lastname' => 'Buyer', 'idnumber' => '12345678Z', 'email' => 'bea@example.com',
        ]);
        $this->grade($course, $student, 80);
        accreditation::sync_course((int) $course->id);

        $records = accreditation::records((int) $course->id);
        $this->assertCount(1, $records);
        // Until the hospital says where the document is kept, the column is empty.
        $row = accreditation::export_row(reset($records), true);
        $this->assertSame('', $row['document']);
        $this->assertSame(array_keys(accreditation::export_columns(true)), array_keys($row));
        $this->assertSame(['Buyer', 'Bea', 'bea@example.com', 'Falls', '09/0001-MD'], [
            $row['lastname'], $row['firstname'], $row['email'], $row['course'], $row['filecode'],
        ]);
        $this->assertSame(format_float(2.4, 2, true, true), $row['credits']);
        $this->assertSame('', $row['attendance']);
        $this->assertArrayNotHasKey('course', accreditation::export_row(reset($records), false));

        set_config('idfield', 'idnumber', 'local_pspvaccredit');
        $this->assertSame('12345678Z', accreditation::export_row(reset($records), true)['document']);

        $generator->create_custom_profile_field(['shortname' => 'nif', 'name' => 'NIF', 'datatype' => 'text']);
        profile_save_custom_fields($student->id, ['nif' => 'X1234567L']);
        set_config('idfield', 'nif', 'local_pspvaccredit');
        $this->assertSame('X1234567L', accreditation::export_row(reset($records), true)['document']);

        // The site report can be limited to a period.
        $this->assertCount(1, accreditation::records(0, time() - HOURSECS, time() + HOURSECS));
        $this->assertCount(0, accreditation::records(0, time() + HOURSECS));
        $this->assertCount(0, accreditation::records(0, 0, time() - HOURSECS));
    }
}

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

/**
 * Register of the students who passed an accredited course.
 *
 * A record keeps a copy of the accreditation data of the course when it was awarded, so
 * later changes to the course do not alter what was certified. Students with a record are
 * also members of a group of the course, which is what restricts access to the diploma.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class accreditation {
    /** @var string Idnumber of the group of a course that holds who passed. */
    public const GROUP = 'pspv-accredited';

    /**
     * The students of a course: the enrolled users whose progress is tracked.
     *
     * @param int $courseid
     * @return \stdClass[] By id.
     */
    public static function students(int $courseid): array {
        $context = \core\context\course::instance($courseid);
        $fields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        return get_enrolled_users(
            $context,
            'moodle/course:isincompletionreports',
            0,
            'u.id, u.idnumber, u.email, ' . $fields,
            'u.lastname, u.firstname',
            0,
            0,
            true,
        );
    }

    /**
     * The record of a student in a course.
     *
     * @param int $courseid
     * @param int $userid
     * @return \stdClass|null
     */
    public static function record(int $courseid, int $userid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_pspvaccredit_record', ['courseid' => $courseid, 'userid' => $userid]) ?: null;
    }

    /**
     * Evaluates every student of a course and records those who pass. Nobody loses a record here.
     *
     * @param int $courseid
     * @return int Number of new records.
     */
    public static function sync_course(int $courseid): int {
        $course = get_course($courseid);
        $evaluator = new evaluator($course);
        if (!$evaluator->settings()->accredited || !$evaluator->has_criteria()) {
            return 0;
        }
        $awarded = 0;
        foreach (self::students($courseid) as $student) {
            $awarded += self::sync_user($course, (int) $student->id, $evaluator) ? 1 : 0;
        }
        return $awarded;
    }

    /**
     * Records a student if they pass and have no record yet.
     *
     * @param \stdClass $course
     * @param int $userid
     * @param evaluator|null $evaluator
     * @return bool Whether a record was created.
     */
    public static function sync_user(\stdClass $course, int $userid, ?evaluator $evaluator = null): bool {
        $evaluator ??= new evaluator($course);
        if (!$evaluator->settings()->accredited || self::record((int) $course->id, $userid)) {
            return false;
        }
        $evaluation = $evaluator->evaluate($userid);
        if (!$evaluation->passed) {
            return false;
        }
        self::award($course, $userid, $evaluator->settings(), $evaluation, 0);
        return true;
    }

    /**
     * Creates the record of a student. Does nothing if they already have one.
     *
     * @param \stdClass $course
     * @param int $userid
     * @param course_settings $settings
     * @param \stdClass $evaluation As evaluator::evaluate() returns it.
     * @param int $awardedby 0 when the criteria were met; the user who awards it by hand otherwise.
     * @return \stdClass The record.
     */
    public static function award(
        \stdClass $course,
        int $userid,
        course_settings $settings,
        \stdClass $evaluation,
        int $awardedby,
    ): \stdClass {
        global $DB;

        if ($record = self::record((int) $course->id, $userid)) {
            return $record;
        }
        $context = \core\context\course::instance($course->id);
        $id = $DB->insert_record('local_pspvaccredit_record', [
            'courseid' => $course->id,
            'userid' => $userid,
            'coursename' => \core_text::substr(
                html_entity_decode(format_string($course->fullname, true, ['context' => $context]), ENT_QUOTES | ENT_HTML5),
                0,
                255,
            ),
            'filecode' => $settings->filecode,
            'credits' => $settings->credits,
            'hours' => $settings->hours,
            'attendance' => $evaluation->attendance,
            'grade' => $evaluation->grade,
            'awardedby' => $awardedby,
            'timeawarded' => time(),
        ]);
        $groupid = self::group((int) $course->id);
        groups_add_member($groupid, $userid);

        event\accreditation_awarded::create([
            'context' => $context,
            'objectid' => $id,
            'relateduserid' => $userid,
            'other' => ['manual' => $awardedby !== 0],
        ])->trigger();
        return $DB->get_record('local_pspvaccredit_record', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Deletes the record of a student, who also loses access to the diploma.
     *
     * @param int $recordid
     */
    public static function revoke(int $recordid): void {
        global $DB;

        $record = $DB->get_record('local_pspvaccredit_record', ['id' => $recordid], '*', MUST_EXIST);
        $DB->delete_records('local_pspvaccredit_record', ['id' => $recordid]);
        if ($groupid = self::group((int) $record->courseid, false)) {
            groups_remove_member($groupid, $record->userid);
        }
        event\accreditation_revoked::create([
            'context' => \core\context\course::instance($record->courseid),
            'objectid' => $recordid,
            'relateduserid' => $record->userid,
        ])->trigger();
    }

    /**
     * The group of a course that holds who passed.
     *
     * @param int $courseid
     * @param bool $create Whether to create it when missing.
     * @return int Group id, 0 when it does not exist and is not created.
     */
    public static function group(int $courseid, bool $create = true): int {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/group/lib.php');

        $id = (int) $DB->get_field('groups', 'id', ['courseid' => $courseid, 'idnumber' => self::GROUP]);
        if (!$id && $create) {
            $id = groups_create_group((object) [
                'courseid' => $courseid,
                'name' => local\setup::site_string('groupname'),
                'idnumber' => self::GROUP,
                'description' => local\setup::site_string('groupdescription'),
                'descriptionformat' => FORMAT_PLAIN,
                'visibility' => GROUPS_VISIBILITY_OWN,
            ]);
        }
        return $id;
    }

    /**
     * Value of the identity document of a user, from the field chosen in the settings.
     *
     * @param \stdClass $user With id and idnumber.
     * @return string
     */
    public static function identity_document(\stdClass $user): string {
        global $CFG;

        $field = trim((string) get_config('local_pspvaccredit', 'idfield'));
        if ($field === '') {
            return '';
        }
        if ($field === 'idnumber') {
            return (string) ($user->idnumber ?? '');
        }
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $profile = profile_user_record($user->id, false);
        return (string) ($profile->{$field} ?? '');
    }

    /**
     * Columns of the exported reports.
     *
     * @param bool $withcourse Whether the report spans several courses.
     * @return string[] By key.
     */
    public static function export_columns(bool $withcourse): array {
        $columns = [
            'lastname' => get_string('lastname'),
            'firstname' => get_string('firstname'),
            'document' => get_string('report_document', 'local_pspvaccredit'),
            'email' => get_string('email'),
        ];
        if ($withcourse) {
            $columns['course'] = get_string('course');
        }
        return $columns + [
            'filecode' => get_string('report_filecode', 'local_pspvaccredit'),
            'credits' => get_string('report_credits', 'local_pspvaccredit'),
            'hours' => get_string('report_hours', 'local_pspvaccredit'),
            'attendance' => get_string('report_attendance', 'local_pspvaccredit'),
            'grade' => get_string('report_grade', 'local_pspvaccredit'),
            'date' => get_string('report_date', 'local_pspvaccredit'),
        ];
    }

    /**
     * A record as a row of the exported reports.
     *
     * @param \stdClass $record With the fields of the record plus lastname, firstname, email and idnumber of the user.
     * @param bool $withcourse
     * @return array
     */
    public static function export_row(\stdClass $record, bool $withcourse): array {
        $number = fn($value, int $decimals) => $value === null ? '' : format_float((float) $value, $decimals, true, true);
        $row = [
            'lastname' => $record->lastname,
            'firstname' => $record->firstname,
            'document' => self::identity_document((object) ['id' => $record->userid, 'idnumber' => $record->idnumber]),
            'email' => $record->email,
        ];
        if ($withcourse) {
            $row['course'] = $record->coursename;
        }
        return $row + [
            'filecode' => (string) $record->filecode,
            'credits' => $number($record->credits, 2),
            'hours' => $number($record->hours, 2),
            'attendance' => $number($record->attendance, 2),
            'grade' => $number($record->grade, 2),
            'date' => userdate($record->timeawarded, get_string('strftimedatefullshort', 'core_langconfig')),
        ];
    }

    /**
     * Records, with the name and email of each student.
     *
     * @param int $courseid 0 for every course.
     * @param int $from Awarded from this time on, 0 for no limit.
     * @param int $to Awarded up to this time, 0 for no limit.
     * @return \stdClass[]
     */
    public static function records(int $courseid = 0, int $from = 0, int $to = 0): array {
        global $DB;

        $where = ['u.deleted = 0'];
        $params = [];
        if ($courseid) {
            $where[] = 'r.courseid = :courseid';
            $params['courseid'] = $courseid;
        }
        if ($from) {
            $where[] = 'r.timeawarded >= :fromtime';
            $params['fromtime'] = $from;
        }
        if ($to) {
            $where[] = 'r.timeawarded <= :totime';
            $params['totime'] = $to;
        }
        $names = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $sql = "SELECT r.*, u.email, u.idnumber, $names
                  FROM {local_pspvaccredit_record} r
                  JOIN {user} u ON u.id = r.userid
                 WHERE " . implode(' AND ', $where) . "
              ORDER BY r.coursename, u.lastname, u.firstname";
        return $DB->get_records_sql($sql, $params);
    }
}

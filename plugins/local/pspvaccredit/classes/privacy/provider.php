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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: attendance to sessions and the register of who passed, both in the context of the course.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_pspvaccredit_attend', [
            'sessionid' => 'privacy:metadata:attend:sessionid',
            'userid' => 'privacy:metadata:attend:userid',
            'markedby' => 'privacy:metadata:attend:markedby',
            'timemodified' => 'privacy:metadata:attend:timemodified',
        ], 'privacy:metadata:attend');
        $collection->add_database_table('local_pspvaccredit_record', [
            'userid' => 'privacy:metadata:record:userid',
            'coursename' => 'privacy:metadata:record:coursename',
            'filecode' => 'privacy:metadata:record:filecode',
            'credits' => 'privacy:metadata:record:credits',
            'hours' => 'privacy:metadata:record:hours',
            'attendance' => 'privacy:metadata:record:attendance',
            'grade' => 'privacy:metadata:record:grade',
            'awardedby' => 'privacy:metadata:record:awardedby',
            'timeawarded' => 'privacy:metadata:record:timeawarded',
        ], 'privacy:metadata:record');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $params = ['level1' => CONTEXT_COURSE, 'level2' => CONTEXT_COURSE, 'userid1' => $userid, 'userid2' => $userid];
        $contextlist->add_from_sql(
            "SELECT ctx.id
               FROM {context} ctx
               JOIN {local_pspvaccredit_record} r ON r.courseid = ctx.instanceid AND ctx.contextlevel = :level1
              WHERE r.userid = :userid1",
            $params,
        );
        $contextlist->add_from_sql(
            "SELECT ctx.id
               FROM {context} ctx
               JOIN {local_pspvaccredit_session} s ON s.courseid = ctx.instanceid AND ctx.contextlevel = :level2
               JOIN {local_pspvaccredit_attend} a ON a.sessionid = s.id
              WHERE a.userid = :userid2",
            $params,
        );
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof \core\context\course) {
            return;
        }
        $params = ['courseid' => $context->instanceid];
        $userlist->add_from_sql('userid', "SELECT userid FROM {local_pspvaccredit_record} WHERE courseid = :courseid", $params);
        $userlist->add_from_sql(
            'userid',
            "SELECT a.userid
               FROM {local_pspvaccredit_attend} a
               JOIN {local_pspvaccredit_session} s ON s.id = a.sessionid
              WHERE s.courseid = :courseid",
            $params,
        );
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \core\context\course) {
                continue;
            }
            $data = [];
            if ($record = $DB->get_record('local_pspvaccredit_record', ['courseid' => $context->instanceid, 'userid' => $userid])) {
                $data['record'] = (object) [
                    'course' => $record->coursename,
                    'filecode' => $record->filecode,
                    'credits' => $record->credits,
                    'hours' => $record->hours,
                    'attendance' => $record->attendance,
                    'grade' => $record->grade,
                    'timeawarded' => transform::datetime($record->timeawarded),
                ];
            }
            $sessions = $DB->get_records_sql(
                "SELECT s.id, s.name, s.sessiondate
                   FROM {local_pspvaccredit_session} s
                   JOIN {local_pspvaccredit_attend} a ON a.sessionid = s.id
                  WHERE s.courseid = :courseid AND a.userid = :userid
               ORDER BY s.sessiondate",
                ['courseid' => $context->instanceid, 'userid' => $userid],
            );
            if ($sessions) {
                $data['attended'] = array_values(array_map(fn($session) => (object) [
                    'session' => $session->name,
                    'date' => transform::datetime($session->sessiondate),
                ], $sessions));
            }
            if ($data) {
                writer::with_context($context)->export_data([get_string('pluginname', 'local_pspvaccredit')], (object) $data);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if (!$context instanceof \core\context\course) {
            return;
        }
        $DB->delete_records('local_pspvaccredit_record', ['courseid' => $context->instanceid]);
        $DB->delete_records_select(
            'local_pspvaccredit_attend',
            'sessionid IN (SELECT id FROM {local_pspvaccredit_session} WHERE courseid = ?)',
            [$context->instanceid],
        );
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \core\context\course) {
                self::delete_users((int) $context->instanceid, [(int) $contextlist->get_user()->id]);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context instanceof \core\context\course && $userlist->get_userids()) {
            self::delete_users((int) $context->instanceid, $userlist->get_userids());
        }
    }

    /**
     * Deletes what the plugin knows about some users in a course.
     *
     * @param int $courseid
     * @param int[] $userids
     */
    protected static function delete_users(int $courseid, array $userids): void {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['courseid'] = $courseid;
        $DB->delete_records_select('local_pspvaccredit_record', "courseid = :courseid AND userid $insql", $params);
        $DB->delete_records_select(
            'local_pspvaccredit_attend',
            "userid $insql AND sessionid IN (SELECT id FROM {local_pspvaccredit_session} WHERE courseid = :courseid)",
            $params,
        );
    }
}

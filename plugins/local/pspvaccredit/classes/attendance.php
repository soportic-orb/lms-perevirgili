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
 * Sessions of a course and who attended them.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attendance {
    /**
     * The sessions of a course, oldest first.
     *
     * @param int $courseid
     * @return \stdClass[] By id.
     */
    public static function sessions(int $courseid): array {
        global $DB;

        return $DB->get_records('local_pspvaccredit_session', ['courseid' => $courseid], 'sessiondate ASC, id ASC');
    }

    /**
     * A session.
     *
     * @param int $id
     * @return \stdClass
     */
    public static function session(int $id): \stdClass {
        global $DB;

        return $DB->get_record('local_pspvaccredit_session', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Creates or updates a session.
     *
     * @param \stdClass $data courseid, name, sessiondate, duration (minutes) and, to update, id.
     * @return \stdClass The session.
     */
    public static function save_session(\stdClass $data): \stdClass {
        global $DB;

        $now = time();
        $record = (object) [
            'name' => \core_text::substr(trim($data->name), 0, 255),
            'sessiondate' => (int) $data->sessiondate,
            'duration' => max(0, (int) $data->duration),
            'timemodified' => $now,
        ];
        if (!empty($data->id)) {
            $record->id = (int) $data->id;
            $DB->update_record('local_pspvaccredit_session', $record);
        } else {
            $record->courseid = (int) $data->courseid;
            $record->timecreated = $now;
            $record->id = $DB->insert_record('local_pspvaccredit_session', $record);
        }
        return self::session((int) $record->id);
    }

    /**
     * Deletes a session and its attendance.
     *
     * @param int $id
     */
    public static function delete_session(int $id): void {
        global $DB;

        $DB->delete_records('local_pspvaccredit_attend', ['sessionid' => $id]);
        $DB->delete_records('local_pspvaccredit_session', ['id' => $id]);
    }

    /**
     * Ids of the users recorded as present in a session.
     *
     * @param int $sessionid
     * @return int[]
     */
    public static function present(int $sessionid): array {
        global $DB;

        return array_map('intval', $DB->get_fieldset_select('local_pspvaccredit_attend', 'userid', 'sessionid = ?', [$sessionid]));
    }

    /**
     * Records who attended a session. Whoever is in $userids and not in $present is recorded as absent.
     *
     * @param int $sessionid
     * @param int[] $userids The users the list was taken for.
     * @param int[] $present Those who attended.
     * @param int $markedby Who takes the list.
     */
    public static function mark(int $sessionid, array $userids, array $present, int $markedby): void {
        global $DB;

        $session = self::session($sessionid);
        $already = array_flip(self::present($sessionid));
        $present = array_flip(array_map('intval', $present));
        $now = time();
        foreach (array_map('intval', $userids) as $userid) {
            if (isset($present[$userid]) && !isset($already[$userid])) {
                $DB->insert_record('local_pspvaccredit_attend', [
                    'sessionid' => $sessionid, 'userid' => $userid, 'markedby' => $markedby, 'timemodified' => $now,
                ]);
            } else if (!isset($present[$userid]) && isset($already[$userid])) {
                $DB->delete_records('local_pspvaccredit_attend', ['sessionid' => $sessionid, 'userid' => $userid]);
            }
        }
        event\attendance_marked::create([
            'context' => \core\context\course::instance($session->courseid),
            'objectid' => $sessionid,
            'other' => ['present' => count(array_intersect_key($present, array_flip(array_map('intval', $userids))))],
        ])->trigger();
    }

    /**
     * Attendance of every user of a course, as a percentage of the time of all its sessions.
     *
     * @param int $courseid
     * @return array [float[] percentage by user id (users who attended nothing are missing), bool whether there are sessions]
     */
    public static function percentages(int $courseid): array {
        global $DB;

        $sessions = self::sessions($courseid);
        if (!$sessions) {
            return [[], false];
        }
        // Sessions without a duration count the same as each other.
        $weights = [];
        foreach ($sessions as $session) {
            $weights[$session->id] = $session->duration > 0 ? (int) $session->duration : 0;
        }
        if (array_sum($weights) === 0) {
            $weights = array_fill_keys(array_keys($weights), 1);
        }
        $total = array_sum($weights);

        [$insql, $params] = $DB->get_in_or_equal(array_keys($sessions));
        $attended = [];
        $rows = $DB->get_recordset_select('local_pspvaccredit_attend', "sessionid $insql", $params, '', 'id, sessionid, userid');
        foreach ($rows as $row) {
            $attended[$row->userid] = ($attended[$row->userid] ?? 0) + $weights[$row->sessionid];
        }
        $rows->close();

        return [array_map(fn($time) => round($time * 100 / $total, 2), $attended), true];
    }
}

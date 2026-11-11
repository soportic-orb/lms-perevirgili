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

use local_pspvaccess\local\structure;

/**
 * Keeps the workers cohort in line with the worker policy.
 *
 * Only users this plugin added are removed automatically: members an
 * administrator adds by hand to the cohort are left alone.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class worker_manager {
    /** @var string Origin: the user logged in. */
    public const ORIGIN_LOGIN = 'login';

    /** @var string Origin: the user's profile was created or changed. */
    public const ORIGIN_PROFILE = 'profile';

    /** @var string Origin: the scheduled re-synchronisation. */
    public const ORIGIN_TASK = 'task';

    /** @var string Origin: an administrator asked for a re-synchronisation. */
    public const ORIGIN_MANUAL = 'manual';

    /**
     * Applies the policy to one user.
     *
     * @param \stdClass $user User record.
     * @param string $origin One of the ORIGIN_ constants.
     * @param int|null $actorid User who caused the check, if any.
     * @return string|null "added", "removed" or null when nothing changed.
     */
    public static function sync_user(\stdClass $user, string $origin, ?int $actorid = null): ?string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        // Not configured yet: nobody is added, and nobody is removed by mistake.
        if (!worker_policy::enabled_rules()) {
            return null;
        }

        $cohort = structure::cohort();
        $ismember = cohort_is_member($cohort->id, $user->id);
        $managed = $DB->get_record('local_pspvaccess_member', ['userid' => $user->id]);
        $isworker = worker_policy::is_worker($user);
        $reason = implode(',', worker_policy::matching_rules($user));
        $now = time();

        if ($isworker) {
            if (!$ismember) {
                cohort_add_member($cohort->id, $user->id);
                if ($managed) {
                    $DB->update_record('local_pspvaccess_member', [
                        'id' => $managed->id, 'reason' => $reason, 'timemodified' => $now,
                    ]);
                } else {
                    $DB->insert_record('local_pspvaccess_member', [
                        'userid' => $user->id, 'reason' => $reason, 'timecreated' => $now, 'timemodified' => $now,
                    ]);
                }
                self::audit($user->id, 'added', $reason, $origin, $actorid);
                return 'added';
            }
            if ($managed && $managed->reason !== $reason) {
                $DB->update_record('local_pspvaccess_member', ['id' => $managed->id, 'reason' => $reason, 'timemodified' => $now]);
            }
            return null;
        }

        if ($managed) {
            $DB->delete_records('local_pspvaccess_member', ['id' => $managed->id]);
            if ($ismember) {
                cohort_remove_member($cohort->id, $user->id);
                self::audit($user->id, 'removed', 'none', $origin, $actorid);
                return 'removed';
            }
        }
        return null;
    }

    /**
     * Applies the policy to every user it could affect.
     *
     * @param string $origin One of the ORIGIN_ constants.
     * @param int|null $actorid User who asked for it, if any.
     * @return int[] Counts: checked, added and removed.
     */
    public static function resync_all(string $origin = self::ORIGIN_TASK, ?int $actorid = null): array {
        global $DB;

        $counts = ['checked' => 0, 'added' => 0, 'removed' => 0];
        if (!worker_policy::enabled_rules()) {
            return $counts;
        }

        // Current managed members, plus everybody who could meet one of the rules.
        $userids = $DB->get_fieldset_select('local_pspvaccess_member', 'userid', '1 = 1');
        if (worker_policy::worker_issuer()) {
            $userids = array_merge($userids, $DB->get_fieldset_select(
                'auth_oauth2_linked_login',
                'userid',
                'issuerid = ?',
                [worker_policy::worker_issuer()],
            ));
        }
        foreach (worker_policy::domains() as $domain) {
            $userids = array_merge($userids, $DB->get_fieldset_select(
                'user',
                'id',
                'deleted = 0 AND ' . $DB->sql_like('email', '?', false),
                ['%@' . $DB->sql_like_escape($domain)],
            ));
        }
        if (get_config('local_pspvaccess', 'useemployeelist')) {
            $userids = array_merge($userids, $DB->get_fieldset_sql(
                "SELECT u.id
                   FROM {user} u
                   JOIN {local_pspvaccess_employee} e ON e.email = LOWER(u.email)
                  WHERE u.deleted = 0"
            ));
        }

        foreach (array_unique(array_map('intval', $userids)) as $userid) {
            $user = $DB->get_record('user', ['id' => $userid]);
            if (!$user) {
                $DB->delete_records('local_pspvaccess_member', ['userid' => $userid]);
                continue;
            }
            $counts['checked']++;
            $change = self::sync_user($user, $origin, $actorid);
            if ($change) {
                $counts[$change]++;
            }
        }
        return $counts;
    }

    /**
     * Remembers that a user has just signed in through the worker issuer.
     *
     * @param int $userid
     */
    public static function record_tenant_login(int $userid): void {
        set_user_preference(worker_policy::PREF_TENANT_LOGIN, time(), $userid);
    }

    /**
     * Writes one line of the audit log.
     *
     * @param int $userid User added or removed.
     * @param string $action "added" or "removed".
     * @param string $reason Rules that matched, or "none".
     * @param string $origin One of the ORIGIN_ constants.
     * @param int|null $actorid User who caused the change, if any.
     */
    protected static function audit(int $userid, string $action, string $reason, string $origin, ?int $actorid): void {
        global $DB;

        $DB->insert_record('local_pspvaccess_audit', [
            'userid' => $userid,
            'action' => $action,
            'reason' => $reason,
            'origin' => $origin,
            'actorid' => $actorid,
            'timecreated' => time(),
        ]);
    }
}

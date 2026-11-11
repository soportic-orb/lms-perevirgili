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
 * Event observers: re-check a user on login and profile changes, and mirror the
 * workers cohort into the role that shows the internal training category.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * A user logged in.
     *
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $event->objectid]);
        if (!$user) {
            return;
        }
        $issuer = worker_policy::worker_issuer();
        if ($issuer && self::login_issuer() === $issuer) {
            worker_manager::record_tenant_login($user->id);
        }
        worker_manager::sync_user($user, worker_manager::ORIGIN_LOGIN, $user->id);
    }

    /**
     * A user was created or their profile changed (the email address may have).
     *
     * @param \core\event\base $event
     */
    public static function user_changed(\core\event\base $event): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $event->objectid]);
        if ($user) {
            worker_manager::sync_user($user, worker_manager::ORIGIN_PROFILE, $event->userid ?: null);
        }
    }

    /**
     * Somebody joined a cohort.
     *
     * @param \core\event\cohort_member_added $event
     */
    public static function cohort_member_added(\core\event\cohort_member_added $event): void {
        if ((int) $event->objectid === structure::cohort_id()) {
            structure::assign_worker_role($event->relateduserid);
        }
    }

    /**
     * Somebody left a cohort.
     *
     * @param \core\event\cohort_member_removed $event
     */
    public static function cohort_member_removed(\core\event\cohort_member_removed $event): void {
        if ((int) $event->objectid === structure::cohort_id()) {
            structure::unassign_worker_role($event->relateduserid);
        }
    }

    /**
     * The OAuth 2 issuer the current request is logging in with.
     *
     * The login event does not carry it, but OAuth 2 logins always finish on
     * auth/oauth2/login.php, which receives the issuer id.
     *
     * @return int Issuer id, 0 when this is not an OAuth 2 login.
     */
    protected static function login_issuer(): int {
        global $SCRIPT;

        if ($SCRIPT !== '/auth/oauth2/login.php') {
            return 0;
        }
        return optional_param('id', 0, PARAM_INT);
    }
}

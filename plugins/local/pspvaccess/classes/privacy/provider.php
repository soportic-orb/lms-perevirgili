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

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_pspvaccess\worker_policy;

/**
 * Privacy provider: cohort membership records, the audit log, the time of the
 * last sign-in through the hospital's tenant, and the employee list.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_pspvaccess_member', [
            'userid' => 'privacy:metadata:member:userid',
            'reason' => 'privacy:metadata:member:reason',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:member');
        $collection->add_database_table('local_pspvaccess_audit', [
            'userid' => 'privacy:metadata:audit:userid',
            'action' => 'privacy:metadata:audit:action',
            'reason' => 'privacy:metadata:audit:reason',
            'origin' => 'privacy:metadata:audit:origin',
            'actorid' => 'privacy:metadata:audit:actorid',
            'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:audit');
        $collection->add_database_table('local_pspvaccess_employee', [
            'email' => 'privacy:metadata:employee:email',
            'employeeid' => 'privacy:metadata:employee:employeeid',
            'department' => 'privacy:metadata:employee:department',
            'jobcategory' => 'privacy:metadata:employee:jobcategory',
        ], 'privacy:metadata:employee');
        $collection->add_user_preference(worker_policy::PREF_TENANT_LOGIN, 'privacy:metadata:preference:tenantlogin');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $user = $DB->get_record('user', ['id' => $userid], 'id, email');
        if (!$user) {
            return $contextlist;
        }
        $hasdata = $DB->record_exists('local_pspvaccess_member', ['userid' => $userid])
            || $DB->record_exists_select('local_pspvaccess_audit', 'userid = ? OR actorid = ?', [$userid, $userid])
            || $DB->record_exists('local_pspvaccess_employee', ['email' => \core_text::strtolower($user->email)]);
        if ($hasdata) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        if (!$userlist->get_context() instanceof \core\context\system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvaccess_member}', []);
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvaccess_audit}', []);
        $userlist->add_from_sql('actorid', 'SELECT actorid FROM {local_pspvaccess_audit} WHERE actorid IS NOT NULL', []);
        $userlist->add_from_sql(
            'id',
            'SELECT u.id FROM {user} u JOIN {local_pspvaccess_employee} e ON e.email = LOWER(u.email) WHERE u.deleted = 0',
            [],
        );
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $user = $contextlist->get_user();
        $system = \core\context\system::instance();
        if (!in_array($system->id, $contextlist->get_contextids())) {
            return;
        }
        $path = [get_string('pluginname', 'local_pspvaccess')];

        $member = $DB->get_record('local_pspvaccess_member', ['userid' => $user->id]);
        if ($member) {
            $subpath = array_merge($path, [get_string('privacy:path:member', 'local_pspvaccess')]);
            writer::with_context($system)->export_data($subpath, (object) [
                'reason' => $member->reason,
                'timecreated' => transform::datetime($member->timecreated),
            ]);
        }

        $audit = [];
        $rows = $DB->get_records_select('local_pspvaccess_audit', 'userid = ? OR actorid = ?', [$user->id, $user->id], 'id');
        foreach ($rows as $row) {
            $audit[] = (object) [
                'aboutyou' => transform::yesno($row->userid == $user->id),
                'causedbyyou' => transform::yesno($row->actorid == $user->id),
                'action' => $row->action,
                'reason' => $row->reason,
                'origin' => $row->origin,
                'timecreated' => transform::datetime($row->timecreated),
            ];
        }
        if ($audit) {
            writer::with_context($system)->export_data(
                array_merge($path, [get_string('privacy:path:audit', 'local_pspvaccess')]),
                (object) ['changes' => $audit],
            );
        }

        $employee = $DB->get_record('local_pspvaccess_employee', ['email' => \core_text::strtolower($user->email)]);
        if ($employee) {
            $subpath = array_merge($path, [get_string('privacy:path:employee', 'local_pspvaccess')]);
            writer::with_context($system)->export_data($subpath, (object) [
                'email' => $employee->email,
                'employeeid' => $employee->employeeid,
                'department' => $employee->department,
                'jobcategory' => $employee->jobcategory,
            ]);
        }
    }

    #[\Override]
    public static function export_user_preferences(int $userid): void {
        $time = get_user_preferences(worker_policy::PREF_TENANT_LOGIN, null, $userid);
        if ($time !== null) {
            writer::export_user_preference(
                'local_pspvaccess',
                worker_policy::PREF_TENANT_LOGIN,
                transform::datetime($time),
                get_string('privacy:metadata:preference:tenantlogin', 'local_pspvaccess'),
            );
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \core\context\system) {
            $DB->delete_records('local_pspvaccess_member');
            $DB->delete_records('local_pspvaccess_audit');
            $DB->delete_records('local_pspvaccess_employee');
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (in_array(\core\context\system::instance()->id, $contextlist->get_contextids())) {
            self::delete_user($contextlist->get_user()->id);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context() instanceof \core\context\system) {
            foreach ($userlist->get_userids() as $userid) {
                self::delete_user($userid);
            }
        }
    }

    /**
     * Deletes everything this plugin holds about a user.
     *
     * The audit lines a user caused about other people are kept, without the actor.
     *
     * @param int $userid
     */
    protected static function delete_user(int $userid): void {
        global $DB;

        $email = $DB->get_field('user', 'email', ['id' => $userid]);
        $DB->delete_records('local_pspvaccess_member', ['userid' => $userid]);
        $DB->delete_records('local_pspvaccess_audit', ['userid' => $userid]);
        $DB->set_field('local_pspvaccess_audit', 'actorid', null, ['actorid' => $userid]);
        if ($email) {
            $DB->delete_records('local_pspvaccess_employee', ['email' => \core_text::strtolower($email)]);
        }
    }
}

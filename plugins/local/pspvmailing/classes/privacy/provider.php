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

namespace local_pspvmailing\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: which campaigns a user received and whether they opted out.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_pspvmailing_recipient', [
            'campaignid' => 'privacy:metadata:recipient:campaignid',
            'userid' => 'privacy:metadata:recipient:userid',
            'status' => 'privacy:metadata:recipient:status',
            'timesent' => 'privacy:metadata:recipient:timesent',
        ], 'privacy:metadata:recipient');
        $collection->add_database_table('local_pspvmailing_optout', [
            'userid' => 'privacy:metadata:optout:userid',
            'timecreated' => 'privacy:metadata:optout:timecreated',
        ], 'privacy:metadata:optout');
        $collection->add_database_table('local_pspvmailing_campaign', [
            'usermodified' => 'privacy:metadata:campaign:usermodified',
        ], 'privacy:metadata:campaign');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if (
            $DB->record_exists('local_pspvmailing_recipient', ['userid' => $userid])
            || $DB->record_exists('local_pspvmailing_optout', ['userid' => $userid])
        ) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        if (!$userlist->get_context() instanceof \core\context\system) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvmailing_recipient}', []);
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvmailing_optout}', []);
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $system = \core\context\system::instance();
        if (!in_array($system->id, $contextlist->get_contextids())) {
            return;
        }
        $userid = (int) $contextlist->get_user()->id;
        $received = $DB->get_records_sql(
            "SELECT r.id, c.subject, r.status, r.timesent
               FROM {local_pspvmailing_recipient} r
               JOIN {local_pspvmailing_campaign} c ON c.id = r.campaignid
              WHERE r.userid = ?
           ORDER BY r.id",
            [$userid],
        );
        $optout = $DB->get_record('local_pspvmailing_optout', ['userid' => $userid]);
        writer::with_context($system)->export_data([get_string('pluginname', 'local_pspvmailing')], (object) [
            'campaigns' => array_values(array_map(fn($row) => (object) [
                'subject' => $row->subject,
                'status' => $row->status,
                'timesent' => $row->timesent ? transform::datetime($row->timesent) : '',
            ], $received)),
            'optedout' => $optout ? transform::datetime($optout->timecreated) : transform::yesno(false),
        ]);
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \core\context\system) {
            $DB->delete_records('local_pspvmailing_recipient');
            $DB->delete_records('local_pspvmailing_optout');
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (in_array(\core\context\system::instance()->id, $contextlist->get_contextids())) {
            self::delete_users([(int) $contextlist->get_user()->id]);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context() instanceof \core\context\system && $userlist->get_userids()) {
            self::delete_users($userlist->get_userids());
        }
    }

    /**
     * Deletes what the plugin knows about some users.
     *
     * A deleted account cannot be written to, so its opt-out is not needed any more.
     *
     * @param int[] $userids
     */
    protected static function delete_users(array $userids): void {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal($userids);
        $DB->delete_records_select('local_pspvmailing_recipient', "userid $insql", $params);
        $DB->delete_records_select('local_pspvmailing_optout', "userid $insql", $params);
    }
}

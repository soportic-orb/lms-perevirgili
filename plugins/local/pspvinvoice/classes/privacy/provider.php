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

namespace local_pspvinvoice\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider: billing details and issued documents.
 *
 * Billing details of orders that never became an invoice are deleted on request.
 * Issued invoices and credit notes are kept: tax law obliges the seller to keep them.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $fields = [
            'name' => 'privacy:metadata:billing:name',
            'taxid' => 'privacy:metadata:billing:taxid',
            'address' => 'privacy:metadata:billing:address',
            'email' => 'privacy:metadata:billing:email',
        ];
        $fields = ['userid' => 'privacy:metadata:userid'] + $fields;
        $collection->add_database_table('local_pspvinvoice_billing', $fields, 'privacy:metadata:billing');
        $collection->add_database_table('local_pspvinvoice_invoice', [
            'userid' => 'privacy:metadata:userid',
            'fullnumber' => 'privacy:metadata:invoice:fullnumber',
            'billing' => 'privacy:metadata:invoice:billing',
            'doclines' => 'privacy:metadata:invoice:lines',
            'total' => 'privacy:metadata:invoice:total',
            'timeissued' => 'privacy:metadata:invoice:timeissued',
        ], 'privacy:metadata:invoice');
        return $collection;
    }

    #[\Override]
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if (
            $DB->record_exists('local_pspvinvoice_billing', ['userid' => $userid])
            || $DB->record_exists('local_pspvinvoice_invoice', ['userid' => $userid])
        ) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    #[\Override]
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context() instanceof \core\context\system) {
            $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvinvoice_billing}', []);
            $userlist->add_from_sql('userid', 'SELECT userid FROM {local_pspvinvoice_invoice}', []);
        }
    }

    #[\Override]
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $system = \core\context\system::instance();
        if (!in_array($system->id, $contextlist->get_contextids())) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $documents = [];
        foreach ($DB->get_records('local_pspvinvoice_invoice', ['userid' => $userid], 'id') as $invoice) {
            $documents[] = (object) [
                'number' => $invoice->fullnumber,
                'type' => $invoice->doctype,
                'total' => $invoice->total,
                'currency' => $invoice->currency,
                'timeissued' => transform::datetime($invoice->timeissued),
                'billing' => json_decode($invoice->billing),
                'lines' => json_decode($invoice->doclines),
            ];
        }
        $billing = [];
        foreach ($DB->get_records('local_pspvinvoice_billing', ['userid' => $userid], 'id') as $record) {
            unset($record->id, $record->userid);
            $billing[] = $record;
        }
        if ($documents || $billing) {
            writer::with_context($system)->export_data(
                [get_string('pluginname', 'local_pspvinvoice')],
                (object) ['documents' => $documents, 'billingdetails' => $billing],
            );
        }
    }

    #[\Override]
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \core\context\system) {
            foreach ($DB->get_fieldset_sql('SELECT DISTINCT userid FROM {local_pspvinvoice_billing}') as $userid) {
                self::delete_user((int) $userid);
            }
        }
    }

    #[\Override]
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (in_array(\core\context\system::instance()->id, $contextlist->get_contextids())) {
            self::delete_user((int) $contextlist->get_user()->id);
        }
    }

    #[\Override]
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context() instanceof \core\context\system) {
            foreach ($userlist->get_userids() as $userid) {
                self::delete_user((int) $userid);
            }
        }
    }

    /**
     * Deletes the billing details of a user that are not part of an issued document.
     *
     * @param int $userid
     */
    protected static function delete_user(int $userid): void {
        global $DB;

        $DB->delete_records_select(
            'local_pspvinvoice_billing',
            'userid = ? AND orderid NOT IN (SELECT orderid FROM {local_pspvinvoice_invoice})',
            [$userid],
        );
    }
}

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

namespace local_pspvinvoice;

/**
 * Messages sent to buyers.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier {
    /**
     * Tells a buyer that their invoice is ready.
     *
     * @param \stdClass $invoice
     */
    public static function invoice_issued(\stdClass $invoice): void {
        $user = \core_user::get_user($invoice->userid);
        if (!$user || $user->deleted) {
            return;
        }
        $manager = get_string_manager();
        $lang = $user->lang ?: null;
        $url = new \core\url('/local/pspvinvoice/invoice.php', ['id' => $invoice->id]);
        $data = (object) ['firstname' => $user->firstname, 'number' => $invoice->fullnumber, 'url' => $url->out(false)];
        $body = $manager->get_string('message_invoice_body', 'local_pspvinvoice', $data, $lang);

        $message = new \core\message\message();
        $message->component = 'local_pspvinvoice';
        $message->name = 'invoiceissued';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $manager->get_string('message_invoice_subject', 'local_pspvinvoice', $invoice->fullnumber, $lang);
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = nl2br(s($body));
        $message->smallmessage = $message->subject;
        $message->notification = 1;
        $message->contexturl = $url->out(false);
        $message->contexturlname = $invoice->fullnumber;
        message_send($message);
    }
}

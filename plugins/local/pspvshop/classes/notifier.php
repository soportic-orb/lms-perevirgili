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

namespace local_pspvshop;

/**
 * Messages sent to buyers.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier {
    /**
     * Tells a buyer that their order is paid and their courses are waiting.
     *
     * @param \stdClass $order
     */
    public static function order_paid(\stdClass $order): void {
        $user = \core_user::get_user($order->userid);
        if (!$user || $user->deleted) {
            return;
        }
        $manager = get_string_manager();
        $lang = $user->lang ?: null;
        $courses = [];
        foreach (order_manager::items($order->id) as $item) {
            $courses[] = '- ' . $item->name;
        }
        $url = new \core\url('/local/pspvshop/orders.php', ['id' => $order->id]);
        $data = (object) [
            'firstname' => $user->firstname,
            'number' => order_manager::number($order),
            'courses' => implode("\n", $courses),
            'total' => money::format(money::cents($order->total), $order->currency),
            'url' => $url->out(false),
        ];
        $body = $manager->get_string('message_orderpaid_body', 'local_pspvshop', $data, $lang);

        $message = new \core\message\message();
        $message->component = 'local_pspvshop';
        $message->name = 'orderpaid';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $manager->get_string('message_orderpaid_subject', 'local_pspvshop', $data->number, $lang);
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = nl2br(s($body));
        $message->smallmessage = $message->subject;
        $message->notification = 1;
        $message->contexturl = $url->out(false);
        $message->contexturlname = $manager->get_string('orders_mine', 'local_pspvshop', null, $lang);
        message_send($message);
    }
}

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

namespace local_pspvshop\payment;

use local_pspvshop\order_manager;

/**
 * Connects the orders of the shop to Moodle's payment subsystem, whatever gateway is configured.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {
    #[\Override]
    public static function get_payable(string $paymentarea, int $itemid): \core_payment\local\entities\payable {
        $order = self::pending_order($paymentarea, $itemid);
        return new \core_payment\local\entities\payable(
            (float) $order->total,
            $order->currency,
            (int) get_config('local_pspvshop', 'paymentaccount'),
        );
    }

    #[\Override]
    public static function get_success_url(string $paymentarea, int $itemid): \moodle_url {
        return new \core\url('/local/pspvshop/orders.php', ['id' => $itemid, 'paid' => 1]);
    }

    #[\Override]
    public static function deliver_order(string $paymentarea, int $itemid, int $paymentid, int $userid): bool {
        global $DB;

        if ($paymentarea !== 'order') {
            return false;
        }
        $order = order_manager::get($itemid);
        if ((int) $order->userid !== $userid) {
            return false;
        }
        $gateway = (string) $DB->get_field('payments', 'gateway', ['id' => $paymentid]);
        // An order that is already paid has been delivered: gateways may notify more than once.
        return order_manager::mark_paid($itemid, $paymentid, $gateway)
            || order_manager::get($itemid)->status === order_manager::PAID;
    }

    /**
     * The order a payment is for: it must exist, be waiting for payment and belong to whoever pays.
     *
     * @param string $paymentarea
     * @param int $itemid Order id.
     * @return \stdClass
     */
    protected static function pending_order(string $paymentarea, int $itemid): \stdClass {
        global $USER;

        if ($paymentarea !== 'order') {
            throw new \moodle_exception('order_notpayable', 'local_pspvshop');
        }
        $order = order_manager::get($itemid);
        if ($order->status !== order_manager::PENDING || (int) $order->userid !== (int) $USER->id) {
            throw new \moodle_exception('order_notpayable', 'local_pspvshop');
        }
        return $order;
    }
}

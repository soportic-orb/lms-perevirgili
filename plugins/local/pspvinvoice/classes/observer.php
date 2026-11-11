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
 * Event observers: invoices follow what happens to the orders of the shop.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * An order was paid: its invoice is issued.
     *
     * @param \local_pspvshop\event\order_paid $event
     */
    public static function order_paid(\local_pspvshop\event\order_paid $event): void {
        invoice_manager::issue_for_order((int) $event->objectid);
    }

    /**
     * An order was refunded: its invoice is cancelled with a credit note.
     *
     * @param \local_pspvshop\event\order_refunded $event
     */
    public static function order_refunded(\local_pspvshop\event\order_refunded $event): void {
        invoice_manager::credit_for_order((int) $event->objectid);
    }
}

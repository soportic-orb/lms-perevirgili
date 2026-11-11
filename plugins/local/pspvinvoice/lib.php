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

/**
 * Callbacks of local_pspvinvoice.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Checkout step of the shop: asks for the billing details before the payment.
 *
 * @param stdClass $order The pending order.
 * @param \core\url $returnurl Where the checkout continues.
 * @return \core\url|null The page to go to first, or null when there is nothing to ask.
 */
function local_pspvinvoice_pspvshop_checkout_step(stdClass $order, \core\url $returnurl): ?\core\url {
    if (!\local_pspvinvoice\invoice_manager::enabled() || (float) $order->total <= 0) {
        return null;
    }
    if (\local_pspvinvoice\billing::for_order($order->id)) {
        return null;
    }
    return new \core\url('/local/pspvinvoice/billing.php', ['order' => $order->id]);
}

/**
 * Links shown next to an order of the shop: its invoice and its credit note.
 *
 * @param stdClass $order
 * @return array[] Each with url and text.
 */
function local_pspvinvoice_pspvshop_order_links(stdClass $order): array {
    global $DB;

    $links = [];
    foreach ($DB->get_records('local_pspvinvoice_invoice', ['orderid' => $order->id], 'id') as $invoice) {
        $links[] = [
            'url' => (new \core\url('/local/pspvinvoice/invoice.php', ['id' => $invoice->id]))->out(false),
            'text' => get_string(
                $invoice->doctype === \local_pspvinvoice\invoice_manager::CREDIT ? 'link_credit' : 'link_invoice',
                'local_pspvinvoice',
                $invoice->fullnumber,
            ),
        ];
    }
    return $links;
}

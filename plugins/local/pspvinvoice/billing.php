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
 * Billing details of an order, asked during the checkout.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvinvoice\billing;
use local_pspvinvoice\form\billing_form;
use local_pspvshop\order_manager;

require(__DIR__ . '/../../config.php');

require_login(null, false);

$orderid = required_param('order', PARAM_INT);

$PAGE->set_context(\core\context\system::instance());
$PAGE->set_url(new \core\url('/local/pspvinvoice/billing.php', ['order' => $orderid]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('billing', 'local_pspvinvoice'));
$PAGE->set_heading(get_string('billing', 'local_pspvinvoice'));
$PAGE->add_body_class('local-pspvshop');

$order = order_manager::get($orderid);
if (isguestuser() || (int) $order->userid !== (int) $USER->id) {
    throw new \moodle_exception('order_notfound', 'local_pspvshop');
}
$checkout = new \core\url('/local/pspvshop/checkout.php', ['order' => $order->id]);
if ($order->status !== order_manager::PENDING) {
    redirect(new \core\url('/local/pspvshop/orders.php', ['id' => $order->id]));
}

$form = new billing_form();
$existing = billing::for_order($order->id);
$form->set_data(['order' => $order->id] + ($existing ? (array) $existing : billing::defaults($USER)));

if ($form->is_cancelled()) {
    redirect(new \core\url('/local/pspvshop/cart.php'));
}
if ($data = $form->get_data()) {
    billing::save($order->id, $USER->id, $data);
    redirect($checkout);
}

echo $OUTPUT->header();
echo $OUTPUT->box(get_string('billing_intro', 'local_pspvinvoice'));
$form->display();
echo $OUTPUT->footer();

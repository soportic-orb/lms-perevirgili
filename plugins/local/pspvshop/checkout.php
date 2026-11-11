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
 * Checkout: turns the cart into an order and takes the payment.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\local\page;
use local_pspvshop\money;
use local_pspvshop\order_manager;

require(__DIR__ . '/../../config.php');

require_login(null, false);

$orderid = optional_param('order', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

page::setup('/local/pspvshop/checkout.php', $orderid ? ['order' => $orderid] : [], get_string('checkout', 'local_pspvshop'));
page::require_account();
$PAGE->navbar->add(get_string('cart', 'local_pspvshop'), new \core\url('/local/pspvshop/cart.php'));
$PAGE->navbar->add(get_string('checkout', 'local_pspvshop'));

if (!$orderid) {
    // Coming from the cart: the order is created here, once. Anybody arriving any other way
    // (after logging in, from a bookmark) goes back to the cart to confirm it.
    $sesskey = optional_param('sesskey', '', PARAM_RAW);
    if ($sesskey === '' || !confirm_sesskey($sesskey)) {
        redirect(new \core\url('/local/pspvshop/cart.php'));
    }
    try {
        $order = order_manager::create_from_cart();
    } catch (\moodle_exception $e) {
        redirect(new \core\url('/local/pspvshop/cart.php'), $e->getMessage(), null, \core\output\notification::NOTIFY_ERROR);
    }
    redirect(new \core\url('/local/pspvshop/checkout.php', ['order' => $order->id]));
}

$order = order_manager::get($orderid);
if ((int) $order->userid !== (int) $USER->id) {
    throw new \moodle_exception('order_notfound', 'local_pspvshop');
}
$orderurl = new \core\url('/local/pspvshop/orders.php', ['id' => $order->id]);
if ($order->status !== order_manager::PENDING) {
    redirect($orderurl);
}

// Other plugins can ask for something before the payment, such as the billing details of the invoice.
foreach (get_plugins_with_function('pspvshop_checkout_step') as $plugins) {
    foreach ($plugins as $function) {
        $step = $function($order, $PAGE->url);
        if ($step instanceof \core\url) {
            redirect($step);
        }
    }
}

$system = \core\context\system::instance();
$cansimulate = get_config('local_pspvshop', 'testmode') && has_capability('local/pspvshop:simulatepayment', $system);

if ($action === 'simulate') {
    require_sesskey();
    if (!$cansimulate) {
        throw new \moodle_exception('nopermissions', 'error', '', get_string('pspvshop:simulatepayment', 'local_pspvshop'));
    }
    // A test payment: recorded like a real one when there is a payment account, but no money moves.
    $accountid = (int) get_config('local_pspvshop', 'paymentaccount');
    $paymentid = $accountid ? \core_payment\helper::save_payment(
        $accountid,
        'local_pspvshop',
        'order',
        $order->id,
        $USER->id,
        (float) $order->total,
        $order->currency,
        'pspvtest',
    ) : null;
    order_manager::mark_paid($order->id, $paymentid, 'pspvtest');
    redirect(new \core\url($orderurl, ['paid' => 1]));
}
if ($action === 'cancel') {
    require_sesskey();
    order_manager::cancel($order->id);
    redirect(new \core\url('/local/pspvshop/cart.php'));
}

// The pay button of Moodle's payment subsystem, when a payment account with a gateway is configured.
$paybutton = '';
$accountid = (int) get_config('local_pspvshop', 'paymentaccount');
if ($accountid && \core_payment\helper::get_available_gateways('local_pspvshop', 'order', $order->id)) {
    $PAGE->requires->js_call_amd('core_payment/gateways_modal', 'init');
    $attributes = \core_payment\helper::gateways_modal_link_params(
        'local_pspvshop',
        'order',
        $order->id,
        get_string('order_description', 'local_pspvshop', order_manager::number($order)),
    );
    $attributes['class'] = 'btn btn-primary btn-lg w-100';
    $attributes['type'] = 'button';
    $paybutton = \html_writer::tag('button', get_string('pay', 'local_pspvshop', money::format(
        money::cents($order->total),
        $order->currency,
    )), $attributes);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_pspvshop/checkout', page::order($order) + [
    'actionurl' => $PAGE->url->out(false),
    'sesskey' => sesskey(),
    'paybutton' => $paybutton,
    'canpay' => $paybutton !== '',
    'cansimulate' => $cansimulate,
]);
echo $OUTPUT->footer();

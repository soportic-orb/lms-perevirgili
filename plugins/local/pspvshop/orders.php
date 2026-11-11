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
 * The purchases of the current user.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\local\page;
use local_pspvshop\order_manager;

require(__DIR__ . '/../../config.php');

require_login(null, false);

$id = optional_param('id', 0, PARAM_INT);
$paid = optional_param('paid', 0, PARAM_BOOL);

page::setup('/local/pspvshop/orders.php', $id ? ['id' => $id] : [], get_string('orders_mine', 'local_pspvshop'));
page::require_account();

if ($id) {
    $order = order_manager::get($id);
    if ((int) $order->userid !== (int) $USER->id) {
        throw new \moodle_exception('order_notfound', 'local_pspvshop');
    }
    $PAGE->navbar->add(get_string('orders_mine', 'local_pspvshop'), new \core\url('/local/pspvshop/orders.php'));
    $PAGE->navbar->add(order_manager::number($order));

    echo $OUTPUT->header();
    if ($paid && $order->status === order_manager::PAID) {
        echo $OUTPUT->notification(get_string('order_thanks', 'local_pspvshop'), \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->render_from_template('local_pspvshop/order', page::order($order) + [
        'checkouturl' => (new \core\url('/local/pspvshop/checkout.php', ['order' => $order->id]))->out(false),
        'listurl' => (new \core\url('/local/pspvshop/orders.php'))->out(false),
    ]);
    echo $OUTPUT->footer();
    exit;
}

$orders = [];
$records = $DB->get_records_select(
    'local_pspvshop_order',
    'userid = ? AND status <> ?',
    [$USER->id, order_manager::CANCELLED],
    'timecreated DESC, id DESC',
);
foreach ($records as $order) {
    $orders[] = page::order($order);
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_pspvshop/orders', [
    'orders' => $orders,
    'hasorders' => !empty($orders),
    'catalogueurl' => (new \core\url('/local/pspvshop/index.php'))->out(false),
]);
echo $OUTPUT->footer();

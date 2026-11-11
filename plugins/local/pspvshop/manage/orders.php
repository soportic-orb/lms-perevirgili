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
 * Every order of the shop.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\local\page;
use local_pspvshop\order_manager;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$status = optional_param('status', '', PARAM_ALPHA);
$pagenumber = optional_param('page', 0, PARAM_INT);
$perpage = 30;

admin_externalpage_setup('local_pspvshop_orders');
$system = \core\context\system::instance();
require_capability('local/pspvshop:manageorders', $system);
$listurl = new \core\url('/local/pspvshop/manage/orders.php', $status !== '' ? ['status' => $status] : []);

if ($id && in_array($action, ['cancel', 'refund'], true)) {
    require_sesskey();
    $order = order_manager::get($id);
    if (!$confirm) {
        echo $OUTPUT->header();
        echo $OUTPUT->confirm(
            get_string('order_confirm_' . $action, 'local_pspvshop', order_manager::number($order)),
            new \core\url($listurl, ['id' => $id, 'action' => $action, 'confirm' => 1, 'sesskey' => sesskey()]),
            $listurl,
        );
        echo $OUTPUT->footer();
        exit;
    }
    $done = $action === 'cancel' ? order_manager::cancel($id) : order_manager::refund($id);
    redirect(
        $listurl,
        get_string($done ? 'order_done_' . $action : 'order_notchanged', 'local_pspvshop', order_manager::number($order)),
        null,
        $done ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_WARNING,
    );
}

$statuses = [order_manager::PENDING, order_manager::PAID, order_manager::CANCELLED, order_manager::REFUNDED];
$conditions = in_array($status, $statuses, true) ? ['status' => $status] : [];
$total = $DB->count_records('local_pspvshop_order', $conditions);
$sort = 'timecreated DESC, id DESC';
$records = $DB->get_records('local_pspvshop_order', $conditions, $sort, '*', $pagenumber * $perpage, $perpage);

$orders = [];
foreach ($records as $order) {
    $data = page::order($order, true);
    $data['cancelurl'] = (new \core\url($listurl, ['id' => $order->id, 'action' => 'cancel', 'sesskey' => sesskey()]))->out(false);
    $data['refundurl'] = (new \core\url($listurl, ['id' => $order->id, 'action' => 'refund', 'sesskey' => sesskey()]))->out(false);
    $data['gateway'] = (string) $order->gateway;
    $orders[] = $data;
}
$filters = [[
    'url' => (new \core\url('/local/pspvshop/manage/orders.php'))->out(false),
    'label' => get_string('all'),
    'active' => $status === '',
]];
foreach ($statuses as $option) {
    $filters[] = [
        'url' => (new \core\url('/local/pspvshop/manage/orders.php', ['status' => $option]))->out(false),
        'label' => get_string('status_' . $option, 'local_pspvshop'),
        'active' => $status === $option,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('orders', 'local_pspvshop'));
echo $OUTPUT->render_from_template('local_pspvshop/manage_orders', [
    'orders' => $orders,
    'hasorders' => !empty($orders),
    'filters' => $filters,
]);
echo $OUTPUT->paging_bar($total, $pagenumber, $perpage, $listurl);
echo $OUTPUT->footer();

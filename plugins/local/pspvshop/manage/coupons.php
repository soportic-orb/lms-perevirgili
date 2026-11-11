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
 * Discount codes.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\coupon;
use local_pspvshop\form\coupon_form;
use local_pspvshop\money;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', -1, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);

admin_externalpage_setup('local_pspvshop_coupons');
require_capability('local/pspvshop:managecoupons', \core\context\system::instance());
$listurl = new \core\url('/local/pspvshop/manage/coupons.php');

if ($delete) {
    require_sesskey();
    // A code that has been used stays, switched off, so the orders that used it still make sense.
    if ($DB->record_exists('local_pspvshop_order', ['couponid' => $delete])) {
        $DB->set_field('local_pspvshop_coupon', 'enabled', 0, ['id' => $delete]);
    } else {
        $DB->delete_records('local_pspvshop_coupon', ['id' => $delete]);
    }
    redirect($listurl);
}

$products = $DB->get_records_sql_menu(
    "SELECT p.id, c.fullname FROM {local_pspvshop_product} p JOIN {course} c ON c.id = p.courseid ORDER BY c.fullname"
);

if ($id >= 0) {
    $form = new coupon_form($listurl->out(false) . '?id=' . $id, ['products' => array_map('format_string', $products)]);
    if ($id > 0) {
        $record = $DB->get_record('local_pspvshop_coupon', ['id' => $id], '*', MUST_EXIST);
        $record->amount = format_float((float) $record->amount, 2);
        $record->productid = (int) $record->productid;
        $form->set_data($record);
    } else {
        $form->set_data(['id' => 0]);
    }
    if ($form->is_cancelled()) {
        redirect($listurl);
    }
    if ($data = $form->get_data()) {
        $record = [
            'code' => coupon::normalise($data->code),
            'discounttype' => $data->discounttype === 'amount' ? 'amount' : 'percent',
            'amount' => money::decimal(money::cents($data->amount)),
            'productid' => $data->productid ?: null,
            'validfrom' => (int) $data->validfrom,
            'validuntil' => (int) $data->validuntil,
            'maxuses' => max(0, (int) $data->maxuses),
            'enabled' => empty($data->enabled) ? 0 : 1,
            'timemodified' => time(),
        ];
        if ($data->id) {
            $DB->update_record('local_pspvshop_coupon', ['id' => $data->id] + $record);
        } else {
            $DB->insert_record('local_pspvshop_coupon', $record + ['timecreated' => time()]);
        }
        redirect($listurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string($id ? 'coupon_edit' : 'coupon_add', 'local_pspvshop'));
    $form->display();
    echo $OUTPUT->footer();
    exit;
}

$rows = [];
foreach ($DB->get_records('local_pspvshop_coupon', null, 'code') as $record) {
    $rows[] = [
        'code' => $record->code,
        'discount' => $record->discounttype === 'percent'
            ? format_float((float) $record->amount, 2, true, true) . ' %'
            : money::format(money::cents($record->amount)),
        'product' => $record->productid && isset($products[$record->productid])
            ? format_string($products[$record->productid]) : get_string('coupon_product_any', 'local_pspvshop'),
        'until' => $record->validuntil ? userdate($record->validuntil, get_string('strftimedatetimeshort', 'core_langconfig')) : '',
        'uses' => $record->timesused . ($record->maxuses ? ' / ' . $record->maxuses : ''),
        'enabled' => (bool) $record->enabled,
        'editurl' => (new \core\url($listurl, ['id' => $record->id]))->out(false),
        'deleteurl' => (new \core\url($listurl, ['delete' => $record->id, 'sesskey' => sesskey()]))->out(false),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('coupons', 'local_pspvshop'));
echo $OUTPUT->render_from_template('local_pspvshop/manage_coupons', [
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'addurl' => (new \core\url($listurl, ['id' => 0]))->out(false),
]);
echo $OUTPUT->footer();

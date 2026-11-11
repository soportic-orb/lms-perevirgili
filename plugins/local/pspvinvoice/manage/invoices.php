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
 * Every invoice and credit note, with the export for accounting.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvinvoice\invoice_manager;
use local_pspvshop\money;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$download = optional_param('download', '', PARAM_ALPHA);
$from = optional_param('from', '', PARAM_RAW_TRIMMED);
$to = optional_param('to', '', PARAM_RAW_TRIMMED);
$pagenumber = optional_param('page', 0, PARAM_INT);
$perpage = 50;

admin_externalpage_setup('local_pspvinvoice_invoices');
require_capability('local/pspvinvoice:manage', \core\context\system::instance());

// Dates as typed in the filter (YYYY-MM-DD), in the time zone of the user.
$parse = function (string $date, bool $endofday): int {
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts)) {
        return 0;
    }
    [$hour, $minute, $second] = $endofday ? [23, 59, 59] : [0, 0, 0];
    return make_timestamp((int) $parts[1], (int) $parts[2], (int) $parts[3], $hour, $minute, $second);
};
$where = '1 = 1';
$params = [];
if ($fromtime = $parse($from, false)) {
    $where .= ' AND timeissued >= :fromtime';
    $params['fromtime'] = $fromtime;
}
if ($totime = $parse($to, true)) {
    $where .= ' AND timeissued <= :totime';
    $params['totime'] = $totime;
}

if ($download !== '') {
    require_sesskey();
    $columns = [
        'number' => get_string('doc_number', 'local_pspvinvoice'),
        'type' => get_string('doc_type', 'local_pspvinvoice'),
        'date' => get_string('doc_date', 'local_pspvinvoice'),
        'corrects' => get_string('export_corrects', 'local_pspvinvoice'),
        'buyer' => get_string('doc_buyer', 'local_pspvinvoice'),
        'taxid' => get_string('billing_taxid', 'local_pspvinvoice'),
        'country' => get_string('country'),
        'net' => get_string('doc_net', 'local_pspvinvoice'),
        'tax' => get_string('doc_tax', 'local_pspvinvoice'),
        'total' => get_string('doc_total', 'local_pspvinvoice'),
        'currency' => get_string('export_currency', 'local_pspvinvoice'),
        'order' => get_string('order', 'local_pspvshop'),
    ];
    $numbers = $DB->get_records_menu('local_pspvinvoice_invoice', null, '', 'id, fullnumber');
    $records = $DB->get_recordset_select('local_pspvinvoice_invoice', $where, $params, 'timeissued, id');
    \core\dataformat::download_data('factures-campus-pspv', $download, $columns, $records, function ($invoice) use ($numbers) {
        $buyer = json_decode($invoice->billing, true);
        return [
            'number' => $invoice->fullnumber,
            'type' => $invoice->doctype,
            'date' => userdate($invoice->timeissued, '%Y-%m-%d'),
            'corrects' => $invoice->refinvoiceid ? ($numbers[$invoice->refinvoiceid] ?? '') : '',
            'buyer' => $buyer['name'] ?? '',
            'taxid' => $buyer['taxid'] ?? '',
            'country' => $buyer['country'] ?? '',
            'net' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'currency' => $invoice->currency,
            'order' => $invoice->orderid,
        ];
    });
    $records->close();
    exit;
}

$total = $DB->count_records_select('local_pspvinvoice_invoice', $where, $params);
$records = $DB->get_records_select(
    'local_pspvinvoice_invoice',
    $where,
    $params,
    'timeissued DESC, id DESC',
    '*',
    $pagenumber * $perpage,
    $perpage,
);
$rows = [];
foreach ($records as $invoice) {
    $buyer = json_decode($invoice->billing, true);
    $rows[] = [
        'number' => $invoice->fullnumber,
        'iscredit' => $invoice->doctype === invoice_manager::CREDIT,
        'date' => userdate($invoice->timeissued, get_string('strftimedatetimeshort', 'core_langconfig')),
        'buyer' => $buyer['name'] ?? '',
        'taxid' => $buyer['taxid'] ?? '',
        'total' => money::format(money::cents($invoice->total), $invoice->currency),
        'url' => (new \core\url('/local/pspvinvoice/invoice.php', ['id' => $invoice->id]))->out(false),
    ];
}
$seller = invoice_manager::seller();
$pageurl = new \core\url('/local/pspvinvoice/manage/invoices.php', array_filter(['from' => $from, 'to' => $to]));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('invoices', 'local_pspvinvoice'));
echo $OUTPUT->render_from_template('local_pspvinvoice/manage_invoices', [
    'actionurl' => (new \core\url('/local/pspvinvoice/manage/invoices.php'))->out(false),
    'from' => $from,
    'to' => $to,
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'noseller' => $seller['name'] === '' || $seller['taxid'] === '',
    'settingsurl' => (new \core\url('/admin/settings.php', ['section' => 'local_pspvinvoice_settings']))->out(false),
    'ordersurl' => (new \core\url('/local/pspvshop/manage/orders.php'))->out(false),
]);
if ($rows) {
    echo $OUTPUT->download_dataformat_selector(
        get_string('export', 'local_pspvinvoice'),
        $pageurl->out_omit_querystring(),
        'download',
        ['from' => $from, 'to' => $to, 'sesskey' => sesskey()],
    );
}
echo $OUTPUT->paging_bar($total, $pagenumber, $perpage, $pageurl);
echo $OUTPUT->footer();

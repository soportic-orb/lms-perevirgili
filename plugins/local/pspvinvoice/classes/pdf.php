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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/pdflib.php');

use local_pspvshop\money;

/**
 * Renders an issued invoice or credit note as a PDF, from the copy of the data it stores.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdf {
    /**
     * The PDF of a document.
     *
     * @param \stdClass $invoice
     * @return string Binary content of the PDF.
     */
    public static function render(\stdClass $invoice): string {
        $doc = new \pdf('P', 'mm', 'A4');
        $doc->SetCreator('Campus PSPV');
        $doc->SetTitle($invoice->fullnumber);
        $doc->setPrintHeader(false);
        $doc->setPrintFooter(false);
        $doc->SetMargins(18, 18, 18);
        $doc->SetAutoPageBreak(true, 18);
        $doc->AddPage();
        $doc->SetFont('helvetica', '', 10);
        $doc->writeHTML(self::html($invoice), true, false, true, false, '');
        return $doc->Output('', 'S');
    }

    /**
     * The document as the HTML the PDF is drawn from.
     *
     * @param \stdClass $invoice
     * @return string
     */
    public static function html(\stdClass $invoice): string {
        $seller = json_decode($invoice->seller, true);
        $buyer = json_decode($invoice->billing, true);
        $currency = $invoice->currency;
        $credit = $invoice->doctype === invoice_manager::CREDIT;
        $str = fn(string $id, $a = null): string => get_string($id, 'local_pspvinvoice', $a);

        $title = $str($credit ? 'doc_credit' : 'doc_invoice') . ' ' . $invoice->fullnumber;
        $date = userdate($invoice->timeissued, get_string('strftimedate', 'core_langconfig'));
        $html = '<h1 style="font-size:18pt;">' . s($title) . '</h1>';
        $html .= '<p>' . s($str('doc_date')) . ': ' . s($date);
        if ($credit) {
            global $DB;
            $original = $DB->get_field('local_pspvinvoice_invoice', 'fullnumber', ['id' => $invoice->refinvoiceid]);
            $html .= '<br>' . s($str('doc_corrects', $original));
        }
        $html .= '</p>';

        $party = function (string $title, array $lines): string {
            $lines = array_filter(array_map('trim', $lines), fn($line) => $line !== '');
            $lines = array_map('nl2br', array_map('s', $lines));
            return '<td width="50%"><strong>' . s($title) . '</strong><br>' . implode('<br>', $lines) . '</td>';
        };
        $html .= '<table cellpadding="4"><tr>';
        $html .= $party($str('doc_seller'), [$seller['name'], $seller['taxid'], $seller['address']]);
        $html .= $party($str('doc_buyer'), [
            $buyer['name'] ?? '',
            $buyer['taxid'] ?? '',
            $buyer['address'] ?? '',
            trim(($buyer['postcode'] ?? '') . ' ' . ($buyer['city'] ?? '')),
            empty($buyer['country']) ? '' : (get_string_manager()->get_list_of_countries()[$buyer['country']] ?? $buyer['country']),
        ]);
        $html .= '</tr></table><br><br>';

        $html .= '<table cellpadding="5" border="0"><thead><tr style="background-color:#eef3f8;font-weight:bold;">'
            . '<th width="46%">' . s($str('doc_description')) . '</th>'
            . '<th width="18%" align="right">' . s($str('doc_net')) . '</th>'
            . '<th width="18%" align="right">' . s($str('doc_tax')) . '</th>'
            . '<th width="18%" align="right">' . s($str('doc_total')) . '</th></tr></thead><tbody>';
        foreach (json_decode($invoice->doclines, true) as $line) {
            $rate = $line['taxrate'] > 0 ? ' (' . format_float($line['taxrate'], 2, true, true) . ' %)' : '';
            $html .= '<tr><td width="46%">' . s($line['description']) . '</td>'
                . '<td width="18%" align="right">' . s(money::format($line['net'], $currency)) . '</td>'
                . '<td width="18%" align="right">' . s(money::format($line['tax'], $currency) . $rate) . '</td>'
                . '<td width="18%" align="right">' . s(money::format($line['total'], $currency)) . '</td></tr>';
        }
        $html .= '</tbody></table><br><br>';

        $html .= '<table cellpadding="4"><tr><td width="64%"></td><td width="18%">' . s($str('doc_net')) . '</td>'
            . '<td width="18%" align="right">' . s(money::format(money::cents($invoice->subtotal), $currency)) . '</td></tr>'
            . '<tr><td width="64%"></td><td width="18%">' . s($str('doc_tax')) . '</td>'
            . '<td width="18%" align="right">' . s(money::format(money::cents($invoice->tax), $currency)) . '</td></tr>'
            . '<tr style="font-weight:bold;font-size:12pt;"><td width="64%"></td><td width="18%">' . s($str('doc_total')) . '</td>'
            . '<td width="18%" align="right">' . s(money::format(money::cents($invoice->total), $currency)) . '</td></tr></table>';

        if (!empty($seller['note'])) {
            $html .= '<br><br><p style="font-size:8pt;color:#5b6676;">' . nl2br(s($seller['note'])) . '</p>';
        }
        if (!empty($invoice->fiscalref)) {
            $html .= '<p style="font-size:8pt;color:#5b6676;">' . s($str('doc_fiscalref', $invoice->fiscalref)) . '</p>';
        }
        return $html;
    }
}

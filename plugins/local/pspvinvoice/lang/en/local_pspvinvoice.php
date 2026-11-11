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
 * Strings for local_pspvinvoice, English.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['billing'] = 'Billing details';
$string['billing_continue'] = 'Continue to payment';
$string['billing_email'] = 'Email for the invoice';
$string['billing_intro'] = 'The invoice of your purchase will be made out to these details. You can change them until you pay.';
$string['billing_name'] = 'Name or company name';
$string['billing_name_help'] = 'The full name of the person, or the registered name of the company, the invoice is for.';
$string['billing_postcode'] = 'Postcode';
$string['billing_taxid'] = 'Tax ID (NIF/CIF)';
$string['billing_taxid_invalid'] = 'Enter the tax ID the invoice has to carry.';
$string['billing_type'] = 'The invoice is for';
$string['billing_type_company'] = 'A company or organisation';
$string['billing_type_person'] = 'An individual';
$string['doc_buyer'] = 'Customer';
$string['doc_corrects'] = 'Cancels the invoice {$a}';
$string['doc_credit'] = 'Credit note';
$string['doc_date'] = 'Date';
$string['doc_description'] = 'Description';
$string['doc_fiscalref'] = 'Fiscal reference: {$a}';
$string['doc_invoice'] = 'Invoice';
$string['doc_net'] = 'Net amount';
$string['doc_number'] = 'Number';
$string['doc_seller'] = 'Issued by';
$string['doc_tax'] = 'Tax';
$string['doc_total'] = 'Total';
$string['doc_type'] = 'Type';
$string['error_numbering'] = 'The invoice could not be numbered because another one was being issued. Try again.';
$string['event_credit_note_issued'] = 'Credit note issued';
$string['event_invoice_issued'] = 'Invoice issued';
$string['export'] = 'Export for accounting';
$string['export_corrects'] = 'Cancels';
$string['export_currency'] = 'Currency';
$string['filter_apply'] = 'Filter';
$string['filter_from'] = 'From';
$string['filter_to'] = 'To';
$string['invoices'] = 'Invoices';
$string['invoices_none'] = 'There are no invoices in these dates.';
$string['invoices_noseller'] = 'The name and the tax ID of whoever issues the invoices are not set: invoices are being issued without them.';
$string['invoices_refundhint'] = 'An invoice is never changed. To cancel one, refund its order: the credit note is issued automatically.';
$string['link_credit'] = 'Credit note {$a}';
$string['link_invoice'] = 'Invoice {$a}';
$string['message_invoice_body'] = 'Hello {$a->firstname},

Your invoice {$a->number} is ready. You can download it here: {$a->url}';
$string['message_invoice_subject'] = 'Invoice {$a}';
$string['messageprovider:invoiceissued'] = 'Invoices';
$string['pluginname'] = 'Campus PSPV invoicing';
$string['privacy:metadata:billing'] = 'Billing details given for an order.';
$string['privacy:metadata:billing:address'] = 'Postal address.';
$string['privacy:metadata:billing:email'] = 'Email for the invoice.';
$string['privacy:metadata:billing:name'] = 'Name or company name.';
$string['privacy:metadata:billing:taxid'] = 'Tax ID.';
$string['privacy:metadata:invoice'] = 'Issued invoices and credit notes. They are kept as tax law requires.';
$string['privacy:metadata:invoice:billing'] = 'Copy of the billing details when the document was issued.';
$string['privacy:metadata:invoice:fullnumber'] = 'Number of the document.';
$string['privacy:metadata:invoice:lines'] = 'What was invoiced.';
$string['privacy:metadata:invoice:timeissued'] = 'When the document was issued.';
$string['privacy:metadata:invoice:total'] = 'Amount of the document.';
$string['privacy:metadata:userid'] = 'The buyer.';
$string['pspvinvoice:manage'] = 'See and export every invoice';
$string['setting_creditseries'] = 'Series of the credit notes';
$string['setting_enabled'] = 'Issue invoices';
$string['setting_enabled_desc'] = 'If ticked, buyers are asked for their billing details and an invoice is issued when an order is paid. If not, the shop sells without invoicing.';
$string['setting_footernote'] = 'Note at the foot of the invoices';
$string['setting_footernote_desc'] = 'Legal text printed on every invoice, such as the article of the tax law an exemption relies on. TODO: the hospital has to provide it.';
$string['setting_invoiceseries'] = 'Series of the invoices';
$string['setting_seller_desc'] = 'Printed on the invoices as the issuer. TODO: the hospital has to provide it.';
$string['setting_selleraddress'] = 'Address of the issuer';
$string['setting_sellername'] = 'Name of the issuer';
$string['setting_sellertaxid'] = 'Tax ID of the issuer';
$string['setting_series_desc'] = 'Letters and digits. Documents are numbered series-year-number, starting at 1 every year. Changing the series starts a new numbering.';
$string['settings'] = 'Invoicing settings';

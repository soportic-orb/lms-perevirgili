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
 * Strings for local_pspvinvoice, Catalan.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['billing'] = 'Dades de facturació';
$string['billing_continue'] = 'Continua cap al pagament';
$string['billing_email'] = 'Correu per a la factura';
$string['billing_intro'] = 'La factura de la compra es farà a nom d\'aquestes dades. Les podeu canviar fins que pagueu.';
$string['billing_name'] = 'Nom o raó social';
$string['billing_name_help'] = 'El nom complet de la persona, o la raó social de l\'empresa, a qui va la factura.';
$string['billing_postcode'] = 'Codi postal';
$string['billing_taxid'] = 'NIF/CIF';
$string['billing_taxid_invalid'] = 'Introduïu el NIF o CIF que ha de constar a la factura.';
$string['billing_type'] = 'La factura és per a';
$string['billing_type_company'] = 'Una empresa o entitat';
$string['billing_type_person'] = 'Un particular';
$string['doc_buyer'] = 'Client';
$string['doc_corrects'] = 'Anul·la la factura {$a}';
$string['doc_credit'] = 'Factura rectificativa';
$string['doc_date'] = 'Data';
$string['doc_description'] = 'Concepte';
$string['doc_fiscalref'] = 'Referència fiscal: {$a}';
$string['doc_invoice'] = 'Factura';
$string['doc_net'] = 'Base imposable';
$string['doc_number'] = 'Número';
$string['doc_seller'] = 'Emissor';
$string['doc_tax'] = 'IVA';
$string['doc_total'] = 'Total';
$string['doc_type'] = 'Tipus';
$string['error_numbering'] = 'No s\'ha pogut numerar la factura perquè se n\'estava emetent una altra. Torneu-ho a provar.';
$string['event_credit_note_issued'] = 'Factura rectificativa emesa';
$string['event_invoice_issued'] = 'Factura emesa';
$string['export'] = 'Exporta per a comptabilitat';
$string['export_corrects'] = 'Anul·la';
$string['export_currency'] = 'Moneda';
$string['filter_apply'] = 'Filtra';
$string['filter_from'] = 'Des de';
$string['filter_to'] = 'Fins a';
$string['invoices'] = 'Factures';
$string['invoices_none'] = 'No hi ha cap factura en aquestes dates.';
$string['invoices_noseller'] = 'No s\'ha indicat el nom ni el NIF de qui emet les factures: s\'estan emetent sense aquestes dades.';
$string['invoices_refundhint'] = 'Una factura no es modifica mai. Per anul·lar-ne una, reemborseu la comanda: la factura rectificativa s\'emet automàticament.';
$string['link_credit'] = 'Rectificativa {$a}';
$string['link_invoice'] = 'Factura {$a}';
$string['message_invoice_body'] = 'Hola {$a->firstname},

Ja teniu disponible la factura {$a->number}. La podeu baixar aquí: {$a->url}';
$string['message_invoice_subject'] = 'Factura {$a}';
$string['messageprovider:invoiceissued'] = 'Factures';
$string['pluginname'] = 'Facturació del Campus PSPV';
$string['privacy:metadata:billing'] = 'Dades de facturació donades per a una comanda.';
$string['privacy:metadata:billing:address'] = 'Adreça postal.';
$string['privacy:metadata:billing:email'] = 'Correu per a la factura.';
$string['privacy:metadata:billing:name'] = 'Nom o raó social.';
$string['privacy:metadata:billing:taxid'] = 'NIF o CIF.';
$string['privacy:metadata:invoice'] = 'Factures i rectificatives emeses. Es conserven com exigeix la normativa fiscal.';
$string['privacy:metadata:invoice:billing'] = 'Còpia de les dades de facturació en emetre el document.';
$string['privacy:metadata:invoice:fullnumber'] = 'Número del document.';
$string['privacy:metadata:invoice:lines'] = 'Què es va facturar.';
$string['privacy:metadata:invoice:timeissued'] = 'Quan es va emetre el document.';
$string['privacy:metadata:invoice:total'] = 'Import del document.';
$string['privacy:metadata:userid'] = 'El comprador.';
$string['pspvinvoice:manage'] = 'Veure i exportar totes les factures';
$string['setting_creditseries'] = 'Sèrie de les rectificatives';
$string['setting_enabled'] = 'Emet factures';
$string['setting_enabled_desc'] = 'Si es marca, es demanen les dades de facturació als compradors i s\'emet una factura quan es paga una comanda. Si no, la botiga ven sense facturar.';
$string['setting_footernote'] = 'Nota al peu de les factures';
$string['setting_footernote_desc'] = 'Text legal que s\'imprimeix a totes les factures, com ara l\'article de la llei en què es basa una exempció. TODO: l\'ha de donar l\'hospital.';
$string['setting_invoiceseries'] = 'Sèrie de les factures';
$string['setting_seller_desc'] = 'S\'imprimeix a les factures com a emissor. TODO: ho ha de donar l\'hospital.';
$string['setting_selleraddress'] = 'Adreça de l\'emissor';
$string['setting_sellername'] = 'Nom de l\'emissor';
$string['setting_sellertaxid'] = 'NIF de l\'emissor';
$string['setting_series_desc'] = 'Lletres i xifres. Els documents es numeren sèrie-any-número, començant per 1 cada any. Canviar la sèrie comença una numeració nova.';
$string['settings'] = 'Configuració de la facturació';

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
 * Strings for local_pspvinvoice, Spanish.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['billing'] = 'Datos de facturación';
$string['billing_continue'] = 'Continuar al pago';
$string['billing_email'] = 'Correo para la factura';
$string['billing_intro'] = 'La factura de la compra se hará a nombre de estos datos. Puede cambiarlos hasta que pague.';
$string['billing_name'] = 'Nombre o razón social';
$string['billing_name_help'] = 'El nombre completo de la persona, o la razón social de la empresa, a quien va la factura.';
$string['billing_postcode'] = 'Código postal';
$string['billing_taxid'] = 'NIF/CIF';
$string['billing_taxid_invalid'] = 'Introduzca el NIF o CIF que debe constar en la factura.';
$string['billing_type'] = 'La factura es para';
$string['billing_type_company'] = 'Una empresa o entidad';
$string['billing_type_person'] = 'Un particular';
$string['doc_buyer'] = 'Cliente';
$string['doc_corrects'] = 'Anula la factura {$a}';
$string['doc_credit'] = 'Factura rectificativa';
$string['doc_date'] = 'Fecha';
$string['doc_description'] = 'Concepto';
$string['doc_fiscalref'] = 'Referencia fiscal: {$a}';
$string['doc_invoice'] = 'Factura';
$string['doc_net'] = 'Base imponible';
$string['doc_number'] = 'Número';
$string['doc_seller'] = 'Emisor';
$string['doc_tax'] = 'IVA';
$string['doc_total'] = 'Total';
$string['doc_type'] = 'Tipo';
$string['error_numbering'] = 'No se ha podido numerar la factura porque se estaba emitiendo otra. Vuelva a intentarlo.';
$string['event_credit_note_issued'] = 'Factura rectificativa emitida';
$string['event_invoice_issued'] = 'Factura emitida';
$string['export'] = 'Exportar para contabilidad';
$string['export_corrects'] = 'Anula';
$string['export_currency'] = 'Moneda';
$string['filter_apply'] = 'Filtrar';
$string['filter_from'] = 'Desde';
$string['filter_to'] = 'Hasta';
$string['invoices'] = 'Facturas';
$string['invoices_none'] = 'No hay ninguna factura en estas fechas.';
$string['invoices_noseller'] = 'No se ha indicado el nombre ni el NIF de quien emite las facturas: se están emitiendo sin estos datos.';
$string['invoices_refundhint'] = 'Una factura no se modifica nunca. Para anular una, reembolse el pedido: la factura rectificativa se emite automáticamente.';
$string['link_credit'] = 'Rectificativa {$a}';
$string['link_invoice'] = 'Factura {$a}';
$string['message_invoice_body'] = 'Hola {$a->firstname}:

Ya tiene disponible la factura {$a->number}. Puede descargarla aquí: {$a->url}';
$string['message_invoice_subject'] = 'Factura {$a}';
$string['messageprovider:invoiceissued'] = 'Facturas';
$string['pluginname'] = 'Facturación del Campus PSPV';
$string['privacy:metadata:billing'] = 'Datos de facturación dados para un pedido.';
$string['privacy:metadata:billing:address'] = 'Dirección postal.';
$string['privacy:metadata:billing:email'] = 'Correo para la factura.';
$string['privacy:metadata:billing:name'] = 'Nombre o razón social.';
$string['privacy:metadata:billing:taxid'] = 'NIF o CIF.';
$string['privacy:metadata:invoice'] = 'Facturas y rectificativas emitidas. Se conservan como exige la normativa fiscal.';
$string['privacy:metadata:invoice:billing'] = 'Copia de los datos de facturación al emitir el documento.';
$string['privacy:metadata:invoice:fullnumber'] = 'Número del documento.';
$string['privacy:metadata:invoice:lines'] = 'Qué se facturó.';
$string['privacy:metadata:invoice:timeissued'] = 'Cuándo se emitió el documento.';
$string['privacy:metadata:invoice:total'] = 'Importe del documento.';
$string['privacy:metadata:userid'] = 'El comprador.';
$string['pspvinvoice:manage'] = 'Ver y exportar todas las facturas';
$string['setting_creditseries'] = 'Serie de las rectificativas';
$string['setting_enabled'] = 'Emitir facturas';
$string['setting_enabled_desc'] = 'Si se marca, se piden los datos de facturación a los compradores y se emite una factura cuando se paga un pedido. Si no, la tienda vende sin facturar.';
$string['setting_footernote'] = 'Nota al pie de las facturas';
$string['setting_footernote_desc'] = 'Texto legal que se imprime en todas las facturas, como el artículo de la ley en que se basa una exención. TODO: debe darlo el hospital.';
$string['setting_invoiceseries'] = 'Serie de las facturas';
$string['setting_seller_desc'] = 'Se imprime en las facturas como emisor. TODO: debe darlo el hospital.';
$string['setting_selleraddress'] = 'Dirección del emisor';
$string['setting_sellername'] = 'Nombre del emisor';
$string['setting_sellertaxid'] = 'NIF del emisor';
$string['setting_series_desc'] = 'Letras y cifras. Los documentos se numeran serie-año-número, empezando por 1 cada año. Cambiar la serie empieza una numeración nueva.';
$string['settings'] = 'Configuración de la facturación';

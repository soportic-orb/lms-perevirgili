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
 * Strings for local_pspvshop, Catalan.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cart'] = 'Cistella';
$string['cart_add'] = 'Afegeix a la cistella';
$string['cart_added'] = 'El curs és a la cistella.';
$string['cart_empty'] = 'La cistella és buida';
$string['cart_goto'] = 'Vés a la cistella';
$string['cart_removed'] = '«{$a}» ja no es pot comprar i s\'ha tret de la cistella.';
$string['catalogue'] = 'Catàleg de cursos';
$string['catalogue_back'] = 'Torna al catàleg';
$string['catalogue_empty'] = 'Cap curs coincideix amb aquests filtres';
$string['catalogue_empty_desc'] = 'Proveu de treure algun filtre o de cercar una altra cosa.';
$string['catalogue_total'] = 'Cursos trobats: {$a}';
$string['checkout'] = 'Pagament';
$string['checkout_back'] = 'Cancel·la i torna a la cistella';
$string['checkout_continue'] = 'Continua cap al pagament';
$string['checkout_needslogin'] = 'Abans de pagar se us demanarà que inicieu la sessió o us registreu.';
$string['checkout_nogateway'] = 'El pagament en línia encara no està configurat. La comanda queda desada; la podreu pagar des de «Les meves compres».';
$string['checkout_simulate'] = 'Simula el pagament';
$string['checkout_simulate_desc'] = 'Mode de proves: marca la comanda com a pagada sense moure diners. Només ho veu qui pot simular pagaments.';
$string['coupon_add'] = 'Codi de descompte nou';
$string['coupon_amount'] = 'Descompte';
$string['coupon_apply'] = 'Aplica';
$string['coupon_code'] = 'Codi';
$string['coupon_edit'] = 'Edita el codi de descompte';
$string['coupon_enabled'] = 'Actiu';
$string['coupon_have'] = 'Codi de descompte';
$string['coupon_invalid'] = 'Aquest codi no és vàlid.';
$string['coupon_maxuses'] = 'Nombre màxim d\'usos';
$string['coupon_maxuses_help'] = 'Quantes comandes pagades poden fer servir el codi. 0 vol dir sense límit.';
$string['coupon_product'] = 'Vàlid per a';
$string['coupon_product_any'] = 'Qualsevol curs';
$string['coupon_type'] = 'Tipus de descompte';
$string['coupon_type_amount'] = 'Import fix ({$a})';
$string['coupon_type_percent'] = 'Percentatge (%)';
$string['coupon_uses'] = 'Usos';
$string['coupon_validfrom'] = 'Vàlid des de';
$string['coupon_validuntil'] = 'Vàlid fins a';
$string['coupons'] = 'Codis de descompte';
$string['coupons_none'] = 'Encara no hi ha cap codi de descompte.';
$string['error_amount'] = 'Introduïu un import vàlid.';
$string['error_couponexists'] = 'Ja hi ha un codi amb aquest nom.';
$string['error_saleend'] = 'El final ha de ser posterior a l\'inici.';
$string['error_seats'] = 'Introduïu un nombre enter, o deixeu-ho buit si no hi ha límit.';
$string['error_taxrate'] = 'Introduïu un percentatge entre 0 i 100, o deixeu-ho buit.';
$string['event_order_cancelled'] = 'Comanda cancel·lada';
$string['event_order_created'] = 'Comanda feta';
$string['event_order_paid'] = 'Comanda pagada';
$string['event_order_refunded'] = 'Comanda reemborsada';
$string['filter_any'] = 'Qualsevol';
$string['filter_apply'] = 'Filtra';
$string['filter_area'] = 'Àrea';
$string['filter_clear'] = 'Neteja';
$string['filter_mode'] = 'Modalitat';
$string['filter_price'] = 'Preu';
$string['filter_start'] = 'Inici';
$string['filters'] = 'Filtres del catàleg';
$string['message_orderpaid_body'] = 'Hola {$a->firstname},

Hem rebut el pagament de la comanda {$a->number} ({$a->total}). Ja teniu la matrícula feta a:

{$a->courses}

Podeu veure la comanda aquí: {$a->url}';
$string['message_orderpaid_subject'] = 'Comanda {$a} confirmada';
$string['messageprovider:orderpaid'] = 'Confirmacions de compra';
$string['mode_blended'] = 'Semipresencial';
$string['mode_online'] = 'En línia';
$string['mode_onsite'] = 'Presencial';
$string['order'] = 'Comanda';
$string['order_buyer'] = 'Comprador/a';
$string['order_confirm_cancel'] = 'Voleu cancel·lar la comanda {$a}?';
$string['order_confirm_refund'] = 'Voleu marcar la comanda {$a} com a reemborsada? Es donarà de baixa el comprador dels cursos. Els diners s\'han de tornar des de la passarel·la de pagament: això no en mou.';
$string['order_description'] = 'Campus PSPV, comanda {$a}';
$string['order_done_cancel'] = 'S\'ha cancel·lat la comanda {$a}.';
$string['order_done_refund'] = 'S\'ha marcat la comanda {$a} com a reemborsada.';
$string['order_notchanged'] = 'La comanda {$a} no estava en un estat que ho permeti.';
$string['order_notfound'] = 'Aquesta comanda no existeix.';
$string['order_notpayable'] = 'Aquesta comanda no es pot pagar.';
$string['order_number'] = 'Comanda {$a}';
$string['order_pay'] = 'Paga ara';
$string['order_refund'] = 'Reemborsa';
$string['order_thanks'] = 'Pagament rebut. Ja teniu la matrícula feta: podeu obrir els cursos des d\'aquesta pàgina.';
$string['orders'] = 'Comandes';
$string['orders_mine'] = 'Les meves compres';
$string['orders_none'] = 'Encara no heu comprat res';
$string['orders_none_admin'] = 'No hi ha cap comanda.';
$string['pay'] = 'Paga {$a}';
$string['pluginname'] = 'Botiga del Campus PSPV';
$string['price_50to150'] = 'De 50 a 150';
$string['price_free'] = 'Gratuït';
$string['price_over150'] = 'Més de 150';
$string['price_upto50'] = 'Fins a 50';
$string['privacy:metadata:cartitem'] = 'Cursos a la cistella d\'un usuari.';
$string['privacy:metadata:cartitem:productid'] = 'El curs en venda.';
$string['privacy:metadata:cartitem:userid'] = 'L\'usuari.';
$string['privacy:metadata:order'] = 'Comandes fetes a la botiga.';
$string['privacy:metadata:order:couponcode'] = 'El codi de descompte fet servir.';
$string['privacy:metadata:order:gateway'] = 'La passarel·la de pagament feta servir.';
$string['privacy:metadata:order:status'] = 'Si la comanda està pendent, pagada, cancel·lada o reemborsada.';
$string['privacy:metadata:order:timepaid'] = 'Quan es va pagar la comanda.';
$string['privacy:metadata:order:total'] = 'L\'import de la comanda.';
$string['privacy:metadata:order:userid'] = 'El comprador.';
$string['privacy:metadata:orderitem'] = 'Línies de les comandes.';
$string['privacy:metadata:orderitem:name'] = 'El curs comprat.';
$string['privacy:metadata:orderitem:total'] = 'L\'import de la línia.';
$string['privacy:metadata:payment'] = 'Els pagaments els cobra el subsistema de pagaments de Moodle.';
$string['privacy:metadata:timecreated'] = 'Quan es va crear el registre.';
$string['product_access'] = 'Accés tan bon punt es confirma el pagament';
$string['product_buy'] = 'Inscripció';
$string['product_cohortprices'] = 'Preus per a col·lectius';
$string['product_cohortprices_desc'] = 'Un preu per als membres d\'una cohort: treballadors, col·legiats, entitats conveniades. Deixeu-ho buit on s\'apliqui el preu general. Un comprador que és a diverses cohorts paga el preu més baix.';
$string['product_description'] = 'Sobre aquest curs';
$string['product_enabled'] = 'En venda';
$string['product_end'] = 'Final';
$string['product_gotocourse'] = 'Vés al curs';
$string['product_mode'] = 'Modalitat';
$string['product_notfound'] = 'Aquest curs no és en venda.';
$string['product_notonsale'] = 'No és en venda';
$string['product_onsale'] = 'En venda';
$string['product_price'] = 'Preu general ({$a})';
$string['product_price_help'] = 'El preu per a un comprador que no té cap preu especial. Si inclou l\'IVA es decideix a la configuració de la botiga.';
$string['product_price_short'] = 'Preu';
$string['product_saleend'] = 'Final de la venda';
$string['product_salestart'] = 'Inici de la venda';
$string['product_saleuntil'] = 'Inscripció oberta fins al {$a}';
$string['product_seats'] = 'Places';
$string['product_seats_help'] = 'Quantes inscripcions es poden vendre. Deixeu-ho buit si no hi ha límit.';
$string['product_seats_short'] = 'Places lliures';
$string['product_sell'] = 'Posa en venda';
$string['product_start'] = 'Inici';
$string['product_taxrate'] = 'Tipus d\'IVA (%)';
$string['product_taxrate_help'] = 'Deixeu-ho buit per fer servir el tipus de la botiga. Poseu 0 per a un curs exempt.';
$string['products'] = 'Cursos en venda';
$string['products_intro'] = 'Es poden posar en venda els cursos de la categoria «{$a}» i de les seves subcategories.';
$string['products_nocategory'] = 'La categoria del catàleg no existeix.';
$string['products_none'] = 'Encara no hi ha cap curs en aquesta categoria.';
$string['products_notaxrate'] = 'La botiga no té cap tipus d\'IVA definit: els cursos es venen sense IVA fins que se\'n defineixi un.';
$string['pspvshop:managecoupons'] = 'Gestionar els codis de descompte';
$string['pspvshop:manageorders'] = 'Veure, cancel·lar i reemborsar comandes';
$string['pspvshop:manageproducts'] = 'Posar cursos en venda i fixar preus';
$string['pspvshop:simulatepayment'] = 'Simular pagaments en mode de proves';
$string['seats_left'] = 'Places lliures: {$a}';
$string['seats_none'] = 'No queden places';
$string['setting_category'] = 'Categoria del catàleg';
$string['setting_category_desc'] = 'Número d\'identificació de la categoria de cursos que es poden vendre, amb les seves subcategories.';
$string['setting_currency'] = 'Moneda';
$string['setting_currency_desc'] = 'Codi ISO de tres lletres. Ha de ser una moneda que accepti la passarel·la de pagament.';
$string['setting_enrolrole'] = 'Rol dels compradors';
$string['setting_enrolrole_default'] = 'Estudiant (per defecte)';
$string['setting_enrolrole_desc'] = 'Rol que rep un comprador als cursos que compra.';
$string['setting_paymentaccount'] = 'Compte de pagament';
$string['setting_paymentaccount_desc'] = 'El compte de pagament de Moodle (Administració del lloc > Pagaments) que cobra, amb la passarel·la que tingui configurada. Sense compte, les comandes es desen però no es poden pagar en línia.';
$string['setting_pricesincludetax'] = 'Els preus inclouen l\'IVA';
$string['setting_pricesincludetax_desc'] = 'Si es marca, el preu escrit en un curs és el que paga el comprador i l\'IVA se\'n desglossa. Si no, l\'IVA s\'hi suma.';
$string['setting_showtiers'] = 'Mostra els preus per a col·lectius';
$string['setting_showtiers_desc'] = 'Mostra a la fitxa d\'un curs els preus que té per a cada cohort, perquè els compradors sàpiguen que existeixen.';
$string['setting_taxrate'] = 'Tipus d\'IVA (%)';
$string['setting_taxrate_desc'] = 'Tipus d\'IVA dels cursos que no en defineixin un de propi. TODO: l\'hospital ha de confirmar el tractament fiscal de la seva formació; mentre això sigui buit, els cursos es venen sense IVA.';
$string['setting_testmode'] = 'Mode de proves';
$string['setting_testmode_desc'] = 'Mostra, al pagament, un botó que marca la comanda com a pagada sense passarel·la. Només el veuen els usuaris amb permís per simular pagaments. Desactiveu-lo a producció.';
$string['settings'] = 'Configuració de la botiga';
$string['start_open'] = 'Ja obert';
$string['start_upcoming'] = 'Encara no ha començat';
$string['status_cancelled'] = 'Cancel·lada';
$string['status_paid'] = 'Pagada';
$string['status_pending'] = 'Pendent de pagament';
$string['status_refunded'] = 'Reemborsada';
$string['tax_added'] = 'Més un {$a} % d\'IVA';
$string['tax_included'] = '{$a} % d\'IVA inclòs';
$string['total'] = 'Total';
$string['total_discount'] = 'Descompte';
$string['total_tax'] = 'IVA';
$string['total_taxincluded'] = 'IVA inclòs';
$string['unavailable_closed'] = 'La inscripció a aquest curs s\'ha tancat.';
$string['unavailable_disabled'] = 'Aquest curs no és en venda.';
$string['unavailable_enrolled'] = 'Ja teniu la matrícula feta en aquest curs.';
$string['unavailable_full'] = 'No queden places en aquest curs.';
$string['unavailable_hidden'] = 'Aquest curs no és en venda.';
$string['unavailable_notyet'] = 'La inscripció a aquest curs encara no s\'ha obert.';

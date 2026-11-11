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
 * Strings for local_pspvshop, Spanish.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cart'] = 'Cesta';
$string['cart_add'] = 'Añadir a la cesta';
$string['cart_added'] = 'El curso está en la cesta.';
$string['cart_empty'] = 'La cesta está vacía';
$string['cart_goto'] = 'Ir a la cesta';
$string['cart_removed'] = '«{$a}» ya no se puede comprar y se ha quitado de la cesta.';
$string['catalogue'] = 'Catálogo de cursos';
$string['catalogue_back'] = 'Volver al catálogo';
$string['catalogue_empty'] = 'Ningún curso coincide con estos filtros';
$string['catalogue_empty_desc'] = 'Pruebe a quitar algún filtro o a buscar otra cosa.';
$string['catalogue_total'] = 'Cursos encontrados: {$a}';
$string['checkout'] = 'Pago';
$string['checkout_back'] = 'Cancelar y volver a la cesta';
$string['checkout_continue'] = 'Continuar al pago';
$string['checkout_needslogin'] = 'Antes de pagar se le pedirá que inicie sesión o se registre.';
$string['checkout_nogateway'] = 'El pago en línea aún no está configurado. El pedido queda guardado; podrá pagarlo desde «Mis compras».';
$string['checkout_simulate'] = 'Simular el pago';
$string['checkout_simulate_desc'] = 'Modo de pruebas: marca el pedido como pagado sin mover dinero. Solo lo ve quien puede simular pagos.';
$string['coupon_add'] = 'Nuevo código de descuento';
$string['coupon_amount'] = 'Descuento';
$string['coupon_apply'] = 'Aplicar';
$string['coupon_code'] = 'Código';
$string['coupon_edit'] = 'Editar el código de descuento';
$string['coupon_enabled'] = 'Activo';
$string['coupon_have'] = 'Código de descuento';
$string['coupon_invalid'] = 'Este código no es válido.';
$string['coupon_maxuses'] = 'Número máximo de usos';
$string['coupon_maxuses_help'] = 'Cuántos pedidos pagados pueden usar el código. 0 significa sin límite.';
$string['coupon_product'] = 'Válido para';
$string['coupon_product_any'] = 'Cualquier curso';
$string['coupon_type'] = 'Tipo de descuento';
$string['coupon_type_amount'] = 'Importe fijo ({$a})';
$string['coupon_type_percent'] = 'Porcentaje (%)';
$string['coupon_uses'] = 'Usos';
$string['coupon_validfrom'] = 'Válido desde';
$string['coupon_validuntil'] = 'Válido hasta';
$string['coupons'] = 'Códigos de descuento';
$string['coupons_none'] = 'Todavía no hay ningún código de descuento.';
$string['error_amount'] = 'Introduzca un importe válido.';
$string['error_couponexists'] = 'Ya existe un código con este nombre.';
$string['error_saleend'] = 'El final debe ser posterior al inicio.';
$string['error_seats'] = 'Introduzca un número entero, o déjelo vacío si no hay límite.';
$string['error_taxrate'] = 'Introduzca un porcentaje entre 0 y 100, o déjelo vacío.';
$string['event_order_cancelled'] = 'Pedido cancelado';
$string['event_order_created'] = 'Pedido realizado';
$string['event_order_paid'] = 'Pedido pagado';
$string['event_order_refunded'] = 'Pedido reembolsado';
$string['filter_any'] = 'Cualquiera';
$string['filter_apply'] = 'Filtrar';
$string['filter_area'] = 'Área';
$string['filter_clear'] = 'Limpiar';
$string['filter_mode'] = 'Modalidad';
$string['filter_price'] = 'Precio';
$string['filter_start'] = 'Inicio';
$string['filters'] = 'Filtros del catálogo';
$string['message_orderpaid_body'] = 'Hola {$a->firstname}:

Hemos recibido el pago del pedido {$a->number} ({$a->total}). Ya está matriculado en:

{$a->courses}

Puede ver el pedido aquí: {$a->url}';
$string['message_orderpaid_subject'] = 'Pedido {$a} confirmado';
$string['messageprovider:orderpaid'] = 'Confirmaciones de compra';
$string['mode_blended'] = 'Semipresencial';
$string['mode_online'] = 'En línea';
$string['mode_onsite'] = 'Presencial';
$string['order'] = 'Pedido';
$string['order_buyer'] = 'Comprador/a';
$string['order_confirm_cancel'] = '¿Desea cancelar el pedido {$a}?';
$string['order_confirm_refund'] = '¿Desea marcar el pedido {$a} como reembolsado? Se dará de baja al comprador de los cursos. El dinero debe devolverse desde la pasarela de pago: esto no lo mueve.';
$string['order_description'] = 'Campus PSPV, pedido {$a}';
$string['order_done_cancel'] = 'Se ha cancelado el pedido {$a}.';
$string['order_done_refund'] = 'Se ha marcado el pedido {$a} como reembolsado.';
$string['order_notchanged'] = 'El pedido {$a} no estaba en un estado que lo permita.';
$string['order_notfound'] = 'Este pedido no existe.';
$string['order_notpayable'] = 'Este pedido no se puede pagar.';
$string['order_number'] = 'Pedido {$a}';
$string['order_pay'] = 'Pagar ahora';
$string['order_refund'] = 'Reembolsar';
$string['order_thanks'] = 'Pago recibido. Ya está matriculado: puede abrir los cursos desde esta página.';
$string['orders'] = 'Pedidos';
$string['orders_mine'] = 'Mis compras';
$string['orders_none'] = 'Todavía no ha comprado nada';
$string['orders_none_admin'] = 'No hay ningún pedido.';
$string['pay'] = 'Pagar {$a}';
$string['pluginname'] = 'Tienda del Campus PSPV';
$string['price_50to150'] = 'De 50 a 150';
$string['price_free'] = 'Gratuito';
$string['price_over150'] = 'Más de 150';
$string['price_upto50'] = 'Hasta 50';
$string['privacy:metadata:cartitem'] = 'Cursos en la cesta de un usuario.';
$string['privacy:metadata:cartitem:productid'] = 'El curso en venta.';
$string['privacy:metadata:cartitem:userid'] = 'El usuario.';
$string['privacy:metadata:order'] = 'Pedidos realizados en la tienda.';
$string['privacy:metadata:order:couponcode'] = 'El código de descuento usado.';
$string['privacy:metadata:order:gateway'] = 'La pasarela de pago usada.';
$string['privacy:metadata:order:status'] = 'Si el pedido está pendiente, pagado, cancelado o reembolsado.';
$string['privacy:metadata:order:timepaid'] = 'Cuándo se pagó el pedido.';
$string['privacy:metadata:order:total'] = 'El importe del pedido.';
$string['privacy:metadata:order:userid'] = 'El comprador.';
$string['privacy:metadata:orderitem'] = 'Líneas de los pedidos.';
$string['privacy:metadata:orderitem:name'] = 'El curso comprado.';
$string['privacy:metadata:orderitem:total'] = 'El importe de la línea.';
$string['privacy:metadata:payment'] = 'Los pagos los cobra el subsistema de pagos de Moodle.';
$string['privacy:metadata:timecreated'] = 'Cuándo se creó el registro.';
$string['product_access'] = 'Acceso en cuanto se confirma el pago';
$string['product_buy'] = 'Inscripción';
$string['product_cohortprices'] = 'Precios para colectivos';
$string['product_cohortprices_desc'] = 'Un precio para los miembros de una cohorte: trabajadores, colegiados, entidades conveniadas. Déjelo vacío donde se aplique el precio general. Un comprador que está en varias cohortes paga el precio más bajo.';
$string['product_description'] = 'Sobre este curso';
$string['product_enabled'] = 'En venta';
$string['product_end'] = 'Final';
$string['product_gotocourse'] = 'Ir al curso';
$string['product_mode'] = 'Modalidad';
$string['product_notfound'] = 'Este curso no está en venta.';
$string['product_notonsale'] = 'No está en venta';
$string['product_onsale'] = 'En venta';
$string['product_price'] = 'Precio general ({$a})';
$string['product_price_help'] = 'El precio para un comprador que no tiene ningún precio especial. Si incluye el IVA se decide en la configuración de la tienda.';
$string['product_price_short'] = 'Precio';
$string['product_saleend'] = 'Final de la venta';
$string['product_salestart'] = 'Inicio de la venta';
$string['product_saleuntil'] = 'Inscripción abierta hasta el {$a}';
$string['product_seats'] = 'Plazas';
$string['product_seats_help'] = 'Cuántas inscripciones se pueden vender. Déjelo vacío si no hay límite.';
$string['product_seats_short'] = 'Plazas libres';
$string['product_sell'] = 'Poner en venta';
$string['product_start'] = 'Inicio';
$string['product_taxrate'] = 'Tipo de IVA (%)';
$string['product_taxrate_help'] = 'Déjelo vacío para usar el tipo de la tienda. Ponga 0 para un curso exento.';
$string['products'] = 'Cursos en venta';
$string['products_intro'] = 'Se pueden poner en venta los cursos de la categoría «{$a}» y de sus subcategorías.';
$string['products_nocategory'] = 'La categoría del catálogo no existe.';
$string['products_none'] = 'Todavía no hay ningún curso en esta categoría.';
$string['products_notaxrate'] = 'La tienda no tiene ningún tipo de IVA definido: los cursos se venden sin IVA hasta que se defina uno.';
$string['pspvshop:managecoupons'] = 'Gestionar los códigos de descuento';
$string['pspvshop:manageorders'] = 'Ver, cancelar y reembolsar pedidos';
$string['pspvshop:manageproducts'] = 'Poner cursos en venta y fijar precios';
$string['pspvshop:simulatepayment'] = 'Simular pagos en modo de pruebas';
$string['seats_left'] = 'Plazas libres: {$a}';
$string['seats_none'] = 'No quedan plazas';
$string['setting_category'] = 'Categoría del catálogo';
$string['setting_category_desc'] = 'Número de identificación de la categoría de cursos que se pueden vender, con sus subcategorías.';
$string['setting_currency'] = 'Moneda';
$string['setting_currency_desc'] = 'Código ISO de tres letras. Debe ser una moneda que acepte la pasarela de pago.';
$string['setting_enrolrole'] = 'Rol de los compradores';
$string['setting_enrolrole_default'] = 'Estudiante (por defecto)';
$string['setting_enrolrole_desc'] = 'Rol que recibe un comprador en los cursos que compra.';
$string['setting_paymentaccount'] = 'Cuenta de pago';
$string['setting_paymentaccount_desc'] = 'La cuenta de pago de Moodle (Administración del sitio > Pagos) que cobra, con la pasarela que tenga configurada. Sin cuenta, los pedidos se guardan pero no se pueden pagar en línea.';
$string['setting_pricesincludetax'] = 'Los precios incluyen el IVA';
$string['setting_pricesincludetax_desc'] = 'Si se marca, el precio escrito en un curso es lo que paga el comprador y el IVA se desglosa de él. Si no, el IVA se le suma.';
$string['setting_showtiers'] = 'Mostrar los precios para colectivos';
$string['setting_showtiers_desc'] = 'Muestra en la ficha de un curso los precios que tiene para cada cohorte, para que los compradores sepan que existen.';
$string['setting_taxrate'] = 'Tipo de IVA (%)';
$string['setting_taxrate_desc'] = 'Tipo de IVA de los cursos que no definan uno propio. TODO: el hospital debe confirmar el tratamiento fiscal de su formación; mientras esto esté vacío, los cursos se venden sin IVA.';
$string['setting_testmode'] = 'Modo de pruebas';
$string['setting_testmode_desc'] = 'Muestra, en el pago, un botón que marca el pedido como pagado sin pasarela. Solo lo ven los usuarios con permiso para simular pagos. Desactívelo en producción.';
$string['settings'] = 'Configuración de la tienda';
$string['start_open'] = 'Ya abierto';
$string['start_upcoming'] = 'Aún no ha empezado';
$string['status_cancelled'] = 'Cancelado';
$string['status_paid'] = 'Pagado';
$string['status_pending'] = 'Pendiente de pago';
$string['status_refunded'] = 'Reembolsado';
$string['tax_added'] = 'Más un {$a} % de IVA';
$string['tax_included'] = '{$a} % de IVA incluido';
$string['total'] = 'Total';
$string['total_discount'] = 'Descuento';
$string['total_tax'] = 'IVA';
$string['total_taxincluded'] = 'IVA incluido';
$string['unavailable_closed'] = 'La inscripción a este curso se ha cerrado.';
$string['unavailable_disabled'] = 'Este curso no está en venta.';
$string['unavailable_enrolled'] = 'Ya está matriculado en este curso.';
$string['unavailable_full'] = 'No quedan plazas en este curso.';
$string['unavailable_hidden'] = 'Este curso no está en venta.';
$string['unavailable_notyet'] = 'La inscripción a este curso aún no se ha abierto.';

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
 * Strings for local_pspvshop, English.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cart'] = 'Cart';
$string['cart_add'] = 'Add to cart';
$string['cart_added'] = 'The course is in your cart.';
$string['cart_empty'] = 'Your cart is empty';
$string['cart_goto'] = 'Go to the cart';
$string['cart_removed'] = '"{$a}" can no longer be bought and has been taken out of your cart.';
$string['catalogue'] = 'Course catalogue';
$string['catalogue_back'] = 'Back to the catalogue';
$string['catalogue_empty'] = 'No course matches these filters';
$string['catalogue_empty_desc'] = 'Try removing a filter or searching for something else.';
$string['catalogue_total'] = 'Courses found: {$a}';
$string['checkout'] = 'Checkout';
$string['checkout_back'] = 'Cancel and go back to the cart';
$string['checkout_continue'] = 'Continue to payment';
$string['checkout_needslogin'] = 'You will be asked to log in or sign up before paying.';
$string['checkout_nogateway'] = 'Online payment is not set up yet. Your order is saved; you will be able to pay it from "My purchases".';
$string['checkout_simulate'] = 'Simulate the payment';
$string['checkout_simulate_desc'] = 'Test mode: marks the order as paid without moving any money. Only visible to those who can simulate payments.';
$string['coupon_add'] = 'New discount code';
$string['coupon_amount'] = 'Discount';
$string['coupon_apply'] = 'Apply';
$string['coupon_code'] = 'Code';
$string['coupon_edit'] = 'Edit discount code';
$string['coupon_enabled'] = 'Active';
$string['coupon_have'] = 'Discount code';
$string['coupon_invalid'] = 'This code is not valid.';
$string['coupon_maxuses'] = 'Maximum number of uses';
$string['coupon_maxuses_help'] = 'How many paid orders can use the code. 0 means no limit.';
$string['coupon_product'] = 'Valid for';
$string['coupon_product_any'] = 'Any course';
$string['coupon_type'] = 'Type of discount';
$string['coupon_type_amount'] = 'Fixed amount ({$a})';
$string['coupon_type_percent'] = 'Percentage (%)';
$string['coupon_uses'] = 'Uses';
$string['coupon_validfrom'] = 'Valid from';
$string['coupon_validuntil'] = 'Valid until';
$string['coupons'] = 'Discount codes';
$string['coupons_none'] = 'There are no discount codes yet.';
$string['error_amount'] = 'Enter a valid amount.';
$string['error_couponexists'] = 'There is already a code with this name.';
$string['error_saleend'] = 'The end must be after the start.';
$string['error_seats'] = 'Enter a whole number, or leave it empty for no limit.';
$string['error_taxrate'] = 'Enter a percentage between 0 and 100, or leave it empty.';
$string['event_order_cancelled'] = 'Order cancelled';
$string['event_order_created'] = 'Order placed';
$string['event_order_paid'] = 'Order paid';
$string['event_order_refunded'] = 'Order refunded';
$string['filter_any'] = 'Any';
$string['filter_apply'] = 'Filter';
$string['filter_area'] = 'Area';
$string['filter_clear'] = 'Clear';
$string['filter_mode'] = 'Mode';
$string['filter_price'] = 'Price';
$string['filter_start'] = 'Start';
$string['filters'] = 'Catalogue filters';
$string['message_orderpaid_body'] = 'Hello {$a->firstname},

We have received the payment of your order {$a->number} ({$a->total}). You are now enrolled in:

{$a->courses}

You can see the order here: {$a->url}';
$string['message_orderpaid_subject'] = 'Order {$a} confirmed';
$string['messageprovider:orderpaid'] = 'Purchase confirmations';
$string['mode_blended'] = 'Blended';
$string['mode_online'] = 'Online';
$string['mode_onsite'] = 'On site';
$string['order'] = 'Order';
$string['order_buyer'] = 'Buyer';
$string['order_confirm_cancel'] = 'Cancel the order {$a}?';
$string['order_confirm_refund'] = 'Mark the order {$a} as refunded? The buyer will be unenrolled from its courses. The money has to be returned from the payment gateway: this does not move any.';
$string['order_description'] = 'Campus PSPV, order {$a}';
$string['order_done_cancel'] = 'The order {$a} has been cancelled.';
$string['order_done_refund'] = 'The order {$a} has been marked as refunded.';
$string['order_notchanged'] = 'The order {$a} was not in a state that allows this.';
$string['order_notfound'] = 'This order does not exist.';
$string['order_notpayable'] = 'This order cannot be paid.';
$string['order_number'] = 'Order {$a}';
$string['order_pay'] = 'Pay now';
$string['order_refund'] = 'Refund';
$string['order_thanks'] = 'Payment received. You are now enrolled: you can open your courses from this page.';
$string['orders'] = 'Orders';
$string['orders_mine'] = 'My purchases';
$string['orders_none'] = 'You have not bought anything yet';
$string['orders_none_admin'] = 'There are no orders.';
$string['pay'] = 'Pay {$a}';
$string['pluginname'] = 'Campus PSPV shop';
$string['price_50to150'] = 'From 50 to 150';
$string['price_free'] = 'Free';
$string['price_over150'] = 'Over 150';
$string['price_upto50'] = 'Up to 50';
$string['privacy:metadata:cartitem'] = 'Courses in the cart of a user.';
$string['privacy:metadata:cartitem:productid'] = 'The course on sale.';
$string['privacy:metadata:cartitem:userid'] = 'The user.';
$string['privacy:metadata:order'] = 'Orders placed in the shop.';
$string['privacy:metadata:order:couponcode'] = 'The discount code used.';
$string['privacy:metadata:order:gateway'] = 'The payment gateway used.';
$string['privacy:metadata:order:status'] = 'Whether the order is pending, paid, cancelled or refunded.';
$string['privacy:metadata:order:timepaid'] = 'When the order was paid.';
$string['privacy:metadata:order:total'] = 'The amount of the order.';
$string['privacy:metadata:order:userid'] = 'The buyer.';
$string['privacy:metadata:orderitem'] = 'Lines of the orders.';
$string['privacy:metadata:orderitem:name'] = 'The course bought.';
$string['privacy:metadata:orderitem:total'] = 'The amount of the line.';
$string['privacy:metadata:payment'] = 'Payments are taken by the payment subsystem of Moodle.';
$string['privacy:metadata:timecreated'] = 'When the record was created.';
$string['product_access'] = 'Access as soon as the payment is confirmed';
$string['product_buy'] = 'Enrolment';
$string['product_cohortprices'] = 'Prices for groups';
$string['product_cohortprices_desc'] = 'A price for the members of a cohort: workers, members of a professional body, partner organisations. Leave it empty where the general price applies. A buyer who belongs to several cohorts pays the lowest price.';
$string['product_description'] = 'About this course';
$string['product_enabled'] = 'On sale';
$string['product_end'] = 'End';
$string['product_gotocourse'] = 'Go to the course';
$string['product_mode'] = 'Mode';
$string['product_notfound'] = 'This course is not on sale.';
$string['product_notonsale'] = 'Not on sale';
$string['product_onsale'] = 'On sale';
$string['product_price'] = 'General price ({$a})';
$string['product_price_help'] = 'The price for a buyer who has no special price. Whether it includes tax is set in the settings of the shop.';
$string['product_price_short'] = 'Price';
$string['product_saleend'] = 'Sale ends';
$string['product_salestart'] = 'Sale starts';
$string['product_saleuntil'] = 'Enrolment open until {$a}';
$string['product_seats'] = 'Places';
$string['product_seats_help'] = 'How many enrolments can be sold. Leave it empty for no limit.';
$string['product_seats_short'] = 'Places left';
$string['product_sell'] = 'Put on sale';
$string['product_start'] = 'Start';
$string['product_taxrate'] = 'Tax rate (%)';
$string['product_taxrate_help'] = 'Leave it empty to use the tax rate of the shop. Enter 0 for a course that is exempt.';
$string['products'] = 'Courses on sale';
$string['products_intro'] = 'The courses of the category "{$a}" and its subcategories can be put on sale.';
$string['products_nocategory'] = 'The category of the catalogue does not exist.';
$string['products_none'] = 'There are no courses in this category yet.';
$string['products_notaxrate'] = 'The shop has no tax rate set: courses are sold without tax until one is set.';
$string['pspvshop:managecoupons'] = 'Manage discount codes';
$string['pspvshop:manageorders'] = 'See, cancel and refund orders';
$string['pspvshop:manageproducts'] = 'Put courses on sale and set prices';
$string['pspvshop:simulatepayment'] = 'Simulate payments in test mode';
$string['seats_left'] = 'Places left: {$a}';
$string['seats_none'] = 'No places left';
$string['setting_category'] = 'Catalogue category';
$string['setting_category_desc'] = 'ID number of the course category whose courses can be sold, with its subcategories.';
$string['setting_currency'] = 'Currency';
$string['setting_currency_desc'] = 'Three-letter ISO code. It has to be a currency the payment gateway accepts.';
$string['setting_enrolrole'] = 'Role of the buyers';
$string['setting_enrolrole_default'] = 'Student (default)';
$string['setting_enrolrole_desc'] = 'Role a buyer gets in the courses they buy.';
$string['setting_paymentaccount'] = 'Payment account';
$string['setting_paymentaccount_desc'] = 'The payment account of Moodle (Site administration > Payments) that takes the money, with the gateway it has configured. Without one, orders are saved but cannot be paid online.';
$string['setting_pricesincludetax'] = 'Prices include tax';
$string['setting_pricesincludetax_desc'] = 'If ticked, the price typed in a course is what the buyer pays and the tax is worked out from it. If not, the tax is added to it.';
$string['setting_showtiers'] = 'Show the prices for groups';
$string['setting_showtiers_desc'] = 'Lists on the page of a course the prices it has for each cohort, so buyers know they exist.';
$string['setting_taxrate'] = 'Tax rate (%)';
$string['setting_taxrate_desc'] = 'Tax rate of the courses that do not set their own. TODO: the hospital has to confirm the tax treatment of its training; while this is empty, courses are sold without tax.';
$string['setting_testmode'] = 'Test mode';
$string['setting_testmode_desc'] = 'Shows, at checkout, a button that marks the order as paid without a payment gateway. Only users with the capability to simulate payments see it. Switch it off in production.';
$string['settings'] = 'Shop settings';
$string['start_open'] = 'Already open';
$string['start_upcoming'] = 'Not started yet';
$string['status_cancelled'] = 'Cancelled';
$string['status_paid'] = 'Paid';
$string['status_pending'] = 'Pending payment';
$string['status_refunded'] = 'Refunded';
$string['tax_added'] = 'Plus {$a} % tax';
$string['tax_included'] = '{$a} % tax included';
$string['total'] = 'Total';
$string['total_discount'] = 'Discount';
$string['total_tax'] = 'Tax';
$string['total_taxincluded'] = 'Tax included';
$string['unavailable_closed'] = 'Enrolment for this course has closed.';
$string['unavailable_disabled'] = 'This course is not on sale.';
$string['unavailable_enrolled'] = 'You are already enrolled in this course.';
$string['unavailable_full'] = 'There are no places left in this course.';
$string['unavailable_hidden'] = 'This course is not on sale.';
$string['unavailable_notyet'] = 'Enrolment for this course has not opened yet.';

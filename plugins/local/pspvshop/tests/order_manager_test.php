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

namespace local_pspvshop;

use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the orders: from the cart to the enrolment, and back.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(order_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(notifier::class)]
final class order_manager_test extends shop_testcase {
    public function test_order_copies_the_amounts_of_the_cart(): void {
        global $DB;

        set_config('taxrate', '21', 'local_pspvshop');
        $product = $this->create_product(121, [], ['fullname' => 'Wound care']);
        $DB->insert_record('local_pspvshop_coupon', [
            'code' => 'TEN', 'discounttype' => 'percent', 'amount' => 10, 'enabled' => 1, 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $user = $this->buyer_with_cart([$product]);
        cart::set_coupon_code('TEN');
        $sink = $this->redirectEvents();

        $order = order_manager::create_from_cart();

        $this->assertSame(order_manager::PENDING, $order->status);
        $this->assertEquals($user->id, $order->userid);
        $this->assertSame(['121.00', '12.10', '18.90', '108.90'], [$order->subtotal, $order->discount, $order->tax, $order->total]);
        $this->assertSame('TEN', $order->couponcode);
        $this->assertSame('EUR', $order->currency);
        $items = order_manager::items((int) $order->id);
        $this->assertCount(1, $items);
        $this->assertSame(['Wound care', '121.00', '12.10', '21.00', '108.90'], [
            $items[0]->name, $items[0]->unitprice, $items[0]->discount, $items[0]->taxrate, $items[0]->total,
        ]);
        $this->assertCount(1, array_filter($sink->get_events(), fn($event) => $event instanceof event\order_created));
        $this->assertMatchesRegularExpression('/^\d{4}-\d{5,}$/', order_manager::number($order));

        // Later price changes do not touch the order.
        product::save((object) ['courseid' => $product->courseid, 'enabled' => 1, 'price' => 500]);
        $this->assertSame('108.90', order_manager::get((int) $order->id)->total);
        // Nobody is enrolled until the order is paid.
        $this->assertFalse(is_enrolled(\core\context\course::instance($product->courseid), $user));
    }

    public function test_payment_enrols_the_buyer_once(): void {
        global $DB;

        $first = $this->create_product(100);
        $second = $this->create_product(50);
        $DB->insert_record('local_pspvshop_coupon', [
            'code' => 'TEN', 'discounttype' => 'percent', 'amount' => 10, 'enabled' => 1, 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $user = $this->buyer_with_cart([$first, $second]);
        cart::set_coupon_code('TEN');
        $order = order_manager::create_from_cart();
        $events = $this->redirectEvents();
        $messages = $this->redirectMessages();

        $this->assertTrue(order_manager::mark_paid((int) $order->id, 77, 'testgateway'));

        $paid = order_manager::get((int) $order->id);
        $this->assertSame(order_manager::PAID, $paid->status);
        $this->assertEquals(77, $paid->paymentid);
        $this->assertSame('testgateway', $paid->gateway);
        $this->assertNotEmpty($paid->timepaid);
        foreach ([$first, $second] as $product) {
            $this->assertTrue(is_enrolled(\core\context\course::instance($product->courseid), $user, '', true));
        }
        $this->assertSame(0, cart::count());
        $this->assertEquals(1, $DB->get_field('local_pspvshop_coupon', 'timesused', ['code' => 'TEN']));
        $this->assertCount(1, array_filter($events->get_events(), fn($event) => $event instanceof event\order_paid));

        // The buyer gets one confirmation, with the courses and the amount.
        $sent = $messages->get_messages();
        $this->assertCount(1, $sent);
        $this->assertEquals($user->id, $sent[0]->useridto);
        $this->assertStringContainsString(order_manager::number($paid), $sent[0]->subject);
        $this->assertStringContainsString("135.00\u{00A0}€", $sent[0]->fullmessage);

        // A gateway that notifies twice changes nothing the second time.
        $this->assertFalse(order_manager::mark_paid((int) $order->id, 78, 'other'));
        $this->assertEquals(77, order_manager::get((int) $order->id)->paymentid);
        $this->assertEquals(1, $DB->get_field('local_pspvshop_coupon', 'timesused', ['code' => 'TEN']));
        $this->assertCount(1, $messages->get_messages());
    }

    public function test_an_order_that_costs_nothing_is_delivered_at_once(): void {
        global $DB;

        $product = $this->create_product(40);
        $DB->insert_record('local_pspvshop_coupon', [
            'code' => 'FREE', 'discounttype' => 'percent', 'amount' => 100, 'enabled' => 1, 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $user = $this->buyer_with_cart([$product]);
        cart::set_coupon_code('FREE');

        $order = order_manager::create_from_cart();

        $this->assertSame(order_manager::PAID, $order->status);
        $this->assertSame('0.00', $order->total);
        $this->assertSame('free', $order->gateway);
        $this->assertTrue(is_enrolled(\core\context\course::instance($product->courseid), $user));
    }

    public function test_there_is_one_checkout_at_a_time(): void {
        $product = $this->create_product(40);
        $this->buyer_with_cart([$product]);

        $first = order_manager::create_from_cart();
        $second = order_manager::create_from_cart();

        $this->assertSame(order_manager::CANCELLED, order_manager::get((int) $first->id)->status);
        $this->assertSame(order_manager::PENDING, $second->status);
        // A cancelled order cannot be paid any more.
        $this->assertFalse(order_manager::mark_paid((int) $first->id, null, 'test'));
    }

    public function test_an_empty_cart_makes_no_order(): void {
        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('cart_empty', 'local_pspvshop'));
        order_manager::create_from_cart();
    }

    public function test_refund_unenrols_the_buyer(): void {
        $product = $this->create_product(40);
        $order = $this->paid_order([$product]);
        $user = \core_user::get_user($order->userid);
        $context = \core\context\course::instance($product->courseid);
        $this->assertTrue(is_enrolled($context, $user));
        $events = $this->redirectEvents();

        // Only paid orders are cancelled by a refund, and only unpaid ones by a cancellation.
        $this->assertFalse(order_manager::cancel((int) $order->id));
        $this->assertTrue(order_manager::refund((int) $order->id));

        $this->assertSame(order_manager::REFUNDED, order_manager::get((int) $order->id)->status);
        $this->assertFalse(is_enrolled($context, $user));
        $this->assertCount(1, array_filter($events->get_events(), fn($event) => $event instanceof event\order_refunded));
        $this->assertFalse(order_manager::refund((int) $order->id));
        // The place is for sale again.
        $this->assertSame(product::AVAILABLE, product::availability($product, (int) $user->id));
    }
}

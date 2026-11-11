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

namespace local_pspvshop\payment;

use local_pspvshop\order_manager;
use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the connection with Moodle's payment subsystem.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(service_provider::class)]
final class service_provider_test extends shop_testcase {
    public function test_payable_is_the_total_of_the_buyers_pending_order(): void {
        set_config('paymentaccount', 5, 'local_pspvshop');
        $product = $this->create_product(75.5);
        $this->buyer_with_cart([$product]);
        $order = order_manager::create_from_cart();

        $payable = service_provider::get_payable('order', (int) $order->id);

        $this->assertSame(75.5, $payable->get_amount());
        $this->assertSame('EUR', $payable->get_currency());
        $this->assertSame(5, $payable->get_account_id());
        $success = service_provider::get_success_url('order', (int) $order->id)->out();
        $this->assertStringContainsString('/local/pspvshop/orders.php', $success);
    }

    public function test_nobody_can_pay_somebody_elses_order_or_one_already_paid(): void {
        $product = $this->create_product(75);
        $this->buyer_with_cart([$product]);
        $order = order_manager::create_from_cart();

        $this->setUser($this->getDataGenerator()->create_user());
        try {
            service_provider::get_payable('order', (int) $order->id);
            $this->fail('A user got the payable of an order that is not theirs.');
        } catch (\moodle_exception $e) {
            $this->assertSame('order_notpayable', $e->errorcode);
        }

        $paid = $this->paid_order([$this->create_product(10)]);
        $this->setUser($paid->userid);
        $this->expectException(\moodle_exception::class);
        service_provider::get_payable('order', (int) $paid->id);
    }

    public function test_deliver_order_marks_it_paid_through_the_payment_subsystem(): void {
        $product = $this->create_product(75);
        $user = $this->buyer_with_cart([$product]);
        $order = order_manager::create_from_cart();
        $account = \core_payment\helper::save_payment_account((object) ['name' => 'Test', 'idnumber' => '', 'enabled' => 1]);
        $paymentid = \core_payment\helper::save_payment(
            $account->get('id'),
            'local_pspvshop',
            'order',
            (int) $order->id,
            (int) $user->id,
            75.0,
            'EUR',
            'testgateway',
        );

        // The buyer of the payment has to be the buyer of the order.
        $this->assertFalse(service_provider::deliver_order('order', (int) $order->id, $paymentid, (int) $user->id + 1));
        $this->assertTrue(
            \core_payment\helper::deliver_order('local_pspvshop', 'order', (int) $order->id, $paymentid, (int) $user->id),
        );

        $paid = order_manager::get((int) $order->id);
        $this->assertSame(order_manager::PAID, $paid->status);
        $this->assertSame('testgateway', $paid->gateway);
        $this->assertTrue(is_enrolled(\core\context\course::instance($product->courseid), $user));
        // A second notification of the same payment still reports success.
        $this->assertTrue(service_provider::deliver_order('order', (int) $order->id, $paymentid, (int) $user->id));
    }
}

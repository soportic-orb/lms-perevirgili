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

use local_pspvshop\cart;
use local_pspvshop\order_manager;
use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the billing step of the checkout and of the links added to an order.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(billing::class)]
final class checkout_test extends shop_testcase {
    public function test_checkout_asks_for_billing_details_once(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/pspvinvoice/lib.php');

        $user = $this->buyer_with_cart([$this->create_product(40)]);
        $order = order_manager::create_from_cart();
        $return = new \core\url('/local/pspvshop/checkout.php', ['order' => $order->id]);

        $step = local_pspvinvoice_pspvshop_checkout_step($order, $return);
        $this->assertStringContainsString('/local/pspvinvoice/billing.php?order=' . $order->id, $step->out(false));

        // The form starts from the profile.
        $defaults = billing::defaults($user);
        $this->assertSame(fullname($user), $defaults['name']);
        $this->assertSame($user->email, $defaults['email']);

        billing::save((int) $order->id, (int) $user->id, (object) [
            'customertype' => 'person', 'name' => 'Bea Buyer', 'taxid' => '12345678 z', 'address' => 'Street 1',
            'postcode' => '08001', 'city' => 'Barcelona', 'country' => 'ES', 'email' => '',
        ]);
        $this->assertNull(local_pspvinvoice_pspvshop_checkout_step($order, $return));
        $this->assertSame('12345678Z', billing::for_order((int) $order->id)->taxid);

        // The next order starts from what was given the last time.
        $this->assertSame('Bea Buyer', billing::defaults($user)['name']);

        // No billing step when invoices are switched off.
        cart::add((int) $this->create_product(40)->id);
        $second = order_manager::create_from_cart();
        set_config('enabled', 0, 'local_pspvinvoice');
        $this->assertNull(local_pspvinvoice_pspvshop_checkout_step($second, $return));
    }

    public function test_order_links_point_to_the_invoice_and_its_credit_note(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/pspvinvoice/lib.php');

        $this->buyer_with_cart([$this->create_product(40)]);
        $order = order_manager::create_from_cart();
        $this->assertSame([], local_pspvinvoice_pspvshop_order_links($order));

        // Paying the order issues the invoice.
        order_manager::mark_paid((int) $order->id, null, 'test');
        $invoice = invoice_manager::invoice_for_order((int) $order->id);
        $links = local_pspvinvoice_pspvshop_order_links($order);
        $this->assertCount(1, $links);
        $this->assertStringContainsString($invoice->fullnumber, $links[0]['text']);
        $this->assertStringContainsString('/local/pspvinvoice/invoice.php?id=' . $invoice->id, $links[0]['url']);

        order_manager::refund((int) $order->id);
        invoice_manager::credit_for_order((int) $order->id);
        $this->assertCount(2, local_pspvinvoice_pspvshop_order_links($order));
    }
}

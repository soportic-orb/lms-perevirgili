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
 * Tests of the issuing of invoices and credit notes.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(invoice_manager::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observer::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(pdf::class)]
final class invoice_manager_test extends shop_testcase {
    /**
     * Billing details of a company.
     *
     * @return \stdClass
     */
    protected function company(): \stdClass {
        return (object) [
            'customertype' => 'company', 'name' => ' Residència Exemple SL ', 'taxid' => 'b-1234.5678',
            'address' => 'Carrer Major, 1',
            'postcode' => '08001', 'city' => 'Barcelona', 'country' => 'es', 'email' => 'admin@example.com',
        ];
    }

    public function test_paying_an_order_issues_its_invoice(): void {
        set_config('taxrate', '21', 'local_pspvshop');
        set_config('sellername', 'Hospital Exemple', 'local_pspvinvoice');
        set_config('sellertaxid', 'Q0000000A', 'local_pspvinvoice');
        $product = $this->create_product(121, [], ['fullname' => 'Wound care']);
        $user = $this->buyer_with_cart([$product]);
        $order = order_manager::create_from_cart();
        billing::save((int) $order->id, (int) $user->id, $this->company());
        $events = $this->redirectEvents();
        $messages = $this->redirectMessages();

        order_manager::mark_paid((int) $order->id, null, 'test');
        // The events were redirected, so the observer is called as the event would call it.
        $paid = array_values(array_filter(
            $events->get_events(),
            fn($event) => $event instanceof \local_pspvshop\event\order_paid
        ));
        $events->close();
        observer::order_paid($paid[0]);

        $invoice = invoice_manager::invoice_for_order((int) $order->id);
        $this->assertSame(invoice_manager::INVOICE, $invoice->doctype);
        $this->assertMatchesRegularExpression('/^F-\d{4}-0001$/', $invoice->fullnumber);
        $this->assertSame(['100.00', '21.00', '121.00'], [$invoice->subtotal, $invoice->tax, $invoice->total]);
        $this->assertSame('EUR', $invoice->currency);
        $this->assertEquals($user->id, $invoice->userid);
        $this->assertSame('none', $invoice->fiscalstatus);

        $buyer = json_decode($invoice->billing, true);
        $this->assertSame(['company', 'Residència Exemple SL', 'B12345678', 'ES'], [
            $buyer['customertype'], $buyer['name'], $buyer['taxid'], $buyer['country'],
        ]);
        $this->assertSame('Hospital Exemple', json_decode($invoice->seller, true)['name']);
        $lines = json_decode($invoice->doclines, true);
        $this->assertSame(['Wound care', 10000, 2100, 12100], [
            $lines[0]['description'], $lines[0]['net'], $lines[0]['tax'], $lines[0]['total'],
        ]);

        // The buyer is told, with a link to the PDF.
        $invoicemessages = array_values(array_filter(
            $messages->get_messages(),
            fn($message) => $message->component === 'local_pspvinvoice'
        ));
        $this->assertCount(1, $invoicemessages);
        $this->assertStringContainsString($invoice->fullnumber, $invoicemessages[0]->subject);

        // Issuing again returns the same invoice.
        $this->assertEquals($invoice->id, invoice_manager::issue_for_order((int) $order->id)->id);

        // An issued invoice does not change when the settings or the billing details do.
        set_config('sellername', 'Another name', 'local_pspvinvoice');
        billing::save((int) $order->id, (int) $user->id, (object) (['name' => 'Changed'] + (array) $this->company()));
        $again = invoice_manager::invoice_for_order((int) $order->id);
        $this->assertSame('Hospital Exemple', json_decode($again->seller, true)['name']);
        $this->assertSame('Residència Exemple SL', json_decode($again->billing, true)['name']);
    }

    public function test_numbers_are_correlative_within_each_series(): void {
        $numbers = [];
        foreach ([1, 2, 3] as $i) {
            $order = $this->paid_order([$this->create_product(10 * $i)]);
            $numbers[] = invoice_manager::issue_for_order((int) $order->id)->docnumber;
            $last = $order;
        }
        $this->assertEquals([1, 2, 3], $numbers);

        // Credit notes have their own series, which starts at 1.
        order_manager::refund((int) $last->id);
        $credit = invoice_manager::credit_for_order((int) $last->id);
        $this->assertMatchesRegularExpression('/^R-\d{4}-0001$/', $credit->fullnumber);

        // A new series starts a new numbering; the old one carries on from where it was.
        set_config('invoiceseries', 'web 2', 'local_pspvinvoice');
        $order = $this->paid_order([$this->create_product(10)]);
        $web = invoice_manager::issue_for_order((int) $order->id);
        $this->assertMatchesRegularExpression('/^WEB2-\d{4}-0001$/', $web->fullnumber);
        set_config('invoiceseries', 'F', 'local_pspvinvoice');
        $order = $this->paid_order([$this->create_product(10)]);
        $this->assertEquals(4, invoice_manager::issue_for_order((int) $order->id)->docnumber);
    }

    public function test_refund_cancels_the_invoice_with_a_credit_note(): void {
        set_config('taxrate', '10', 'local_pspvshop');
        $order = $this->paid_order([$this->create_product(110, [], ['fullname' => 'Falls'])]);
        $invoice = invoice_manager::issue_for_order((int) $order->id);

        // Nothing to cancel while the order is not refunded.
        $this->assertNull(invoice_manager::credit_for_order((int) $order->id));

        order_manager::refund((int) $order->id);
        $credit = invoice_manager::credit_for_order((int) $order->id);

        $this->assertSame(invoice_manager::CREDIT, $credit->doctype);
        $this->assertEquals($invoice->id, $credit->refinvoiceid);
        $this->assertSame(['-100.00', '-10.00', '-110.00'], [$credit->subtotal, $credit->tax, $credit->total]);
        $this->assertSame($invoice->billing, $credit->billing);
        $this->assertSame(-11000, json_decode($credit->doclines, true)[0]['total']);
        // The invoice itself is untouched, and there is only ever one credit note for it.
        $this->assertSame('110.00', invoice_manager::invoice_for_order((int) $order->id)->total);
        $this->assertEquals($credit->id, invoice_manager::credit_for_order((int) $order->id)->id);
    }

    public function test_no_invoice_for_free_or_unpaid_orders_or_when_switched_off(): void {
        global $DB;

        // Unpaid.
        $this->buyer_with_cart([$this->create_product(10)]);
        $pending = order_manager::create_from_cart();
        $this->assertNull(invoice_manager::issue_for_order((int) $pending->id));

        // Free.
        $DB->insert_record('local_pspvshop_coupon', [
            'code' => 'FREE', 'discounttype' => 'percent', 'amount' => 100, 'enabled' => 1, 'timecreated' => 1, 'timemodified' => 1,
        ]);
        $this->buyer_with_cart([$this->create_product(10)]);
        cart::set_coupon_code('FREE');
        $free = order_manager::create_from_cart();
        $this->assertSame(order_manager::PAID, $free->status);
        $this->assertNull(invoice_manager::issue_for_order((int) $free->id));

        // Switched off.
        set_config('enabled', 0, 'local_pspvinvoice');
        $paid = $this->paid_order([$this->create_product(10)]);
        $this->assertNull(invoice_manager::issue_for_order((int) $paid->id));
        $this->assertSame(0, $DB->count_records('local_pspvinvoice_invoice'));
    }

    public function test_invoice_without_billing_details_uses_the_profile(): void {
        $order = $this->paid_order([$this->create_product(10)]);
        $user = \core_user::get_user($order->userid);

        $invoice = invoice_manager::issue_for_order((int) $order->id);

        $buyer = json_decode($invoice->billing, true);
        $this->assertSame(fullname($user), $buyer['name']);
        $this->assertSame('', $buyer['taxid']);
    }

    public function test_pdf_is_drawn_from_the_stored_copy(): void {
        set_config('sellername', 'Hospital Exemple', 'local_pspvinvoice');
        set_config('footernote', 'Legal note of the hospital', 'local_pspvinvoice');
        $product = $this->create_product(75, [], ['fullname' => 'Wound care & "more"']);
        $user = $this->buyer_with_cart([$product]);
        $order = order_manager::create_from_cart();
        billing::save((int) $order->id, (int) $user->id, $this->company());
        order_manager::mark_paid((int) $order->id, null, 'test');
        $invoice = invoice_manager::issue_for_order((int) $order->id);

        $html = pdf::html($invoice);
        $this->assertStringContainsString($invoice->fullnumber, $html);
        $this->assertStringContainsString('Hospital Exemple', $html);
        $this->assertStringContainsString('Residència Exemple SL', $html);
        $this->assertStringContainsString('B12345678', $html);
        $this->assertStringContainsString('Legal note of the hospital', $html);
        // Names typed by people are escaped.
        $this->assertStringContainsString('Wound care &amp; &quot;more&quot;', $html);

        $this->assertStringStartsWith('%PDF-', pdf::render($invoice));

        order_manager::refund((int) $order->id);
        $credit = invoice_manager::credit_for_order((int) $order->id);
        $corrects = get_string('doc_corrects', 'local_pspvinvoice', $invoice->fullnumber);
        $this->assertStringContainsString($corrects, pdf::html($credit));
    }
}

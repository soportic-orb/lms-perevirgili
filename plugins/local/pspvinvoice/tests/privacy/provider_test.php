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

namespace local_pspvinvoice\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_pspvinvoice\billing;
use local_pspvinvoice\invoice_manager;
use local_pspvshop\cart;
use local_pspvshop\order_manager;
use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the privacy provider.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends shop_testcase {
    public function test_invoices_are_exported_and_kept(): void {
        global $DB;

        $system = \core\context\system::instance();
        $details = (object) [
            'customertype' => 'person', 'name' => 'Bea Buyer', 'taxid' => '12345678Z', 'address' => 'Street 1',
            'postcode' => '08001', 'city' => 'Barcelona', 'country' => 'ES', 'email' => '',
        ];
        // One invoiced order, and one abandoned at the billing step.
        $user = $this->buyer_with_cart([$this->create_product(40)]);
        $invoiced = order_manager::create_from_cart();
        billing::save((int) $invoiced->id, (int) $user->id, $details);
        order_manager::mark_paid((int) $invoiced->id, null, 'test');
        $invoice = invoice_manager::issue_for_order((int) $invoiced->id);
        cart::add((int) $this->create_product(30)->id);
        $abandoned = order_manager::create_from_cart();
        billing::save((int) $abandoned->id, (int) $user->id, $details);

        $this->assertSame([$system->id], array_map('intval', provider::get_contexts_for_userid((int) $user->id)->get_contextids()));
        $userlist = new \core_privacy\local\request\userlist($system, 'local_pspvinvoice');
        provider::get_users_in_context($userlist);
        $this->assertEquals([$user->id], $userlist->get_userids());

        $approved = new approved_contextlist($user, 'local_pspvinvoice', [$system->id]);
        provider::export_user_data($approved);
        $data = writer::with_context($system)->get_data([get_string('pluginname', 'local_pspvinvoice')]);
        $this->assertSame($invoice->fullnumber, $data->documents[0]->number);
        $this->assertCount(2, $data->billingdetails);

        provider::delete_data_for_user($approved);

        // What is part of an issued invoice stays; the abandoned details go.
        $this->assertTrue($DB->record_exists('local_pspvinvoice_invoice', ['id' => $invoice->id]));
        $this->assertTrue($DB->record_exists('local_pspvinvoice_billing', ['orderid' => $invoiced->id]));
        $this->assertFalse($DB->record_exists('local_pspvinvoice_billing', ['orderid' => $abandoned->id]));
    }
}

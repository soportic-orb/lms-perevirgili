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

namespace local_pspvshop\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\writer;
use local_pspvshop\cart;
use local_pspvshop\order_manager;
use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the privacy provider.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends shop_testcase {
    public function test_export_and_delete_keep_the_record_of_sales(): void {
        global $DB;

        $system = \core\context\system::instance();
        $paid = $this->paid_order([$this->create_product(40, [], ['fullname' => 'Paid course'])]);
        $userid = (int) $paid->userid;
        // The same buyer leaves another order unpaid and something in the cart.
        cart::add((int) $this->create_product(30, [], ['fullname' => 'Unpaid course'])->id);
        $pending = order_manager::create_from_cart();
        $other = $this->paid_order([$this->create_product(10)]);

        $this->assertSame([$system->id], array_map('intval', provider::get_contexts_for_userid($userid)->get_contextids()));
        $this->assertCount(0, provider::get_contexts_for_userid((int) $this->getDataGenerator()->create_user()->id));
        $userlist = new \core_privacy\local\request\userlist($system, 'local_pspvshop');
        provider::get_users_in_context($userlist);
        $this->assertEqualsCanonicalizing([$userid, (int) $other->userid], $userlist->get_userids());

        provider::export_user_data(new approved_contextlist(\core_user::get_user($userid), 'local_pspvshop', [$system->id]));
        $writer = writer::with_context($system);
        $path = [get_string('pluginname', 'local_pspvshop')];
        $orders = $writer->get_data(array_merge($path, [get_string('orders', 'local_pspvshop')]))->orders;
        $this->assertCount(2, $orders);
        $this->assertSame('Paid course', $orders[0]->lines[0]->name);
        $cart = $writer->get_data(array_merge($path, [get_string('cart', 'local_pspvshop')]));
        $this->assertSame(['Unpaid course'], $cart->courses);

        provider::delete_data_for_user(new approved_contextlist(\core_user::get_user($userid), 'local_pspvshop', [$system->id]));

        $this->assertSame(0, $DB->count_records('local_pspvshop_cartitem', ['userid' => $userid]));
        $this->assertFalse($DB->record_exists('local_pspvshop_order', ['id' => $pending->id]));
        $this->assertSame(0, $DB->count_records('local_pspvshop_orderitem', ['orderid' => $pending->id]));
        // The sale stays on record.
        $this->assertTrue($DB->record_exists('local_pspvshop_order', ['id' => $paid->id]));
        $this->assertSame(1, $DB->count_records('local_pspvshop_orderitem', ['orderid' => $paid->id]));
        $this->assertTrue($DB->record_exists('local_pspvshop_order', ['id' => $other->id]));
    }
}

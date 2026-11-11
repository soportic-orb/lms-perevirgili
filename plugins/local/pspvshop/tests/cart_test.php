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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');

use local_pspvshop\tests\shop_testcase;

/**
 * Tests of the shopping cart.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(cart::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(observer::class)]
final class cart_test extends shop_testcase {
    public function test_a_visitors_cart_follows_them_when_they_log_in(): void {
        global $DB, $SESSION;

        $first = $this->create_product(100);
        $second = $this->create_product(50);
        $user = $this->getDataGenerator()->create_user();

        // Not logged in: the cart lives in the session.
        cart::add((int) $first->id);
        cart::add((int) $first->id);
        $this->assertSame([(int) $first->id], cart::product_ids());
        $this->assertSame(0, $DB->count_records('local_pspvshop_cartitem'));

        // The user already had something in their cart from another device.
        $DB->insert_record('local_pspvshop_cartitem', ['userid' => $user->id, 'productid' => $second->id, 'timecreated' => 1]);

        // Logging in keeps the session, which the test framework replaces when it switches user.
        $visitorcart = $SESSION->local_pspvshop_cart;
        $this->setUser($user);
        $SESSION->local_pspvshop_cart = $visitorcart;
        \core\event\user_loggedin::create([
            'userid' => $user->id, 'objectid' => $user->id, 'other' => ['username' => $user->username],
        ])->trigger();

        $this->assertEqualsCanonicalizing([(int) $first->id, (int) $second->id], cart::product_ids());
        $this->assertSame(2, cart::count());

        cart::remove((int) $second->id);
        $this->assertSame([(int) $first->id], cart::product_ids());
        cart::clear();
        $this->assertSame(0, cart::count());
    }

    public function test_only_products_on_sale_can_be_added(): void {
        $closed = $this->create_product(100, ['saleend' => time() - DAYSECS]);
        try {
            cart::add((int) $closed->id);
            $this->fail('A product whose sale has closed was added to the cart.');
        } catch (\moodle_exception $e) {
            $this->assertSame('unavailable_closed', $e->errorcode);
        }
        $this->expectException(\moodle_exception::class);
        cart::add(999999);
    }

    public function test_summary_applies_prices_discounts_and_tax(): void {
        global $DB;

        set_config('taxrate', '21', 'local_pspvshop');
        $cohort = $this->getDataGenerator()->create_cohort();
        $first = $this->create_product(121, [], ['fullname' => 'First course'], [$cohort->id => 100]);
        $second = $this->create_product(60.5, ['taxrate' => '0'], ['fullname' => 'Second course']);
        $user = $this->buyer_with_cart([$first, $second]);

        $summary = cart::summary();
        $this->assertSame(['First course', 'Second course'], array_column($summary['lines'], 'name'));
        $this->assertSame(18150, $summary['subtotal']);
        $this->assertSame(0, $summary['discount']);
        $this->assertSame(2100, $summary['tax']);
        $this->assertSame(18150, $summary['total']);
        $this->assertNull($summary['coupon']);
        $this->assertFalse($summary['couponinvalid']);

        // The price of the buyer's cohort, and a code that takes 10 % off.
        cohort_add_member($cohort->id, $user->id);
        $DB->insert_record('local_pspvshop_coupon', [
            'code' => 'TEN', 'discounttype' => 'percent', 'amount' => 10, 'enabled' => 1, 'timecreated' => 1, 'timemodified' => 1,
        ]);
        cart::set_coupon_code(' ten ');
        $summary = cart::summary();
        $this->assertSame(16050, $summary['subtotal']);
        $this->assertSame(1605, $summary['discount']);
        $this->assertSame(14445, $summary['total']);
        // Tax is worked out on what is actually paid for the taxed course: 90.00 with 21 % included.
        $this->assertSame(1562, $summary['tax']);
        $this->assertSame('TEN', $summary['coupon']->code);

        cart::set_coupon_code('wrong');
        $summary = cart::summary();
        $this->assertTrue($summary['couponinvalid']);
        $this->assertSame(0, $summary['discount']);
    }

    public function test_products_that_can_no_longer_be_bought_leave_the_cart(): void {
        $kept = $this->create_product(100, [], ['fullname' => 'Kept']);
        $gone = $this->create_product(50, [], ['fullname' => 'Gone']);
        $this->buyer_with_cart([$kept, $gone]);
        product::save((object) ['courseid' => $gone->courseid, 'enabled' => 0, 'price' => 50]);

        $summary = cart::summary();

        $this->assertSame(['Kept'], array_column($summary['lines'], 'name'));
        $this->assertSame(['Gone'], $summary['removed']);
        $this->assertSame([(int) $kept->id], cart::product_ids());
    }
}

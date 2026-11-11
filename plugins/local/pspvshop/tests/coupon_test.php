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

/**
 * Tests of the discount codes.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(coupon::class)]
final class coupon_test extends \advanced_testcase {
    /**
     * Creates a discount code.
     *
     * @param array $fields
     * @return \stdClass
     */
    protected function create_coupon(array $fields): \stdClass {
        global $DB;

        $id = $DB->insert_record('local_pspvshop_coupon', $fields + [
            'code' => 'TEST', 'discounttype' => 'percent', 'amount' => 10, 'enabled' => 1, 'timecreated' => 1, 'timemodified' => 1,
        ]);
        return $DB->get_record('local_pspvshop_coupon', ['id' => $id]);
    }

    public function test_find_valid(): void {
        $this->resetAfterTest();
        $now = time();
        $this->create_coupon(['code' => 'OK']);
        $this->create_coupon(['code' => 'OFF', 'enabled' => 0]);
        $this->create_coupon(['code' => 'EARLY', 'validfrom' => $now + DAYSECS]);
        $this->create_coupon(['code' => 'LATE', 'validuntil' => $now - DAYSECS]);
        $this->create_coupon(['code' => 'USEDUP', 'maxuses' => 2, 'timesused' => 2]);
        $this->create_coupon(['code' => 'ONELEFT', 'maxuses' => 2, 'timesused' => 1]);

        $this->assertSame('OK', coupon::find_valid(' ok ')->code);
        $this->assertSame('ONELEFT', coupon::find_valid('oneleft')->code);
        foreach (['OFF', 'EARLY', 'LATE', 'USEDUP', 'NOPE', ''] as $code) {
            $this->assertNull(coupon::find_valid($code), $code);
        }
    }

    public function test_percentage(): void {
        $this->resetAfterTest();
        $coupon = $this->create_coupon(['amount' => 10]);
        $this->assertSame([1 => 1200, 2 => 995], coupon::discounts($coupon, [1 => 12000, 2 => 9950]));
        // Never more than the price.
        $this->assertSame([1 => 12000], coupon::discounts($this->create_coupon(['code' => 'ALL', 'amount' => 150]), [1 => 12000]));
    }

    public function test_fixed_amount_is_shared_and_never_exceeds_the_prices(): void {
        $this->resetAfterTest();
        $coupon = $this->create_coupon(['discounttype' => 'amount', 'amount' => 30]);

        $discounts = coupon::discounts($coupon, [1 => 10000, 2 => 5000]);
        $this->assertSame(3000, array_sum($discounts));
        $this->assertSame([1 => 2000, 2 => 1000], $discounts);

        // Amounts that do not divide evenly still add up to the cent.
        $this->assertSame(3000, array_sum(coupon::discounts($coupon, [1 => 3333, 2 => 3333, 3 => 3334])));
        // A discount larger than the cart makes it free, not negative.
        $this->assertSame([1 => 1000, 2 => 500], coupon::discounts($coupon, [1 => 1000, 2 => 500]));
    }

    public function test_code_for_one_product_only(): void {
        $this->resetAfterTest();
        $coupon = $this->create_coupon(['amount' => 50, 'productid' => 2]);
        $this->assertSame([1 => 0, 2 => 2500], coupon::discounts($coupon, [1 => 10000, 2 => 5000]));
        $this->assertSame([1 => 0], coupon::discounts($coupon, [1 => 10000]));
    }
}

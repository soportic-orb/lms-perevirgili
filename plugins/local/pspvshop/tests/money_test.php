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
 * Tests of the money helpers.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(money::class)]
final class money_test extends \advanced_testcase {
    public function test_amounts_are_kept_in_cents(): void {
        $this->assertSame(12000, money::cents('120.00'));
        $this->assertSame(1999, money::cents(19.99));
        $this->assertSame(0, money::cents(null));
        // The classic floating point trap: 0.1 + 0.2.
        $this->assertSame(30, money::cents(0.1 + 0.2));
        $this->assertSame('19.99', money::decimal(1999));
        $this->assertSame('0.05', money::decimal(5));
    }

    public function test_format(): void {
        $this->assertSame("180.00\u{00A0}€", money::format(18000, 'EUR'));
        $this->assertSame("9.50\u{00A0}CHF", money::format(950, 'CHF'));
    }

    public function test_currency_falls_back_to_euros(): void {
        $this->resetAfterTest();
        $this->assertSame('EUR', money::currency());
        set_config('currency', 'usd', 'local_pspvshop');
        $this->assertSame('USD', money::currency());
        set_config('currency', 'not a currency', 'local_pspvshop');
        $this->assertSame('EUR', money::currency());
    }

    public function test_split_with_tax_included_or_added(): void {
        $this->resetAfterTest();
        // No tax: nothing to split.
        $this->assertSame([12100, 0, 12100], money::split(12100, 0));

        // Prices include tax by default: the buyer pays what the product says.
        $this->assertSame([10000, 2100, 12100], money::split(12100, 21));
        // Net and tax always add up to the price, whatever the rounding.
        [$net, $tax, $total] = money::split(9999, 21);
        $this->assertSame(9999, $net + $tax);
        $this->assertSame(9999, $total);

        set_config('pricesincludetax', 0, 'local_pspvshop');
        $this->assertSame([10000, 2100, 12100], money::split(10000, 21));
    }
}

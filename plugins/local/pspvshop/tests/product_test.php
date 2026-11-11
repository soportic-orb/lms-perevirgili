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
 * Tests of products, their prices and their availability.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(product::class)]
final class product_test extends shop_testcase {
    public function test_save_creates_and_updates(): void {
        $product = $this->create_product(120, ['mode' => 'online', 'seats' => 10, 'taxrate' => '']);
        $this->assertSame('120.00', $product->price);
        $this->assertSame('online', $product->mode);
        $this->assertEquals(10, $product->seats);
        $this->assertNull($product->taxrate);

        $updated = product::save((object) [
            'courseid' => $product->courseid, 'enabled' => 0, 'price' => '95.5', 'mode' => 'nonsense', 'seats' => '',
            'taxrate' => '21',
        ]);
        $this->assertSame($product->id, $updated->id);
        $this->assertSame('95.50', $updated->price);
        $this->assertNull($updated->mode);
        $this->assertNull($updated->seats);
        $this->assertSame('21.00', $updated->taxrate);
        $this->assertEquals(0, $updated->enabled);
    }

    public function test_buyers_pay_the_lowest_price_of_their_cohorts(): void {
        $generator = $this->getDataGenerator();
        $workers = $generator->create_cohort();
        $partners = $generator->create_cohort();
        $product = $this->create_product(180, [], [], [$workers->id => 150, $partners->id => 160]);
        $worker = $generator->create_user();
        $both = $generator->create_user();
        $external = $generator->create_user();
        cohort_add_member($workers->id, $worker->id);
        cohort_add_member($workers->id, $both->id);
        cohort_add_member($partners->id, $both->id);

        $this->assertSame(18000, product::price_for($product, null));
        $this->assertSame(18000, product::price_for($product, (int) $external->id));
        $this->assertSame(15000, product::price_for($product, (int) $worker->id));
        $this->assertSame(15000, product::price_for($product, (int) $both->id));

        // An empty price removes the special price.
        product::save((object) ['courseid' => $product->courseid, 'enabled' => 1, 'price' => 180], [$workers->id => '']);
        $this->assertSame(16000, product::price_for($product, (int) $both->id));
        $this->assertSame(18000, product::price_for($product, (int) $worker->id));
    }

    public function test_tax_rate_of_the_product_or_of_the_shop(): void {
        $default = $this->create_product(100);
        $own = $this->create_product(100, ['taxrate' => '10']);
        $exempt = $this->create_product(100, ['taxrate' => '0']);

        // Nothing is assumed while the shop has no tax rate.
        $this->assertSame(0.0, product::tax_rate($default));
        set_config('taxrate', '21', 'local_pspvshop');
        $this->assertSame(21.0, product::tax_rate($default));
        $this->assertSame(10.0, product::tax_rate($own));
        $this->assertSame(0.0, product::tax_rate($exempt));
    }

    public function test_availability(): void {
        $now = time();
        $this->assertSame(product::AVAILABLE, product::availability($this->create_product(10)));
        $this->assertSame('disabled', product::availability($this->create_product(10, ['enabled' => 0])));
        $this->assertSame('hidden', product::availability($this->create_product(10, [], ['visible' => 0])));
        $this->assertSame('notyet', product::availability($this->create_product(10, ['salestart' => $now + DAYSECS])));
        $this->assertSame('closed', product::availability($this->create_product(10, ['saleend' => $now - DAYSECS])));

        $product = $this->create_product(10);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $product->courseid);
        $this->assertSame('enrolled', product::availability($product, (int) $student->id));
        $this->assertSame(product::AVAILABLE, product::availability($product, null));
    }

    public function test_seats_are_counted_from_paid_orders(): void {
        $product = $this->create_product(10, ['seats' => 2]);
        $this->assertSame(2, product::seats_left($product));
        $this->assertNull(product::seats_left($this->create_product(10)));

        $this->paid_order([$product]);
        $this->assertSame(1, product::seats_left($product));

        // An unpaid order holds no place.
        $this->buyer_with_cart([$product]);
        order_manager::create_from_cart();
        $this->assertSame(1, product::seats_left($product));

        $this->paid_order([$product]);
        $this->assertSame(0, product::seats_left($product));
        $this->assertSame('full', product::availability($product));
    }

    public function test_deleting_a_course_takes_it_off_sale(): void {
        global $DB;

        $product = $this->create_product(10);
        $this->buyer_with_cart([$product]);
        delete_course($product->courseid, false);

        $this->assertNull(product::get((int) $product->id));
        $this->assertSame(0, $DB->count_records('local_pspvshop_cartitem'));
    }
}

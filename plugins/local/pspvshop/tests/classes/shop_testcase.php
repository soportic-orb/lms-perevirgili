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

namespace local_pspvshop\tests;

use local_pspvshop\cart;
use local_pspvshop\order_manager;
use local_pspvshop\product;

/**
 * Base class with helpers shared by the tests of the shop and of the plugins built on it.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class shop_testcase extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        // Purchases enrol through the manual method and send a confirmation.
        $this->preventResetByRollback();
        $this->redirectMessages();
    }

    /**
     * Creates a course and puts it on sale.
     *
     * @param float $price General price.
     * @param array $product Other fields of the product.
     * @param array $course Fields of the course.
     * @param array $cohortprices Cohort id => price.
     * @return \stdClass The product.
     */
    protected function create_product(
        float $price,
        array $product = [],
        array $course = [],
        array $cohortprices = [],
    ): \stdClass {
        $course = $this->getDataGenerator()->create_course($course);
        return product::save((object) ($product + ['courseid' => $course->id, 'enabled' => 1, 'price' => $price]), $cohortprices);
    }

    /**
     * Logs a new user in and puts products in their cart.
     *
     * @param \stdClass[] $products
     * @return \stdClass The user.
     */
    protected function buyer_with_cart(array $products): \stdClass {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        foreach ($products as $product) {
            cart::add((int) $product->id);
        }
        return $user;
    }

    /**
     * A paid order of a new user for the given products.
     *
     * @param \stdClass[] $products
     * @return \stdClass The order.
     */
    protected function paid_order(array $products): \stdClass {
        $this->buyer_with_cart($products);
        $order = order_manager::create_from_cart();
        order_manager::mark_paid((int) $order->id, null, 'test');
        return order_manager::get((int) $order->id);
    }
}

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

/**
 * Data generator of the shop, for tests.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Data generator of the shop.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_pspvshop_generator extends component_generator_base {
    /**
     * Puts a course on sale.
     *
     * @param array|stdClass $data courseid and price; optionally enabled, taxrate, mode, seats, salestart and saleend.
     * @return stdClass The product.
     */
    public function create_product($data): stdClass {
        $data = (object) ((array) $data + ['enabled' => 1]);
        return \local_pspvshop\product::save($data);
    }

    /**
     * Creates a discount code.
     *
     * @param array|stdClass $data code and amount; optionally discounttype, productid, validfrom, validuntil, maxuses and enabled.
     * @return stdClass The code.
     */
    public function create_coupon($data): stdClass {
        global $DB;

        $record = (array) $data + ['discounttype' => 'percent', 'enabled' => 1, 'timecreated' => time(), 'timemodified' => time()];
        $record['code'] = \local_pspvshop\coupon::normalise($record['code']);
        $id = $DB->insert_record('local_pspvshop_coupon', $record);
        return $DB->get_record('local_pspvshop_coupon', ['id' => $id]);
    }
}

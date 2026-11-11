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
 * Products: the courses offered for sale, their prices and their availability.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class product {
    /** @var string[] Delivery modes. */
    public const MODES = ['online', 'blended', 'onsite'];

    /** @var string The product can be bought. */
    public const AVAILABLE = 'available';

    /**
     * A product by id.
     *
     * @param int $id
     * @return \stdClass|null
     */
    public static function get(int $id): ?\stdClass {
        global $DB;

        return $DB->get_record('local_pspvshop_product', ['id' => $id]) ?: null;
    }

    /**
     * The product of a course.
     *
     * @param int $courseid
     * @return \stdClass|null Null when the course is not for sale.
     */
    public static function for_course(int $courseid): ?\stdClass {
        global $DB;

        return $DB->get_record('local_pspvshop_product', ['courseid' => $courseid]) ?: null;
    }

    /**
     * Creates or updates the product of a course.
     *
     * @param \stdClass $data courseid, enabled, price (decimal), taxrate (decimal or ''), mode, seats (int or ''),
     *                        salestart and saleend.
     * @param array $cohortprices Cohort id => price (decimal or '' to remove it).
     * @return \stdClass The product.
     */
    public static function save(\stdClass $data, array $cohortprices = []): \stdClass {
        global $DB;

        $now = time();
        $record = [
            'courseid' => (int) $data->courseid,
            'enabled' => empty($data->enabled) ? 0 : 1,
            'price' => money::decimal(max(0, money::cents($data->price ?? 0))),
            'taxrate' => self::blank($data->taxrate ?? '') ? null : number_format((float) $data->taxrate, 2, '.', ''),
            'mode' => in_array($data->mode ?? '', self::MODES, true) ? $data->mode : null,
            'seats' => self::blank($data->seats ?? '') ? null : max(0, (int) $data->seats),
            'salestart' => (int) ($data->salestart ?? 0),
            'saleend' => (int) ($data->saleend ?? 0),
            'timemodified' => $now,
        ];

        $transaction = $DB->start_delegated_transaction();
        $existing = self::for_course($record['courseid']);
        if ($existing) {
            $record['id'] = $existing->id;
            $DB->update_record('local_pspvshop_product', $record);
        } else {
            $record['timecreated'] = $now;
            $record['id'] = $DB->insert_record('local_pspvshop_product', $record);
        }
        foreach ($cohortprices as $cohortid => $price) {
            $conditions = ['productid' => $record['id'], 'cohortid' => (int) $cohortid];
            if (self::blank($price)) {
                $DB->delete_records('local_pspvshop_price', $conditions);
            } else if ($row = $DB->get_record('local_pspvshop_price', $conditions)) {
                $DB->set_field('local_pspvshop_price', 'price', money::decimal(max(0, money::cents($price))), ['id' => $row->id]);
            } else {
                $DB->insert_record('local_pspvshop_price', $conditions + ['price' => money::decimal(max(0, money::cents($price)))]);
            }
        }
        $transaction->allow_commit();

        return self::get($record['id']);
    }

    /**
     * Removes the product of a course, when the course is deleted.
     *
     * Orders keep their lines: they hold their own copy of the name and the amounts.
     *
     * @param int $courseid
     */
    public static function delete_for_course(int $courseid): void {
        global $DB;

        $product = self::for_course($courseid);
        if ($product) {
            $DB->delete_records('local_pspvshop_price', ['productid' => $product->id]);
            $DB->delete_records('local_pspvshop_cartitem', ['productid' => $product->id]);
            $DB->delete_records('local_pspvshop_product', ['id' => $product->id]);
        }
    }

    /**
     * The special prices of a product.
     *
     * @param int $productid
     * @return int[] Cohort id => price in cents.
     */
    public static function cohort_prices(int $productid): array {
        global $DB;

        $prices = [];
        foreach ($DB->get_records('local_pspvshop_price', ['productid' => $productid]) as $row) {
            $prices[(int) $row->cohortid] = money::cents($row->price);
        }
        return $prices;
    }

    /**
     * What a user pays for a product: the general price, or the lowest price of the cohorts they belong to.
     *
     * @param \stdClass $product
     * @param int|null $userid Null, 0 or the guest user for a visitor.
     * @return int Price in cents, as entered in the product.
     */
    public static function price_for(\stdClass $product, ?int $userid): int {
        global $DB;

        $price = money::cents($product->price);
        if ($userid && !isguestuser($userid)) {
            $cohortprices = self::cohort_prices($product->id);
            if ($cohortprices) {
                [$insql, $params] = $DB->get_in_or_equal(array_keys($cohortprices), SQL_PARAMS_NAMED);
                $mine = $DB->get_fieldset_select('cohort_members', 'cohortid', "userid = :userid AND cohortid $insql", $params + [
                    'userid' => $userid,
                ]);
                foreach ($mine as $cohortid) {
                    $price = min($price, $cohortprices[(int) $cohortid]);
                }
            }
        }
        return $price;
    }

    /**
     * Tax rate of a product.
     *
     * @param \stdClass $product
     * @return float Percent. 0 while the shop has no tax rate configured.
     */
    public static function tax_rate(\stdClass $product): float {
        if ($product->taxrate !== null && $product->taxrate !== '') {
            return (float) $product->taxrate;
        }
        return max(0, (float) get_config('local_pspvshop', 'taxrate'));
    }

    /**
     * Places still for sale.
     *
     * @param \stdClass $product
     * @return int|null Null when there is no limit.
     */
    public static function seats_left(\stdClass $product): ?int {
        global $DB;

        if ($product->seats === null) {
            return null;
        }
        $sold = $DB->count_records_sql(
            "SELECT COUNT(i.id)
               FROM {local_pspvshop_orderitem} i
               JOIN {local_pspvshop_order} o ON o.id = i.orderid
              WHERE i.productid = :productid AND o.status = :paid",
            ['productid' => $product->id, 'paid' => order_manager::PAID],
        );
        return max(0, (int) $product->seats - $sold);
    }

    /**
     * Whether a product can be bought now, and if not, why.
     *
     * @param \stdClass $product
     * @param int|null $userid The buyer, if known.
     * @return string AVAILABLE, or one of: disabled, hidden, notyet, closed, full, enrolled.
     */
    public static function availability(\stdClass $product, ?int $userid = null): string {
        global $DB;

        if (empty($product->enabled)) {
            return 'disabled';
        }
        $course = $DB->get_record('course', ['id' => $product->courseid], 'id, visible');
        if (!$course || !$course->visible) {
            return 'hidden';
        }
        $now = time();
        if ($product->salestart && $now < $product->salestart) {
            return 'notyet';
        }
        if ($product->saleend && $now > $product->saleend) {
            return 'closed';
        }
        if ($userid && !isguestuser($userid) && is_enrolled(\core\context\course::instance($course->id), $userid)) {
            return 'enrolled';
        }
        if (self::seats_left($product) === 0) {
            return 'full';
        }
        return self::AVAILABLE;
    }

    /**
     * URL of the public page of a product.
     *
     * @param int $productid
     * @return \core\url
     */
    public static function url(int $productid): \core\url {
        return new \core\url('/local/pspvshop/product.php', ['id' => $productid]);
    }

    /**
     * Whether a form value was left empty.
     *
     * @param mixed $value
     * @return bool
     */
    protected static function blank($value): bool {
        return $value === null || trim((string) $value) === '';
    }
}

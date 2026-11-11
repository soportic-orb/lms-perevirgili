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
 * Discount codes.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupon {
    /**
     * Normalises a code as typed by a buyer.
     *
     * @param string $code
     * @return string
     */
    public static function normalise(string $code): string {
        return \core_text::strtoupper(trim($code));
    }

    /**
     * Finds a code that can be used now.
     *
     * @param string $code
     * @return \stdClass|null Null when it does not exist, is switched off, is out of dates or is used up.
     */
    public static function find_valid(string $code): ?\stdClass {
        global $DB;

        $code = self::normalise($code);
        if ($code === '') {
            return null;
        }
        $coupon = $DB->get_record('local_pspvshop_coupon', ['code' => $code]);
        $now = time();
        if (
            !$coupon
            || !$coupon->enabled
            || ($coupon->validfrom && $now < $coupon->validfrom)
            || ($coupon->validuntil && $now > $coupon->validuntil)
            || ($coupon->maxuses && $coupon->timesused >= $coupon->maxuses)
        ) {
            return null;
        }
        return $coupon;
    }

    /**
     * Works out the discount of each line.
     *
     * @param \stdClass $coupon
     * @param int[] $prices Product id => price in cents.
     * @return int[] Product id => discount in cents (never more than the price).
     */
    public static function discounts(\stdClass $coupon, array $prices): array {
        $eligible = $prices;
        if ($coupon->productid) {
            $eligible = array_intersect_key($prices, [(int) $coupon->productid => true]);
        }
        $discounts = array_fill_keys(array_keys($prices), 0);
        if (!$eligible) {
            return $discounts;
        }

        if ($coupon->discounttype === 'percent') {
            $percent = min(100, max(0, (float) $coupon->amount));
            foreach ($eligible as $productid => $price) {
                $discounts[$productid] = (int) round($price * $percent / 100);
            }
            return $discounts;
        }

        // A fixed amount is shared among the lines in proportion to their price.
        $sum = array_sum($eligible);
        $remaining = min(money::cents($coupon->amount), $sum);
        $last = array_key_last($eligible);
        foreach ($eligible as $productid => $price) {
            $share = $productid === $last ? $remaining : (int) floor(money::cents($coupon->amount) * $price / max(1, $sum));
            $share = min($share, $price, $remaining);
            $discounts[$productid] = $share;
            $remaining -= $share;
        }
        return $discounts;
    }
}

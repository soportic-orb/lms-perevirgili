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
 * The shopping cart.
 *
 * A visitor's cart lives in the session; once they log in it is moved to the
 * database, so it follows them from one device to another.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cart {
    /**
     * Ids of the products in the cart of the current user.
     *
     * @return int[]
     */
    public static function product_ids(): array {
        global $DB, $SESSION, $USER;

        if (self::has_account()) {
            return array_map('intval', $DB->get_fieldset_select(
                'local_pspvshop_cartitem',
                'productid',
                'userid = ? ORDER BY timecreated, id',
                [$USER->id],
            ));
        }
        return array_values(array_map('intval', $SESSION->local_pspvshop_cart ?? []));
    }

    /**
     * Number of products in the cart.
     *
     * @return int
     */
    public static function count(): int {
        return count(self::product_ids());
    }

    /**
     * Adds a product to the cart.
     *
     * @param int $productid
     * @throws \moodle_exception When the product cannot be bought.
     */
    public static function add(int $productid): void {
        global $DB, $SESSION, $USER;

        $product = product::get($productid);
        $userid = self::has_account() ? (int) $USER->id : null;
        $availability = $product ? product::availability($product, $userid) : 'disabled';
        if ($availability !== product::AVAILABLE) {
            throw new \moodle_exception('unavailable_' . $availability, 'local_pspvshop');
        }
        if (in_array($productid, self::product_ids(), true)) {
            return;
        }
        if ($userid) {
            $DB->insert_record('local_pspvshop_cartitem', [
                'userid' => $userid, 'productid' => $productid, 'timecreated' => time(),
            ]);
        } else {
            $SESSION->local_pspvshop_cart = array_merge($SESSION->local_pspvshop_cart ?? [], [$productid]);
        }
    }

    /**
     * Removes a product from the cart.
     *
     * @param int $productid
     */
    public static function remove(int $productid): void {
        global $DB, $SESSION, $USER;

        if (self::has_account()) {
            $DB->delete_records('local_pspvshop_cartitem', ['userid' => $USER->id, 'productid' => $productid]);
        } else {
            $SESSION->local_pspvshop_cart = array_values(array_diff($SESSION->local_pspvshop_cart ?? [], [$productid]));
        }
    }

    /**
     * Empties the cart of a user and forgets the discount code.
     *
     * @param int|null $userid Defaults to the current user.
     */
    public static function clear(?int $userid = null): void {
        global $DB, $SESSION, $USER;

        $userid = $userid ?? (self::has_account() ? (int) $USER->id : 0);
        if ($userid) {
            $DB->delete_records('local_pspvshop_cartitem', ['userid' => $userid]);
        }
        if (!$userid || $userid == $USER->id) {
            unset($SESSION->local_pspvshop_cart, $SESSION->local_pspvshop_coupon);
        }
    }

    /**
     * Moves what a visitor put in the cart to their account, right after they log in.
     *
     * @param int $userid
     */
    public static function merge_session(int $userid): void {
        global $DB, $SESSION;

        foreach ($SESSION->local_pspvshop_cart ?? [] as $productid) {
            $conditions = ['userid' => $userid, 'productid' => (int) $productid];
            if (product::get((int) $productid) && !$DB->record_exists('local_pspvshop_cartitem', $conditions)) {
                $DB->insert_record('local_pspvshop_cartitem', $conditions + ['timecreated' => time()]);
            }
        }
        unset($SESSION->local_pspvshop_cart);
    }

    /**
     * The discount code the buyer typed, if any.
     *
     * @return string
     */
    public static function coupon_code(): string {
        global $SESSION;

        return (string) ($SESSION->local_pspvshop_coupon ?? '');
    }

    /**
     * Remembers the discount code the buyer typed.
     *
     * @param string $code Empty to forget it.
     */
    public static function set_coupon_code(string $code): void {
        global $SESSION;

        $SESSION->local_pspvshop_coupon = coupon::normalise($code);
    }

    /**
     * The cart with its amounts worked out.
     *
     * Products that can no longer be bought are taken out of the cart and reported in "removed".
     *
     * @return array Keys: lines (each with product, name, unitprice, discount, taxrate, tax and total, in cents),
     *               subtotal, discount, tax, total, coupon (record or null), couponinvalid (bool), removed (names).
     */
    public static function summary(): array {
        global $DB, $USER;

        $userid = self::has_account() ? (int) $USER->id : null;
        $lines = [];
        $removed = [];
        foreach (self::product_ids() as $productid) {
            $product = product::get($productid);
            $course = $product ? $DB->get_record('course', ['id' => $product->courseid], 'id, fullname') : false;
            $name = '';
            if ($course) {
                $name = format_string($course->fullname, true, ['context' => \core\context\course::instance($course->id)]);
            }
            if (!$product || !$course || product::availability($product, $userid) !== product::AVAILABLE) {
                self::remove($productid);
                $removed[] = $name;
                continue;
            }
            $lines[$productid] = [
                'product' => $product,
                'name' => $name,
                'unitprice' => product::price_for($product, $userid),
                'taxrate' => product::tax_rate($product),
            ];
        }

        $code = self::coupon_code();
        $coupon = $code !== '' ? coupon::find_valid($code) : null;
        $discounts = $coupon ? coupon::discounts($coupon, array_map(fn($line) => $line['unitprice'], $lines)) : [];

        $totals = ['subtotal' => 0, 'discount' => 0, 'tax' => 0, 'total' => 0];
        foreach ($lines as $productid => &$line) {
            $line['discount'] = $discounts[$productid] ?? 0;
            [, $line['tax'], $line['total']] = money::split($line['unitprice'] - $line['discount'], $line['taxrate']);
            $totals['subtotal'] += $line['unitprice'];
            $totals['discount'] += $line['discount'];
            $totals['tax'] += $line['tax'];
            $totals['total'] += $line['total'];
        }
        unset($line);

        return $totals + [
            'lines' => array_values($lines),
            'coupon' => $coupon,
            'couponinvalid' => $code !== '' && !$coupon,
            'removed' => array_values(array_filter($removed, fn($name) => $name !== '')),
        ];
    }

    /**
     * Whether the current user has an account of their own.
     *
     * @return bool
     */
    protected static function has_account(): bool {
        return isloggedin() && !isguestuser();
    }
}

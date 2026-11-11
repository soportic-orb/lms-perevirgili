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
 * Amounts of money, always handled as integer cents so sums never drift.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class money {
    /**
     * Converts a decimal amount, as stored in the database or typed in a form, to cents.
     *
     * @param string|float|int|null $amount
     * @return int
     */
    public static function cents($amount): int {
        return (int) round(((float) $amount) * 100);
    }

    /**
     * Converts cents to the decimal string stored in the database.
     *
     * @param int $cents
     * @return string
     */
    public static function decimal(int $cents): string {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Currency of the shop.
     *
     * @return string ISO 4217 code.
     */
    public static function currency(): string {
        $currency = strtoupper(trim((string) get_config('local_pspvshop', 'currency')));
        return preg_match('/^[A-Z]{3}$/', $currency) ? $currency : 'EUR';
    }

    /**
     * An amount as shown to people, with the decimal separator of the current language.
     *
     * @param int $cents
     * @param string|null $currency Defaults to the currency of the shop.
     * @return string Such as "180,00 €".
     */
    public static function format(int $cents, ?string $currency = null): string {
        $currency = $currency ?? self::currency();
        $symbols = ['EUR' => '€', 'USD' => '$', 'GBP' => '£'];
        return format_float($cents / 100, 2) . "\u{00A0}" . ($symbols[$currency] ?? $currency);
    }

    /**
     * Splits a price into what is tax and what is not.
     *
     * @param int $price Price as entered in the product, in cents.
     * @param float $rate Tax rate in percent.
     * @return int[] Net amount, tax and amount to pay, in cents.
     */
    public static function split(int $price, float $rate): array {
        if ($rate <= 0) {
            return [$price, 0, $price];
        }
        if (self::prices_include_tax()) {
            $net = (int) round($price / (1 + $rate / 100));
            return [$net, $price - $net, $price];
        }
        $tax = (int) round($price * $rate / 100);
        return [$price, $tax, $price + $tax];
    }

    /**
     * Whether the prices typed in the products already include tax.
     *
     * @return bool
     */
    public static function prices_include_tax(): bool {
        $setting = get_config('local_pspvshop', 'pricesincludetax');
        return $setting === false || !empty($setting);
    }
}

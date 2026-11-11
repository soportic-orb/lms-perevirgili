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
 * Callbacks of local_pspvshop.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Link to the cart in the top bar, while there is something in it.
 *
 * @param renderer_base $renderer
 * @return string HTML.
 */
function local_pspvshop_render_navbar_output(renderer_base $renderer): string {
    if (during_initial_install() || !get_config('local_pspvshop', 'version')) {
        return '';
    }
    $count = \local_pspvshop\cart::count();
    if ($count === 0) {
        return '';
    }
    return $renderer->render_from_template('local_pspvshop/navbar_cart', [
        'url' => (new \core\url('/local/pspvshop/cart.php'))->out(false),
        'count' => $count,
    ]);
}

/**
 * Tells theme_pspv where the whole catalogue is.
 *
 * @return \core\url|null
 */
function local_pspvshop_theme_pspv_catalogue_url(): ?\core\url {
    return \local_pspvshop\catalogue::category() ? new \core\url('/local/pspvshop/index.php') : null;
}

/**
 * Tells theme_pspv what to add to the card of a course that is on sale: its page in the shop and its price.
 *
 * @param int $courseid
 * @return array Empty when the course is not on sale.
 */
function local_pspvshop_theme_pspv_course_card(int $courseid): array {
    global $USER;

    $product = \local_pspvshop\product::for_course($courseid);
    if (!$product || empty($product->enabled)) {
        return [];
    }
    $userid = isloggedin() && !isguestuser() ? (int) $USER->id : null;
    $price = \local_pspvshop\product::price_for($product, $userid);
    [, , $topay] = \local_pspvshop\money::split($price, \local_pspvshop\product::tax_rate($product));
    return [
        'url' => \local_pspvshop\product::url((int) $product->id)->out(false),
        'hasprice' => true,
        'price' => $topay === 0 ? get_string('price_free', 'local_pspvshop') : \local_pspvshop\money::format($topay),
    ];
}

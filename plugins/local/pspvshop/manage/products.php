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
 * Courses that can be sold, with their price and state.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\catalogue;
use local_pspvshop\money;
use local_pspvshop\product;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_pspvshop_products');
require_capability('local/pspvshop:manageproducts', \core\context\system::instance());

$category = catalogue::category();
$rows = [];
if ($category) {
    foreach ($category->get_courses(['recursive' => true, 'sort' => ['fullname' => 1]]) as $course) {
        $product = product::for_course($course->id);
        $seats = $product ? product::seats_left($product) : null;
        $rows[] = [
            'name' => $course->get_formatted_fullname(),
            'visible' => (bool) $course->visible,
            'onsale' => $product && $product->enabled,
            'hasproduct' => (bool) $product,
            'price' => $product ? money::format(money::cents($product->price)) : '',
            'mode' => $product && $product->mode ? get_string('mode_' . $product->mode, 'local_pspvshop') : '',
            'haslimit' => $seats !== null,
            'seats' => (int) $seats,
            'editurl' => (new \core\url('/local/pspvshop/manage/product.php', ['courseid' => $course->id]))->out(false),
            'producturl' => $product ? product::url($product->id)->out(false) : '',
        ];
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('products', 'local_pspvshop'));
echo $OUTPUT->render_from_template('local_pspvshop/manage_products', [
    'rows' => $rows,
    'hasrows' => !empty($rows),
    'hascategory' => (bool) $category,
    'category' => $category ? $category->get_formatted_name() : '',
    'notaxrate' => trim((string) get_config('local_pspvshop', 'taxrate')) === '',
    'settingsurl' => (new \core\url('/admin/settings.php', ['section' => 'local_pspvshop_settings']))->out(false),
]);
echo $OUTPUT->footer();

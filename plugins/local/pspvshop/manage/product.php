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
 * Puts a course on sale or changes its price.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\form\product_form;
use local_pspvshop\money;
use local_pspvshop\product;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$courseid = required_param('courseid', PARAM_INT);

admin_externalpage_setup('local_pspvshop_products', '', ['courseid' => $courseid], '/local/pspvshop/manage/product.php');
$system = \core\context\system::instance();
require_capability('local/pspvshop:manageproducts', $system);

$course = get_course($courseid);
if ($course->id == SITEID) {
    throw new \moodle_exception('invalidcourseid');
}
$listurl = new \core\url('/local/pspvshop/manage/products.php');

// Prices can be set for any cohort of the site: workers, members of professional bodies, partner organisations.
$cohorts = [];
foreach ($DB->get_records('cohort', ['contextid' => $system->id], 'name', 'id, name', 0, 100) as $cohort) {
    $cohorts[$cohort->id] = format_string($cohort->name, true, ['context' => $system]);
}

$form = new product_form(null, ['cohorts' => $cohorts]);
$product = product::for_course($course->id);
$data = ['courseid' => $course->id];
if ($product) {
    $data += [
        'enabled' => $product->enabled,
        'price' => format_float((float) $product->price, 2),
        'taxrate' => $product->taxrate === null ? '' : format_float((float) $product->taxrate, 2),
        'mode' => (string) $product->mode,
        'seats' => $product->seats === null ? '' : $product->seats,
        'salestart' => $product->salestart,
        'saleend' => $product->saleend,
    ];
    foreach (product::cohort_prices($product->id) as $cohortid => $price) {
        $data["cohortprice[$cohortid]"] = format_float($price / 100, 2);
    }
}
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($listurl);
}
if ($submitted = $form->get_data()) {
    $submitted->taxrate = $submitted->taxrate === '' ? '' : unformat_float($submitted->taxrate);
    $prices = [];
    foreach ($cohorts as $cohortid => $name) {
        $price = $submitted->cohortprice[$cohortid] ?? '';
        $prices[$cohortid] = $price === '' ? '' : unformat_float($price);
    }
    product::save($submitted, $prices);
    redirect($listurl, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($course->fullname, true, ['context' => \core\context\course::instance($course->id)]));
$form->display();
echo $OUTPUT->footer();

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
 * Public page of a course on sale.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\cart;
use local_pspvshop\catalogue;
use local_pspvshop\hook_callbacks;
use local_pspvshop\local\page;
use local_pspvshop\money;
use local_pspvshop\product;

// A public page: visitors can browse and fill the cart without an account.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

$product = product::get($id);
$course = $product ? $DB->get_record('course', ['id' => $product->courseid]) : false;
$category = $course ? \core_course_category::get($course->category, IGNORE_MISSING) : null;
if (!$product || !$course || !$category || !$product->enabled || !$course->visible) {
    throw new \moodle_exception('product_notfound', 'local_pspvshop', new \core\url('/local/pspvshop/index.php'));
}

$context = \core\context\course::instance($course->id);
$name = format_string($course->fullname, true, ['context' => $context]);
page::setup('/local/pspvshop/product.php', ['id' => $id], $name);
$PAGE->navbar->add(get_string('catalogue', 'local_pspvshop'), new \core\url('/local/pspvshop/index.php'));
$PAGE->navbar->add($category->get_formatted_name(), new \core\url('/local/pspvshop/index.php', ['area' => $category->id]));
$PAGE->navbar->add($name);

$userid = isloggedin() && !isguestuser() ? (int) $USER->id : null;
$row = (object) ((array) $product + [
    'fullname' => $course->fullname,
    'summary' => $course->summary,
    'summaryformat' => $course->summaryformat,
    'category' => $course->category,
    'startdate' => $course->startdate,
]);
$card = catalogue::card($row, $category, $userid);
hook_callbacks::describe_product($card, $product);

$availability = product::availability($product, $userid);
$rate = product::tax_rate($product);

// The other prices of the product, so buyers know they exist.
$tiers = [];
if (get_config('local_pspvshop', 'showtiers')) {
    foreach (product::cohort_prices($product->id) as $cohortid => $price) {
        $cohort = $DB->get_record('cohort', ['id' => $cohortid], 'id, name, contextid');
        if ($cohort) {
            [, , $topay] = money::split($price, $rate);
            $tiers[] = [
                'name' => format_string($cohort->name, true, ['context' => \core\context\system::instance()]),
                'price' => money::format($topay),
            ];
        }
    }
}

$summary = file_rewrite_pluginfile_urls($course->summary, 'pluginfile.php', $context->id, 'course', 'summary', null);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_pspvshop/product', $card + [
    'description' => format_text($summary, $course->summaryformat, ['context' => $context]),
    'hasend' => !empty($course->enddate),
    'end' => userdate($course->enddate, get_string('strftimedate', 'core_langconfig')),
    'carturl' => (new \core\url('/local/pspvshop/cart.php'))->out(false),
    'sesskey' => sesskey(),
    'available' => $availability === product::AVAILABLE,
    'incart' => in_array((int) $product->id, cart::product_ids(), true),
    'enrolled' => $availability === 'enrolled',
    'courseurl' => (new \core\url('/course/view.php', ['id' => $course->id]))->out(false),
    'unavailable' => in_array($availability, ['notyet', 'closed', 'full'], true)
        ? get_string('unavailable_' . $availability, 'local_pspvshop') : '',
    'hastax' => $rate > 0,
    'taxnote' => $rate > 0 ? get_string(
        money::prices_include_tax() ? 'tax_included' : 'tax_added',
        'local_pspvshop',
        format_float($rate, 2, true, true),
    ) : '',
    'hassaleend' => !empty($product->saleend),
    'saleend' => userdate($product->saleend, get_string('strftimedate', 'core_langconfig')),
    'tiers' => $tiers,
    'hastiers' => !empty($tiers),
    'catalogueurl' => (new \core\url('/local/pspvshop/index.php'))->out(false),
]);
echo $OUTPUT->footer();

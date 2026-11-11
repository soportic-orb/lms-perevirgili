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
 * Shopping cart.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\cart;
use local_pspvshop\local\page;
use local_pspvshop\money;
use local_pspvshop\product;

// A public page: visitors can browse and fill the cart without an account.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$action = optional_param('action', '', PARAM_ALPHA);
$productid = optional_param('productid', 0, PARAM_INT);

page::setup('/local/pspvshop/cart.php', [], get_string('cart', 'local_pspvshop'));
$PAGE->navbar->add(get_string('catalogue', 'local_pspvshop'), new \core\url('/local/pspvshop/index.php'));
$PAGE->navbar->add(get_string('cart', 'local_pspvshop'));

if ($action !== '') {
    require_sesskey();
    $message = null;
    $type = \core\output\notification::NOTIFY_SUCCESS;
    try {
        switch ($action) {
            case 'add':
                cart::add($productid);
                $message = get_string('cart_added', 'local_pspvshop');
                break;
            case 'remove':
                cart::remove($productid);
                break;
            case 'coupon':
                cart::set_coupon_code(optional_param('code', '', PARAM_TEXT));
                break;
            case 'nocoupon':
                cart::set_coupon_code('');
                break;
        }
    } catch (\moodle_exception $e) {
        $message = $e->getMessage();
        $type = \core\output\notification::NOTIFY_ERROR;
    }
    redirect($PAGE->url, $message, null, $type);
}

$summary = cart::summary();
$lines = [];
foreach ($summary['lines'] as $line) {
    $lines[] = [
        'productid' => (int) $line['product']->id,
        'name' => $line['name'],
        'url' => product::url($line['product']->id)->out(false),
        'unitprice' => money::format($line['unitprice']),
        'hasdiscount' => $line['discount'] > 0,
        'discount' => money::format($line['discount']),
        'total' => money::format($line['total']),
    ];
}

echo $OUTPUT->header();
foreach ($summary['removed'] as $name) {
    echo $OUTPUT->notification(get_string('cart_removed', 'local_pspvshop', $name), \core\output\notification::NOTIFY_WARNING);
}
echo $OUTPUT->render_from_template('local_pspvshop/cart', [
    'actionurl' => $PAGE->url->out(false),
    'sesskey' => sesskey(),
    'lines' => $lines,
    'haslines' => !empty($lines),
    'hasdiscount' => $summary['discount'] > 0,
    'discount' => money::format($summary['discount']),
    'hastax' => $summary['tax'] > 0,
    'tax' => money::format($summary['tax']),
    'taxincluded' => money::prices_include_tax(),
    'total' => money::format($summary['total']),
    'couponcode' => cart::coupon_code(),
    'hascoupon' => $summary['coupon'] !== null,
    'couponinvalid' => $summary['couponinvalid'],
    'checkouturl' => (new \core\url('/local/pspvshop/checkout.php'))->out(false),
    'catalogueurl' => (new \core\url('/local/pspvshop/index.php'))->out(false),
    'needslogin' => !isloggedin() || isguestuser(),
]);
echo $OUTPUT->footer();

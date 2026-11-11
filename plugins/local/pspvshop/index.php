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
 * Public catalogue of the courses on sale.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvshop\catalogue;
use local_pspvshop\local\page;
use local_pspvshop\product;

// A public page: visitors can browse and fill the cart without an account.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$filters = [
    'q' => optional_param('q', '', PARAM_TEXT),
    'area' => optional_param('area', 0, PARAM_INT),
    'mode' => optional_param('mode', '', PARAM_ALPHA),
    'price' => optional_param('price', '', PARAM_ALPHANUM),
    'start' => optional_param('start', '', PARAM_ALPHA),
];
$pagenumber = optional_param('page', 0, PARAM_INT);
$perpage = 12;

$active = array_filter($filters, fn($value) => $value !== '' && $value !== 0);
page::setup('/local/pspvshop/index.php', $active, get_string('catalogue', 'local_pspvshop'));

$result = catalogue::search($filters, $pagenumber, $perpage);

$options = function (array $choices, $selected): array {
    $list = [];
    foreach ($choices as $value => $label) {
        $list[] = ['value' => $value, 'label' => $label, 'selected' => (string) $value === (string) $selected];
    }
    return $list;
};
$modes = [];
foreach (product::MODES as $mode) {
    $modes[$mode] = get_string('mode_' . $mode, 'local_pspvshop');
}
$prices = [];
foreach (array_keys(catalogue::PRICE_RANGES) as $range) {
    $prices[$range] = get_string('price_' . $range, 'local_pspvshop');
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_pspvshop/catalogue', [
    'actionurl' => (new \core\url('/local/pspvshop/index.php'))->out(false),
    'q' => $filters['q'],
    'areas' => $options(catalogue::areas(), $filters['area']),
    'modes' => $options($modes, $filters['mode']),
    'prices' => $options($prices, $filters['price']),
    'starts' => $options([
        'upcoming' => get_string('start_upcoming', 'local_pspvshop'),
        'open' => get_string('start_open', 'local_pspvshop'),
    ], $filters['start']),
    'hasfilters' => !empty($active),
    'total' => $result['total'],
    'cards' => $result['cards'],
    'hascards' => !empty($result['cards']),
]);
echo $OUTPUT->paging_bar($result['total'], $pagenumber, $perpage, $PAGE->url);
echo $OUTPUT->footer();

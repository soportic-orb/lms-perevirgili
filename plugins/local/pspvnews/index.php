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
 * The news of the campus. A public page.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvnews\article;

// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$pagenumber = optional_param('page', 0, PARAM_INT);
$perpage = 9;

$system = \core\context\system::instance();
$PAGE->set_url(new \core\url('/local/pspvnews/index.php', $pagenumber ? ['page' => $pagenumber] : []));
$PAGE->set_context($system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('news', 'local_pspvnews'));
$PAGE->set_heading(get_string('news', 'local_pspvnews'));
$PAGE->set_primary_active_tab('pspvnews');

$total = article::count_published();
$cards = array_values(array_map([article::class, 'card'], article::published($perpage, $pagenumber * $perpage)));

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_pspvnews/list', [
    'cards' => $cards,
    'hascards' => !empty($cards),
    'canmanage' => has_capability('local/pspvnews:manage', $system),
    'manageurl' => (new \core\url('/local/pspvnews/manage/index.php'))->out(false),
]);
echo $OUTPUT->paging_bar($total, $pagenumber, $perpage, new \core\url('/local/pspvnews/index.php'));
echo $OUTPUT->footer();

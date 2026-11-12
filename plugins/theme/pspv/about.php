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
 * The "About us" page of the campus. A public page.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_pspv\local\about;

// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$system = \core\context\system::instance();
$PAGE->set_url(about::url());
$PAGE->set_context($system);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('about', 'theme_pspv'));
$PAGE->set_heading(get_string('about', 'theme_pspv'));
$PAGE->set_primary_active_tab('pspvabout');

echo $OUTPUT->header();
if (!about::enabled()) {
    echo $OUTPUT->notification(get_string('about_disabled', 'theme_pspv'), \core\output\notification::NOTIFY_INFO);
} else {
    echo $OUTPUT->render_from_template('theme_pspv/about', [
        'content' => about::content(),
        'canedit' => has_capability('moodle/site:config', $system),
        'sample' => about::is_sample(),
        'settingsurl' => (new \core\url('/admin/settings.php', ['section' => 'themesettingpspv']))->out(false),
    ]);
}
echo $OUTPUT->footer();

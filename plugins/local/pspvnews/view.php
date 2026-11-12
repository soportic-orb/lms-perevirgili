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
 * A news article. A public page.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvnews\article;

// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$record = $DB->get_record('local_pspvnews_article', ['id' => $id]);
$system = \core\context\system::instance();
$canmanage = has_capability('local/pspvnews:manage', $system);
$list = new \core\url('/local/pspvnews/index.php');

$PAGE->set_url(new \core\url('/local/pspvnews/view.php', ['id' => $id]));
$PAGE->set_context($system);
$PAGE->set_pagelayout('standard');
$PAGE->set_primary_active_tab('pspvnews');

// A draft, or an article dated in the future, is only for who writes the news.
if (!$record || (!article::is_public($record) && !$canmanage)) {
    $PAGE->set_title(get_string('news', 'local_pspvnews'));
    $PAGE->set_heading(get_string('news', 'local_pspvnews'));
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('article_notfound', 'local_pspvnews'), \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->single_button($list, get_string('news_all', 'local_pspvnews'), 'get');
    echo $OUTPUT->footer();
    exit;
}

$card = article::card($record);
$PAGE->set_title($card['title']);
$PAGE->set_heading(get_string('news', 'local_pspvnews'));
$PAGE->navbar->add(get_string('news', 'local_pspvnews'), $list);
$PAGE->navbar->add($card['title']);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_pspvnews/article', $card + [
    'body' => article::body($record),
    'draft' => !article::is_public($record),
    'listurl' => $list->out(false),
    'canmanage' => $canmanage,
    'editurl' => (new \core\url('/local/pspvnews/manage/edit.php', ['id' => $id]))->out(false),
]);
echo $OUTPUT->footer();

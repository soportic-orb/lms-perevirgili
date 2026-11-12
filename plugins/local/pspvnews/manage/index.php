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
 * List of every news article, to write, publish and delete them.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvnews\article;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);

admin_externalpage_setup('local_pspvnews_manage');
$system = \core\context\system::instance();
require_capability('local/pspvnews:manage', $system);

if ($delete) {
    $record = article::get($delete);
    if ($confirm) {
        require_sesskey();
        article::delete($delete);
        redirect($PAGE->url, get_string('article_deleted', 'local_pspvnews'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('article_delete_confirm', 'local_pspvnews', format_string($record->title)),
        new \core\url($PAGE->url, ['delete' => $delete, 'confirm' => 1, 'sesskey' => sesskey()]),
        $PAGE->url,
    );
    echo $OUTPUT->footer();
    exit;
}

$dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
$rows = [];
foreach ($DB->get_records('local_pspvnews_article', null, 'timepublished DESC, id DESC') as $record) {
    $rows[] = [
        'title' => format_string($record->title),
        'viewurl' => (new \core\url('/local/pspvnews/view.php', ['id' => $record->id]))->out(false),
        'editurl' => (new \core\url('/local/pspvnews/manage/edit.php', ['id' => $record->id]))->out(false),
        'deleteurl' => (new \core\url($PAGE->url, ['delete' => $record->id]))->out(false),
        'date' => userdate($record->timepublished, $dateformat),
        'ispublic' => article::is_public($record),
        'isscheduled' => $record->published && !article::is_public($record),
        'isdraft' => !$record->published,
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage', 'local_pspvnews'));
echo $OUTPUT->render_from_template('local_pspvnews/manage', [
    'addurl' => (new \core\url('/local/pspvnews/manage/edit.php'))->out(false),
    'publicurl' => (new \core\url('/local/pspvnews/index.php'))->out(false),
    'articles' => $rows,
    'hasarticles' => !empty($rows),
]);
echo $OUTPUT->footer();

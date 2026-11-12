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
 * Writes or changes a news article.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvnews\article;
use local_pspvnews\form\article_form;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

$url = new \core\url('/local/pspvnews/manage/edit.php', $id ? ['id' => $id] : []);
admin_externalpage_setup('local_pspvnews_manage', '', null, $url);
$system = \core\context\system::instance();
require_capability('local/pspvnews:manage', $system);

$list = new \core\url('/local/pspvnews/manage/index.php');
$record = $id ? article::get($id) : null;

$data = (object) ($record
    ? ['id' => $record->id, 'title' => $record->title, 'summary' => $record->summary, 'published' => $record->published,
        'timepublished' => $record->timepublished, 'body_editor' => ['text' => $record->body, 'format' => FORMAT_HTML]]
    : ['id' => 0, 'published' => 1, 'timepublished' => time()]);
$data = file_prepare_standard_filemanager(
    $data,
    'cover',
    article_form::cover_options(),
    $system,
    'local_pspvnews',
    article::FILEAREA,
    $id ?: null,
);

$form = new article_form($url);
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($list);
}
if ($submitted = $form->get_data()) {
    $submitted->body = $submitted->body_editor['text'];
    $submitted->id = $id;
    $saved = article::save($submitted);
    file_postupdate_standard_filemanager(
        $submitted,
        'cover',
        article_form::cover_options(),
        $system,
        'local_pspvnews',
        article::FILEAREA,
        $saved->id,
    );
    redirect($list, get_string('article_saved', 'local_pspvnews'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($record ? 'article_edit' : 'article_add', 'local_pspvnews'));
$form->display();
echo $OUTPUT->footer();

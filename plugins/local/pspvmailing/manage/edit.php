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
 * Creates or changes the draft of a campaign.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_pspvmailing\campaign;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

$url = new \core\url('/local/pspvmailing/manage/edit.php', ['id' => $id]);
admin_externalpage_setup('local_pspvmailing_campaigns', '', null, $url);
require_capability('local/pspvmailing:manage', \core\context\system::instance());

$list = new \core\url('/local/pspvmailing/manage/index.php');
$record = $id ? campaign::get($id) : null;
if ($record && $record->status !== campaign::DRAFT) {
    redirect(new \core\url('/local/pspvmailing/manage/view.php', ['id' => $id]));
}

$form = new \local_pspvmailing\form\campaign_form($PAGE->url);
if ($record) {
    $form->set_data([
        'id' => $record->id,
        'name' => $record->name,
        'subject' => $record->subject,
        'body_editor' => ['text' => $record->body, 'format' => FORMAT_HTML],
        'commercial' => $record->commercial,
    ] + campaign::criteria($record));
}

if ($form->is_cancelled()) {
    redirect($record ? new \core\url('/local/pspvmailing/manage/view.php', ['id' => $id]) : $list);
}
if ($data = $form->get_data()) {
    $data->body = $data->body_editor['text'];
    $saved = campaign::save($data);
    redirect(
        new \core\url('/local/pspvmailing/manage/view.php', ['id' => $saved->id]),
        get_string('campaign_saved', 'local_pspvmailing'),
        null,
        \core\output\notification::NOTIFY_SUCCESS,
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string($record ? 'campaign_edit' : 'campaign_add', 'local_pspvmailing'));
$form->display();
echo $OUTPUT->footer();

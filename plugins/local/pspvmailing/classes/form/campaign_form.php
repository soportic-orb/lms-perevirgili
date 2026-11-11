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

namespace local_pspvmailing\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form of a campaign.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class campaign_form extends \moodleform {
    #[\Override]
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('header', 'messagehdr', get_string('campaign_message', 'local_pspvmailing'));
        $mform->addElement('text', 'name', get_string('campaign_name', 'local_pspvmailing'), ['size' => 60, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'campaign_name', 'local_pspvmailing');

        $subjectlabel = get_string('campaign_subject', 'local_pspvmailing');
        $mform->addElement('text', 'subject', $subjectlabel, ['size' => 60, 'maxlength' => 255]);
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', null, 'required', null, 'client');

        $mform->addElement('editor', 'body_editor', get_string('campaign_body', 'local_pspvmailing'), ['rows' => 12], [
            'maxfiles' => 0, 'context' => \core\context\system::instance(),
        ]);
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', null, 'required', null, 'client');
        $mform->addElement('static', 'placeholders', '', get_string(
            'campaign_placeholders',
            'local_pspvmailing',
            implode(' ', \local_pspvmailing\sender::PLACEHOLDERS),
        ));

        $mform->addElement('advcheckbox', 'commercial', get_string('campaign_commercial', 'local_pspvmailing'));
        $mform->addHelpButton('commercial', 'campaign_commercial', 'local_pspvmailing');
        $mform->setDefault('commercial', 1);

        $mform->addElement('header', 'segmenthdr', get_string('campaign_segment', 'local_pspvmailing'));
        $mform->setExpanded('segmenthdr');
        $system = \core\context\system::instance();
        $cohorts = [0 => get_string('segment_anycohort', 'local_pspvmailing')];
        foreach ($DB->get_records('cohort', ['contextid' => $system->id], 'name', 'id, name') as $cohort) {
            $cohorts[$cohort->id] = format_string($cohort->name, true, ['context' => $system]);
        }
        $mform->addElement('select', 'cohortid', get_string('segment_cohort', 'local_pspvmailing'), $cohorts);

        $mform->addElement('course', 'courseid', get_string('segment_course', 'local_pspvmailing'), ['multiple' => false]);
        $mform->addHelpButton('courseid', 'segment_course', 'local_pspvmailing');

        if (\local_pspvmailing\segment::shop_installed()) {
            $mform->addElement('select', 'purchase', get_string('segment_purchase', 'local_pspvmailing'), [
                '' => get_string('segment_purchase_any', 'local_pspvmailing'),
                \local_pspvmailing\segment::BUYERS => get_string('segment_purchase_buyers', 'local_pspvmailing'),
                \local_pspvmailing\segment::NONBUYERS => get_string('segment_purchase_nonbuyers', 'local_pspvmailing'),
            ]);
        }

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        foreach (['name', 'subject'] as $field) {
            if (trim($data[$field]) === '') {
                $errors[$field] = get_string('required');
            }
        }
        if (trim(html_to_text($data['body_editor']['text'] ?? '', 0, false)) === '') {
            $errors['body_editor'] = get_string('required');
        }
        return $errors;
    }
}

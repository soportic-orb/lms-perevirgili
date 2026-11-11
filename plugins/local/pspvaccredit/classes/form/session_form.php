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

namespace local_pspvaccredit\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form of a session.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class session_form extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('session_name', 'local_pspvaccredit'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        $mform->addElement('date_time_selector', 'sessiondate', get_string('session_date', 'local_pspvaccredit'));

        $mform->addElement('text', 'duration', get_string('session_duration', 'local_pspvaccredit'), ['size' => 6]);
        $mform->setType('duration', PARAM_INT);
        $mform->addHelpButton('duration', 'session_duration', 'local_pspvaccredit');
        $mform->setDefault('duration', 60);

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['name']) === '') {
            $errors['name'] = get_string('required');
        }
        if ((int) $data['duration'] < 0) {
            $errors['duration'] = get_string('session_duration_invalid', 'local_pspvaccredit');
        }
        return $errors;
    }
}

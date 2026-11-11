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

namespace local_pspvaccess\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Form to upload the employee list.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_employees extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'csvfile', get_string('file'), null, ['accepted_types' => ['.csv', '.txt']]);
        $mform->addRule('csvfile', null, 'required');
        $mform->addHelpButton('csvfile', 'employees_file', 'local_pspvaccess');

        $delimiters = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'tool_uploaduser'), $delimiters);
        $mform->setDefault('delimiter', get_string('listsep', 'langconfig') === ';' ? 'semicolon' : 'comma');

        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), \core_text::get_encodings());
        $mform->setDefault('encoding', 'UTF-8');

        $mform->addElement('advcheckbox', 'replace', get_string('employees_replace', 'local_pspvaccess'));
        $mform->addHelpButton('replace', 'employees_replace', 'local_pspvaccess');

        $this->add_action_buttons(false, get_string('employees_import', 'local_pspvaccess'));
    }
}

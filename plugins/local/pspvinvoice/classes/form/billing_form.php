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

namespace local_pspvinvoice\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form with the billing details of an order.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class billing_form extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'order');
        $mform->setType('order', PARAM_INT);

        $mform->addElement('select', 'customertype', get_string('billing_type', 'local_pspvinvoice'), [
            'person' => get_string('billing_type_person', 'local_pspvinvoice'),
            'company' => get_string('billing_type_company', 'local_pspvinvoice'),
        ]);

        $mform->addElement('text', 'name', get_string('billing_name', 'local_pspvinvoice'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addHelpButton('name', 'billing_name', 'local_pspvinvoice');

        $mform->addElement('text', 'taxid', get_string('billing_taxid', 'local_pspvinvoice'), ['size' => 20, 'maxlength' => 30]);
        $mform->setType('taxid', PARAM_TEXT);
        $mform->addRule('taxid', null, 'required', null, 'client');

        $mform->addElement('text', 'address', get_string('address'), ['size' => 50, 'maxlength' => 255]);
        $mform->setType('address', PARAM_TEXT);
        $mform->addRule('address', null, 'required', null, 'client');

        $label = get_string('billing_postcode', 'local_pspvinvoice');
        $mform->addElement('text', 'postcode', $label, ['size' => 10, 'maxlength' => 15]);
        $mform->setType('postcode', PARAM_TEXT);
        $mform->addRule('postcode', null, 'required', null, 'client');

        $mform->addElement('text', 'city', get_string('city'), ['size' => 30, 'maxlength' => 120]);
        $mform->setType('city', PARAM_TEXT);
        $mform->addRule('city', null, 'required', null, 'client');

        $mform->addElement('select', 'country', get_string('country'), get_string_manager()->get_list_of_countries());
        $mform->setDefault('country', 'ES');

        $mform->addElement('text', 'email', get_string('billing_email', 'local_pspvinvoice'), ['size' => 40, 'maxlength' => 100]);
        $mform->setType('email', PARAM_RAW_TRIMMED);

        $this->add_action_buttons(true, get_string('billing_continue', 'local_pspvinvoice'));
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (\core_text::strlen(preg_replace('/[\s.-]+/', '', $data['taxid'])) < 5) {
            $errors['taxid'] = get_string('billing_taxid_invalid', 'local_pspvinvoice');
        }
        if ($data['email'] !== '' && !validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        }
        return $errors;
    }
}

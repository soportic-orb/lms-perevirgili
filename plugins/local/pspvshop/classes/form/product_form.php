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

namespace local_pspvshop\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

use local_pspvshop\money;
use local_pspvshop\product;

/**
 * Form to put a course on sale.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class product_form extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $cohorts = $this->_customdata['cohorts'];

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('advcheckbox', 'enabled', get_string('product_enabled', 'local_pspvshop'));
        $mform->setDefault('enabled', 1);

        $mform->addElement('text', 'price', get_string('product_price', 'local_pspvshop', money::currency()), ['size' => 10]);
        $mform->setType('price', PARAM_LOCALISEDFLOAT);
        $mform->addRule('price', null, 'required', null, 'client');
        $mform->addHelpButton('price', 'product_price', 'local_pspvshop');

        $mform->addElement('text', 'taxrate', get_string('product_taxrate', 'local_pspvshop'), ['size' => 6]);
        $mform->setType('taxrate', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('taxrate', 'product_taxrate', 'local_pspvshop');

        $modes = ['' => get_string('choosedots')];
        foreach (product::MODES as $mode) {
            $modes[$mode] = get_string('mode_' . $mode, 'local_pspvshop');
        }
        $mform->addElement('select', 'mode', get_string('product_mode', 'local_pspvshop'), $modes);

        $mform->addElement('text', 'seats', get_string('product_seats', 'local_pspvshop'), ['size' => 6]);
        $mform->setType('seats', PARAM_RAW_TRIMMED);
        $mform->addHelpButton('seats', 'product_seats', 'local_pspvshop');

        $label = get_string('product_salestart', 'local_pspvshop');
        $mform->addElement('date_time_selector', 'salestart', $label, ['optional' => true]);
        $label = get_string('product_saleend', 'local_pspvshop');
        $mform->addElement('date_time_selector', 'saleend', $label, ['optional' => true]);

        if ($cohorts) {
            $mform->addElement('header', 'cohortprices', get_string('product_cohortprices', 'local_pspvshop'));
            $mform->addElement('static', 'cohortpricesinfo', '', get_string('product_cohortprices_desc', 'local_pspvshop'));
            foreach ($cohorts as $cohortid => $name) {
                $mform->addElement('text', "cohortprice[$cohortid]", $name, ['size' => 10]);
                $mform->setType("cohortprice[$cohortid]", PARAM_RAW_TRIMMED);
            }
        }

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ((float) $data['price'] < 0) {
            $errors['price'] = get_string('error_amount', 'local_pspvshop');
        }
        if ($data['taxrate'] !== '') {
            $rate = unformat_float($data['taxrate'], true);
            if ($rate === false || $rate < 0 || $rate > 100) {
                $errors['taxrate'] = get_string('error_taxrate', 'local_pspvshop');
            }
        }
        if ($data['seats'] !== '' && (!ctype_digit($data['seats']))) {
            $errors['seats'] = get_string('error_seats', 'local_pspvshop');
        }
        if (!empty($data['salestart']) && !empty($data['saleend']) && $data['saleend'] <= $data['salestart']) {
            $errors['saleend'] = get_string('error_saleend', 'local_pspvshop');
        }
        foreach ($data['cohortprice'] ?? [] as $cohortid => $price) {
            if ($price !== '' && (unformat_float($price, true) === false || unformat_float($price, true) < 0)) {
                $errors["cohortprice[$cohortid]"] = get_string('error_amount', 'local_pspvshop');
            }
        }
        return $errors;
    }
}

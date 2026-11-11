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

/**
 * Form to create or change a discount code.
 *
 * @package    local_pspvshop
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coupon_form extends \moodleform {
    #[\Override]
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'code', get_string('coupon_code', 'local_pspvshop'), ['size' => 20, 'maxlength' => 50]);
        $mform->setType('code', PARAM_ALPHANUMEXT);
        $mform->addRule('code', null, 'required', null, 'client');

        $mform->addElement('select', 'discounttype', get_string('coupon_type', 'local_pspvshop'), [
            'percent' => get_string('coupon_type_percent', 'local_pspvshop'),
            'amount' => get_string('coupon_type_amount', 'local_pspvshop', money::currency()),
        ]);

        $mform->addElement('text', 'amount', get_string('coupon_amount', 'local_pspvshop'), ['size' => 10]);
        $mform->setType('amount', PARAM_LOCALISEDFLOAT);
        $mform->addRule('amount', null, 'required', null, 'client');

        $mform->addElement('select', 'productid', get_string('coupon_product', 'local_pspvshop'), [
            0 => get_string('coupon_product_any', 'local_pspvshop'),
        ] + $this->_customdata['products']);

        $label = get_string('coupon_validfrom', 'local_pspvshop');
        $mform->addElement('date_time_selector', 'validfrom', $label, ['optional' => true]);
        $label = get_string('coupon_validuntil', 'local_pspvshop');
        $mform->addElement('date_time_selector', 'validuntil', $label, ['optional' => true]);

        $mform->addElement('text', 'maxuses', get_string('coupon_maxuses', 'local_pspvshop'), ['size' => 6]);
        $mform->setType('maxuses', PARAM_INT);
        $mform->setDefault('maxuses', 0);
        $mform->addHelpButton('maxuses', 'coupon_maxuses', 'local_pspvshop');

        $mform->addElement('advcheckbox', 'enabled', get_string('coupon_enabled', 'local_pspvshop'));
        $mform->setDefault('enabled', 1);

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        $code = \local_pspvshop\coupon::normalise($data['code']);
        $other = $DB->get_field('local_pspvshop_coupon', 'id', ['code' => $code]);
        if ($other && (int) $other !== (int) $data['id']) {
            $errors['code'] = get_string('error_couponexists', 'local_pspvshop');
        }
        if ((float) $data['amount'] <= 0 || ($data['discounttype'] === 'percent' && (float) $data['amount'] > 100)) {
            $errors['amount'] = get_string('error_amount', 'local_pspvshop');
        }
        if (!empty($data['validfrom']) && !empty($data['validuntil']) && $data['validuntil'] <= $data['validfrom']) {
            $errors['validuntil'] = get_string('error_saleend', 'local_pspvshop');
        }
        return $errors;
    }
}

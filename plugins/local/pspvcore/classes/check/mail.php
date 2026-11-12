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

namespace local_pspvcore\check;

use core\check\check;
use core\check\result;

/**
 * Health check: the campus is allowed to send email.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mail extends check {
    #[\Override]
    public function get_name(): string {
        return get_string('check_mail', 'local_pspvcore');
    }

    #[\Override]
    public function get_result(): result {
        global $CFG;

        if (!empty($CFG->noemailever)) {
            return new result(result::ERROR, get_string('check_mail_off', 'local_pspvcore'));
        }
        if (!empty($CFG->divertallemailsto)) {
            return new result(result::WARNING, get_string('check_mail_diverted', 'local_pspvcore', $CFG->divertallemailsto));
        }
        if (empty($CFG->smtphosts)) {
            return new result(result::WARNING, get_string('check_mail_nosmtp', 'local_pspvcore'));
        }
        return new result(result::OK, get_string('check_mail_ok', 'local_pspvcore', $CFG->smtphosts));
    }
}

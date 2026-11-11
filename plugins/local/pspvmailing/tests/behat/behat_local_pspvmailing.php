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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps of local_pspvmailing.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_pspvmailing extends behat_base {
    /**
     * Opens the link a user finds in an email to unsubscribe.
     *
     * @Given I open the unsubscribe link of :username
     * @param string $username
     */
    public function i_open_the_unsubscribe_link_of(string $username): void {
        $user = $this->get_user_by_identifier($username);
        $this->execute('behat_general::i_visit', [\local_pspvmailing\unsubscribe::url((int) $user->id)->out_as_local_url(false)]);
    }
}

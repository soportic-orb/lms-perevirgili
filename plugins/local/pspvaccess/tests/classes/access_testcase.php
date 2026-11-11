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

namespace local_pspvaccess\tests;

use local_pspvaccess\local\structure;

/**
 * Base class with helpers shared by the tests of the plugin.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class access_testcase extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        structure::ensure();
    }

    /**
     * Creates an OAuth 2 issuer and makes it the worker issuer.
     *
     * @return int Issuer id.
     */
    protected function create_worker_issuer(): int {
        $issuer = new \core\oauth2\issuer(0, (object) [
            'name' => 'Tenant',
            'image' => '',
            'baseurl' => 'https://login.example.com/tenant/v2.0',
            'clientid' => 'client',
            'clientsecret' => 'secret',
        ]);
        $issuer->create();
        set_config('workerissuer', $issuer->get('id'), 'local_pspvaccess');
        return $issuer->get('id');
    }

    /**
     * Links a user to an issuer, as a completed OAuth 2 login does.
     *
     * @param int $userid
     * @param int $issuerid
     * @param bool $confirmed Whether the link has been confirmed.
     */
    protected function link_login(int $userid, int $issuerid, bool $confirmed = true): void {
        global $DB;

        $DB->insert_record('auth_oauth2_linked_login', [
            'timecreated' => time(),
            'timemodified' => time(),
            'usermodified' => $userid,
            'userid' => $userid,
            'issuerid' => $issuerid,
            'username' => 'user' . $userid . '@tenant.example',
            'email' => 'user' . $userid . '@tenant.example',
            'confirmtoken' => $confirmed ? '' : 'pending',
            'confirmtokenexpires' => $confirmed ? 0 : time() + HOURSECS,
        ]);
    }

    /**
     * Whether a user is in the workers cohort.
     *
     * @param int $userid
     * @return bool
     */
    protected function in_cohort(int $userid): bool {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        return cohort_is_member(structure::cohort_id(), $userid);
    }
}

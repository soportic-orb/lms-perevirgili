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
 * Site setup of the access model: structure, OAuth 2 providers and privacy tools.
 *
 * Safe to run again: every step checks what is already in place.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_pspvaccess\local\site_setup;
use local_pspvaccess\local\structure;

[$options, $unrecognised] = cli_get_params(['help' => false], ['h' => 'help']);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help']) {
    cli_writeln("Sets up the access model of Campus PSPV: cohort, categories and roles, the Google and
Microsoft sign-in providers and the privacy tools.

The OAuth 2 credentials come from config.php (OAUTH_* in .env). A provider without credentials is skipped.");
    exit(0);
}

// The OAuth 2 and policy APIs check the capabilities of the current user.
\core\session\manager::set_user(get_admin());

structure::ensure();
cli_writeln('Cohort, categories and roles in place.');
foreach (
    array_merge(
        site_setup::configure_authentication(),
        site_setup::configure_issuers(),
        site_setup::configure_privacy(),
    ) as $line
) {
    cli_writeln($line);
}

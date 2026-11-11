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
 * One-off site setup after installing Moodle: language packs and the Redis cache store.
 *
 * Safe to run again: every step checks what is already in place.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_pspvcore\local\site_setup;

[$options, $unrecognised] = cli_get_params(['help' => false], ['h' => 'help']);
if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}
if ($options['help']) {
    cli_writeln("Sets up the language packs (ca, es) and the Redis cache store of Campus PSPV.

The Redis connection comes from config.php (REDIS_HOST, REDIS_PORT, REDIS_PASSWORD, REDIS_PREFIX in .env).");
    exit(0);
}

foreach (site_setup::install_languages() as $line) {
    cli_writeln($line);
}
foreach (site_setup::configure_cache() as $line) {
    cli_writeln($line);
}

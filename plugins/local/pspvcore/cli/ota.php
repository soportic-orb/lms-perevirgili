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
 * OTA updates from the command line.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_pspvcore\ota\updater;

[$options, $unrecognised] = cli_get_params(
    ['help' => false, 'status' => false, 'check' => false, 'apply' => false, 'rollback' => false],
    ['h' => 'help'],
);

if ($unrecognised) {
    cli_error(get_string('cliunknowoption', 'admin', implode(PHP_EOL . '  ', $unrecognised)));
}

if ($options['help'] || !($options['status'] || $options['check'] || $options['apply'] || $options['rollback'])) {
    cli_writeln("OTA updates of the Campus PSPV plugins (docs/OTA.md).

Options:
    --status    Show the current version and what stops updates, if anything
    --check     Look for a newer release
    --apply     Download, verify and install the newest release
    --rollback  Put back the folders saved before the last update
-h, --help      Print this help

After --apply, run admin/cli/upgrade.php if it says a database upgrade is pending.
Run it as the user that owns the Moodle files (the site user on CloudPanel).");
    exit(0);
}

$updater = new updater();

try {
    if ($options['status']) {
        cli_writeln('Current version: ' . ($updater->current_version() ?? '-'));
        $available = $updater->available();
        cli_writeln('Available:       ' . ($available['version'] ?? '-'));
        foreach ($updater->requirements() as $problem) {
            cli_writeln('Problem:         ' . $problem);
        }
    }
    if ($options['check']) {
        $release = $updater->check();
        cli_writeln($release ? "Version {$release['version']} is available." : 'Up to date.');
    }
    if ($options['apply']) {
        $result = $updater->apply();
        cli_writeln("Applied {$result['version']}.");
        foreach ($result['plugins'] as $component => $version) {
            cli_writeln("  $component $version");
        }
        if ($result['needsupgrade']) {
            cli_writeln('A database upgrade is pending: run admin/cli/upgrade.php --non-interactive');
        }
    }
    if ($options['rollback']) {
        $version = $updater->rollback();
        cli_writeln('Rolled back to ' . ($version ?? 'the state before any update') . '.');
    }
} catch (\moodle_exception $e) {
    cli_error($e->getMessage());
}

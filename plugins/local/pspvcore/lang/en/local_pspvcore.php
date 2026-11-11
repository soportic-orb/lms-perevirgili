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
 * Strings for local_pspvcore, English.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['event_ota_applied'] = 'OTA update applied';
$string['event_ota_rolled_back'] = 'OTA update rolled back';
$string['ota_action_applied'] = 'Applied';
$string['ota_action_rolledback'] = 'Rolled back';
$string['ota_applied'] = 'Version {$a} has been applied.';
$string['ota_apply'] = 'Apply version {$a}';
$string['ota_available'] = 'Version {$a} is available';
$string['ota_check'] = 'Check for updates';
$string['ota_confirm_apply'] = 'Version {$a} will replace the code of the Campus PSPV plugins. The current code is backed up first. If a plugin changes its database, Moodle\'s upgrade page opens next. Continue?';
$string['ota_confirm_rollback'] = 'The plugin folders saved before the last update will be put back. Continue?';
$string['ota_current'] = 'Current version';
$string['ota_err_bundle'] = 'The update package cannot be read.';
$string['ota_err_downgrade'] = 'The update carries an older version of {$a} than the one installed. Nothing has been changed.';
$string['ota_err_download'] = 'The file {$a} of the update could not be downloaded.';
$string['ota_err_github'] = 'GitHub did not return the list of releases (HTTP {$a}).';
$string['ota_err_hash'] = 'The update package does not match its signed manifest. Nothing has been changed.';
$string['ota_err_locked'] = 'Another update is in progress. Try again in a minute.';
$string['ota_err_manifest'] = 'The manifest of the update is not valid.';
$string['ota_err_nobackup'] = 'There is no backup to go back to.';
$string['ota_err_nothing'] = 'There is no newer version to apply.';
$string['ota_err_path'] = 'The update contains a file outside the allowed folders ({$a}). Nothing has been changed.';
$string['ota_err_replay'] = 'The signed manifest belongs to a different version than the release. Nothing has been changed.';
$string['ota_err_repository'] = 'The OTA repository is not configured correctly (OTA_REPOSITORY must be "owner/name").';
$string['ota_err_requirements'] = 'Updates cannot be applied on this server: {$a}';
$string['ota_err_rollback_upgraded'] = 'The update cannot be undone: the database of {$a} has already been upgraded and Moodle does not accept older code. Publish a new version with the fix instead.';
$string['ota_err_root'] = 'The update tries to replace a folder that is not a Campus PSPV plugin ({$a}). Nothing has been changed.';
$string['ota_err_signature'] = 'The signature of the update is not valid. Nothing has been changed.';
$string['ota_err_swap'] = 'The files could not be replaced ({$a}). The previous code has been kept.';
$string['ota_err_version'] = 'The folder {$a} of the update has no valid version.php.';
$string['ota_history'] = 'History';
$string['ota_intro'] = 'The Campus PSPV plugins (theme, access, shop, invoicing, accreditation, mailing and reports) are updated from the project\'s repository. Moodle core, community plugins, the configuration and the uploaded files are never touched.';
$string['ota_lastcheck'] = 'Last check';
$string['ota_none_available'] = 'No newer version has been found.';
$string['ota_problems'] = 'Updates cannot be applied until this is solved:';
$string['ota_req_publickey'] = 'The public key that verifies the updates is not configured (OTA_PUBLIC_KEY in .env).';
$string['ota_req_sodium'] = 'The PHP extension sodium is missing.';
$string['ota_req_writable'] = 'The web server user cannot write to the Moodle code folders.';
$string['ota_req_zip'] = 'The PHP extension zip is missing.';
$string['ota_rollback'] = 'Undo the last update';
$string['ota_rolledback'] = 'The last update has been undone.';
$string['ota_title'] = 'Campus PSPV updates';
$string['ota_uptodate'] = 'The site is up to date.';
$string['ota_version_none'] = 'As installed (no update applied)';
$string['pluginname'] = 'Campus PSPV core';
$string['privacy:metadata'] = 'The Campus PSPV core plugin does not store any personal data.';
$string['pspvcore:manageupdates'] = 'Apply and undo Campus PSPV updates';
$string['task_checkupdates'] = 'Check for Campus PSPV updates';

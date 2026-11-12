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
 * Health of the campus for an external monitor: JSON, with HTTP 503 when something is broken.
 *
 * It needs no login but a token, which is set in .env (HEALTH_TOKEN). Without a token the page does not exist.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);
define('NO_UPGRADE_CHECK', true);

// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../config.php');

$expected = trim((string) get_config('local_pspvcore', 'healthtoken'));
$given = optional_param('token', '', PARAM_RAW);
// Monitors can also send the token in a header, which keeps it out of the access logs.
if ($given === '' && !empty($_SERVER['HTTP_X_HEALTH_TOKEN'])) {
    $given = (string) $_SERVER['HTTP_X_HEALTH_TOKEN'];
}
if ($expected === '' || !hash_equals($expected, $given)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

$report = \local_pspvcore\health\checker::run();
http_response_code($report['status'] === 'error' ? 503 : 200);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

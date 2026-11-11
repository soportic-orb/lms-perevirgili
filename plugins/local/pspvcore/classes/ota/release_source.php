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

namespace local_pspvcore\ota;

/**
 * Where OTA releases come from (GitHub in production, a fake in tests).
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface release_source {
    /**
     * The newest published release that carries the three OTA files.
     *
     * @return array|null Keys: version (string), name (string), notes (string), published (int timestamp) and
     *                    assets (array asset file name => opaque reference understood by download()).
     */
    public function latest_release(): ?array;

    /**
     * Saves one file of a release to disk.
     *
     * @param array $release A release as returned by latest_release().
     * @param string $asset Asset file name.
     * @param string $target Full path of the file to write.
     * @throws \moodle_exception If it cannot be downloaded.
     */
    public function download(array $release, string $asset, string $target): void;
}

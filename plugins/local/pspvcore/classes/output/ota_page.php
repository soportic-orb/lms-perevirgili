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

namespace local_pspvcore\output;

use local_pspvcore\ota\updater;

/**
 * Data of the OTA updates page.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ota_page implements \core\output\renderable, \core\output\templatable {
    /**
     * Constructor.
     *
     * @param updater $updater
     */
    public function __construct(
        /** @var updater The updater whose state is shown. */
        protected updater $updater,
    ) {
    }

    #[\Override]
    public function export_for_template(\core\output\renderer_base $output): array {
        $problems = $this->updater->requirements();
        $available = $this->updater->available();
        $current = $this->updater->current_version();
        $lastcheck = $this->updater->last_check();

        $history = [];
        foreach ($this->updater->history() as $entry) {
            $history[] = [
                'version' => $entry['version'] !== '' ? $entry['version'] : get_string('ota_version_none', 'local_pspvcore'),
                'action' => get_string('ota_action_' . $entry['action'], 'local_pspvcore'),
                'time' => userdate($entry['time'], get_string('strftimedatetimeshort', 'core_langconfig')),
            ];
        }

        return [
            'actionurl' => (new \core\url('/local/pspvcore/ota.php'))->out(false),
            'sesskey' => sesskey(),
            'current' => $current ?? get_string('ota_version_none', 'local_pspvcore'),
            'lastcheck' => $lastcheck
                ? userdate($lastcheck, get_string('strftimedatetimeshort', 'core_langconfig'))
                : get_string('never'),
            'problems' => $problems,
            'hasproblems' => !empty($problems),
            'available' => $available ? [
                'version' => $available['version'],
                'published' => userdate($available['published'], get_string('strftimedatetimeshort', 'core_langconfig')),
                'notes' => format_text($available['notes'], FORMAT_MARKDOWN, ['context' => \core\context\system::instance()]),
                'hasnotes' => trim($available['notes']) !== '',
            ] : null,
            'canapply' => $available && !$problems,
            'canrollback' => $this->updater->has_backup(),
            'history' => $history,
            'hashistory' => !empty($history),
        ];
    }
}

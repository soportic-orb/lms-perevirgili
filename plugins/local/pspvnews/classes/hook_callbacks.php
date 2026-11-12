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

namespace local_pspvnews;

/**
 * Hook callbacks: the link to the news in the top menu.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Adds "News" to the primary navigation, before the site administration.
     *
     * @param \core\hook\navigation\primary_extend $hook
     */
    public static function primary_extend(\core\hook\navigation\primary_extend $hook): void {
        if (during_initial_install() || !get_config('local_pspvnews', 'version')) {
            return;
        }
        $node = \navigation_node::create(
            get_string('news', 'local_pspvnews'),
            new \core\url('/local/pspvnews/index.php'),
            \navigation_node::TYPE_CUSTOM,
            null,
            'pspvnews',
        );
        // Before the site administration, for those who have it; otherwise, last.
        $view = $hook->get_primaryview();
        $view->add_node($node, $view->get('siteadminnode') ? 'siteadminnode' : null);
    }
}

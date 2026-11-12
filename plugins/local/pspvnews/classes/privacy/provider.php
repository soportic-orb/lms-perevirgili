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

namespace local_pspvnews\privacy;

use core_privacy\local\metadata\collection;

/**
 * Privacy provider: the plugin only records who last changed each article.
 *
 * The articles are content of the site, not personal data of who wrote them: they are neither
 * exported nor deleted with the account of their author.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider {
    #[\Override]
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_pspvnews_article', [
            'usermodified' => 'privacy:metadata:article:usermodified',
            'timemodified' => 'privacy:metadata:article:timemodified',
        ], 'privacy:metadata:article');
        return $collection;
    }
}

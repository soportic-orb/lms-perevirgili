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

namespace local_pspvinvoice\fiscal;

/**
 * The connector used while no fiscal system is connected: it reports nothing.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class none implements connector {
    #[\Override]
    public function name(): string {
        return 'none';
    }

    #[\Override]
    public function submit(\stdClass $invoice): result {
        return new result(result::NONE);
    }

    /**
     * The connector to use: the one a plugin provides, or this one.
     *
     * @return connector
     */
    public static function resolve(): connector {
        foreach (get_plugins_with_function('pspvinvoice_fiscal_connector') as $plugins) {
            foreach ($plugins as $function) {
                $connector = $function();
                if ($connector instanceof connector) {
                    return $connector;
                }
            }
        }
        return new self();
    }
}

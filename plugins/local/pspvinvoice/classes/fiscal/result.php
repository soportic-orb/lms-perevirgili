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
 * Outcome of reporting a document to a fiscal system.
 *
 * @package    local_pspvinvoice
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class result {
    /** @var string Nothing was reported: no fiscal system is connected. */
    public const NONE = 'none';

    /** @var string The fiscal system accepted the document. */
    public const SENT = 'sent';

    /** @var string The fiscal system could not be reached or rejected the document. */
    public const FAILED = 'failed';

    /**
     * Constructor.
     *
     * @param string $status One of the constants of this class.
     * @param string|null $reference Reference given by the fiscal system, or the reason of a failure.
     */
    public function __construct(
        /** @var string One of the constants of this class. */
        public readonly string $status,
        /** @var string|null Reference given by the fiscal system, or the reason of a failure. */
        public readonly ?string $reference = null,
    ) {
    }
}

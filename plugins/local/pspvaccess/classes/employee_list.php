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

namespace local_pspvaccess;

/**
 * The employee list imported from HR.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class employee_list {
    /** @var string[] Optional columns of the CSV file, besides "email". */
    protected const OPTIONAL = ['employeeid', 'department', 'jobcategory'];

    /**
     * Number of employees in the list.
     *
     * @return int
     */
    public static function count(): int {
        global $DB;

        return $DB->count_records('local_pspvaccess_employee');
    }

    /**
     * Imports a CSV file.
     *
     * The first row holds the column names: "email" is required; "employeeid",
     * "department" and "jobcategory" are optional. Rows with an invalid email
     * address are skipped.
     *
     * @param string $content Content of the file.
     * @param string $delimiter Delimiter name as used by csv_import_reader ("comma", "semicolon"…).
     * @param string $encoding Encoding of the file.
     * @param bool $replace Whether employees missing from the file are removed from the list.
     * @return int[] Counts: created, updated, deleted and skipped.
     * @throws \moodle_exception When the file cannot be read or has no "email" column.
     */
    public static function import_csv(
        string $content,
        string $delimiter = 'comma',
        string $encoding = 'UTF-8',
        bool $replace = false,
    ): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/csvlib.class.php');

        $iid = \csv_import_reader::get_new_iid('local_pspvaccess');
        $reader = new \csv_import_reader($iid, 'local_pspvaccess');
        $reader->load_csv_content($content, $encoding, $delimiter);
        if ($reader->get_error() !== null) {
            $error = $reader->get_error();
            $reader->cleanup();
            throw new \moodle_exception('employees_err_csv', 'local_pspvaccess', '', $error);
        }
        $columns = array_map(fn($column) => \core_text::strtolower(trim($column)), $reader->get_columns() ?: []);
        if (!in_array('email', $columns, true)) {
            $reader->cleanup();
            throw new \moodle_exception('employees_err_noemail', 'local_pspvaccess');
        }

        $counts = ['created' => 0, 'updated' => 0, 'deleted' => 0, 'skipped' => 0];
        $existing = $DB->get_records('local_pspvaccess_employee', null, '', 'email, id, employeeid, department, jobcategory');
        $seen = [];
        $now = time();

        $transaction = $DB->start_delegated_transaction();
        $reader->init();
        while ($row = $reader->next()) {
            $row = array_combine($columns, array_pad(array_slice($row, 0, count($columns)), count($columns), ''));
            $email = \core_text::strtolower(trim((string) $row['email']));
            if ($email === '' || !validate_email($email) || \core_text::strlen($email) > 100 || isset($seen[$email])) {
                $counts['skipped']++;
                continue;
            }
            $seen[$email] = true;

            $record = ['email' => $email, 'timemodified' => $now];
            foreach (self::OPTIONAL as $field) {
                $value = isset($row[$field]) ? trim(clean_param($row[$field], PARAM_TEXT)) : '';
                $record[$field] = $value === '' ? null : \core_text::substr($value, 0, $field === 'employeeid' ? 100 : 255);
            }

            if (isset($existing[$email])) {
                $current = $existing[$email];
                $changed = false;
                foreach (self::OPTIONAL as $field) {
                    $changed = $changed || $current->$field !== $record[$field];
                }
                if ($changed) {
                    $DB->update_record('local_pspvaccess_employee', ['id' => $current->id] + $record);
                    $counts['updated']++;
                }
            } else {
                $DB->insert_record('local_pspvaccess_employee', $record + ['timecreated' => $now]);
                $counts['created']++;
            }
        }
        $reader->close();
        $reader->cleanup();

        if ($replace) {
            foreach ($existing as $email => $current) {
                if (!isset($seen[$email])) {
                    $DB->delete_records('local_pspvaccess_employee', ['id' => $current->id]);
                    $counts['deleted']++;
                }
            }
        }
        $transaction->allow_commit();

        set_config('employeesimported', $now, 'local_pspvaccess');
        event\employee_list_imported::create([
            'context' => \core\context\system::instance(),
            'other' => $counts,
        ])->trigger();

        return $counts;
    }
}

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

namespace local_pspvaccess\local;

/**
 * The cohort, categories and roles the access model is built on.
 *
 * - Cohort "pspv-workers": the workers of the hospital.
 * - Category "pspv-internal": its course list is hidden from guests and ordinary
 *   users, and shown to the members of the cohort through the role "pspvworker".
 * - Category "pspv-external": the public catalogue, visible to everybody.
 * - Roles "pspvtrainingmanager" and "pspvsalesmanager".
 *
 * Everything is looked up by idnumber or shortname, so names can be changed
 * from the administration.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class structure {
    /** @var string Idnumber of the workers cohort. */
    public const COHORT = 'pspv-workers';

    /** @var string Idnumber of the internal training category. */
    public const CATEGORY_INTERNAL = 'pspv-internal';

    /** @var string Idnumber of the external training category. */
    public const CATEGORY_EXTERNAL = 'pspv-external';

    /** @var string Shortname of the role that shows the internal category to workers. */
    public const ROLE_WORKER = 'pspvworker';

    /** @var string Shortname of the training manager role. */
    public const ROLE_TRAINING_MANAGER = 'pspvtrainingmanager';

    /** @var string Shortname of the sales manager role. */
    public const ROLE_SALES_MANAGER = 'pspvsalesmanager';

    /** @var string Capability that lets a user see a category and its courses. */
    protected const VIEW = 'moodle/category:viewcourselist';

    /**
     * Creates whatever is missing. Safe to call any number of times.
     */
    public static function ensure(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $system = \core\context\system::instance();

        if (!$DB->record_exists('cohort', ['idnumber' => self::COHORT])) {
            cohort_add_cohort((object) [
                'contextid' => $system->id,
                'name' => self::name('cohort_workers'),
                'idnumber' => self::COHORT,
                'description' => self::name('cohort_workers_desc'),
                'descriptionformat' => FORMAT_HTML,
                'visible' => 0,
                'component' => '',
            ]);
        }

        foreach ([self::CATEGORY_INTERNAL => 'category_internal', self::CATEGORY_EXTERNAL => 'category_external'] as $id => $name) {
            if (!$DB->record_exists('course_categories', ['idnumber' => $id])) {
                \core_course_category::create((object) [
                    'name' => self::name($name),
                    'idnumber' => $id,
                    'description' => self::name($name . '_desc'),
                    'descriptionformat' => FORMAT_HTML,
                    'parent' => 0,
                ]);
            }
        }

        $worker = self::ensure_role(self::ROLE_WORKER, '', [CONTEXT_COURSECAT]);
        assign_capability(self::VIEW, CAP_ALLOW, $worker, $system->id, true);
        $training = self::ensure_role(self::ROLE_TRAINING_MANAGER, 'manager', [CONTEXT_SYSTEM, CONTEXT_COURSECAT]);
        // Its capabilities arrive with the shop and invoicing plugins.
        self::ensure_role(self::ROLE_SALES_MANAGER, '', [CONTEXT_SYSTEM]);

        // The internal category: hidden from guests and ordinary users, shown to those who manage it.
        $internal = \core\context\coursecat::instance(self::category_id(self::CATEGORY_INTERNAL));
        foreach (array_merge(get_archetype_roles('guest'), get_archetype_roles('user')) as $role) {
            assign_capability(self::VIEW, CAP_PREVENT, $role->id, $internal->id, true);
        }
        $managers = array_merge(get_archetype_roles('manager'), get_archetype_roles('coursecreator'));
        foreach (array_unique(array_merge(array_column($managers, 'id'), [$training])) as $roleid) {
            assign_capability(self::VIEW, CAP_ALLOW, $roleid, $internal->id, true);
        }

        // Members added before the role existed.
        foreach ($DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [self::cohort_id()]) as $userid) {
            self::assign_worker_role((int) $userid);
        }
    }

    /**
     * The workers cohort.
     *
     * @return \stdClass
     */
    public static function cohort(): \stdClass {
        global $DB;

        $cohort = $DB->get_record('cohort', ['idnumber' => self::COHORT]);
        if (!$cohort) {
            self::ensure();
            $cohort = $DB->get_record('cohort', ['idnumber' => self::COHORT], '*', MUST_EXIST);
        }
        return $cohort;
    }

    /**
     * Id of the workers cohort.
     *
     * @return int 0 while it does not exist.
     */
    public static function cohort_id(): int {
        global $DB;

        return (int) $DB->get_field('cohort', 'id', ['idnumber' => self::COHORT]);
    }

    /**
     * Id of one of the two categories.
     *
     * @param string $idnumber CATEGORY_INTERNAL or CATEGORY_EXTERNAL.
     * @return int 0 while it does not exist.
     */
    public static function category_id(string $idnumber): int {
        global $DB;

        return (int) $DB->get_field('course_categories', 'id', ['idnumber' => $idnumber]);
    }

    /**
     * Shows the internal category to a user.
     *
     * @param int $userid
     */
    public static function assign_worker_role(int $userid): void {
        global $DB;

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => self::ROLE_WORKER]);
        $categoryid = self::category_id(self::CATEGORY_INTERNAL);
        if ($roleid && $categoryid) {
            role_assign($roleid, $userid, \core\context\coursecat::instance($categoryid)->id, 'local_pspvaccess');
        }
    }

    /**
     * Stops showing the internal category to a user.
     *
     * @param int $userid
     */
    public static function unassign_worker_role(int $userid): void {
        global $DB;

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => self::ROLE_WORKER]);
        $categoryid = self::category_id(self::CATEGORY_INTERNAL);
        if ($roleid && $categoryid) {
            role_unassign($roleid, $userid, \core\context\coursecat::instance($categoryid)->id, 'local_pspvaccess');
        }
    }

    /**
     * Creates a role if there is none with that shortname.
     *
     * @param string $shortname
     * @param string $archetype Archetype whose default permissions the new role gets, or ''.
     * @param int[] $contextlevels Where the role can be assigned.
     * @return int Role id.
     */
    protected static function ensure_role(string $shortname, string $archetype, array $contextlevels): int {
        global $DB;

        $roleid = (int) $DB->get_field('role', 'id', ['shortname' => $shortname]);
        if ($roleid) {
            return $roleid;
        }
        $roleid = create_role(self::name('role_' . $shortname), $shortname, self::name('role_' . $shortname . '_desc'), $archetype);
        set_role_contextlevels($roleid, $contextlevels);
        if ($archetype !== '') {
            reset_role_capabilities($roleid);
            foreach (['assign', 'override', 'switch', 'view'] as $type) {
                $allow = 'core_role_set_' . $type . '_allowed';
                foreach (get_default_role_archetype_allows($type, $archetype) as $targetid) {
                    $allow($roleid, $targetid);
                }
            }
        }
        return $roleid;
    }

    /**
     * A name in the default language of the site, to store in the database.
     *
     * @param string $identifier String identifier.
     * @return string
     */
    protected static function name(string $identifier): string {
        global $CFG;

        return get_string_manager()->get_string($identifier, 'local_pspvaccess', null, $CFG->lang ?? 'en');
    }
}

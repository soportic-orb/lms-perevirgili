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

namespace local_pspvaccredit;

/**
 * Diploma of an accredited course, issued with the community plugin mod_customcert.
 *
 * Everything here is optional: without mod_customcert the rest of the plugin works the same.
 * The diploma is a certificate activity built from a site template, which only the students in
 * the group of those who passed can open. It carries a code and a QR that anyone can verify.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class certificate {
    /** @var string Idnumber of the course module that holds the diploma. */
    public const IDNUMBER = 'pspv-diploma';

    /** @var int Width of an A4 landscape page, in mm. */
    protected const WIDTH = 297;

    /** @var int Height of an A4 landscape page, in mm. */
    protected const HEIGHT = 210;

    /** @var int Reference point of an element: its top left corner. */
    protected const TOPLEFT = 0;

    /** @var int Reference point of an element: the centre of its top side. */
    protected const TOPCENTRE = 1;

    /**
     * Whether mod_customcert is installed and enabled.
     *
     * @return bool
     */
    public static function available(): bool {
        return class_exists(\mod_customcert\template::class)
            && array_key_exists('customcert', \core_plugin_manager::instance()->get_enabled_plugins('mod') ?? []);
    }

    /**
     * The site template diplomas are built from, created the first time it is needed.
     *
     * Once created it belongs to the administrators, who can change it at will: it is never rebuilt
     * unless they delete it.
     *
     * @return \mod_customcert\template
     */
    public static function template(): \mod_customcert\template {
        global $DB;

        $id = (int) get_config('local_pspvaccredit', 'templateid');
        if ($id && ($record = $DB->get_record('customcert_templates', ['id' => $id]))) {
            return \mod_customcert\template::from_record($record);
        }

        $system = \core\context\system::instance();
        $template = \mod_customcert\template::create(local\setup::site_string('diploma_template'), $system->id);
        $pageid = \mod_customcert\service\template_service::create()->add_page($template, false);
        $DB->update_record('customcert_pages', (object) [
            'id' => $pageid, 'width' => self::WIDTH, 'height' => self::HEIGHT, 'leftmargin' => 0, 'rightmargin' => 0,
        ]);
        $sequence = 0;
        foreach (self::elements() as [$type, $name, $data, $posx, $posy, $refpoint]) {
            self::add_element($pageid, $type, $name, $data, $posx, $posy, $refpoint, ++$sequence);
        }
        set_config('templateid', $template->get_id(), 'local_pspvaccredit');
        // A diploma has to be verifiable by whoever receives it.
        set_config('verifyallcertificates', 1, 'customcert');
        return $template;
    }

    /**
     * The elements of the diploma: type, name, data, x, y and reference point.
     *
     * @return array[]
     */
    protected static function elements(): array {
        global $CFG;

        $text = fn(string $id, $a = null) => local\setup::site_string($id, $a);
        $style = fn(int $size, string $colour = '#182433', string $font = 'freesans', int $width = 250) =>
            ['font' => $font, 'fontsize' => $size, 'colour' => $colour, 'width' => $width];
        $centre = intdiv(self::WIDTH, 2);
        $owner = trim((string) get_config('theme_pspv', 'owner')) ?: 'Parc Sanitari Pere Virgili';
        $fields = [];
        foreach (\core_course\customfield\course_handler::create()->get_fields() as $field) {
            $fields[$field->get('shortname')] = (string) $field->get('id');
        }

        $elements = [
            ['border', $text('diploma_border'), ['colour' => '#0074d1', 'width' => 2], 0, 0, self::TOPLEFT],
        ];
        if ($logo = self::logo()) {
            $elements[] = ['image', $text('diploma_logo'), ['fileid' => $logo->get_id(), 'width' => 46, 'height' => 0],
                18, 14, self::TOPLEFT];
        } else {
            $elements[] = ['text', $text('diploma_owner'), ['text' => $owner] + $style(16, '#005fad'),
                $centre, 20, self::TOPCENTRE];
        }
        $elements = array_merge($elements, [
            ['text', $text('diploma_title'), ['text' => $text('diploma_title')] + $style(40, '#182433', 'freeserif'),
                $centre, 34, self::TOPCENTRE],
            ['text', $text('diploma_certifies'), ['text' => $text('diploma_certifies')] + $style(13, '#5b6676'),
                $centre, 60, self::TOPCENTRE],
            ['studentname', $text('diploma_student'), $style(28, '#182433', 'freeserif'), $centre, 69, self::TOPCENTRE],
            ['text', $text('diploma_completed'), ['text' => $text('diploma_completed')] + $style(13, '#5b6676'),
                $centre, 89, self::TOPCENTRE],
            ['coursename', $text('diploma_course'), ['coursenamedisplay' => 2] + $style(22, '#005fad'),
                $centre, 98, self::TOPCENTRE],
        ]);
        // Hours, credits and file code of the accreditation, in three columns.
        $columns = ['pspv_hours' => 74, 'pspv_credits' => $centre, 'pspv_filecode' => 223];
        foreach ($columns as $shortname => $x) {
            if (!isset($fields[$shortname])) {
                continue;
            }
            $label = $text('field_' . $shortname);
            $elements[] = ['text', $label, ['text' => $label] + $style(10, '#5b6676', 'freesans', 70), $x, 124, self::TOPCENTRE];
            $elements[] = ['coursefield', $label, ['coursefield' => $fields[$shortname]] + $style(15, '#182433', 'freesans', 70),
                $x, 130, self::TOPCENTRE];
        }
        $verifyurl = (new \core\url('/mod/customcert/verify_certificate.php'))->out(false);
        return array_merge($elements, [
            ['date', $text('diploma_date'), ['dateitem' => '-1', 'dateformat' => 'strftimedate'] + $style(12),
                $centre, 150, self::TOPCENTRE],
            ['text', $text('diploma_code'), ['text' => $text('diploma_code')] + $style(9, '#5b6676', 'freesans', 120),
                20, 174, self::TOPLEFT],
            ['code', $text('diploma_code'), $style(12, '#182433', 'freesans', 120), 20, 179, self::TOPLEFT],
            ['text', $text('diploma_verify'), ['text' => $text('diploma_verify', $verifyurl)]
                + $style(8, '#5b6676', 'freesans', 200), 20, 187, self::TOPLEFT],
            ['qrcode', $text('diploma_qr'), ['width' => 32, 'height' => 32], 245, 160, self::TOPLEFT],
        ]);
    }

    /**
     * The logo of the hospital, copied from theme_pspv to the images of mod_customcert.
     *
     * @return \stored_file|null Null when the theme is not installed.
     */
    protected static function logo(): ?\stored_file {
        $themedir = \core_component::get_plugin_directory('theme', 'pspv');
        if (!$themedir || !is_readable($themedir . '/pix/logo.png')) {
            return null;
        }
        $fs = get_file_storage();
        $record = [
            'contextid' => \core\context\system::instance()->id,
            'component' => 'mod_customcert',
            'filearea' => 'image',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'pspv-logo.png',
        ];
        $file = $fs->get_file(...array_values($record));
        return $file ?: $fs->create_file_from_pathname($record, $themedir . '/pix/logo.png');
    }

    /**
     * Adds an element to a page of a template, encoded as mod_customcert itself would.
     *
     * @param int $pageid
     * @param string $type
     * @param string $name
     * @param array $data What the form of the element would send.
     * @param int $posx
     * @param int $posy
     * @param int $refpoint
     * @param int $sequence
     */
    protected static function add_element(
        int $pageid,
        string $type,
        string $name,
        array $data,
        int $posx,
        int $posy,
        int $refpoint,
        int $sequence,
    ): void {
        global $DB;

        $record = (object) [
            'pageid' => $pageid,
            'element' => $type,
            'name' => \core_text::substr($name, 0, 255),
            'data' => null,
            'posx' => $posx,
            'posy' => $posy,
            'refpoint' => $refpoint,
            'alignment' => $refpoint === self::TOPCENTRE ? 'C' : 'L',
            'sequence' => $sequence,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $factory = \mod_customcert\service\element_factory::build_with_defaults();
        $instance = $factory->create_from_record(clone $record);
        if (!$instance) {
            // An element type that this version of mod_customcert does not have: the diploma goes without it.
            return;
        }
        $record->data = \mod_customcert\service\persistence_helper::to_json_data($instance, (object) $data);
        $DB->insert_record('customcert_elements', $record);
    }

    /**
     * The diploma activity of a course.
     *
     * @param int $courseid
     * @return \cm_info|null
     */
    public static function for_course(int $courseid): ?\cm_info {
        if (!self::available()) {
            return null;
        }
        foreach (get_fast_modinfo($courseid)->get_instances_of('customcert') as $cm) {
            if ($cm->idnumber === self::IDNUMBER && !$cm->deletioninprogress) {
                return $cm;
            }
        }
        return null;
    }

    /**
     * Adds the diploma to a course: a certificate built from the site template that only those who passed can open.
     *
     * @param \stdClass $course
     * @return \cm_info The activity; the existing one when the course already has it.
     */
    public static function add_to_course(\stdClass $course): \cm_info {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/modlib.php');

        if ($cm = self::for_course((int) $course->id)) {
            return $cm;
        }
        $source = self::template();
        $restriction = \core_availability\tree::get_root_json(
            [\availability_group\condition::get_json(accreditation::group((int) $course->id))],
            \core_availability\tree::OP_AND,
            false,
        );
        $module = create_module((object) [
            'modulename' => 'customcert',
            'course' => $course->id,
            'section' => 0,
            'visible' => 1,
            'name' => local\setup::site_string('diploma_activity'),
            'introeditor' => ['text' => '', 'format' => FORMAT_HTML, 'itemid' => 0],
            'cmidnumber' => self::IDNUMBER,
            'availability' => json_encode($restriction),
            'requiredtime' => 0,
            'verifyany' => 1,
            'deliveryoption' => 'I',
            'usecustomfilename' => 0,
            'emailstudents' => 0,
            'emailteachers' => 0,
            'emailothers' => '',
            'issueautomatically' => 0,
        ]);
        $templateid = (int) $DB->get_field('customcert', 'templateid', ['id' => $module->instance], MUST_EXIST);
        \mod_customcert\service\template_load_service::create()->replace($templateid, $source->get_id());

        return get_fast_modinfo($course->id)->get_cm($module->coursemodule);
    }
}

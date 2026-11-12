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
 * Creates sample courses to show the design of the campus, and removes them again.
 *
 * Six free courses go to the internal training category and five paid ones to the external
 * category, on sale in the shop. Every course has a cover image, a summary and three topics.
 * Prices, hours and credits are EXAMPLES, not decisions of the hospital.
 *
 * Run it from the root of the repository, as the user of the site:
 *
 *   php8.4 -d max_input_vars=5000 deploy/demo/demo-courses.php
 *   php8.4 -d max_input_vars=5000 deploy/demo/demo-courses.php --delete
 *
 * It can be run again: courses that already exist are left as they are.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../moodle/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');

[$options] = cli_get_params(['delete' => false, 'help' => false], ['h' => 'help']);
if ($options['help']) {
    echo "Creates the sample courses of the campus; with --delete, removes them.\n";
    exit(0);
}

/** Prefix of the idnumber of every sample course: what --delete looks for. */
const PSPV_DEMO_PREFIX = 'pspv-demo-';

\core\session\manager::set_user(get_admin());

if ($options['delete']) {
    $select = $DB->sql_like('idnumber', ':prefix');
    $courses = $DB->get_records_select('course', $select, ['prefix' => $DB->sql_like_escape(PSPV_DEMO_PREFIX) . '%']);
    foreach ($courses as $course) {
        delete_course($course, false);
        cli_writeln('Deleted: ' . $course->fullname);
    }
    cli_writeln(count($courses) . ' sample courses deleted.');
    exit(0);
}

$categories = [];
foreach (['internal' => 'pspv-internal', 'external' => 'pspv-external'] as $key => $idnumber) {
    $categories[$key] = $DB->get_field('course_categories', 'id', ['idnumber' => $idnumber]);
    if (!$categories[$key]) {
        cli_error("The category '$idnumber' does not exist: install local_pspvaccess first.");
    }
}

// Accreditation fields, when local_pspvaccredit is installed.
$customfields = [];
foreach (\core_course\customfield\course_handler::create()->get_fields() as $field) {
    $customfields[$field->get('shortname')] = true;
}
$shop = class_exists(\local_pspvshop\product::class) && get_config('local_pspvshop', 'version');

$now = time();
$topics = fn(string ...$names) => $names;
// Each course: key, category, name, summary, topics, start (days from now), hours, credits and,
// for the paid ones, price (EUR), mode and seats.
$courses = [
    ['persona', 'internal', 'Atenció centrada en la persona gran',
        'Com posar les preferències, la història de vida i l\'autonomia de la persona al centre de les cures del dia a dia.',
        $topics('Què vol dir atenció centrada en la persona', 'La història de vida i el pla de cures', 'Autonomia, dignitat i presa de decisions'),
        14, 10, null],
    ['caigudes', 'internal', 'Prevenció de caigudes en persones grans',
        'Factors de risc, valoració i mesures per reduir les caigudes a l\'hospital, a la residència i al domicili.',
        $topics('Per què cauen les persones grans', 'Valoració del risc de caiguda', 'Mesures de prevenció i actuació després d\'una caiguda'),
        21, 8, null],
    ['pell', 'internal', 'Cura de la pell i lesions per pressió',
        'Prevenció, valoració i tractament de les lesions per pressió i d\'altres lesions relacionades amb la dependència.',
        $topics('La pell de la persona gran', 'Prevenció: canvis posturals i superfícies de suport', 'Valoració i tractament de les lesions'),
        30, 12, null],
    ['finalvida', 'internal', 'Acompanyament al final de la vida',
        'Control de símptomes, comunicació amb la persona i la família i suport en el dol des de l\'atenció pal·liativa.',
        $topics('Principis de l\'atenció pal·liativa', 'Control de símptomes i confort', 'Comunicació, família i dol'),
        45, 15, null],
    ['demencia', 'internal', 'Demència i deteriorament cognitiu: comunicació i maneig',
        'Eines per entendre la persona amb demència, comunicar-s\'hi i abordar les alteracions de conducta sense contencions.',
        $topics('Tipus de demència i com evolucionen', 'Comunicar-se amb la persona amb demència', 'Alteracions de conducta i alternatives a les contencions'),
        7, 10, null],
    ['cuidadors', 'internal', 'Dependència i suport a la persona cuidadora',
        'Valoració de la dependència, recursos disponibles i com detectar i prevenir la sobrecàrrega de qui cuida.',
        $topics('Valorar la dependència', 'Recursos i prestacions', 'Cuidar qui cuida: la sobrecàrrega'),
        60, 6, null],

    ['valoracio', 'external', 'Valoració geriàtrica integral a la pràctica',
        'Les eines per valorar la persona gran de manera global: funció, cognició, nutrició, estat d\'ànim i entorn social.',
        $topics('Les dimensions de la valoració geriàtrica', 'Escales i instruments', 'Del resultat al pla d\'atenció'),
        20, 30, 3.5, 180, 'blended', 40],
    ['nutricio', 'external', 'Nutrició i disfàgia en la persona gran',
        'Cribratge de la desnutrició, adaptació de textures i alimentació segura en persones amb dificultat per empassar.',
        $topics('Desnutrició: cribratge i valoració', 'Disfàgia: detecció i adaptació de la dieta', 'Hidratació i suplements'),
        35, 20, 2.4, 120, 'online', null],
    ['rehabilitacio', 'external', 'Rehabilitació funcional després d\'una fractura de fèmur',
        'Protocol de rehabilitació precoç, prevenció de complicacions i retorn al domicili de la persona gran operada.',
        $topics('Del quiròfan a la sedestació', 'Reeducació de la marxa', 'Planificació de l\'alta i continuïtat'),
        50, 25, 3.0, 150, 'onsite', 24],
    ['dolor', 'external', 'Maneig del dolor crònic en geriatria',
        'Valoració del dolor en persones amb i sense deteriorament cognitiu, i tractament farmacològic i no farmacològic.',
        $topics('Valorar el dolor quan la persona no el pot explicar', 'Tractament farmacològic segur', 'Mesures no farmacològiques'),
        10, 15, 1.8, 95, 'online', null],
    ['qualitat', 'external', 'Qualitat i seguretat del pacient en centres sociosanitaris',
        'Cultura de seguretat, notificació d\'incidents i pràctiques segures en l\'atenció intermèdia i residencial.',
        $topics('Cultura de seguretat', 'Notificació i anàlisi d\'incidents', 'Pràctiques segures: medicació, infeccions i identificació'),
        75, 20, 2.4, 110, 'blended', 60],
];

$fs = get_file_storage();
$created = 0;
foreach ($courses as $definition) {
    [$key, $where, $fullname, $summary, $sections, $startdays, $hours, $credits] = $definition;
    [$price, $mode, $seats] = array_slice($definition, 8) + [null, null, null];
    $idnumber = PSPV_DEMO_PREFIX . $key;

    $course = $DB->get_record('course', ['idnumber' => $idnumber]);
    if ($course) {
        cli_writeln('Already there: ' . $fullname);
        continue;
    }

    $data = (object) [
        'fullname' => $fullname,
        'shortname' => 'DEMO-' . strtoupper($key),
        'idnumber' => $idnumber,
        'category' => $categories[$where],
        'summary' => '<p>' . s($summary) . '</p>',
        'summaryformat' => FORMAT_HTML,
        'format' => 'topics',
        'numsections' => count($sections),
        'startdate' => usergetmidnight($now + $startdays * DAYSECS),
        'enddate' => usergetmidnight($now + ($startdays + 42) * DAYSECS),
        'visible' => 1,
    ];
    // The accreditation data are examples.
    $accreditation = ['pspv_hours' => $hours];
    if ($credits !== null) {
        $accreditation += ['pspv_accredited' => 1, 'pspv_credits' => $credits, 'pspv_filecode' => 'EXEMPLE-' . strtoupper($key)];
    }
    foreach ($accreditation as $shortname => $value) {
        if (isset($customfields[$shortname])) {
            $data->{'customfield_' . $shortname} = $value;
        }
    }
    $course = create_course($data);

    foreach (array_values($sections) as $index => $name) {
        $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $index + 1]);
        if ($section) {
            course_update_section($course, $section, ['name' => $name]);
        }
    }

    $fs->create_file_from_pathname([
        'contextid' => \core\context\course::instance($course->id)->id,
        'component' => 'course',
        'filearea' => 'overviewfiles',
        'itemid' => 0,
        'filepath' => '/',
        'filename' => $key . '.jpg',
    ], __DIR__ . '/covers/' . $key . '.jpg');

    $sale = '';
    if ($price !== null && $shop) {
        \local_pspvshop\product::save((object) [
            'courseid' => $course->id, 'enabled' => 1, 'price' => $price, 'taxrate' => '', 'mode' => $mode, 'seats' => $seats ?? '',
        ]);
        $sale = " (on sale, $price EUR)";
    }
    $created++;
    cli_writeln("Created: $fullname$sale");
}

rebuild_course_cache(0, true);
cli_writeln("$created sample courses created. Remove them with: php deploy/demo/demo-courses.php --delete");

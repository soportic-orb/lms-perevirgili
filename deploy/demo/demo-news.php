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
 * Creates four sample news articles to show the design of the campus, and removes them again.
 *
 * They are explanatory texts about intermediate care, home care and continuing education, NOT
 * real news: they report no event, figure or statement. Replace them before the campus opens.
 *
 * Run it from the root of the repository, as the user of the site:
 *
 *   php8.4 -d max_input_vars=5000 deploy/demo/demo-news.php
 *   php8.4 -d max_input_vars=5000 deploy/demo/demo-news.php --delete
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../moodle/config.php');
require_once($CFG->libdir . '/clilib.php');

use local_pspvnews\article;

[$options] = cli_get_params(['delete' => false, 'help' => false], ['h' => 'help']);
if ($options['help']) {
    echo "Creates the sample news of the campus; with --delete, removes them.\n";
    exit(0);
}
if (!get_config('local_pspvnews', 'version')) {
    cli_error('local_pspvnews is not installed.');
}

\core\session\manager::set_user(get_admin());

// The ids of the sample articles are remembered so that only they are deleted.
$existing = array_filter(array_map('intval', explode(',', (string) get_config('local_pspvnews', 'demoids'))));

if ($options['delete']) {
    foreach ($existing as $id) {
        if ($DB->record_exists('local_pspvnews_article', ['id' => $id])) {
            article::delete($id);
        }
    }
    unset_config('demoids', 'local_pspvnews');
    cli_writeln(count($existing) . ' sample news deleted.');
    exit(0);
}
if ($existing) {
    cli_writeln('The sample news are already there. Remove them first with --delete to create them again.');
    exit(0);
}

$paragraphs = fn(string ...$texts) => implode('', array_map(fn($text) => '<p>' . s($text) . '</p>', $texts));
// Each article: cover, days ago, title, summary and text.
$news = [
    ['intermedia', 2, 'L\'atenció intermèdia, un pont entre l\'hospital i casa',
        'Què és l\'atenció intermèdia i per què és clau per a les persones grans després d\'un ingrés.',
        $paragraphs(
            'L\'atenció intermèdia acompanya les persones que ja no necessiten un hospital d\'aguts però que encara no poden '
            . 'tornar a casa amb seguretat. És un temps per recuperar funció, ajustar tractaments i preparar el retorn al domicili.',
            'Hi treballen plegats professionals de medicina, infermeria, fisioteràpia, teràpia ocupacional i treball social, '
            . 'amb un pla fet a mida de cada persona i de la seva família.',
            'Al campus hi trobareu formació per entendre aquest model d\'atenció i per treballar-hi amb eines pràctiques.',
        )],
    ['domicili', 6, 'Cures a domicili: acompanyar la persona gran a casa',
        'Cuidar a casa demana organització, formació i suport. Recollim les idees principals per començar.',
        $paragraphs(
            'La majoria de persones grans volen viure a casa seva tant de temps com sigui possible. Fer-ho bé vol dir adaptar '
            . 'l\'entorn, mantenir les rutines i saber quan cal demanar ajuda.',
            'Qui cuida també necessita cura: descansar, compartir la càrrega i conèixer els recursos que té a l\'abast.',
            'Els cursos del campus sobre dependència i suport a la persona cuidadora donen pautes per al dia a dia.',
        )],
    ['envelliment', 11, 'Envelliment actiu: petits hàbits que sumen salut',
        'Moure\'s, menjar bé i mantenir els vincles són la base per envellir amb autonomia.',
        $paragraphs(
            'Envellir amb salut no depèn d\'un sol factor. L\'activitat física adaptada, una alimentació variada i la vida '
            . 'social ajuden a conservar l\'autonomia i a prevenir caigudes i fragilitat.',
            'Els professionals hi tenen un paper important: detectar a temps els canvis i proposar objectius realistes.',
            'Al catàleg hi ha formació sobre prevenció de caigudes, nutrició i valoració geriàtrica integral.',
        )],
    ['formacio', 16, 'Formació continuada: per què actualitzar-se en geriatria',
        'Les necessitats de les persones grans canvien, i la manera d\'atendre-les també. La formació hi ajuda.',
        $paragraphs(
            'Tenir cura de persones grans demana coneixements que es renoven: noves maneres d\'abordar el dolor, la demència '
            . 'o el final de la vida, i una mirada cada cop més centrada en la persona.',
            'La formació en línia permet actualitzar-se al propi ritme i compaginar-ho amb la feina.',
            'El Campus PSPV reuneix cursos fets per professionals que treballen cada dia en aquest àmbit.',
        )],
];

$ids = [];
foreach ($news as [$cover, $daysago, $title, $summary, $body]) {
    $saved = article::save((object) [
        'title' => $title,
        'summary' => $summary,
        'body' => $body,
        'published' => 1,
        'timepublished' => time() - $daysago * DAYSECS,
    ]);
    article::set_cover_from_path((int) $saved->id, __DIR__ . '/news/' . $cover . '.jpg', $cover . '.jpg');
    $ids[] = (int) $saved->id;
    cli_writeln('Created: ' . $title);
}
set_config('demoids', implode(',', $ids), 'local_pspvnews');
cli_writeln(count($ids) . ' sample news created. Remove them with: php deploy/demo/demo-news.php --delete');

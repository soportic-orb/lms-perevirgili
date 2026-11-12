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
 * Callbacks of local_pspvnews.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Serves the cover images of the articles. They are public, like the news themselves.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool False when the file does not exist.
 */
function local_pspvnews_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== \local_pspvnews\article::FILEAREA) {
        return false;
    }
    $itemid = (int) array_shift($args);
    $article = $DB->get_record('local_pspvnews_article', ['id' => $itemid]);
    // The cover of a draft is only for who writes the news.
    if (!$article || (!\local_pspvnews\article::is_public($article) && !has_capability('local/pspvnews:manage', $context))) {
        return false;
    }
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';
    $file = get_file_storage()->get_file($context->id, 'local_pspvnews', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}

/**
 * Gives theme_pspv the latest news for the front page.
 *
 * @param int $limit
 * @return array url (of the news page) and cards (see \local_pspvnews\article::card()).
 */
function local_pspvnews_theme_pspv_frontpage_news(int $limit): array {
    return [
        'url' => (new \core\url('/local/pspvnews/index.php'))->out(false),
        'cards' => array_values(array_map(
            [\local_pspvnews\article::class, 'card'],
            \local_pspvnews\article::published($limit),
        )),
    ];
}

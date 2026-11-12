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
 * News articles: storing them, listing those that are published and preparing them for the templates.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class article {
    /** @var string File area of the cover image of an article. */
    public const FILEAREA = 'cover';

    /** @var int Number of cover colours the stylesheet defines for articles without an image. */
    protected const TONES = 6;

    /**
     * An article.
     *
     * @param int $id
     * @return \stdClass
     */
    public static function get(int $id): \stdClass {
        global $DB;

        return $DB->get_record('local_pspvnews_article', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Whether an article can be read by everyone now.
     *
     * @param \stdClass $article
     * @return bool
     */
    public static function is_public(\stdClass $article): bool {
        return !empty($article->published) && $article->timepublished <= time();
    }

    /**
     * Creates or updates an article.
     *
     * @param \stdClass $data title, summary, body, published, timepublished and, to update, id.
     * @return \stdClass The article.
     */
    public static function save(\stdClass $data): \stdClass {
        global $DB, $USER;

        $now = time();
        $record = (object) [
            'title' => \core_text::substr(trim($data->title), 0, 255),
            'summary' => trim((string) ($data->summary ?? '')),
            'body' => (string) ($data->body ?? ''),
            'bodyformat' => FORMAT_HTML,
            'published' => empty($data->published) ? 0 : 1,
            'timepublished' => !empty($data->timepublished) ? (int) $data->timepublished : $now,
            'usermodified' => $USER->id,
            'timemodified' => $now,
        ];
        if (!empty($data->id)) {
            $record->id = (int) $data->id;
            $DB->update_record('local_pspvnews_article', $record);
        } else {
            $record->timecreated = $now;
            $record->id = $DB->insert_record('local_pspvnews_article', $record);
        }
        return self::get((int) $record->id);
    }

    /**
     * Deletes an article and its cover.
     *
     * @param int $id
     */
    public static function delete(int $id): void {
        global $DB;

        get_file_storage()->delete_area_files(\core\context\system::instance()->id, 'local_pspvnews', self::FILEAREA, $id);
        $DB->delete_records('local_pspvnews_article', ['id' => $id]);
    }

    /**
     * Sets the cover of an article from a file on disk, replacing the one it had.
     *
     * @param int $id
     * @param string $path
     * @param string $filename
     */
    public static function set_cover_from_path(int $id, string $path, string $filename): void {
        $fs = get_file_storage();
        $contextid = \core\context\system::instance()->id;
        $fs->delete_area_files($contextid, 'local_pspvnews', self::FILEAREA, $id);
        $fs->create_file_from_pathname([
            'contextid' => $contextid,
            'component' => 'local_pspvnews',
            'filearea' => self::FILEAREA,
            'itemid' => $id,
            'filepath' => '/',
            'filename' => clean_filename($filename),
        ], $path);
    }

    /**
     * URL of the cover of an article.
     *
     * @param int $id
     * @return string|null Null when it has none.
     */
    public static function cover_url(int $id): ?string {
        $files = get_file_storage()->get_area_files(
            \core\context\system::instance()->id,
            'local_pspvnews',
            self::FILEAREA,
            $id,
            'filename',
            false,
        );
        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return \core\url::make_pluginfile_url(
                    $file->get_contextid(),
                    'local_pspvnews',
                    self::FILEAREA,
                    $id,
                    $file->get_filepath(),
                    $file->get_filename(),
                )->out(false);
            }
        }
        return null;
    }

    /**
     * The published articles, newest first.
     *
     * @param int $limit Maximum number; 0 for all.
     * @param int $offset
     * @return \stdClass[]
     */
    public static function published(int $limit = 0, int $offset = 0): array {
        global $DB;

        return $DB->get_records_select(
            'local_pspvnews_article',
            'published = 1 AND timepublished <= :now',
            ['now' => time()],
            'timepublished DESC, id DESC',
            '*',
            $offset,
            $limit,
        );
    }

    /**
     * Number of published articles.
     *
     * @return int
     */
    public static function count_published(): int {
        global $DB;

        return $DB->count_records_select('local_pspvnews_article', 'published = 1 AND timepublished <= :now', ['now' => time()]);
    }

    /**
     * An article as the cards show it.
     *
     * @param \stdClass $article
     * @return array
     */
    public static function card(\stdClass $article): array {
        $image = self::cover_url((int) $article->id);
        $summary = trim((string) $article->summary);
        if ($summary === '') {
            // Articles without a summary show the start of their text.
            // Paragraphs and line breaks become spaces, so that words do not run together.
            $text = preg_replace('~</(p|div|li|h[1-6])>|<br\s*/?>~i', '$0 ', self::body($article));
            $summary = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5);
        }
        return [
            'id' => (int) $article->id,
            'url' => (new \core\url('/local/pspvnews/view.php', ['id' => $article->id]))->out(false),
            'title' => format_string($article->title, true, ['context' => \core\context\system::instance()]),
            'summary' => shorten_text(trim(preg_replace('/\s+/', ' ', $summary)), 160),
            'date' => userdate($article->timepublished, get_string('strftimedate', 'core_langconfig')),
            'isodate' => date('Y-m-d', (int) $article->timepublished),
            'hasimage' => $image !== null,
            'image' => (string) $image,
            'tone' => $article->id % self::TONES + 1,
        ];
    }

    /**
     * The text of an article, ready to be shown.
     *
     * @param \stdClass $article
     * @return string HTML.
     */
    public static function body(\stdClass $article): string {
        return format_text((string) $article->body, (int) $article->bodyformat, ['context' => \core\context\system::instance()]);
    }
}

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

namespace theme_pspv\output;

use theme_pspv\local\catalogue;
use theme_pspv\local\texts;

/**
 * Data of the public front page.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontpage implements \core\output\renderable, \core\output\templatable {
    /** @var string[] Icon of each reason to train here, in order: there are as many reasons as icons. */
    public const REASON_ICONS = ['building-hospital', 'users', 'device-laptop', 'certificate'];

    /** @var int News shown on the front page when the setting is empty. */
    public const NEWS_COUNT = 4;

    #[\Override]
    public function export_for_template(\core\output\renderer_base $output): array {
        $courses = catalogue::courses(max(0, (int) (get_config('theme_pspv', 'cataloguecount') ?: 6)));
        $guest = !isloggedin() || isguestuser();

        // Reasons to train here, shown before the catalogue. Texts come from the settings or their defaults.
        $showwhy = get_config('theme_pspv', 'showwhy');
        $showwhy = $showwhy === false || !empty($showwhy);
        $reasons = [];
        foreach (self::REASON_ICONS as $index => $icon) {
            $number = $index + 1;
            $reasons[] = [
                'icon' => $icon,
                'title' => texts::get("why{$number}title"),
                'text' => texts::get("why{$number}text"),
            ];
        }

        // The latest news, from whichever plugin offers them.
        $news = ['url' => '', 'cards' => []];
        $shownews = get_config('theme_pspv', 'shownews');
        if ($shownews === false || !empty($shownews)) {
            $count = get_config('theme_pspv', 'newscount');
            $count = $count === false ? self::NEWS_COUNT : max(0, (int) $count);
            foreach (get_plugins_with_function('theme_pspv_frontpage_news') as $plugins) {
                foreach ($plugins as $function) {
                    $offered = (array) $function($count);
                    if (!empty($offered['cards'])) {
                        $news = ['url' => (string) ($offered['url'] ?? ''), 'cards' => array_values($offered['cards'])];
                        break 2;
                    }
                }
            }
        }

        return [
            'hasnews' => !empty($news['cards']),
            'news' => $news['cards'],
            'newsurl' => $news['url'],
            'showwhy' => $showwhy,
            'whytitle' => texts::get('whytitle'),
            'whylead' => texts::get('whylead'),
            'reasons' => $reasons,
            'title' => texts::get('herotitle'),
            'lead' => texts::get('herolead'),
            'searchurl' => (new \core\url('/course/search.php'))->out(false),
            'showdoors' => $guest,
            'workerurl' => self::worker_login_url()->out(false),
            'externalurl' => (new \core\url('/login/index.php'))->out(false),
            'dashboardurl' => (new \core\url('/my/'))->out(false),
            'catalogueurl' => catalogue::url()->out(false),
            'hascourses' => !empty($courses),
            'courses' => $courses,
        ];
    }

    /**
     * Where the "I am a worker" entry point leads: straight to the hospital's sign-in provider
     * when the access plugin has one configured, otherwise to the login page.
     *
     * @return \core\url
     */
    protected static function worker_login_url(): \core\url {
        if (class_exists(\local_pspvaccess\worker_policy::class)) {
            $issuerid = \local_pspvaccess\worker_policy::worker_issuer();
            $issuer = $issuerid ? \core\oauth2\issuer::get_record(['id' => $issuerid]) : false;
            if ($issuer && $issuer->get('enabled') && is_enabled_auth('oauth2')) {
                return new \core\url('/auth/oauth2/login.php', [
                    'id' => $issuerid,
                    'wantsurl' => (new \core\url('/my/'))->out(false),
                    'sesskey' => sesskey(),
                ]);
            }
        }
        return new \core\url('/login/index.php');
    }
}

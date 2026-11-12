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

/**
 * Core renderer: the logo and the institutional footer used by the navbar and footer templates.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_renderer extends \theme_boost\output\core_renderer {
    /** @var string Who developed the campus, as credited in the footer. */
    public const DEVELOPER = 'Octavi Rodríguez';

    /** @var string Where the credit in the footer links to. */
    public const DEVELOPER_URL = 'https://www.soportic.es';

    /**
     * URL of the logo: the one uploaded in the theme settings, or the hospital's logo shipped with the theme.
     *
     * @return string
     */
    public function pspv_logo_url(): string {
        $url = $this->page->theme->setting_file_url('logo', 'logo');
        return $url ? (string) $url : $this->image_url('logo', 'theme')->out(false);
    }

    /**
     * Alternative text of the logo: the name of the institution.
     *
     * @return string
     */
    public function pspv_logo_alt(): string {
        return self::owner();
    }

    /**
     * Title of the front page and of the login page panel.
     *
     * @return string
     */
    public function pspv_hero_title(): string {
        return \theme_pspv\local\texts::get('herotitle');
    }

    /**
     * Introduction of the front page and of the login page panel.
     *
     * @return string
     */
    public function pspv_hero_lead(): string {
        return \theme_pspv\local\texts::get('herolead');
    }

    /**
     * The institutional footer.
     *
     * @return string HTML.
     */
    public function pspv_footer(): string {
        $system = \core\context\system::instance();
        $contact = trim((string) get_config('theme_pspv', 'footercontact'));

        $links = [];
        $showpolicies = get_config('theme_pspv', 'footerpolicies');
        if (($showpolicies === false || $showpolicies) && class_exists(\tool_policy\api::class)) {
            foreach (\tool_policy\api::list_current_versions() as $policy) {
                $links[] = [
                    'url' => (new \core\url('/admin/tool/policy/view.php', ['versionid' => $policy->id]))->out(false),
                    'text' => format_string($policy->name, true, ['context' => $system]),
                ];
            }
        }

        return $this->render_from_template('theme_pspv/footer_institutional', [
            'logourl' => $this->pspv_logo_url(),
            'logoalt' => $this->pspv_logo_alt(),
            'homeurl' => (new \core\url('/'))->out(false),
            'description' => get_string('footer_description', 'theme_pspv'),
            'contact' => format_text($contact, FORMAT_HTML, ['context' => $system]),
            'hascontact' => $contact !== '',
            'catalogueurl' => \theme_pspv\local\catalogue::url()->out(false),
            'links' => $links,
            'haslinks' => !empty($links),
            'year' => userdate(time(), '%Y'),
            'owner' => self::owner(),
            'developer' => self::DEVELOPER,
            'developerurl' => self::DEVELOPER_URL,
        ]);
    }

    /**
     * Name of the institution that owns the site.
     *
     * @return string
     */
    protected static function owner(): string {
        $owner = trim((string) get_config('theme_pspv', 'owner'));
        return $owner !== '' ? format_string($owner) : 'Parc Sanitari Pere Virgili';
    }
}

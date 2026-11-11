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

namespace theme_pspv\local;

/**
 * Site settings the theme needs: it is the active theme, and visitors reach the public front page.
 *
 * A new installation of Moodle writes every default setting after the plugins are
 * installed, which undoes what db/install.php sets. So the same settings are
 * applied from two places: db/install.php, which is what runs when the theme
 * arrives by OTA on an existing site, and cli/setup.php, which the installation
 * scripts run once after a new installation.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_setup {
    /**
     * Applies the settings.
     */
    public static function apply(): void {
        set_config('theme', 'pspv');
        // New installations of Moodle 5.3 come with the site home switched off, and send
        // everybody to the dashboard, which makes visitors log in.
        set_config('enablemyhome', 1);
        set_config('defaulthomepage', HOMEPAGE_SITE);
        // The front page is built by the theme (hero, entry points and catalogue). The lists Moodle
        // would add under it are switched off; they can be switched on again in the site home settings.
        set_config('frontpage', '');
        set_config('frontpageloggedin', '');
        theme_reset_all_caches();
    }

    /**
     * Applies the settings the first time it is called on a site, and never again,
     * so later changes made by an administrator are respected.
     *
     * @return bool Whether the settings were applied.
     */
    public static function apply_once(): bool {
        if (get_config('theme_pspv', 'sitesetupdone')) {
            return false;
        }
        self::apply();
        set_config('sitesetupdone', 1, 'theme_pspv');
        return true;
    }
}

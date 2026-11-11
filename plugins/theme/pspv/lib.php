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
 * Theme callbacks.
 *
 * @package    theme_pspv
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The main SCSS: Boost's default preset. Everything of ours comes before (variables) or after (styles).
 *
 * @param theme_config $theme
 * @return string
 */
function theme_pspv_get_main_scss_content($theme) {
    global $CFG;

    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}

/**
 * SCSS variables set before Bootstrap is compiled: the Tabler tokens and the hospital's colours.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_pspv_get_pre_scss($theme) {
    global $CFG;

    $primary = \theme_pspv\local\colour::clean($theme->settings->brandcolor ?? '', '#0074d1');
    $secondary = \theme_pspv\local\colour::clean($theme->settings->secondarycolor ?? '', '#009de4');
    // Text in the brand colour sits on tinted backgrounds too: darken it until it is readable there.
    $text = \theme_pspv\local\colour::readable($primary, '#eef3f8');

    $scss = "\$primary: $primary;\n";
    $scss .= "\$pspv-secondary: $secondary;\n";
    $scss .= "\$pspv-primary-text: $text;\n";
    $scss .= file_get_contents($CFG->dirroot . '/theme/pspv/scss/pspv/_variables.scss');
    if (defined('BEHAT_SITE_RUNNING')) {
        $scss .= "\$behatsite: true;\n";
    }
    return $scss;
}

/**
 * SCSS added after Boost's: fonts, icons and the Tabler-style components.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_pspv_get_extra_scss($theme) {
    global $CFG;

    $scss = '';
    foreach (['fonts', 'icons', 'base', 'components', 'frontpage', 'footer'] as $partial) {
        $scss .= file_get_contents($CFG->dirroot . '/theme/pspv/scss/pspv/_' . $partial . '.scss') . "\n";
    }
    return $scss;
}

/**
 * Serves the files uploaded in the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool False when the file is not found.
 */
function theme_pspv_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel == CONTEXT_SYSTEM && $filearea === 'logo') {
        $theme = theme_config::load('pspv');
        if (!array_key_exists('cacheability', $options)) {
            $options['cacheability'] = 'public';
        }
        return $theme->setting_file_serve('logo', $args, $forcedownload, $options);
    }
    send_file_not_found();
}

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

namespace local_pspvnews\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form of a news article.
 *
 * @package    local_pspvnews
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class article_form extends \moodleform {
    /**
     * Options of the file picker of the cover.
     *
     * @return array
     */
    public static function cover_options(): array {
        return ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['web_image']];
    }

    #[\Override]
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('article_title', 'local_pspvnews'), ['size' => 70, 'maxlength' => 255]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('textarea', 'summary', get_string('article_summary', 'local_pspvnews'), ['rows' => 3, 'cols' => 70]);
        $mform->setType('summary', PARAM_TEXT);
        $mform->addHelpButton('summary', 'article_summary', 'local_pspvnews');

        $coverlabel = get_string('article_cover', 'local_pspvnews');
        $mform->addElement('filemanager', 'cover_filemanager', $coverlabel, null, self::cover_options());

        $mform->addElement('editor', 'body_editor', get_string('article_body', 'local_pspvnews'), ['rows' => 14], [
            'maxfiles' => 0, 'context' => \core\context\system::instance(),
        ]);
        $mform->setType('body_editor', PARAM_RAW);
        $mform->addRule('body_editor', null, 'required', null, 'client');

        $mform->addElement('advcheckbox', 'published', get_string('article_published', 'local_pspvnews'));
        $mform->addHelpButton('published', 'article_published', 'local_pspvnews');

        $mform->addElement('date_time_selector', 'timepublished', get_string('article_date', 'local_pspvnews'));
        $mform->addHelpButton('timepublished', 'article_date', 'local_pspvnews');

        $this->add_action_buttons();
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['title']) === '') {
            $errors['title'] = get_string('required');
        }
        if (trim(html_to_text($data['body_editor']['text'] ?? '', 0, false)) === '') {
            $errors['body_editor'] = get_string('required');
        }
        return $errors;
    }
}

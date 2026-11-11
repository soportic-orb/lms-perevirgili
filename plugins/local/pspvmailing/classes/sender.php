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

namespace local_pspvmailing;

/**
 * Builds the message of a campaign for a user and sends it by email.
 *
 * Emails go out with the image of the hospital and, when the campaign is commercial, with
 * the link to unsubscribe, both in the body and in the header mail programs show a button for.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sender {
    /** @var string[] What can be written in the subject and the body, and is replaced for each recipient. */
    public const PLACEHOLDERS = ['{{firstname}}', '{{lastname}}', '{{fullname}}', '{{sitename}}'];

    /**
     * Replaces the placeholders of a text.
     *
     * @param string $text
     * @param \stdClass $user
     * @param bool $html Whether the values go into HTML.
     * @return string
     */
    public static function personalise(string $text, \stdClass $user, bool $html): string {
        global $SITE;

        $values = [
            $user->firstname,
            $user->lastname,
            fullname($user),
            html_entity_decode(format_string($SITE->fullname), ENT_QUOTES | ENT_HTML5),
        ];
        return str_replace(self::PLACEHOLDERS, $html ? array_map('s', $values) : $values, $text);
    }

    /**
     * The message of a campaign for a user.
     *
     * @param \stdClass $campaign
     * @param \stdClass $user
     * @return array [string subject, string html, string plain text]
     */
    public static function build(\stdClass $campaign, \stdClass $user): array {
        global $OUTPUT, $SITE;

        $system = \core\context\system::instance();
        $subject = self::personalise($campaign->subject, $user, false);
        $body = self::personalise(format_text((string) $campaign->body, FORMAT_HTML, ['context' => $system]), $user, true);
        $footer = trim((string) get_config('local_pspvmailing', 'footer'));
        $primary = (string) get_config('theme_pspv', 'primarycolour');

        $html = $OUTPUT->render_from_template('local_pspvmailing/email', [
            'subject' => $subject,
            'body' => $body,
            'sitename' => format_string($SITE->fullname),
            'siteurl' => (new \core\url('/'))->out(false),
            'haslogo' => (bool) \core_component::get_plugin_directory('theme', 'pspv'),
            'logourl' => $OUTPUT->image_url('logo', 'theme_pspv')->out(false),
            'colour' => preg_match('/^#[0-9a-fA-F]{6}$/', $primary) ? $primary : '#0074d1',
            'footer' => format_text($footer, FORMAT_HTML, ['context' => $system]),
            'hasfooter' => $footer !== '',
            'commercial' => (bool) $campaign->commercial,
            'unsubscribeurl' => unsubscribe::url((int) $user->id)->out(false),
        ]);

        $text = html_to_text($body, 75, false);
        if ($footer !== '') {
            $text .= "\n\n" . html_to_text($footer, 75, false);
        }
        if ($campaign->commercial) {
            $unsubscribe = unsubscribe::url((int) $user->id)->out(false);
            $text .= "\n\n" . get_string('email_unsubscribe_text', 'local_pspvmailing', $unsubscribe);
        }
        return [$subject, $html, $text];
    }

    /**
     * Sends a campaign to a user.
     *
     * @param \stdClass $campaign
     * @param \stdClass $user
     * @param bool $test Whether it is a test, which is marked as such in the subject.
     * @return bool Whether the email was handed to the mail system.
     */
    public static function send(\stdClass $campaign, \stdClass $user, bool $test = false): bool {
        [$subject, $html, $text] = self::build($campaign, $user);
        if ($test) {
            $subject = get_string('email_test_prefix', 'local_pspvmailing') . ' ' . $subject;
        }
        $from = \core_user::get_noreply_user();
        if ($campaign->commercial) {
            // What makes mail programs offer their own "unsubscribe" button (RFC 8058).
            $from->customheaders = [
                'List-Unsubscribe: <' . unsubscribe::url((int) $user->id)->out(false) . '>',
                'List-Unsubscribe-Post: List-Unsubscribe=One-Click',
            ];
        }
        return (bool) email_to_user($user, $from, $subject, $text, $html);
    }
}

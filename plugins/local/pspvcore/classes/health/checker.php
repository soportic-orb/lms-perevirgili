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

namespace local_pspvcore\health;

use core\check\result;

/**
 * Runs the health checks of the campus and tells who has to know when one fails.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checker {
    /** @var string[] Statuses that mean something is broken. */
    public const FAILING = [result::ERROR, result::CRITICAL];

    /**
     * The checks: those of this plugin and the ones of Moodle about the task queue.
     *
     * @return \core\check\check[] By reference (component and id).
     */
    public static function checks(): array {
        $checks = [];
        foreach (self::own_checks() as $check) {
            $checks[$check->get_ref()] = $check;
        }
        foreach ([\tool_task\check\maxfaildelay::class, \tool_task\check\adhocqueue::class] as $class) {
            if (class_exists($class)) {
                $check = new $class();
                $check->set_component('tool_task');
                $checks[$check->get_ref()] = $check;
            }
        }
        return $checks;
    }

    /**
     * The checks this plugin adds to the status report of Moodle.
     *
     * @return \core\check\check[]
     */
    public static function own_checks(): array {
        $checks = [
            new \local_pspvcore\check\cron(),
            new \local_pspvcore\check\disk(),
            new \local_pspvcore\check\backup(),
            new \local_pspvcore\check\ota(),
            new \local_pspvcore\check\mail(),
        ];
        foreach ($checks as $check) {
            $check->set_component('local_pspvcore');
        }
        return $checks;
    }

    /**
     * Runs every check.
     *
     * @return array status ("ok", "warning" or "error"), time and checks (id, name, status and summary each).
     */
    public static function run(): array {
        $rows = [];
        $worst = 'ok';
        foreach (self::checks() as $id => $check) {
            try {
                $result = $check->get_result();
                $status = $result->get_status();
                $summary = html_to_text($result->get_summary(), 0, false);
            } catch (\Throwable $e) {
                $status = result::ERROR;
                $summary = $e->getMessage();
            }
            if (in_array($status, self::FAILING, true)) {
                $worst = 'error';
            } else if ($status === result::WARNING && $worst === 'ok') {
                $worst = 'warning';
            }
            $rows[] = ['id' => $id, 'name' => $check->get_name(), 'status' => $status, 'summary' => trim($summary)];
        }
        return ['status' => $worst, 'time' => time(), 'checks' => $rows];
    }

    /**
     * Tells the administrators when the set of failing checks changes: once when something
     * breaks, and once when everything works again. Nothing is sent while nothing changes.
     *
     * @param array|null $report As run() returns it; it is run when not given.
     * @return string "alert", "recovered" or "" when nothing was sent.
     */
    public static function notify(?array $report = null): string {
        $report ??= self::run();
        $failing = array_values(array_filter($report['checks'], fn($row) => in_array($row['status'], self::FAILING, true)));
        $ids = array_column($failing, 'id');
        sort($ids);
        $signature = implode(',', $ids);
        $previous = (string) get_config('local_pspvcore', 'healthalerted');
        if ($signature === $previous) {
            return '';
        }
        set_config('healthalerted', $signature, 'local_pspvcore');

        $kind = $failing ? 'alert' : 'recovered';
        $lines = array_map(fn($row) => '- ' . $row['name'] . ': ' . $row['summary'], $failing);
        $site = html_entity_decode(format_string(get_site()->fullname), ENT_QUOTES | ENT_HTML5);
        $url = new \core\url('/report/status/index.php');
        $body = get_string('health_' . $kind . '_body', 'local_pspvcore', (object) [
            'site' => $site,
            'checks' => implode("\n", $lines),
            'url' => $url->out(false),
        ]);
        foreach (get_admins() as $admin) {
            $message = new \core\message\message();
            $message->component = 'local_pspvcore';
            $message->name = 'healthalert';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $admin;
            $message->subject = get_string('health_' . $kind . '_subject', 'local_pspvcore', $site);
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = nl2br(s($body));
            $message->smallmessage = $message->subject;
            $message->notification = 1;
            $message->contexturl = $url->out(false);
            $message->contexturlname = get_string('health_report', 'local_pspvcore');
            message_send($message);
        }
        return $kind;
    }
}

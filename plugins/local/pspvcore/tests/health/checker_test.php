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
use local_pspvcore\check\backup;
use local_pspvcore\check\cron;
use local_pspvcore\check\disk;
use local_pspvcore\check\mail;
use local_pspvcore\check\ota;

/**
 * Tests of the health checks and of the notifications to the administrators.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(checker::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_pspvcore\check\backup::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_pspvcore\check\cron::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_pspvcore\check\disk::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_pspvcore\check\mail::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_pspvcore\check\ota::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_pspvcore\task\health_alert::class)]
final class checker_test extends \advanced_testcase {
    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Leaves every check of the plugin passing.
     *
     * @return string The backup folder.
     */
    protected function healthy(): string {
        global $CFG;

        set_config('lastcronstart', time(), 'tool_task');
        $dir = make_request_directory();
        file_put_contents($dir . '/' . backup::MARKER, json_encode(['time' => time(), 'name' => 'x']));
        set_config('backupdir', $dir, 'local_pspvcore');
        set_config('otapublickey', 'key', 'local_pspvcore');
        set_config('otalastcheck', time(), 'local_pspvcore');
        set_config('otahistory', '', 'local_pspvcore');
        set_config('otaavailable', '', 'local_pspvcore');
        $CFG->noemailever = false;
        $CFG->smtphosts = 'smtp.example.com';
        unset($CFG->divertallemailsto);
        return $dir;
    }

    /**
     * Status of each check of the plugin.
     *
     * @return string[] By id.
     */
    protected function statuses(): array {
        return array_column(checker::run()['checks'], 'status', 'id');
    }

    public function test_cron_that_has_stopped_is_an_error(): void {
        set_config('lastcronstart', 0, 'tool_task');
        $this->assertSame(result::ERROR, (new cron())->get_result()->get_status());
        set_config('lastcronstart', time() - cron::LIMIT - MINSECS, 'tool_task');
        $this->assertSame(result::ERROR, (new cron())->get_result()->get_status());
        set_config('lastcronstart', time() - 5 * MINSECS, 'tool_task');
        $this->assertSame(result::OK, (new cron())->get_result()->get_status());
    }

    public function test_disk_warns_before_it_is_full(): void {
        $this->assertSame(result::OK, disk::evaluate(50, 100)->get_status());
        $this->assertSame(result::WARNING, disk::evaluate(10, 100)->get_status());
        $this->assertSame(result::ERROR, disk::evaluate(4, 100)->get_status());
        // On a real disk the result is one of those.
        $this->assertContains((new disk())->get_result()->get_status(), [result::OK, result::WARNING, result::ERROR]);
    }

    public function test_backups_are_watched_only_when_a_folder_is_set(): void {
        set_config('backupdir', '', 'local_pspvcore');
        $this->assertSame(result::NA, (new backup())->get_result()->get_status());

        $dir = make_request_directory();
        set_config('backupdir', $dir . '/', 'local_pspvcore');
        $this->assertSame(result::ERROR, (new backup())->get_result()->get_status());

        $marker = $dir . '/' . backup::MARKER;
        file_put_contents($marker, json_encode(['time' => time() - HOURSECS]));
        $this->assertSame(result::OK, (new backup())->get_result()->get_status());
        file_put_contents($marker, json_encode(['time' => time() - backup::LIMIT - HOURSECS]));
        $this->assertSame(result::ERROR, (new backup())->get_result()->get_status());
        file_put_contents($marker, 'not json');
        $this->assertSame(result::ERROR, (new backup())->get_result()->get_status());
    }

    public function test_ota_reports_what_stops_or_awaits_an_update(): void {
        $this->healthy();
        $this->assertSame(result::OK, (new ota())->get_result()->get_status());

        set_config('otaavailable', json_encode(['version' => '20270101.0900-abc']), 'local_pspvcore');
        $result = (new ota())->get_result();
        $this->assertSame(result::INFO, $result->get_status());
        $this->assertStringContainsString('20270101.0900-abc', $result->get_summary());

        set_config('otahistory', json_encode([['version' => 'a', 'action' => 'rolledback', 'time' => time()]]), 'local_pspvcore');
        $this->assertSame(result::WARNING, (new ota())->get_result()->get_status());
        set_config('otahistory', json_encode([['version' => 'a', 'action' => 'applied', 'time' => time()]]), 'local_pspvcore');
        $this->assertSame(result::INFO, (new ota())->get_result()->get_status());

        set_config('otalastcheck', time() - ota::STALE - HOURSECS, 'local_pspvcore');
        $this->assertSame(result::WARNING, (new ota())->get_result()->get_status());
        set_config('otapublickey', '', 'local_pspvcore');
        $this->assertSame(result::WARNING, (new ota())->get_result()->get_status());
    }

    public function test_mail_that_is_off_or_diverted_is_reported(): void {
        global $CFG;

        $this->healthy();
        $this->assertSame(result::OK, (new mail())->get_result()->get_status());
        $CFG->smtphosts = '';
        $this->assertSame(result::WARNING, (new mail())->get_result()->get_status());
        $CFG->divertallemailsto = 'dev@example.com';
        $this->assertSame(result::WARNING, (new mail())->get_result()->get_status());
        $CFG->noemailever = true;
        $this->assertSame(result::ERROR, (new mail())->get_result()->get_status());
    }

    public function test_run_gives_the_worst_status_and_one_line_per_check(): void {
        global $CFG;

        $this->healthy();
        $statuses = $this->statuses();
        foreach (['cron', 'disk', 'backup', 'ota', 'mail'] as $name) {
            $this->assertArrayHasKey('local_pspvcore_' . $name, $statuses);
        }
        $this->assertSame(result::OK, $statuses['local_pspvcore_cron']);
        $own = count(checker::own_checks());
        $this->assertSame(5, $own);
        $this->assertGreaterThanOrEqual($own, count($statuses));

        // A warning is reported as such; the free space of the machine the tests run on is not ours to assert.
        $CFG->smtphosts = '';
        $statuses = $this->statuses();
        $this->assertSame(result::WARNING, $statuses['local_pspvcore_mail']);
        $this->assertContains(checker::run()['status'], ['warning', 'error']);

        set_config('lastcronstart', time() - DAYSECS, 'tool_task');
        $report = checker::run();
        $this->assertSame('error', $report['status']);
        $rows = array_column($report['checks'], null, 'id');
        $this->assertSame(result::ERROR, $rows['local_pspvcore_cron']['status']);
        $this->assertNotSame('', $rows['local_pspvcore_cron']['summary']);
        $this->assertNotSame('', $rows['local_pspvcore_cron']['name']);
    }

    public function test_administrators_are_told_once_when_something_breaks_and_once_when_it_is_fixed(): void {
        $this->healthy();
        $ok = ['status' => 'ok', 'time' => time(), 'checks' => [
            ['id' => 'local_pspvcore_cron', 'name' => 'Cron', 'status' => result::OK, 'summary' => 'fine'],
        ]];
        $broken = ['status' => 'error', 'time' => time(), 'checks' => [
            ['id' => 'local_pspvcore_cron', 'name' => 'Cron', 'status' => result::ERROR, 'summary' => 'Cron stopped'],
            ['id' => 'local_pspvcore_mail', 'name' => 'Mail', 'status' => result::WARNING, 'summary' => 'No SMTP'],
        ]];
        $worse = $broken;
        $worse['checks'][] = ['id' => 'local_pspvcore_disk', 'name' => 'Disk', 'status' => result::CRITICAL, 'summary' => 'Full'];
        $sink = $this->redirectMessages();

        // Nothing to say while everything works.
        $this->assertSame('', checker::notify($ok));
        $this->assertSame(0, $sink->count());

        $this->assertSame('alert', checker::notify($broken));
        $messages = $sink->get_messages();
        $this->assertCount(count(get_admins()), $messages);
        $this->assertSame('local_pspvcore', $messages[0]->component);
        $this->assertSame('healthalert', $messages[0]->eventtype);
        $this->assertStringContainsString('Cron stopped', $messages[0]->fullmessage);
        // Warnings do not trigger a notification and are not listed in it.
        $this->assertStringNotContainsString('No SMTP', $messages[0]->fullmessage);
        $sink->clear();

        // The same failure is not repeated; a new one is told.
        $this->assertSame('', checker::notify($broken));
        $this->assertSame(0, $sink->count());
        $this->assertSame('alert', checker::notify($worse));
        $this->assertStringContainsString('Full', $sink->get_messages()[0]->fullmessage);
        $sink->clear();

        $this->assertSame('recovered', checker::notify($ok));
        $this->assertCount(count(get_admins()), $sink->get_messages());
        $sink->clear();
        $this->assertSame('', checker::notify($ok));

        // The task runs the real checks.
        set_config('lastcronstart', time() - DAYSECS, 'tool_task');
        ob_start();
        (new \local_pspvcore\task\health_alert())->execute();
        $output = ob_get_clean();
        $this->assertStringContainsString('alert', $output);
        $this->assertGreaterThan(0, $sink->count());
    }
}

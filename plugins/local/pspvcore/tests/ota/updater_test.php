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

namespace local_pspvcore\ota;

use local_pspvcore\event\ota_applied;
use local_pspvcore\event\ota_rolled_back;
use local_pspvcore\tests\fake_release_source;

/**
 * Tests of the OTA updater.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(updater::class)]
final class updater_test extends \advanced_testcase {
    /** @var string Folder that plays the role of Moodle's public/. */
    protected string $public;

    /** @var string Folder that holds the backups. */
    protected string $backups;

    /** @var fake_release_source Source of the releases. */
    protected fake_release_source $source;

    /** @var updater The updater under test. */
    protected updater $updater;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $base = make_request_directory();
        $this->public = $base . '/public';
        $this->backups = $base . '/backups';
        mkdir($base . '/releases');
        mkdir($this->public . '/local/pspvdemo', 0777, true);
        file_put_contents(
            $this->public . '/local/pspvdemo/version.php',
            fake_release_source::version_file('local_pspvdemo', 2026010100),
        );
        file_put_contents($this->public . '/local/pspvdemo/old.txt', 'old');

        $this->source = new fake_release_source($base . '/releases');
        $this->updater = new updater(
            $this->source,
            $this->source->public_key(),
            $this->public,
            $base . '/staging',
            $this->backups,
        );
    }

    /**
     * Files of a release of the demo plugin.
     *
     * @param int $version Plugin version.
     * @return string[]
     */
    protected function demo_files(int $version = 2026020100): array {
        return [
            'local/pspvdemo/version.php' => fake_release_source::version_file('local_pspvdemo', $version),
            'local/pspvdemo/classes/new.php' => '<?php // New.',
        ];
    }

    /**
     * Asserts that the demo plugin on disk is still the one created in setUp().
     */
    protected function assert_untouched(): void {
        $this->assertFileExists($this->public . '/local/pspvdemo/old.txt');
        $this->assertFileDoesNotExist($this->public . '/local/pspvdemo/classes/new.php');
        $this->assertNull($this->updater->current_version());
        $this->assertFalse($this->updater->has_backup());
        $this->assertEmpty(glob($this->public . '/local/*.ota*'));
    }

    public function test_check_remembers_only_newer_releases(): void {
        $this->assertNull($this->updater->check());
        $this->assertNull($this->updater->available());
        $this->assertGreaterThan(0, $this->updater->last_check());

        $this->source->publish('20261111.1000-aaaaaaa', $this->demo_files(), ['local/pspvdemo']);
        $this->assertSame('20261111.1000-aaaaaaa', $this->updater->check()['version']);
        $this->assertSame('20261111.1000-aaaaaaa', $this->updater->available()['version']);

        set_config('otaversion', '20261111.1000-aaaaaaa', 'local_pspvcore');
        $this->assertNull($this->updater->check());
        $this->assertNull($this->updater->available());

        set_config('otaversion', '20261112.0900-bbbbbbb', 'local_pspvcore');
        $this->assertNull($this->updater->check());
    }

    public function test_requirements_report_a_missing_public_key(): void {
        $this->assertSame([], $this->updater->requirements());

        $updater = new updater($this->source, 'not-a-key', $this->public, dirname($this->public) . '/staging', $this->backups);
        $this->assertFalse($updater->is_configured());
        $this->assertSame([get_string('ota_req_publickey', 'local_pspvcore')], $updater->requirements());

        $this->source->publish('20261111.1000-aaaaaaa', $this->demo_files(), ['local/pspvdemo']);
        try {
            $updater->apply();
            $this->fail('An update was applied without a public key.');
        } catch (\moodle_exception $e) {
            $this->assertSame('ota_err_requirements', $e->errorcode);
        }
        $this->assert_untouched();
    }

    public function test_apply_replaces_the_plugin_and_backs_it_up(): void {
        $this->source->publish('20261111.1000-aaaaaaa', $this->demo_files(), ['local/pspvdemo']);
        $sink = $this->redirectEvents();

        $result = $this->updater->apply();

        $this->assertSame('20261111.1000-aaaaaaa', $result['version']);
        $this->assertNull($result['previous']);
        $this->assertSame(['local_pspvdemo' => 2026020100], $result['plugins']);
        $this->assertTrue($result['needsupgrade']);

        // The folder is replaced, not merged.
        $this->assertFileExists($this->public . '/local/pspvdemo/classes/new.php');
        $this->assertFileDoesNotExist($this->public . '/local/pspvdemo/old.txt');
        $this->assertSame('20261111.1000-aaaaaaa', $this->updater->current_version());
        $this->assertNull($this->updater->available());
        $this->assertTrue($this->updater->has_backup());
        $this->assertSame('applied', $this->updater->history()[0]['action']);
        $this->assertCount(1, glob($this->backups . '/*/files/local/pspvdemo/old.txt'));

        $events = array_values(array_filter($sink->get_events(), fn($event) => $event instanceof ota_applied));
        $this->assertCount(1, $events);
        $this->assertSame('20261111.1000-aaaaaaa', $events[0]->other['version']);

        // Nothing newer: a second apply is refused.
        try {
            $this->updater->apply();
            $this->fail('The same version was applied twice.');
        } catch (\moodle_exception $e) {
            $this->assertSame('ota_err_nothing', $e->errorcode);
        }
    }

    public function test_apply_installs_a_new_plugin(): void {
        $files = $this->demo_files() + [
            'theme/pspvnew/version.php' => fake_release_source::version_file('theme_pspvnew', 2026020100),
        ];
        mkdir($this->public . '/theme');
        $this->source->publish('20261111.1000-aaaaaaa', $files, ['local/pspvdemo', 'theme/pspvnew']);

        $this->updater->apply();
        $this->assertFileExists($this->public . '/theme/pspvnew/version.php');

        // Rolling back removes the plugin that did not exist before.
        $this->updater->rollback();
        $this->assertDirectoryDoesNotExist($this->public . '/theme/pspvnew');
        $this->assertFileExists($this->public . '/local/pspvdemo/old.txt');
    }

    /**
     * Releases that must be refused, leaving the site as it was.
     *
     * @return array[]
     */
    public static function rejected_release_provider(): array {
        $version = fake_release_source::version_file('local_pspvdemo', 2026020100);
        $files = ['local/pspvdemo/version.php' => $version];
        return [
            'signed with another key' => [$files, ['local/pspvdemo'], ['badsignature' => true], 'ota_err_signature'],
            'bundle changed after signing' => [$files, ['local/pspvdemo'], ['badhash' => true], 'ota_err_hash'],
            'old manifest under a new tag' => [
                $files, ['local/pspvdemo'], ['manifestversion' => '20260101.0000-0000000'], 'ota_err_replay',
            ],
            'root in Moodle core' => [['lib/version.php' => $version], ['lib'], [], 'ota_err_root'],
            'root of a community plugin' => [['mod/forum/version.php' => $version], ['mod/forum'], [], 'ota_err_root'],
            'root with a traversal' => [$files, ['local/../pspvdemo'], [], 'ota_err_root'],
            'no roots' => [$files, [], [], 'ota_err_manifest'],
            'file outside the roots' => [$files + ['config.php' => '<?php'], ['local/pspvdemo'], [], 'ota_err_path'],
            'file with a traversal' => [
                $files + ['local/pspvdemo/../../evil.php' => '<?php'], ['local/pspvdemo'], [], 'ota_err_path',
            ],
            'no version.php' => [['local/pspvdemo/lib.php' => '<?php'], ['local/pspvdemo'], [], 'ota_err_version'],
            'version.php of another plugin' => [
                ['local/pspvdemo/version.php' => fake_release_source::version_file('local_other', 2026020100)],
                ['local/pspvdemo'], [], 'ota_err_version',
            ],
        ];
    }

    /**
     * A release that is not acceptable changes nothing.
     *
     * @param string[] $files Zip entry name => content.
     * @param string[] $roots Roots declared in the manifest.
     * @param array $tamper What to break in the release.
     * @param string $errorcode Expected error.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('rejected_release_provider')]
    public function test_apply_rejects(array $files, array $roots, array $tamper, string $errorcode): void {
        $this->source->publish('20261111.1000-aaaaaaa', $files, $roots, $tamper);
        try {
            $this->updater->apply();
            $this->fail('The release was applied.');
        } catch (\moodle_exception $e) {
            $this->assertSame($errorcode, $e->errorcode);
        }
        $this->assert_untouched();
        $this->assertFileDoesNotExist(dirname($this->public) . '/evil.php');
    }

    public function test_apply_rejects_a_downgrade(): void {
        set_config('version', 2026030100, 'local_pspvdemo');
        $this->source->publish('20261111.1000-aaaaaaa', $this->demo_files(2026020100), ['local/pspvdemo']);
        try {
            $this->updater->apply();
            $this->fail('An older plugin version was applied.');
        } catch (\moodle_exception $e) {
            $this->assertSame('ota_err_downgrade', $e->errorcode);
        }
        $this->assert_untouched();
    }

    public function test_apply_reports_when_no_upgrade_is_needed(): void {
        set_config('version', 2026020100, 'local_pspvdemo');
        $this->source->publish('20261111.1000-aaaaaaa', $this->demo_files(2026020100), ['local/pspvdemo']);
        $this->assertFalse($this->updater->apply()['needsupgrade']);
    }

    public function test_rollback_restores_the_previous_code(): void {
        set_config('version', 2026010100, 'local_pspvdemo');
        $this->source->publish('20261111.1000-aaaaaaa', $this->demo_files(), ['local/pspvdemo']);
        $this->updater->apply();
        $sink = $this->redirectEvents();

        $this->assertNull($this->updater->rollback());

        $this->assertFileExists($this->public . '/local/pspvdemo/old.txt');
        $this->assertFileDoesNotExist($this->public . '/local/pspvdemo/classes/new.php');
        $this->assertNull($this->updater->current_version());
        $this->assertFalse($this->updater->has_backup());
        $this->assertSame('rolledback', $this->updater->history()[0]['action']);
        $events = array_filter($sink->get_events(), fn($event) => $event instanceof ota_rolled_back);
        $this->assertCount(1, $events);

        // The release is offered again.
        $this->assertSame('20261111.1000-aaaaaaa', $this->updater->check()['version']);
    }

    public function test_rollback_is_refused_once_the_database_was_upgraded(): void {
        set_config('version', 2026010100, 'local_pspvdemo');
        $this->source->publish('20261111.1000-aaaaaaa', $this->demo_files(), ['local/pspvdemo']);
        $this->updater->apply();
        // Moodle's upgrade ran.
        set_config('version', 2026020100, 'local_pspvdemo');

        try {
            $this->updater->rollback();
            $this->fail('Code older than the database was restored.');
        } catch (\moodle_exception $e) {
            $this->assertSame('ota_err_rollback_upgraded', $e->errorcode);
        }
        $this->assertFileExists($this->public . '/local/pspvdemo/classes/new.php');
        $this->assertTrue($this->updater->has_backup());
    }

    public function test_rollback_without_backup(): void {
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage(get_string('ota_err_nobackup', 'local_pspvcore'));
        $this->updater->rollback();
    }

    public function test_only_the_last_backups_are_kept(): void {
        foreach (['1000', '1001', '1002', '1003', '1004'] as $i => $time) {
            $this->source->publish("20261111.$time-aaaaaaa", $this->demo_files(2026020100 + $i), ['local/pspvdemo']);
            $this->updater->apply();
            $this->waitForSecond();
        }
        $this->assertCount(3, glob($this->backups . '/*', GLOB_ONLYDIR));
        $this->assertCount(5, $this->updater->history());
    }
}

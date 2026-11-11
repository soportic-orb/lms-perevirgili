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

/**
 * Over-the-air updates of the site's own plugins.
 *
 * A release is three files published by the repository's "OTA release" workflow:
 * a zip with the plugin folders, a manifest with its sha256 and an Ed25519
 * signature of the manifest. This class downloads them, checks the signature and
 * the hash, backs up the folders it is about to replace and swaps the new ones in
 * with renames, undoing the swap if any step fails. The database upgrade is then
 * done by Moodle's own upgrade.
 *
 * Only folders of "pspv" plugins can be replaced: Moodle core, community plugins,
 * config.php and moodledata are never touched.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class updater {
    /** @var string Prefix of the git tags of OTA releases. */
    public const TAG_PREFIX = 'ota-';

    /** @var string Release file with the plugin folders. */
    public const BUNDLE = 'ota-bundle.zip';

    /** @var string Release file describing the bundle. */
    public const MANIFEST = 'ota-manifest.json';

    /** @var string Release file with the signature of the manifest. */
    public const SIGNATURE = 'ota-manifest.json.sig';

    /** @var int Manifest format this code understands. */
    protected const FORMAT = 1;

    /** @var int Backups kept on disk. */
    protected const KEEP_BACKUPS = 3;

    /** @var int Entries kept in the history. */
    protected const KEEP_HISTORY = 20;

    /** @var string[] Plugin type folder (relative to public/) => frankenstyle prefix. */
    protected const TYPES = [
        'local' => 'local',
        'theme' => 'theme',
        'report' => 'report',
        'blocks' => 'block',
        'enrol' => 'enrol',
        'auth' => 'auth',
        'mod' => 'mod',
        'payment/gateway' => 'paygw',
        'admin/tool' => 'tool',
    ];

    /** @var release_source Where releases come from. */
    protected release_source $source;

    /** @var string Ed25519 public key, base64. */
    protected string $publickey;

    /** @var string Folder that holds the plugin type folders (Moodle's public/). */
    protected string $publicroot;

    /** @var string Scratch folder on the same filesystem as $publicroot. */
    protected string $stagingroot;

    /** @var string Folder that holds the backups (inside moodledata). */
    protected string $backuproot;

    /** @var bool Whether the running site's own code is the one being replaced. */
    protected bool $live;

    /**
     * Constructor. Every argument defaults to the running site; tests pass their own.
     *
     * @param release_source|null $source Where releases come from.
     * @param string|null $publickey Ed25519 public key, base64.
     * @param string|null $publicroot Folder that holds the plugin type folders.
     * @param string|null $stagingroot Scratch folder on the same filesystem as $publicroot.
     * @param string|null $backuproot Folder that holds the backups.
     */
    public function __construct(
        ?release_source $source = null,
        ?string $publickey = null,
        ?string $publicroot = null,
        ?string $stagingroot = null,
        ?string $backuproot = null,
    ) {
        global $CFG;

        $this->source = $source ?? new github_release_source(
            (string) get_config('local_pspvcore', 'otarepository'),
            (string) get_config('local_pspvcore', 'otagithubtoken'),
        );
        $this->publickey = trim($publickey ?? (string) get_config('local_pspvcore', 'otapublickey'));
        $this->live = $publicroot === null;
        $this->publicroot = rtrim($publicroot ?? $CFG->dirroot, '/');
        $this->stagingroot = rtrim($stagingroot ?? $CFG->root . '/.pspv-ota', '/');
        $this->backuproot = rtrim($backuproot ?? $CFG->dataroot . '/local_pspvcore/ota/backups', '/');
    }

    /**
     * Version of the last update applied, or null if none has been applied yet.
     *
     * @return string|null
     */
    public function current_version(): ?string {
        $version = get_config('local_pspvcore', 'otaversion');
        return ($version === false || $version === '') ? null : (string) $version;
    }

    /**
     * The newer release found by the last check, if any.
     *
     * @return array|null See release_source::latest_release().
     */
    public function available(): ?array {
        $release = json_decode((string) get_config('local_pspvcore', 'otaavailable'), true);
        return is_array($release) && !empty($release['version']) ? $release : null;
    }

    /**
     * Time of the last successful check.
     *
     * @return int Timestamp, 0 if never.
     */
    public function last_check(): int {
        return (int) get_config('local_pspvcore', 'otalastcheck');
    }

    /**
     * Updates applied and rolled back, newest first.
     *
     * @return array[] Each with version, action ("applied" or "rolledback") and time.
     */
    public function history(): array {
        $history = json_decode((string) get_config('local_pspvcore', 'otahistory'), true);
        return is_array($history) ? $history : [];
    }

    /**
     * Whether a valid public key is configured.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return $this->public_key() !== null;
    }

    /**
     * What stops updates from being applied on this server.
     *
     * @return string[] Localised problems; empty when everything is ready.
     */
    public function requirements(): array {
        $problems = [];
        if (!function_exists('sodium_crypto_sign_verify_detached')) {
            $problems[] = get_string('ota_req_sodium', 'local_pspvcore');
        }
        if (!class_exists(\ZipArchive::class)) {
            $problems[] = get_string('ota_req_zip', 'local_pspvcore');
        }
        if (!$this->is_configured()) {
            $problems[] = get_string('ota_req_publickey', 'local_pspvcore');
        }
        $stagingparent = is_dir($this->stagingroot) ? $this->stagingroot : dirname($this->stagingroot);
        if (!is_writable($this->publicroot . '/local') || !is_writable($stagingparent)) {
            $problems[] = get_string('ota_req_writable', 'local_pspvcore');
        }
        return $problems;
    }

    /**
     * Asks the release source for a newer version and remembers the answer.
     *
     * @return array|null The newer release, or null when the site is up to date.
     */
    public function check(): ?array {
        $release = $this->source->latest_release();
        $current = $this->current_version();
        if ($release && $current !== null && strcmp($release['version'], $current) <= 0) {
            $release = null;
        }
        set_config('otaavailable', $release ? json_encode($release) : '', 'local_pspvcore');
        set_config('otalastcheck', time(), 'local_pspvcore');
        return $release;
    }

    /**
     * Downloads, verifies and installs the newest release.
     *
     * @return array version (string), previous (string|null), plugins (component => version) and
     *               needsupgrade (bool: Moodle's upgrade has to run next).
     * @throws \moodle_exception When there is nothing to apply or the release is not acceptable.
     */
    public function apply(): array {
        $problems = $this->requirements();
        if ($problems) {
            throw new \moodle_exception('ota_err_requirements', 'local_pspvcore', '', implode(' ', $problems));
        }
        $release = $this->check();
        if (!$release) {
            throw new \moodle_exception('ota_err_nothing', 'local_pspvcore');
        }

        return $this->locked(function () use ($release): array {
            $work = make_request_directory();
            foreach ([self::MANIFEST, self::SIGNATURE, self::BUNDLE] as $asset) {
                $this->source->download($release, $asset, $work . '/' . $asset);
            }
            $manifest = $this->verify($work, $release['version']);
            $roots = $manifest['roots'];
            $previous = $this->current_version();

            $staging = $this->staging_dir('new');
            try {
                $plugins = $this->unpack($work . '/' . self::BUNDLE, $roots, $staging);
                $this->backup($roots, $previous);
                $this->swap($staging, $roots);
            } finally {
                remove_dir($staging);
            }

            $needsupgrade = false;
            $versions = [];
            foreach ($plugins as $plugin) {
                $versions[$plugin['component']] = $plugin['version'];
                $needsupgrade = $needsupgrade || $this->installed_version($plugin['component']) !== $plugin['version'];
            }

            set_config('otaversion', $release['version'], 'local_pspvcore');
            set_config('otaavailable', '', 'local_pspvcore');
            $this->remember($release['version'], 'applied');
            $this->prune_backups();
            $this->refresh_runtime();

            ota_applied::create([
                'context' => \core\context\system::instance(),
                'other' => ['version' => $release['version'], 'previous' => (string) $previous],
            ])->trigger();

            return [
                'version' => $release['version'],
                'previous' => $previous,
                'plugins' => $versions,
                'needsupgrade' => $needsupgrade,
            ];
        });
    }

    /**
     * Whether there is a backup to go back to.
     *
     * @return bool
     */
    public function has_backup(): bool {
        return $this->latest_backup() !== null;
    }

    /**
     * Puts back the folders saved before the last update.
     *
     * Code can only go back while the database has not moved forward: once Moodle
     * has upgraded a plugin, older code would be refused as a downgrade.
     *
     * @return string|null The version the site is back on (null: the state before any update).
     * @throws \moodle_exception When there is no backup or the database was already upgraded.
     */
    public function rollback(): ?string {
        return $this->locked(function (): ?string {
            $backup = $this->latest_backup();
            if ($backup === null) {
                throw new \moodle_exception('ota_err_nobackup', 'local_pspvcore');
            }
            $meta = json_decode((string) file_get_contents($backup . '/backup.json'), true);
            $roots = $this->validate_roots($meta['roots'] ?? null);

            foreach ($roots as $root) {
                $component = $this->component($root);
                $installed = $this->installed_version($component);
                $saved = $backup . '/files/' . $root;
                $savedversion = is_dir($saved) ? $this->read_version($saved, $component, $root) : null;
                if ($installed !== null && ($savedversion === null || $installed > $savedversion)) {
                    throw new \moodle_exception('ota_err_rollback_upgraded', 'local_pspvcore', '', $component);
                }
            }

            $staging = $this->staging_dir('new');
            try {
                foreach ($roots as $root) {
                    if (is_dir($backup . '/files/' . $root)) {
                        $this->copy_dir($backup . '/files/' . $root, $staging . '/' . $root);
                    }
                }
                $this->swap($staging, $roots);
            } finally {
                remove_dir($staging);
            }
            remove_dir($backup);

            $from = (string) $this->current_version();
            $version = isset($meta['version']) && $meta['version'] !== '' ? (string) $meta['version'] : null;
            set_config('otaversion', (string) $version, 'local_pspvcore');
            $this->remember((string) $version, 'rolledback');
            $this->refresh_runtime();

            ota_rolled_back::create([
                'context' => \core\context\system::instance(),
                'other' => ['version' => (string) $version, 'previous' => $from],
            ])->trigger();

            return $version;
        });
    }

    /**
     * Checks the signature of the manifest, the manifest itself and the hash of the bundle.
     *
     * @param string $work Folder with the three downloaded files.
     * @param string $version Version announced by the release.
     * @return array The manifest, with validated roots.
     */
    protected function verify(string $work, string $version): array {
        $raw = (string) file_get_contents($work . '/' . self::MANIFEST);
        $signature = base64_decode(trim((string) file_get_contents($work . '/' . self::SIGNATURE)), true);
        $key = $this->public_key();
        if (
            $key === null
            || $signature === false
            || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || !sodium_crypto_sign_verify_detached($signature, $raw, $key)
        ) {
            throw new \moodle_exception('ota_err_signature', 'local_pspvcore');
        }

        $manifest = json_decode($raw, true);
        if (!is_array($manifest) || (int) ($manifest['format'] ?? 0) !== self::FORMAT) {
            throw new \moodle_exception('ota_err_manifest', 'local_pspvcore');
        }
        // A correctly signed but older bundle republished under a newer tag is not an update.
        if ((string) ($manifest['version'] ?? '') !== $version) {
            throw new \moodle_exception('ota_err_replay', 'local_pspvcore');
        }
        if (!hash_equals((string) ($manifest['sha256'] ?? ''), (string) hash_file('sha256', $work . '/' . self::BUNDLE))) {
            throw new \moodle_exception('ota_err_hash', 'local_pspvcore');
        }
        $manifest['roots'] = $this->validate_roots($manifest['roots'] ?? null);
        return $manifest;
    }

    /**
     * Makes sure every root is the folder of one of our own plugins.
     *
     * @param mixed $roots Roots as read from a manifest or a backup.
     * @return string[]
     */
    protected function validate_roots($roots): array {
        if (!is_array($roots) || !$roots) {
            throw new \moodle_exception('ota_err_manifest', 'local_pspvcore');
        }
        foreach ($roots as $root) {
            if (!is_string($root) || $this->component($root) === null) {
                throw new \moodle_exception('ota_err_root', 'local_pspvcore', '', is_string($root) ? $root : '?');
            }
        }
        return array_values(array_unique($roots));
    }

    /**
     * Frankenstyle name of the plugin that lives in a root, or null if the root is not allowed.
     *
     * @param string $root Folder relative to public/, such as "local/pspvcore".
     * @return string|null
     */
    protected function component(string $root): ?string {
        $slash = strrpos($root, '/');
        if ($slash === false) {
            return null;
        }
        $type = substr($root, 0, $slash);
        $name = substr($root, $slash + 1);
        if (!isset(self::TYPES[$type]) || !preg_match('/^pspv[a-z0-9]*$/', $name)) {
            return null;
        }
        return self::TYPES[$type] . '_' . $name;
    }

    /**
     * Extracts the bundle, refusing any file outside the roots.
     *
     * @param string $zippath The bundle.
     * @param string[] $roots Validated roots.
     * @param string $staging Empty folder to extract into.
     * @return array[] One entry per root, with root, component and version.
     */
    protected function unpack(string $zippath, array $roots, string $staging): array {
        global $CFG;

        $zip = new \ZipArchive();
        if ($zip->open($zippath, \ZipArchive::RDONLY) !== true) {
            throw new \moodle_exception('ota_err_bundle', 'local_pspvcore');
        }
        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);
                if (!$this->is_safe_path($name, $roots)) {
                    throw new \moodle_exception('ota_err_path', 'local_pspvcore', '', s($name));
                }
                $opsys = 0;
                $attributes = 0;
                $zip->getExternalAttributesIndex($i, $opsys, $attributes);
                if ($opsys === \ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                    // Symbolic links could point outside the plugin folder.
                    throw new \moodle_exception('ota_err_path', 'local_pspvcore', '', s($name));
                }
                if (str_ends_with($name, '/')) {
                    continue;
                }
                $target = $staging . '/' . $name;
                if (!is_dir(dirname($target))) {
                    mkdir(dirname($target), $CFG->directorypermissions, true);
                }
                $in = $zip->getStream($name);
                $out = $in ? fopen($target, 'wb') : false;
                if (!$in || !$out || stream_copy_to_stream($in, $out) === false) {
                    throw new \moodle_exception('ota_err_bundle', 'local_pspvcore');
                }
                fclose($in);
                fclose($out);
            }
        } finally {
            $zip->close();
        }

        $plugins = [];
        foreach ($roots as $root) {
            $component = $this->component($root);
            $version = $this->read_version($staging . '/' . $root, $component, $root);
            $installed = $this->installed_version($component);
            if ($installed !== null && $version < $installed) {
                throw new \moodle_exception('ota_err_downgrade', 'local_pspvcore', '', $component);
            }
            $plugins[] = ['root' => $root, 'component' => $component, 'version' => $version];
        }
        return $plugins;
    }

    /**
     * Whether a zip entry stays inside one of the roots.
     *
     * @param string $name Entry name.
     * @param string[] $roots Validated roots.
     * @return bool
     */
    protected function is_safe_path(string $name, array $roots): bool {
        if ($name === '' || $name[0] === '/' || strpbrk($name, "\\\0:") !== false) {
            return false;
        }
        foreach (explode('/', rtrim($name, '/')) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }
        foreach ($roots as $root) {
            if (str_starts_with($name, $root . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reads the version of a plugin folder without running its code.
     *
     * @param string $dir Plugin folder.
     * @param string $component Component the folder must declare.
     * @param string $root Root, for error messages.
     * @return int
     */
    protected function read_version(string $dir, string $component, string $root): int {
        $code = is_file($dir . '/version.php') ? (string) file_get_contents($dir . '/version.php') : '';
        if (
            !preg_match('/\$plugin->version\s*=\s*(\d{10})\b/', $code, $version)
            || !preg_match('/\$plugin->component\s*=\s*[\'"]([a-z0-9_]+)[\'"]/', $code, $declared)
            || $declared[1] !== $component
        ) {
            throw new \moodle_exception('ota_err_version', 'local_pspvcore', '', $root);
        }
        return (int) $version[1];
    }

    /**
     * Version of a plugin as installed in the database.
     *
     * @param string $component Frankenstyle name.
     * @return int|null Null when the plugin is not installed.
     */
    protected function installed_version(string $component): ?int {
        $version = get_config($component, 'version');
        return ($version === false || $version === '') ? null : (int) $version;
    }

    /**
     * Copies the folders about to be replaced into a new backup.
     *
     * @param string[] $roots Validated roots.
     * @param string|null $version Version the site is on now.
     */
    protected function backup(array $roots, ?string $version): void {
        global $CFG;

        $dir = $this->backuproot . '/' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3));
        if (!mkdir($dir . '/files', $CFG->directorypermissions, true)) {
            throw new \moodle_exception('ota_err_swap', 'local_pspvcore', '', $this->backuproot);
        }
        foreach ($roots as $root) {
            if (is_dir($this->publicroot . '/' . $root)) {
                $this->copy_dir($this->publicroot . '/' . $root, $dir . '/files/' . $root);
            }
        }
        file_put_contents($dir . '/backup.json', json_encode([
            'version' => (string) $version,
            'created' => time(),
            'roots' => $roots,
        ]));
    }

    /**
     * Replaces each root with its folder in $staging, or removes it when $staging has none.
     *
     * Every replacement is a pair of renames; if one fails the ones already done are undone.
     *
     * @param string $staging Folder with the new content, laid out like public/.
     * @param string[] $roots Validated roots.
     */
    protected function swap(string $staging, array $roots): void {
        $parking = $this->staging_dir('old');
        $done = [];
        try {
            foreach ($roots as $root) {
                $target = $this->publicroot . '/' . $root;
                $new = $staging . '/' . $root;
                $parked = $parking . '/' . str_replace('/', '__', $root);
                $existed = is_dir($target);
                if ($existed && !@rename($target, $parked)) {
                    throw new \moodle_exception('ota_err_swap', 'local_pspvcore', '', $root);
                }
                $done[] = [$target, $parked, $existed];
                if (is_dir($new) && !@rename($new, $target)) {
                    throw new \moodle_exception('ota_err_swap', 'local_pspvcore', '', $root);
                }
            }
        } catch (\Throwable $e) {
            foreach (array_reverse($done) as [$target, $parked, $existed]) {
                if (is_dir($parked) || !$existed) {
                    remove_dir($target);
                }
                if ($existed && is_dir($parked)) {
                    @rename($parked, $target);
                }
            }
            remove_dir($parking);
            throw $e;
        }
        remove_dir($parking);
    }

    /**
     * Copies a folder, skipping symbolic links.
     *
     * @param string $from Source folder.
     * @param string $to Destination folder (created).
     */
    protected function copy_dir(string $from, string $to): void {
        global $CFG;

        if (!is_dir($to) && !mkdir($to, $CFG->directorypermissions, true)) {
            throw new \moodle_exception('ota_err_swap', 'local_pspvcore', '', $to);
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($from, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isLink()) {
                continue;
            }
            $target = $to . '/' . $items->getSubPathname();
            if ($item->isDir()) {
                if (!is_dir($target) && !mkdir($target, $CFG->directorypermissions, true)) {
                    throw new \moodle_exception('ota_err_swap', 'local_pspvcore', '', $target);
                }
            } else if (!copy($item->getPathname(), $target)) {
                throw new \moodle_exception('ota_err_swap', 'local_pspvcore', '', $target);
            }
        }
    }

    /**
     * Creates an empty scratch folder next to public/, so renames stay on one filesystem.
     *
     * @param string $kind Short label used in the folder name.
     * @return string
     */
    protected function staging_dir(string $kind): string {
        global $CFG;

        $dir = $this->stagingroot . '/' . $kind . '-' . bin2hex(random_bytes(6));
        if (!@mkdir($dir, $CFG->directorypermissions, true)) {
            throw new \moodle_exception('ota_err_swap', 'local_pspvcore', '', $this->stagingroot);
        }
        return $dir;
    }

    /**
     * Folder of the newest backup.
     *
     * @return string|null
     */
    protected function latest_backup(): ?string {
        $backups = $this->backups();
        return $backups ? end($backups) : null;
    }

    /**
     * Complete backups, oldest first.
     *
     * @return string[]
     */
    protected function backups(): array {
        $backups = [];
        foreach (glob($this->backuproot . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
            if (is_file($dir . '/backup.json')) {
                $backups[] = $dir;
            }
        }
        sort($backups);
        return $backups;
    }

    /**
     * Deletes the oldest backups.
     */
    protected function prune_backups(): void {
        foreach (array_slice($this->backups(), 0, -self::KEEP_BACKUPS) as $dir) {
            remove_dir($dir);
        }
    }

    /**
     * Adds an entry to the history.
     *
     * @param string $version Version the site is on after the action.
     * @param string $action "applied" or "rolledback".
     */
    protected function remember(string $version, string $action): void {
        $history = $this->history();
        array_unshift($history, ['version' => $version, 'action' => $action, 'time' => time()]);
        set_config('otahistory', json_encode(array_slice($history, 0, self::KEEP_HISTORY)), 'local_pspvcore');
    }

    /**
     * Makes the running site notice that code on disk has changed.
     */
    protected function refresh_runtime(): void {
        if (!$this->live) {
            return;
        }
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
        purge_all_caches();
    }

    /**
     * Runs a callback while holding the update lock.
     *
     * @param callable $callback
     * @return mixed What the callback returns.
     */
    protected function locked(callable $callback) {
        $lock = \core\lock\lock_config::get_lock_factory('local_pspvcore_ota')->get_lock('update', 1);
        if (!$lock) {
            throw new \moodle_exception('ota_err_locked', 'local_pspvcore');
        }
        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    /**
     * The configured public key as raw bytes.
     *
     * @return string|null Null when missing or malformed.
     */
    protected function public_key(): ?string {
        if (!defined('SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES')) {
            return null;
        }
        $key = base64_decode($this->publickey, true);
        return ($key !== false && strlen($key) === SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) ? $key : null;
    }
}

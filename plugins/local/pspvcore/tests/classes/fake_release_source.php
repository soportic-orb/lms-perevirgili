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

namespace local_pspvcore\tests;

use local_pspvcore\ota\release_source;
use local_pspvcore\ota\updater;

/**
 * A release source that builds and signs releases on disk, for tests.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fake_release_source implements release_source {
    /** @var array|null The release offered. */
    protected ?array $release = null;

    /** @var string Ed25519 key pair that signs the releases. */
    protected string $keypair;

    /**
     * Constructor.
     *
     * @param string $dir Empty folder where releases are built.
     */
    public function __construct(
        /** @var string Folder where releases are built. */
        protected string $dir,
    ) {
        $this->keypair = sodium_crypto_sign_keypair();
    }

    /**
     * The public key of the releases, as configured on a site.
     *
     * @return string
     */
    public function public_key(): string {
        return base64_encode(sodium_crypto_sign_publickey($this->keypair));
    }

    /**
     * Content of a plugin's version.php.
     *
     * @param string $component Frankenstyle name.
     * @param int $version Plugin version.
     * @return string
     */
    public static function version_file(string $component, int $version): string {
        return "<?php\n\$plugin->component = '$component';\n\$plugin->version = $version;\n";
    }

    /**
     * Builds a signed release and offers it.
     *
     * @param string $version Version announced by the release.
     * @param string[] $files Zip entry name => content.
     * @param string[] $roots Roots declared in the manifest.
     * @param array $tamper What to break: "manifestversion" (string), "badhash" (bool), "badsignature" (bool).
     */
    public function publish(string $version, array $files, array $roots, array $tamper = []): void {
        $dir = $this->dir . '/' . count(scandir($this->dir));
        mkdir($dir);

        $zip = new \ZipArchive();
        $zip->open($dir . '/' . updater::BUNDLE, \ZipArchive::CREATE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        $manifest = json_encode([
            'format' => 1,
            'version' => $tamper['manifestversion'] ?? $version,
            'sha256' => empty($tamper['badhash']) ? hash_file('sha256', $dir . '/' . updater::BUNDLE) : str_repeat('0', 64),
            'roots' => $roots,
        ]);
        file_put_contents($dir . '/' . updater::MANIFEST, $manifest);

        $secret = sodium_crypto_sign_secretkey(empty($tamper['badsignature']) ? $this->keypair : sodium_crypto_sign_keypair());
        file_put_contents($dir . '/' . updater::SIGNATURE, base64_encode(sodium_crypto_sign_detached($manifest, $secret)));

        $this->release = [
            'version' => $version,
            'name' => 'Release ' . $version,
            'notes' => '- Change',
            'published' => 1794398400,
            'assets' => [
                updater::BUNDLE => $dir . '/' . updater::BUNDLE,
                updater::MANIFEST => $dir . '/' . updater::MANIFEST,
                updater::SIGNATURE => $dir . '/' . updater::SIGNATURE,
            ],
        ];
    }

    #[\Override]
    public function latest_release(): ?array {
        return $this->release;
    }

    #[\Override]
    public function download(array $release, string $asset, string $target): void {
        copy($release['assets'][$asset], $target);
    }
}

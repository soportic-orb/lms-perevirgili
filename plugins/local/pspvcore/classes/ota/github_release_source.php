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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/filelib.php');

/**
 * Reads the "ota-*" releases of the project's GitHub repository.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class github_release_source implements release_source {
    /** @var string GitHub REST API base URL. */
    protected const API = 'https://api.github.com';

    /**
     * Constructor.
     *
     * @param string $repository "owner/name" of the repository that publishes the releases.
     * @param string $token Optional token, only needed while the repository is private.
     */
    public function __construct(
        /** @var string "owner/name" of the repository. */
        protected string $repository,
        /** @var string Optional GitHub token. */
        protected string $token = '',
    ) {
    }

    #[\Override]
    public function latest_release(): ?array {
        if (!preg_match('~^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$~', $this->repository)) {
            throw new \moodle_exception('ota_err_repository', 'local_pspvcore');
        }

        $curl = $this->curl();
        $raw = $curl->get(self::API . '/repos/' . $this->repository . '/releases', ['per_page' => 30]);
        $info = $curl->get_info();
        if ($curl->get_errno() || (int) ($info['http_code'] ?? 0) !== 200) {
            throw new \moodle_exception('ota_err_github', 'local_pspvcore', '', (int) ($info['http_code'] ?? 0));
        }
        $releases = json_decode($raw, true);
        if (!is_array($releases)) {
            throw new \moodle_exception('ota_err_github', 'local_pspvcore', '', 200);
        }

        $latest = null;
        foreach ($releases as $release) {
            $tag = (string) ($release['tag_name'] ?? '');
            if (!empty($release['draft']) || !empty($release['prerelease']) || !str_starts_with($tag, updater::TAG_PREFIX)) {
                continue;
            }
            $assets = [];
            foreach ($release['assets'] ?? [] as $asset) {
                $assets[$asset['name']] = [
                    'api' => $asset['url'],
                    'public' => $asset['browser_download_url'],
                ];
            }
            if (array_diff([updater::BUNDLE, updater::MANIFEST, updater::SIGNATURE], array_keys($assets))) {
                continue;
            }
            $version = substr($tag, strlen(updater::TAG_PREFIX));
            if ($latest === null || strcmp($version, $latest['version']) > 0) {
                $latest = [
                    'version' => $version,
                    'name' => (string) ($release['name'] ?? $tag),
                    'notes' => (string) ($release['body'] ?? ''),
                    'published' => (int) strtotime((string) ($release['published_at'] ?? 'now')),
                    'assets' => $assets,
                ];
            }
        }
        return $latest;
    }

    #[\Override]
    public function download(array $release, string $asset, string $target): void {
        $reference = $release['assets'][$asset] ?? null;
        if (!$reference) {
            throw new \moodle_exception('ota_err_download', 'local_pspvcore', '', $asset);
        }

        $url = $reference['public'];
        if ($this->token !== '') {
            // Private repository: the API answers with a short-lived signed URL. It must be fetched
            // without the Authorization header, so the redirect is followed by hand.
            $curl = $this->curl('application/octet-stream');
            $curl->get($reference['api'], null, ['CURLOPT_FOLLOWLOCATION' => 0]);
            $info = $curl->get_info();
            $url = (string) ($info['redirect_url'] ?? '');
            if ($url === '') {
                throw new \moodle_exception('ota_err_download', 'local_pspvcore', '', $asset);
            }
        }

        $curl = new \curl();
        $result = $curl->download_one($url, null, ['filepath' => $target, 'timeout' => 300, 'followlocation' => true]);
        $info = $curl->get_info();
        if ($result !== true || (int) ($info['http_code'] ?? 0) !== 200 || !is_file($target)) {
            @unlink($target);
            throw new \moodle_exception('ota_err_download', 'local_pspvcore', '', $asset);
        }
    }

    /**
     * A curl client with the GitHub API headers.
     *
     * @param string $accept Value of the Accept header.
     * @return \curl
     */
    protected function curl(string $accept = 'application/vnd.github+json'): \curl {
        $curl = new \curl();
        $headers = ['Accept: ' . $accept, 'X-GitHub-Api-Version: 2022-11-28'];
        if ($this->token !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }
        $curl->setHeader($headers);
        $curl->setopt(['CURLOPT_USERAGENT' => 'campus-pspv-ota', 'CURLOPT_TIMEOUT' => 30]);
        return $curl;
    }
}

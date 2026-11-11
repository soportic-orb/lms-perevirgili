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

namespace local_pspvcore\local;

use core_cache\config_writer;
use core_cache\store;

/**
 * Site setup steps that Moodle's installer does not do by itself.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class site_setup {
    /** @var string[] Language packs of the site, besides English. */
    public const LANGUAGES = ['ca', 'es'];

    /** @var string Name of the Redis cache store instance. */
    public const CACHE_STORE = 'pspvredis';

    /**
     * Installs the missing language packs.
     *
     * @return string[] Lines describing what was done.
     */
    public static function install_languages(): array {
        $installed = get_string_manager()->get_list_of_translations(true);
        $missing = array_values(array_diff(self::LANGUAGES, array_keys($installed)));
        if (!$missing) {
            return ['Language packs already installed: ' . implode(', ', self::LANGUAGES)];
        }
        try {
            $controller = new \tool_langimport\controller();
            $controller->install_languagepacks($missing);
            get_string_manager()->reset_caches();
        } catch (\Throwable $e) {
            return ['Could not install the language packs (' . implode(', ', $missing) . '): '
                . trim(preg_replace('/\s+/', ' ', strip_tags($e->getMessage())))];
        }
        return array_merge(
            array_map('strip_tags', $controller->info),
            array_map('strip_tags', $controller->errors),
        );
    }

    /**
     * Adds the Redis cache store and makes it the default for application and session caches.
     *
     * @return string[] Lines describing what was done.
     */
    public static function configure_cache(): array {
        $host = (string) get_config('local_pspvcore', 'redishost');
        if ($host === '') {
            return ['Redis is not configured (REDIS_HOST): the cache stays on the default file store.'];
        }
        $port = (string) get_config('local_pspvcore', 'redisport');
        $configuration = [
            'server' => $host . ($port !== '' ? ':' . $port : ''),
            'prefix' => get_config('local_pspvcore', 'redisprefix') . 'muc_',
            'password' => (string) get_config('local_pspvcore', 'redispassword'),
            'serializer' => \Redis::SERIALIZER_PHP,
            'compressor' => \cachestore_redis::COMPRESSOR_NONE,
        ];

        $writer = config_writer::instance();
        $stores = $writer->get_all_stores();
        if (isset($stores[self::CACHE_STORE])) {
            $writer->edit_store_instance(self::CACHE_STORE, 'redis', $configuration);
            $lines = ['Redis cache store updated: ' . $configuration['server']];
        } else {
            $writer->add_store_instance(self::CACHE_STORE, 'redis', $configuration);
            $lines = ['Redis cache store added: ' . $configuration['server']];
        }
        $writer->set_mode_mappings([
            store::MODE_APPLICATION => [self::CACHE_STORE],
            store::MODE_SESSION => [self::CACHE_STORE],
            store::MODE_REQUEST => ['default_request'],
        ]);
        \core_cache\helper::purge_all();
        $lines[] = 'Application and session caches now use Redis.';
        return $lines;
    }
}

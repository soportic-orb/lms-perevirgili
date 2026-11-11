<?php
// Campus PSPV — configuració de Moodle.
//
// Aquest fitxer s'enllaça a moodle/config.php (bin/moodle-fetch.sh). No conté
// cap secret: tot surt de variables d'entorn o del fitxer .env de l'arrel del
// repositori (vegeu .env.example).

unset($CFG);
global $CFG;
$CFG = new stdClass();

// Arrel del repositori (aquest fitxer viu a <arrel>/config/).
$pspvroot = dirname(__DIR__);

$pspvenv = [];
if (is_readable($pspvroot . '/.env')) {
    foreach (file($pspvroot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $pspvline) {
        $pspvline = trim($pspvline);
        if ($pspvline === '' || $pspvline[0] === '#' || !str_contains($pspvline, '=')) {
            continue;
        }
        [$pspvkey, $pspvvalue] = explode('=', $pspvline, 2);
        $pspvvalue = trim($pspvvalue);
        if (strlen($pspvvalue) >= 2 && ($pspvvalue[0] === '"' || $pspvvalue[0] === "'") && substr($pspvvalue, -1) === $pspvvalue[0]) {
            $pspvvalue = substr($pspvvalue, 1, -1);
        }
        $pspvenv[trim($pspvkey)] = $pspvvalue;
    }
}

/**
 * Valor de configuració: variable d'entorn real, després .env, després el valor per defecte.
 */
$pspvget = static function (string $name, ?string $default = null) use ($pspvenv): ?string {
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return $value;
    }
    if (isset($pspvenv[$name]) && $pspvenv[$name] !== '') {
        return $pspvenv[$name];
    }
    return $default;
};

$pspvappenv = $pspvget('APP_ENV', 'production');

// Base de dades.
$CFG->dbtype    = $pspvget('DB_TYPE', 'mariadb');
$CFG->dblibrary = 'native';
$CFG->dbhost    = $pspvget('DB_HOST', '127.0.0.1');
$CFG->dbname    = $pspvget('DB_NAME', 'moodle');
$CFG->dbuser    = $pspvget('DB_USER', 'moodle');
$CFG->dbpass    = $pspvget('DB_PASS', '');
$CFG->prefix    = $pspvget('DB_PREFIX', 'mdl_');
$CFG->dboptions = [
    'dbpersist' => false,
    'dbsocket'  => false,
    'dbport'    => $pspvget('DB_PORT', ''),
];
if ($CFG->dbtype !== 'pgsql') {
    $CFG->dboptions['dbcollation'] = 'utf8mb4_unicode_ci';
}

$CFG->wwwroot  = rtrim($pspvget('MOODLE_WWWROOT', 'http://localhost:8080'), '/');
$CFG->dataroot = $pspvget('MOODLE_DATAROOT', dirname($pspvroot) . '/moodledata');
$CFG->admin    = 'admin';
// En local la CLI dels tests (root) i el servidor web (www-data) comparteixen els directoris de dades.
$CFG->directorypermissions = $pspvappenv === 'local' ? 02777 : 02770;

// El servidor web envia a r.php tot el que no és un fitxer (deploy/cloudpanel/nginx-vhost.conf).
$CFG->routerconfigured = $pspvget('MOODLE_ROUTER', '1') === '1';

// Darrere del proxy invers de CloudPanel / Cloudflare.
if (str_starts_with($CFG->wwwroot, 'https://')) {
    $CFG->sslproxy = $pspvget('MOODLE_SSLPROXY', '0') === '1';
}

// Sessions a Redis. La memòria cau MUC es configura amb local/pspvcore/cli/configure_cache.php.
if ($pspvget('REDIS_HOST')) {
    $CFG->session_handler_class = '\core\session\redis';
    $CFG->session_redis_host = $pspvget('REDIS_HOST');
    $CFG->session_redis_port = (int) $pspvget('REDIS_PORT', '6379');
    $CFG->session_redis_prefix = $pspvget('REDIS_PREFIX', 'pspv_') . 'sess_';
    if ($pspvget('REDIS_PASSWORD')) {
        $CFG->session_redis_auth = $pspvget('REDIS_PASSWORD');
    }
    $CFG->session_redis_acquire_lock_timeout = 120;
    $CFG->session_redis_lock_expire = 7200;
}

// Correu sortint.
if ($pspvget('SMTP_HOST')) {
    $CFG->smtphosts = $pspvget('SMTP_HOST');
    $CFG->smtpuser = $pspvget('SMTP_USER', '');
    $CFG->smtppass = $pspvget('SMTP_PASS', '');
    $CFG->smtpsecure = $pspvget('SMTP_SECURE', '');
}
if ($pspvget('NOREPLY_ADDRESS')) {
    $CFG->noreplyaddress = $pspvget('NOREPLY_ADDRESS');
}

// Idiomes: català per defecte (els paquets ca i es s'instal·len amb `make install`).
$CFG->lang = 'ca';

// Actualitzacions OTA (docs/OTA.md). Es fixen aquí perquè no es puguin canviar des de l'administració.
$CFG->forced_plugin_settings['local_pspvcore'] = [
    'otarepository' => $pspvget('OTA_REPOSITORY', 'soportic-orb/lms-perevirgili'),
    'otapublickey' => $pspvget('OTA_PUBLIC_KEY', ''),
    'otagithubtoken' => $pspvget('OTA_GITHUB_TOKEN', ''),
    'redishost' => $pspvget('REDIS_HOST', ''),
    'redisport' => $pspvget('REDIS_PORT', '6379'),
    'redispassword' => $pspvget('REDIS_PASSWORD', ''),
    'redisprefix' => $pspvget('REDIS_PREFIX', 'pspv_'),
];

// Aplicacions OAuth 2 (Google i Microsoft): local/pspvaccess/cli/setup.php en crea els proveïdors.
$CFG->forced_plugin_settings['local_pspvaccess'] = [
    'googleclientid' => $pspvget('OAUTH_GOOGLE_CLIENT_ID', ''),
    'googleclientsecret' => $pspvget('OAUTH_GOOGLE_CLIENT_SECRET', ''),
    'microsoftclientid' => $pspvget('OAUTH_MICROSOFT_CLIENT_ID', ''),
    'microsoftclientsecret' => $pspvget('OAUTH_MICROSOFT_CLIENT_SECRET', ''),
    'microsofttenant' => $pspvget('OAUTH_MICROSOFT_TENANT', ''),
];

// El codi el desplega el repositori (OTA o SSH), no l'instal·lador de plugins de Moodle.
$CFG->disableupdateautodeploy = true;

if ($pspvappenv === 'local') {
    $CFG->debug = E_ALL;
    $CFG->debugdisplay = 1;
    $CFG->cachejs = false;
    $CFG->langstringcache = false;
} else {
    $CFG->debug = 0;
    $CFG->debugdisplay = 0;
    $CFG->preventexecpath = true;
}

// Tests (només local i CI).
if ($pspvget('PHPUNIT_DATAROOT')) {
    $CFG->phpunit_prefix = 'phpu_';
    $CFG->phpunit_dataroot = $pspvget('PHPUNIT_DATAROOT');
}
if ($pspvget('BEHAT_DATAROOT') && $pspvget('BEHAT_WWWROOT')) {
    $CFG->behat_prefix = 'bht_';
    $CFG->behat_dataroot = $pspvget('BEHAT_DATAROOT');
    $CFG->behat_wwwroot = $pspvget('BEHAT_WWWROOT');
    if ($pspvget('BEHAT_FAILDUMP_PATH')) {
        $CFG->behat_faildump_path = $pspvget('BEHAT_FAILDUMP_PATH');
    }
    $CFG->behat_profiles = [
        'default' => [
            'browser' => 'chrome',
            'wd_host' => $pspvget('BEHAT_WD_HOST', 'http://selenium:4444/wd/hub'),
        ],
    ];
}

unset($pspvenv, $pspvget, $pspvline, $pspvkey, $pspvvalue, $pspvappenv);

require_once($pspvroot . '/moodle/lib/setup.php');

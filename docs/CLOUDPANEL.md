# Instal·lació en un servidor amb CloudPanel

Exemple per a staging (`perevirgili.soportic.es`, usuari de lloc
`perevirgili`). Per a producció, el mateix amb el seu domini i
`APP_ENV=production`.

## Abans de començar

Moodle 5.3 exigeix:

- **PHP 8.3 o 8.4** (recomanat 8.4) amb les extensions `sodium`, `zip`,
  `intl`, `gd`, `soap`, `mysqli`, `redis` i `opcache`.
- **MariaDB 11.4 o superior, o MySQL 8.4 o superior.** Comprova-ho al
  servidor abans de res: amb MariaDB 10.11 o MySQL 8.0 l'instal·lador de
  Moodle s'atura.

  ```bash
  mysql --version
  ```

- **Redis** (CloudPanel el porta instal·lat) i **Composer**.

## 1. A CloudPanel

1. **Sites → Add Site → Create a PHP Site**: plantilla **Generic**, domini
   `perevirgili.soportic.es`, PHP **8.4**. Anota l'usuari del lloc.
2. **SSL/TLS** del lloc → Let's Encrypt.
3. **Databases → Add Database** (per exemple `campuspspv`), amb el seu usuari.
4. **Vhost** del lloc: canvia `root` perquè apunti a
   `/home/perevirgili/htdocs/perevirgili.soportic.es/moodle/public` i
   substitueix els blocs `location` pels de
   [`deploy/cloudpanel/nginx-vhost.conf`](../deploy/cloudpanel/nginx-vhost.conf).
   A **PHP Settings**: `max_input_vars` 5000 o més, `memory_limit` 512M,
   `upload_max_filesize` i `post_max_size` segons la mida màxima dels fitxers
   dels cursos.
5. **Cron Jobs** del lloc, cada minut:

   ```
   * * * * * php8.4 -d max_input_vars=5000 /home/perevirgili/htdocs/perevirgili.soportic.es/moodle/admin/cli/cron.php >> /dev/null 2>&1
   ```

## 2. Codi i `.env` (SSH)

Treballa sempre com l'usuari del lloc (`su - perevirgili`). Si alguna cosa es
crea com a root, PHP no podrà escriure-hi i les actualitzacions OTA fallaran.

```bash
cd ~/htdocs/perevirgili.soportic.es
```

Buida la carpeta (la pàgina d'exemple de CloudPanel) i clona-hi el repositori:

```bash
git clone https://github.com/soportic-orb/lms-perevirgili.git .
```

```bash
cp .env.example .env && nano .env
```

A `.env`:

- `APP_ENV=staging` (o `production`), `MOODLE_WWWROOT=https://perevirgili.soportic.es`
- `MOODLE_DATAROOT=/home/perevirgili/moodledata` (fora de `htdocs`)
- `MOODLE_ADMIN_USER`, `MOODLE_ADMIN_PASS`, `MOODLE_ADMIN_EMAIL`: l'administrador inicial
- `DB_TYPE=mariadb` (o `mysqli` si el servidor és MySQL), `DB_HOST=127.0.0.1`,
  `DB_NAME`, `DB_USER`, `DB_PASS`: els del pas 1.3
- `REDIS_HOST=127.0.0.1`, i un `REDIS_PREFIX` diferent per a cada web del servidor
- `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `SMTP_SECURE`, `NOREPLY_ADDRESS`: el correu sortint
- `OAUTH_*`: inici de sessió amb Google i Microsoft ([`docs/ACCES.md`](ACCES.md))
- `OTA_PUBLIC_KEY=…` ([`docs/OTA.md`](OTA.md))
- Esborra les línies `PHPUNIT_DATAROOT`, `BEHAT_WWWROOT` i `BEHAT_DATAROOT`.

## 3. Instal·la

```bash
PHP_BIN=php8.4 bash deploy/cloudpanel/install.sh
```

L'script baixa Moodle a l'etiqueta de `.moodle-version`, hi instal·la les
dependències amb Composer, copia els plugins propis, crea la base de dades,
instal·la els idiomes, configura la memòria cau a Redis i deixa a punt
l'accés (cohort, categories, rols, proveïdors d'inici de sessió i polítiques)
i el tema. Es pot tornar a
executar sempre que calgui.

En acabar, esborra `MOODLE_ADMIN_PASS` de `.env` i canvia la contrasenya de
l'administrador en el primer accés.

Si alguna cosa dona un error 500: `tail -n 30 ~/logs/php/error.log`.

## 4. Comprova

- `https://perevirgili.soportic.es/login/index.php` carrega amb estils.
- Administració del lloc → Connectors → Connectors locals → **Actualitzacions
  del Campus PSPV**: sense avisos; «Cerca actualitzacions».
- Administració del lloc → Servidor → Tasques → Tasques programades: el cron
  s'ha executat en l'últim minut.

## Després

- **Plugins propis:** amb el botó d'actualitzacions OTA ([`docs/OTA.md`](OTA.md)).
- **Core de Moodle, plugins de la comunitat, configuració i scripts** (canvi
  de `.moodle-version`, `.community-plugins`, `config/`, `deploy/`): per SSH,
  com l'usuari del lloc. Cal fer-ho un cop després de la fase 5 perquè
  s'instal·li `mod_customcert` (els diplomes); fins aleshores la resta de la
  plataforma funciona igual.

  ```bash
  cd ~/htdocs/perevirgili.soportic.es && git fetch origin main && git reset --hard origin/main
  ```

  ```bash
  PHP_BIN=php8.4 bash deploy/cloudpanel/install.sh
  ```

  L'script posa el lloc en manteniment mentre Moodle s'actualitza. Les OTA no
  deixen canvis locals al repositori (escriuen dins `moodle/`, que git no
  segueix), i `git reset --hard` no toca `.env`.

- **Còpies de seguretat:** [`COPIES.md`](COPIES.md). Cal afegir-ne la tasca
  de cron i una còpia remota.
- **Monitoratge:** [`MONITORATGE.md`](MONITORATGE.md).
- **Abans d'obrir producció:** [`PRODUCCIO.md`](PRODUCCIO.md).

# Instal·lació en un servidor amb CloudPanel

Exemple per al campus de producció (`perevirgili.soportic.es`, usuari de
lloc `soportic-perevirgili`). Per a un lloc de proves, el mateix amb el seu domini,
la seva base de dades i `APP_ENV=staging`.

## Abans de començar

Moodle 5.3 exigeix:

- **PHP 8.3 o 8.4** (recomanat 8.4) amb les extensions `sodium`, `zip`,
  `intl`, `gd`, `soap`, `mysqli`, `redis` i `opcache`.
- **MariaDB 11.4 o superior, o MySQL 8.4 o superior.** Comprova-ho al
  servidor abans de res: amb MariaDB 10.11 o MySQL 8.0 l'instal·lador de
  Moodle s'atura a «Entorn» sense haver creat res. La versió que compta és la
  del servidor, no la del client:

  ```bash
  mariadb -e "SELECT VERSION();"
  ```

  Actualitzar el motor afecta totes les webs del servidor: fes-ne còpia abans.

- **Redis** (CloudPanel el porta instal·lat) i **Composer**.

## 1. A CloudPanel

1. **Sites → Add Site → Create a PHP Site**: plantilla **Generic**, domini
   `perevirgili.soportic.es`, PHP **8.4**. Anota l'usuari del lloc.
2. **SSL/TLS** del lloc → Let's Encrypt.
3. **Databases → Add Database** (per exemple `campuspspv`), amb el seu usuari.
4. **PHP Settings** del lloc: `max_input_vars` 5000 o més, `memory_limit`
   512M, `upload_max_filesize` i `post_max_size` segons la mida màxima dels
   fitxers dels cursos. El **Vhost** es canvia després d'instal·lar (pas 4).
5. **Cron Jobs** del lloc, cada minut:

   ```
   * * * * * php8.4 -d max_input_vars=5000 /home/soportic-perevirgili/htdocs/perevirgili.soportic.es/moodle/admin/cli/cron.php >> /dev/null 2>&1
   ```

## 2. Codi i `.env` (SSH)

Treballa sempre com **l'usuari del lloc** (el que surt a *Settings* del lloc;
aquí, `soportic-perevirgili`), no com a root ni amb un usuari SSH addicional:
PHP corre amb l'usuari del lloc i, si els fitxers són d'un altre, no hi podrà
escriure i les actualitzacions OTA fallaran. Comprova-ho:

```bash
id -un; stat -c '%U' ~/htdocs/perevirgili.soportic.es
```

Han de sortir dos noms iguals. Si ja has creat fitxers amb un altre usuari,
com a root: `chown -R <usuari del lloc>: <carpeta del lloc>`.

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

- `APP_ENV=production` (`staging` en un lloc de proves), `MOODLE_WWWROOT=https://perevirgili.soportic.es`
- `MOODLE_DATAROOT=/home/soportic-perevirgili/moodledata` (fora de `htdocs`).
  El valor de l'exemple, `/var/www/moodledata`, és el de Docker: si es queda,
  l'script falla amb «Permission denied».
- `MOODLE_ADMIN_USER`, `MOODLE_ADMIN_PASS`, `MOODLE_ADMIN_EMAIL`: l'administrador inicial
- `DB_TYPE=mariadb` (o `mysqli` si el servidor és MySQL), `DB_HOST=127.0.0.1`,
  `DB_NAME`, `DB_USER`, `DB_PASS`: els del pas 1.3 (una contrasenya amb `#`, `$` o
  espais va entre cometes dobles)
- `REDIS_HOST=127.0.0.1` (no `redis`, que és el de Docker), i un `REDIS_PREFIX` diferent per a cada web del servidor
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

## 4. Vhost

A CloudPanel, lloc → **Vhost**: substitueix-ne el contingut pel de
[`deploy/cloudpanel/nginx-vhost.conf`](../deploy/cloudpanel/nginx-vhost.conf),
canviant-hi `<domini>` i `<usuari>`. El fitxer explica per què cada cosa va
on va. CloudPanel comprova la sintaxi en desar.

Si alguna cosa no carrega:

| Què es veu | Causa |
|---|---|
| 403 a la portada, i «directory index is forbidden» a `~/logs/nginx/error.log` | El primer bloc `server` no reenvia al segon (falta el seu `location /` amb el proxy) |
| La web carrega sense format | `styles.php` respon 404: falta `set $path_info` al bloc de PHP, o hi ha un bloc de fitxers estàtics al primer `server` |
| 502 o 500 | Falla PHP: `tail -n 30 ~/logs/php/error.log` |

Comprovació ràpida del full d'estils (ha de dir `200` i `text/css`):

```bash
CSS=$(curl -s https://perevirgili.soportic.es/login/index.php | grep -o 'https://[^"]*styles\.php[^"]*' | head -1); curl -sI "$CSS" | head -4
```

## 5. Comprova

- `https://perevirgili.soportic.es/login/index.php` carrega amb estils.
- Administració del lloc → Connectors → Connectors locals → **Actualitzacions
  del Campus PSPV**: sense avisos; «Cerca actualitzacions».
- Administració del lloc → Servidor → Tasques → Tasques programades: el cron
  s'ha executat en l'últim minut.

## Cursos d'exemple

Per ensenyar el disseny abans de tenir cursos reals, com l'usuari del lloc:

```bash
php8.4 -d max_input_vars=5000 deploy/demo/demo-courses.php
```

Crea sis cursos a «Formació interna» i cinc a «Formació externa», aquests
a la venda, tots amb caràtula. Els preus, les hores i els crèdits són
exemples. Es treuen amb la mateixa ordre i `--delete`; **treu-los abans
d'obrir el campus**.

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

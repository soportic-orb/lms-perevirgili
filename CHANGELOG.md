# Registre de canvis

## Fase 1 — Fonaments (11-11-2026)

### Afegit

- Estructura del repositori: plugins propis a `plugins/`, core de Moodle
  fixat a `v5.3.0` (`.moodle-version`) i descarregat amb `bin/moodle-fetch.sh`.
- `config/config.php`: configuració de Moodle a partir de `.env` (base de
  dades, Redis, correu, OTA), sense secrets al repositori.
- Entorn local amb Docker: PHP 8.4, MariaDB 11.4, Redis, Mailpit i cron, amb
  `Makefile` (`up`, `down`, `install`, `upgrade`, `test`, `behat`, `lint`, `backup`).
- `local_pspvcore` 0.1.0: esquelet de referència (capability, tasca
  programada, esdeveniments, Privacy API, cadenes en anglès, català i castellà,
  PHPUnit i Behat) i actualitzador OTA amb pàgina d'administració i CLI.
- Actualitzacions OTA: `scripts/ota-keygen.sh`, `scripts/ota-bundle.sh` i el
  workflow `OTA release`, amb manifest signat amb Ed25519.
- Desplegament a CloudPanel: `deploy/cloudpanel/install.sh` (idempotent) i
  blocs del vhost de Nginx.
- CI amb `moodle-plugin-ci`: sintaxi, estil Moodle, documentació, validació,
  plantilles Mustache, PHPUnit i Behat.
- `CLAUDE.md`, `docs/PROPOSTA.md`, `docs/CLOUDPANEL.md`, `docs/OTA.md`.

### Decidit

- Base de dades MariaDB/MySQL en lloc de PostgreSQL: CloudPanel no ofereix
  PostgreSQL. Moodle 5.3 exigeix MariaDB ≥ 11.4 o MySQL ≥ 8.4.
- El core de Moodle no s'actualitza per OTA, només els plugins propis.

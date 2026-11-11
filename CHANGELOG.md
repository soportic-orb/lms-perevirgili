# Registre de canvis

## Fase 3 — Imatge gràfica (en curs)

### Afegit

- `docs/maquetes/`: tres maquetes estàtiques amb Tabler 1.7.4 i Tabler Icons
  (portada pública amb catàleg, tauler de l'alumne i fitxa de curs de
  pagament), amb captures d'escriptori i de mòbil. Pendents d'aprovació.

## Fase 2 — Accés i identitat (11-11-2026)

### Afegit

- `local_pspvaccess` 0.1.0:
  - Regles de treballador configurables (compte del tenant de Microsoft,
    domini de correu, llista de RRHH), combinables amb «una» o «totes».
    Desactivades fins que es configurin.
  - Cohort «Treballadors PSPV», sincronitzada en cada inici de sessió, en
    canviar un compte i cada nit. Els membres afegits a mà no es treuen.
  - Registre d'auditoria de cada alta i baixa automàtica.
  - Importació de la llista de treballadors des de CSV (amb servei i
    categoria professional).
  - Categories «Formació interna» (només visible per a la cohort) i
    «Formació externa» (pública), i rols de treballador/a, gestor/a de
    formació i gestor/a comercial.
  - Proveïdors d'inici de sessió de Google i Microsoft creats a partir de
    `.env`, amb un proveïdor de Microsoft restringit al tenant de l'hospital.
  - `tool_policy` com a gestor de polítiques i tres polítiques en esborrany.
  - Privacy API completa, 23 tests PHPUnit i 4 escenaris Behat.
- `docs/ACCES.md`.

### Canviat

- La instal·lació deixa de forçar l'inici de sessió: el catàleg extern es pot
  consultar sense compte.
- La CI prova cada plugin amb PHP 8.3 i 8.4.

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

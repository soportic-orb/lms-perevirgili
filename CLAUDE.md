# Campus PSPV

Plataforma d'e-learning del Parc Sanitari Pere Virgili sobre **Moodle 5.3 LTS**:
formació interna gratuïta per als treballadors i catàleg extern de pagament.
Proposta completa i fases a `docs/PROPOSTA.md`.

Documentació i missatges de commit en **català**; codi, identificadors i
comentaris de codi en **anglès**, seguint les normes de Moodle.

## Regles

- **No modifiquis mai el core de Moodle.** Tot va en plugins propis a
  `plugins/` (prefix `pspv`). El core viu a `moodle/`, fora de git, a
  l'etiqueta de `.moodle-version`.
- **Tot plugin propi s'actualitza per OTA** (`docs/OTA.md`). Per tant:
  - cap pas manual al servidor: el que calgui fer ho fa `db/install.php`,
    `db/upgrade.php` o una tasca;
  - tot canvi de base de dades, capabilities, tasques o cadenes noves demana
    incrementar `$plugin->version` a `version.php`;
  - mai una versió inferior a una de publicada (l'OTA la rebutja);
  - cada merge a `main` que toca `plugins/` es publica com a versió OTA.
    **No facis push a `main` sense demanar-ho.**
- **Producció és CloudPanel**, no Docker (`docs/CLOUDPANEL.md`): Nginx +
  PHP-FPM, MariaDB/MySQL, un usuari de lloc sense root. Docker és només
  l'entorn local.
- **Estàndards Moodle** a cada plugin: frankenstyle, `version.php`,
  `db/access.php` amb capabilities pròpies, `db/install.xml` (XMLDB),
  `db/upgrade.php`, `lang/{en,ca,es}` (l'anglès és obligatori i és la
  referència), Privacy API a tot plugin que guardi dades personals, Events API
  per a tot el que hagi de quedar registrat.
- **Seguretat:** `require_login`, `require_capability`, `sesskey` a tota
  acció, paràmetres amb `PARAM_*`, consultes amb `$DB` i placeholders,
  sortida amb plantilles Mustache. Cap secret al repositori: tot via `.env`.
- **Tests:** PHPUnit per a la lògica i Behat per als fluxos crítics. La CI ha
  de passar abans de tancar cada fase.
- **Decisions de l'hospital** (pagaments, facturació, criteris de
  treballador, allotjament de producció): atura't i pregunta. No inventis
  valors: configuració amb un `TODO` clar.
- Commits petits i descriptius. Mantén `CHANGELOG.md`.
- El que es crea a la base de dades (cohorts, categories, rols) es busca
  sempre per `idnumber` o `shortname`, mai pel nom, i es crea de manera
  idempotent des de `db/install.php` o `db/upgrade.php`.
- El que `db/install.php` posi a la configuració global del lloc no sobreviu
  a una instal·lació nova (Moodle reescriu els valors per defecte després):
  cal repetir-ho en un `cli/setup.php` que executin `make install` i
  `deploy/cloudpanel/install.sh`.
- El compilador SCSS de Moodle no accepta sumar unitats diferents dins de
  `clamp()`: cal `clamp(1rem, calc(1rem + 1vw), 2rem)`.
- Una fase per vegada; en acabar-la, resum, com provar-ho, pendents, i espera
  l'aprovació.

## Versions verificades (11-11-2026)

| Què | Valor | On s'ha comprovat |
| --- | --- | --- |
| Moodle | 5.3.0 (LTS), 5-10-2026; seguretat fins a l'1-10-2029 | moodledev.io/general/releases/5.3 |
| PHP | mínim 8.3.0, 8.4 admès; `sodium` obligatori; `max_input_vars` ≥ 5000 | ídem |
| Base de dades | MariaDB ≥ 11.4, MySQL ≥ 8.4, PostgreSQL ≥ 17 | ídem |
| Estructura | codi web a `public/`; `config.php`, `admin/cli/` i `vendor/` a l'arrel; cal `composer install` | codi de `v5.3.0` |
| Plugins | `public/<tipus>/<nom>` (p. ex. `public/local/pspvcore`, `public/theme/pspv`) | codi de `v5.3.0` |
| Router | tot el que no és un fitxer va a `public/r.php` (`$CFG->routerconfigured`) | codi de `v5.3.0` |
| Bootstrap de Moodle | 5.3.x (`package.json` del core: `^5.3.8`) | codi de `v5.3.0` |
| moodle-plugin-ci | 4.5.11, amb suport de `public/` | changelog del projecte |
| Tabler | 1.7.4 (`@tabler/core`); Tabler Icons 3.51.0 (`@tabler/icons-webfont`) | npm |

Pendent de verificar quan toqui: versió de Bootstrap que porta Tabler (fase 3)
i compatibilitat amb 5.3 de `paygw_stripe` (fase 4) i `mod_customcert` (fase 5).
moodle.org bloqueja les consultes automàtiques: cal mirar-ho als repositoris
de GitHub de cada plugin.

## Estructura

```
plugins/<tipus>/<nom>/   plugins propis, amb la ruta que tenen dins moodle/public/
config/config.php        configuració de Moodle; tot surt de .env
bin/                     moodle-fetch.sh (core + Composer), sync-plugins.sh
docker-compose.yml       entorn local: PHP 8.4, MariaDB 11.4, Redis, Mailpit, cron
deploy/cloudpanel/       install.sh i vhost de Nginx
scripts/                 ota-keygen.sh, ota-bundle.sh, theme-icons.py
.github/workflows/       ci.yml (moodle-plugin-ci), ota-release.yml
docs/                    PROPOSTA.md, CLOUDPANEL.md, OTA.md, ACCES.md, maquetes/
moodle/                  core de Moodle (no versionat)
```

En afegir un plugin: carpeta a `plugins/`, línia a `x-volumes` de
`docker-compose.yml` i entrada a la matriu `plugin` de `.github/workflows/ci.yml`.

## Ordres

```bash
make up        # arrenca l'entorn (baixa el core el primer cop)
make install   # instal·la Moodle, idiomes i memòria cau Redis
make test      # PHPUnit dels plugins propis
make behat     # Behat (TAGS=@local_pspvcore)
make lint      # sintaxi PHP i estil Moodle (abans: composer install a l'arrel)
make backup    # base de dades i moodledata a backups/
```

`bin/sync-plugins.sh` és per a CloudPanel. No l'executis amb l'entorn Docker
arrencat: substitueix les carpetes on Docker munta els plugins i el contenidor
es queda amb una còpia antiga fins que es recrea (`docker compose up -d --force-recreate web cron`).

## Plugins

- `local_pspvaccess`: qui és treballador (regles configurables: tenant de
  Microsoft, domini de correu, llista de RRHH), cohort `pspv-workers`,
  categories `pspv-internal` i `pspv-external`, rols, auditoria, i
  configuració d'OAuth 2 i de `tool_policy`. Vegeu `docs/ACCES.md`.
- `theme_pspv`: tema fill de Boost amb tokens de Tabler, Tabler Icons,
  logotip i colors de l'hospital, portada pública i peu institucional. Les
  icones es regeneren amb `scripts/theme-icons.py`. Vegeu el seu `README.md`.
- `local_pspvcore`: esquelet de referència per als altres plugins i
  actualitzador OTA (`classes/ota/updater.php`). Només substitueix carpetes de
  plugins el nom dels quals comença per `pspv`.

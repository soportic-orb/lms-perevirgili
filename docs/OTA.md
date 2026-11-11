# Actualitzacions OTA dels plugins propis

Els plugins del Campus PSPV (`plugins/`) s'actualitzen des del repositori,
sense FTP ni SSH:

1. Es treballa en una branca i s'obre una PR.
2. En fusionar-la a `main`, la GitHub Action **OTA release** empaqueta totes
   les carpetes de `plugins/`, signa el manifest i publica una release
   `ota-<versió>`.
3. Cada web (staging i producció) la detecta en menys d'una hora i la mostra
   a **Administració del lloc → Connectors → Connectors locals →
   Actualitzacions del Campus PSPV**. L'aplica una persona administradora amb
   el botó «Aplica la versió …».
4. Si algun plugin ha canviat de versió (`version.php`), tot seguit s'obre la
   pàgina d'actualització de Moodle, que fa els canvis de base de dades.

## Què s'actualitza i què no

| S'actualitza | No es toca mai |
|---|---|
| Carpetes de plugins el nom dels quals comença per `pspv` (`local/pspvcore`, `theme/pspv`, `local/pspvshop`…) | Core de Moodle i `vendor/` |
| | Plugins de la comunitat (`mod_customcert`, `paygw_stripe`…) |
| | `config.php`, `.env`, `moodledata`, base de dades (la canvia només l'actualització de Moodle) |

Una actualització que contingui qualsevol altre fitxer es rebutja sencera.
El core de Moodle, la configuració i els scripts de desplegament s'actualitzen
per SSH (`docs/CLOUDPANEL.md`, «Després»).

## Seguretat

- El manifest (`ota-manifest.json`, amb el sha256 del paquet i la llista de
  carpetes) va signat amb Ed25519. La web només accepta paquets signats amb la
  clau privada del secret `OTA_SIGNING_KEY` del repositori i amb el hash que
  diu el manifest.
- La versió del manifest ha de ser la de la release: un paquet antic ben
  signat no es pot tornar a publicar com a nou.
- No s'accepta cap plugin amb una versió inferior a la instal·lada.
- La clau pública es fixa a `.env` (`OTA_PUBLIC_KEY`): no es pot canviar des de
  l'administració de Moodle.
- Abans d'aplicar es desa una còpia de les carpetes a
  `moodledata/local_pspvcore/ota/backups` (es conserven les 3 últimes).
- Cada carpeta se substitueix amb un parell de reanomenats; si un falla, es
  desfà el que ja s'havia canviat.
- Només els administradors del lloc veuen la pàgina i poden aplicar
  (capability `local/pspvcore:manageupdates`, que cap rol té per defecte).
  Cada aplicació i cada desfer queda al registre de Moodle.

## Desfer una actualització

«Desfés l'última actualització» torna a posar les carpetes desades. Només es
pot fer **mentre Moodle no hagi actualitzat la base de dades** del plugin: un
cop feta, Moodle no accepta codi més antic i el botó ho avisa. En aquest cas
la correcció es publica com a versió nova.

## Posada en marxa (una vegada)

1. **Claus.** En local:

   ```bash
   bash scripts/ota-keygen.sh /ruta/segura
   ```

   - Enganxa el contingut de `ota-signing-key.pem` a GitHub → repositori
     `lms-perevirgili` → Settings → Secrets and variables → Actions → New
     repository secret, amb el nom `OTA_SIGNING_KEY`. Després esborra el fitxer.
   - Afegeix la línia `OTA_PUBLIC_KEY=…` que imprimeix l'script al `.env` de
     staging i de producció.

2. **`.env` de cada web** (opcionals):

   ```
   OTA_REPOSITORY=soportic-orb/lms-perevirgili
   OTA_GITHUB_TOKEN=   # només si el repositori passa a ser privat: token fine-grained amb lectura de Contents
   ```

3. **Cron** de Moodle (`docs/CLOUDPANEL.md`, pas 1.5): la tasca «Cerca
   actualitzacions del Campus PSPV» s'executa cada hora. Sense cron, el botó
   «Cerca actualitzacions» fa el mateix.

4. **Primera OTA.** La instal·lació per SSH ja porta el codi de l'OTA. A
   partir d'aquí, qualsevol PR que toqui `plugins/` genera una release en
   fusionar-se.

La pàgina d'actualitzacions avisa si falta res (extensions `zip` o `sodium`
de PHP, la clau pública o permisos d'escriptura).

## Des de la línia d'ordres

Com l'usuari del lloc, des de `~/htdocs/<domini>`:

```bash
php moodle/public/local/pspvcore/cli/ota.php --status
```

```bash
php moodle/public/local/pspvcore/cli/ota.php --check
```

```bash
php moodle/public/local/pspvcore/cli/ota.php --apply
```

```bash
php moodle/public/local/pspvcore/cli/ota.php --rollback
```

Després d'un `--apply` que avisi d'una actualització pendent:

```bash
php -d max_input_vars=5000 moodle/admin/cli/upgrade.php --non-interactive
```

## Per a qui desenvolupa

- Tot canvi de base de dades, capabilities, tasques, esdeveniments o
  plantilles en memòria cau demana incrementar `$plugin->version`.
- La versió de l'OTA (`AAAAMMDD.HHMM-commit`) la posa la GitHub Action; no cal
  tocar res a mà.
- En local, amb Docker, la pàgina avisa que el servidor web no pot escriure
  al codi: és normal, els plugins hi són muntats directament des de `plugins/`.

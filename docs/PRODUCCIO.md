# Posada en producció

Llista de comprovació per obrir el Campus PSPV. Producció serà **un altre
lloc al mateix servidor CloudPanel** que staging (`perevirgili.soportic.es`),
amb la seva base de dades, la seva carpeta de dades i el seu `.env`.

TODO (hospital): **domini de producció**. Tot el que segueix el fa servir
com a `<domini>`.

## 1. Abans de crear el lloc

- [ ] Domini decidit i DNS apuntant al servidor.
- [ ] Secret `OTA_SIGNING_KEY` creat a GitHub i la clau pública a mà
      ([`OTA.md`](OTA.md)). És la mateixa clau per a staging i producció.
- [ ] Servidor SMTP per al correu de sortida, amb SPF i DKIM del domini
      del remitent. Sense això, els correus de compra, els diplomes i les
      campanyes acaben al correu brossa.
- [ ] Mida del servidor revisada: staging i producció comparteixen CPU,
      memòria i disc. Una prova de càrrega a staging pot alentir producció.

## 2. Crear el lloc

Segueix [`CLOUDPANEL.md`](CLOUDPANEL.md) de dalt a baix amb el domini de
producció, **una base de dades nova** i un `MOODLE_DATAROOT` nou. Al
`.env` de producció:

- [ ] `APP_ENV=production`
- [ ] `MOODLE_WWWROOT=https://<domini>`
- [ ] Base de dades i contrasenyes pròpies (no les de staging).
- [ ] `REDIS_PREFIX` diferent del de staging, si comparteixen Redis.
- [ ] `OTA_PUBLIC_KEY`
- [ ] `SMTP_*`
- [ ] `OAUTH_*` amb les aplicacions de producció (les URL de retorn porten
      el domini: les de staging no serveixen).
- [ ] `BACKUP_DIR`, `BACKUP_KEEP_DAYS` i `HEALTH_TOKEN`.

Dues tasques de cron al lloc: `cron.php` cada minut i `backup.sh` cada nit
([`COPIES.md`](COPIES.md)).

## 3. Configuració que depèn de l'hospital

Cap d'aquests valors s'ha inventat: ara són buits o esborranys.

| Què | On | Document |
|---|---|---|
| Regles de qui és treballador (tenant, dominis, llista de RRHH) | *Plugins locals → Campus PSPV accés* | [`ACCES.md`](ACCES.md) |
| Aplicacions d'inici de sessió de Microsoft i Google | `.env` | [`ACCES.md`](ACCES.md) |
| Textos de les polítiques (privadesa, condicions, comunicacions comercials) i publicar-les | *Usuaris → Privadesa i polítiques* | [`ACCES.md`](ACCES.md) |
| Logotip en SVG, si n'hi ha | *Aparença → Campus PSPV* | README del tema |
| Passarel·la de pagament i compte de pagament | *Plugins → Passarel·les de pagament* | [`VENDA.md`](VENDA.md) |
| IVA dels cursos | *Campus PSPV botiga* | [`VENDA.md`](VENDA.md) |
| Dades fiscals de l'emissor i sèries de factura | *Campus PSPV facturació* | [`VENDA.md`](VENDA.md) |
| Preu per als treballadors | A cada producte | [`VENDA.md`](VENDA.md) |
| On es guarda el NIF/NIE dels alumnes | *Campus PSPV acreditació* | [`ACREDITACIO.md`](ACREDITACIO.md) |
| Signatura del diploma | Plantilla «Diploma PSPV» | [`ACREDITACIO.md`](ACREDITACIO.md) |
| Validació del DPD de l'enviament comercial als treballadors; peu legal dels correus | *Campus PSPV comunicació* | [`COMUNICACIO.md`](COMUNICACIO.md) |
| Destí de la còpia remota i retenció | CloudPanel | [`COPIES.md`](COPIES.md) |

## 4. Abans d'obrir

- [ ] **Mode de proves de la botiga desactivat** (`local_pspvshop/testmode`).
      Amb el mode activat, un administrador pot donar per pagada una comanda.
- [ ] Contrasenya de l'administrador canviada i `MOODLE_ADMIN_PASS`
      esborrada de `.env`.
- [ ] Una compra real de prova amb la passarel·la: pagament, matrícula,
      factura, correu. I el seu reemborsament.
- [ ] Un treballador de prova entra amb Microsoft i veu la formació
      interna; un extern no la veu.
- [ ] Un diploma emès i verificat amb el QR des d'un mòbil sense sessió.
- [ ] Una campanya de prova enviada a un compte extern: arriba, no va al
      correu brossa i l'enllaç de baixa funciona.
- [ ] `backup.sh` executat i **restaurat a staging** ([`COPIES.md`](COPIES.md)).
- [ ] Informe d'estat sense errors i monitor extern en marxa
      ([`MONITORATGE.md`](MONITORATGE.md)).
- [ ] Prova de càrrega a staging amb el nombre d'usuaris esperat
      ([`RENDIMENT.md`](RENDIMENT.md)).
- [ ] Una actualització OTA aplicada a producció i desfeta.
- [ ] Registre d'activitats de tractament i avaluació de protecció de
      dades revisats pel DPD de l'hospital.

## 5. Després d'obrir

- Les actualitzacions arriben primer a staging. No apliquis a producció una
  OTA que no s'hagi provat a staging.
- El core de Moodle 5.3 té correccions de seguretat fins a l'1-10-2029:
  cal anar pujant l'etiqueta de `.moodle-version` a cada versió menor
  (5.3.x) i executar `install.sh` per SSH.
- Prova de restauració cada sis mesos.

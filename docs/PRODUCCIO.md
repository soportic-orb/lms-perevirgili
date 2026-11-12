# Posada en producció

Llista de comprovació per obrir el Campus PSPV. El domini de producció és
**`perevirgili.soportic.es`**: el mateix lloc de CloudPanel on fins ara
s'ha desplegat per provar. No hi ha, doncs, un lloc de staging a part.

Això té dues conseqüències que cal decidir abans d'obrir:

- **Les dades de prova no poden quedar.** Les comandes i factures de prova
  gasten números de la sèrie de factures, que ha de ser correlativa. Cal
  començar producció amb una base de dades i una carpeta de dades netes
  (pas 2).
- **No hi ha on provar abans.** Sense staging, una actualització OTA, una
  restauració o una prova de càrrega es fan directament sobre el campus
  real. Proposta: crear un segon lloc al mateix servidor (per exemple
  `proves.perevirgili.soportic.es`) seguint [`CLOUDPANEL.md`](CLOUDPANEL.md).
  TODO (Soportic): decidir si es crea. Mentrestant, les actualitzacions es
  proven a l'entorn local (`make up`) i s'apliquen a producció fora
  d'hores de feina, amb el botó «Desfés» a mà.

## 1. Abans de començar

- [ ] Secret `OTA_SIGNING_KEY` creat a GitHub i la clau pública a mà
      ([`OTA.md`](OTA.md)).
- [ ] Servidor SMTP per al correu de sortida, amb SPF i DKIM del domini
      del remitent. Sense això, els correus de compra, els diplomes i les
      campanyes acaben al correu brossa.
- [ ] Decidit si es crea un lloc de proves a part.

## 2. Deixar el lloc net

El lloc ja existeix a CloudPanel. Per començar sense dades de prova:

1. Còpia del que hi ha ara, per si de cas: `bash deploy/cloudpanel/backup.sh`
   (després de posar `BACKUP_DIR` a `.env`).
2. A CloudPanel, **crea una base de dades nova** i apunta-hi `DB_NAME`,
   `DB_USER` i `DB_PASS` a `.env`. Canvia `MOODLE_DATAROOT` a una carpeta
   nova i buida.
3. `PHP_BIN=php8.4 bash deploy/cloudpanel/install.sh`: en trobar la base
   de dades buida, instal·la Moodle de nou amb tots els plugins.
4. Quan tot funcioni, esborra la base de dades i la carpeta de dades
   antigues.

Al `.env`:

- [ ] `APP_ENV=production`
- [ ] `MOODLE_WWWROOT=https://perevirgili.soportic.es`
- [ ] `MOODLE_ADMIN_PASS` nova per a la instal·lació (i esborrada després).
- [ ] `OTA_PUBLIC_KEY`
- [ ] `SMTP_*`
- [ ] `OAUTH_*` amb les aplicacions de l'hospital (URL de retorn amb
      aquest domini).
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
      interna; un ciutadà no la veu.
- [ ] Un diploma emès i verificat amb el QR des d'un mòbil sense sessió.
- [ ] Una campanya de prova enviada a un compte de ciutadà: arriba, no va al
      correu brossa i l'enllaç de baixa funciona.
- [ ] `backup.sh` executat i **restaurat de prova** abans d'entrar-hi dades
      reals ([`COPIES.md`](COPIES.md)).
- [ ] Informe d'estat sense errors i monitor extern en marxa
      ([`MONITORATGE.md`](MONITORATGE.md)).
- [ ] Prova de càrrega, abans d'obrir, amb el nombre d'usuaris esperat
      ([`RENDIMENT.md`](RENDIMENT.md)).
- [ ] Una actualització OTA aplicada a producció i desfeta.
- [ ] Registre d'activitats de tractament i avaluació de protecció de
      dades revisats pel DPD de l'hospital.

## 5. Després d'obrir

- Prova cada actualització abans d'aplicar-la: al lloc de proves si n'hi
  ha, o a l'entorn local. Aplica-la fora d'hores de feina.
- El core de Moodle 5.3 té correccions de seguretat fins a l'1-10-2029:
  cal anar pujant l'etiqueta de `.moodle-version` a cada versió menor
  (5.3.x) i executar `install.sh` per SSH.
- Prova de restauració cada sis mesos, al lloc de proves o en un servidor
  a part: mai sobre el campus real.

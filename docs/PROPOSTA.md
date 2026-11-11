# Campus PSPV — Proposta de desenvolupament (Moodle)

11 de novembre de 2026 · Octavi Rodriguez

> Revisada l'11 de novembre de 2026: allotjament a CloudPanel i actualitzacions OTA, com la resta de plataformes de Soportic. Les versions s'han verificat a moodledev.io (vegeu `CLAUDE.md`).

## Objectiu i abast

Campus PSPV és una única instal·lació de Moodle 5.3 LTS amb dos espais separats: formació interna gratuïta per als treballadors i un catàleg extern de cursos de pagament obert a tothom.

|  | Formació interna | Formació externa |
| --- | --- | --- |
| Públic | Professionals i treballadors del Parc Sanitari Pere Virgili | Professionals externs i públic general |
| Preu | Gratuïta | De pagament (venda de cursos) |
| Accés | Només treballadors, validats pel compte Microsoft corporatiu | Autoregistre amb correu, Google o Microsoft |
| Visibilitat | Categoria de cursos invisible per a externs | Catàleg públic, visible sense iniciar sessió |
| Matrícula | Automàtica per cohort o autoinscripció restringida | Matrícula en confirmar-se el pagament |

Inclou tot el que s'espera d'un LMS: gestió de cursos i usuaris, diplomes verificables, mailing, informes i accés amb comptes Microsoft i Google. La personalització gràfica i les funcions que Moodle no porta es fan com a plugins propis, sense modificar el core.

## Arquitectura i stack

Un sol Moodle separa els dos públics: els treballadors entren per la cohort i els externs pel pagament.

El treballador mai passa per la botiga, i l'extern mai entra a la cohort interna; tota la resta (diplomes, acreditació, mailing, informes) és comú.

- **Base:** Moodle 5.3 LTS, amb suport de seguretat fins a l'octubre de 2029.
- **Servidor:** CloudPanel (Nginx + PHP-FPM 8.4). Base de dades MariaDB ≥ 11.4 o MySQL ≥ 8.4, que són les que ofereix CloudPanel i les mínimes que exigeix Moodle 5.3. Redis per a sessions i memòria cau.
- **Entorns:** local amb Docker; staging a `perevirgili.soportic.es` (CloudPanel); producció al domini que decideixi l'hospital. Tots tres surten del mateix repositori.
- **Actualitzacions OTA:** cada canvi dels plugins propis que arriba a `main` es publica com a versió signada; cada web la detecta i una persona administradora l'aplica amb un botó, sense SSH ni FTP. El core de Moodle i la configuració s'actualitzen per SSH amb un script.
- **Repositori:** només el codi propi (plugins `pspv`) i la configuració; el core de Moodle es fixa a una etiqueta oficial.

## Mapa funcional

La majoria de funcions ja existeixen al core de Moodle o en plugins mantinguts. El desenvolupament propi se centra en el control d'accés intern, l'aparador comercial, la facturació i l'acreditació.

| Funcionalitat | Solució | Origen |
| --- | --- | --- |
| Gestió de cursos, activitats, avaluació, SCORM, H5P | Moodle core | Core |
| Gestió d'usuaris, rols, cohorts, càrrega massiva | Moodle core | Core |
| Login amb Microsoft (Entra ID) i Google | `auth_oauth2` amb proveïdors Microsoft i Google | Core |
| Validació de treballador i assignació a cohort interna | `local_pspvaccess` | A mida |
| Venda de cursos | `enrol_fee` + subsistema de pagaments | Core |
| Pagament amb targeta (Stripe) | `paygw_stripe` | Comunitat |
| Pagament amb TPV bancari (Redsys), si l'hospital ho exigeix | `paygw_redsys` | A mida (si no n'hi ha cap de mantingut) |
| Aparador públic: catàleg, fitxa de curs, cistella | `local_pspvshop` | A mida |
| Factures i abonaments (Verifactu si escau) | `local_pspvinvoice` | A mida |
| Diplomes amb codi de verificació i QR | `mod_customcert` | Comunitat |
| Crèdits CCFCPS, hores i registre d'acreditació | `local_pspvaccredit` | A mida |
| Mailing massiu i campanyes per segments | `local_pspvmailing` sobre l'API de missatges de Moodle | A mida |
| Notificacions transaccionals (benvinguda, compra, diploma) | Plantilles de Moodle + esdeveniments de `local_pspvshop` | Core + a mida |
| RGPD: consentiments i drets ARSULIPO | `tool_policy` + `tool_dataprivacy` | Core |
| Informes i quadre de comandament | Report builder de Moodle + `report_pspvdashboard` | Core + a mida |
| Imatge gràfica de l'hospital | `theme_pspv`, tema fill de Boost amb disseny Tabler i Tabler Icons | A mida |
| Idiomes català, castellà i anglès | Paquets d'idioma de Moodle + cadenes pròpies | Core |

Cal verificar la compatibilitat amb Moodle 5.3 de cada plugin de la comunitat abans d'instal·lar-lo. Si algun no és compatible, Claude Code n'ha de fer el port o substituir-lo per un mòdul propi.

## Mòduls a mida

Són set plugins propis, més `local_pspvcore` (esquelet de referència i actualitzador OTA), tots amb prefix `pspv`, tests PHPUnit i Behat, i cadenes en català, castellà i anglès.

1. **`theme_pspv` — imatge gràfica.** Tema fill de Boost amb el sistema de disseny Tabler i Tabler Icons, adaptat a la identitat de l'hospital: colors, tipografia, logotips, capçalera i peu institucionals. Portada pública amb el catàleg extern i accés diferenciat "Sóc treballador/a" / "Sóc extern". Compliment WCAG 2.1 AA.
2. **`local_pspvaccess` — control d'accés intern.** En cada login decideix si l'usuari és treballador: compte del tenant Entra ID de l'hospital, domini de correu corporatiu o llista de treballadors importada de RRHH. Si ho és, l'afegeix a la cohort "Treballadors PSPV"; si deixa de ser-ho, el treu. Registre d'auditoria de cada canvi.
3. **`local_pspvshop` — aparador i compra.** Catàleg públic amb filtres (àrea, modalitat, crèdits, preu, data), fitxa de curs amb SEO, cistella, codis de descompte i preus diferenciats (per exemple, col·legiats o entitats conveniades). Fa servir el subsistema de pagaments de Moodle i matricula en confirmar-se el pagament.
4. **`local_pspvinvoice` — facturació.** Sol·licitud de factura amb dades fiscals (particular o empresa), numeració per sèries, PDF, abonaments i reemborsaments, i exportació per a comptabilitat. Integració amb Verifactu o amb l'ERP de l'hospital, segons es decideixi.
5. **`local_pspvaccredit` — acreditació CCFCPS.** Camps per curs: codi d'expedient, crèdits, hores, dates d'acreditació. Criteris d'aprovació (assistència i avaluació) i informe d'alumnes aptes exportable per a la justificació.
6. **`local_pspvmailing` — comunicació.** Campanyes a segments (cohort, curs, estat de compra, consentiment comercial), plantilles amb la imatge de l'hospital, enviament programat via cua de Moodle i baixa amb un clic. Respecta el consentiment registrat a `tool_policy`.
7. **`report_pspvdashboard` — quadre de comandament.** Matrícules, finalització, vendes i ingressos per curs i període, i formació interna per servei o categoria professional.

## Disseny gràfic: Tabler

L'entorn gràfic es basa en [Tabler](https://github.com/tabler/tabler) (llicència MIT): interfície clara i senzilla, targetes, molt espai en blanc i Tabler Icons.

- **Sense Bootstrap duplicat.** Tabler 1.5 porta Bootstrap dins el paquet, i Moodle 5.3 ja carrega el seu. No es carrega `tabler.min.css` sencer: es porten a l'SCSS del tema els tokens de Tabler (colors, radis, ombres, tipografia, espaiats) i només els components que es facin servir.
- **Patrons de Tabler a les plantilles.** Les plantilles Mustache del tema reprodueixen la navbar, la capçalera de pàgina, les targetes de curs, els estats buits, les taules i els badges de Tabler. També a la botiga i al quadre de comandament.
- **Icones.** Tabler Icons (`@tabler/icons-webfont`, MIT) amb un sistema d'icones propi del tema que tradueix les icones de Moodle; on no hi hagi equivalent, Font Awesome com a reserva.
- **Sense CDN.** Fonts i icones allotjades al servidor, per RGPD i per funcionar en xarxes hospitalàries restringides.
- **Colors de l'hospital** aplicats sobre els tokens primari i secundari de Tabler, configurables des de l'administració i amb contrast WCAG 2.1 AA.
- **Maquetes primer.** Abans d'implementar el tema, tres pantalles estàtiques per aprovar: portada amb catàleg, tauler de l'alumne i fitxa de curs de pagament.

## Fases i lliurables

El desenvolupament va en sis fases. Cada una acaba amb una demo a l'hospital i no comença la següent fins que s'aprova.

1. **Fonaments.** Entorn Docker, Moodle 5.3 LTS net, repositori Git, `CLAUDE.md`, CI amb PHPUnit, Behat i comprovació d'estil Moodle, instal·lació a CloudPanel i actualitzacions OTA. Lliurable: entorn local i de staging funcionant, i una OTA aplicada a staging.
2. **Accés i identitat.** OAuth2 Microsoft i Google, `local_pspvaccess`, cohorts, categories interna i externa amb permisos, polítiques RGPD. Lliurable: un treballador veu només la formació interna; un extern no la veu.
3. **Imatge gràfica.** `theme_pspv` amb disseny Tabler i Tabler Icons, i portada pública. Lliurable: maquetes aprovades per comunicació de l'hospital.
4. **Venda.** `local_pspvshop`, passarel·la de pagament en mode test, `local_pspvinvoice`. Lliurable: compra completa de prova amb matrícula i factura.
5. **Diplomes, acreditació i comunicació.** `mod_customcert` amb plantilles, `local_pspvaccredit`, `local_pspvmailing`. Lliurable: curs acreditat de punta a punta amb diploma verificable.
6. **Informes i posada en producció.** `report_pspvdashboard`, proves de càrrega, còpies de seguretat, monitoratge, manual d'administració. Lliurable: plataforma en producció i formació a l'equip gestor.

## Decisions pendents amb l'hospital

Aquestes respostes condicionen el codi; cal tenir-les abans de la fase 2.

- [ ] Com s'identifica un treballador: tenant Entra ID, domini de correu, llista de RRHH o una combinació?
- [ ] Què passa amb el personal sense compte Microsoft corporatiu (per exemple, personal extern o en pràctiques)?
- [ ] Passarel·la de pagament: Stripe, Redsys o totes dues? Hi ha requisits de contractació pública?
- [ ] Qui emet les factures i amb quin sistema: Moodle o l'ERP de l'hospital?
- [ ] Pot un treballador comprar cursos externs? Amb descompte?
- [ ] Manual d'identitat corporativa, logotips i tipografies.
- [x] Allotjament de staging: servidor CloudPanel de Soportic, a `perevirgili.soportic.es`.
- [ ] Allotjament de producció: el mateix servidor CloudPanel, servidors de l'hospital o un altre? Requisits del departament de sistemes i del DPD.
- [ ] Domini de producció: per exemple `campus.` sota el domini de l'hospital.
- [ ] Cursos acreditats pel CCFCPS i criteris d'aprovació que s'han de registrar.

## Prompt per a Claude Code

Enganxa'l a Claude Code en un directori buit. Està pensat per executar-se fase a fase: Claude Code s'atura en acabar cada fase perquè la revisis.

```markdown
# Projecte: Campus PSPV — plataforma d'e-learning del Parc Sanitari Pere Virgili

Ets l'enginyer principal d'aquest projecte. Construirem una plataforma d'e-learning sobre Moodle 5.3 LTS (open source), personalitzada en funcionalitats i en imatge gràfica. Treballa en català als comentaris de commits i a la documentació; el codi i els identificadors, en anglès, seguint les normes de Moodle.

## Context funcional

Una sola instal·lació de Moodle amb dos espais:

1. Formació interna: per als treballadors del centre. Gratuïta. Només hi poden accedir usuaris validats com a treballadors.
2. Formació externa: per a tothom (professionals externs i públic general). De pagament: venda de cursos amb catàleg públic, cistella i passarel·la de pagament.

Funcions requerides: gestió de cursos, gestió d'usuaris, diplomes verificables, mailing, informes, i accés i registre amb comptes Microsoft i Google. Idiomes: català (per defecte), castellà i anglès.

## Regles innegociables

- No modifiquis MAI el core de Moodle. Tota personalització va en plugins propis (tema fill, local_, report_, block_, paygw_, mod_ si cal). L'objectiu és poder actualitzar a la següent LTS sense reescriure res.
- Abans de crear cap plugin, comprova a moodledev.io l'estructura de directoris de Moodle 5.3 (carpeta public/ i ubicació dels plugins) i la versió de PHP i de base de dades suportades. No suposis res de versions anteriors.
- Segueix el Moodle coding style, el frankenstyle (prefix pspv per a tots els plugins propis), version.php, db/access.php amb capabilities pròpies, db/install.xml amb XMLDB, db/upgrade.php, lang/{ca,es,en}, Privacy API (provider.php) a tot plugin que guardi dades personals, i Events API per a tot el que hagi de quedar registrat.
- Seguretat: require_login, require_capability, sesskey en totes les accions, parametres netejats amb PARAM_*, consultes amb $DB i placeholders, sortida escapada amb templates Mustache. Cap secret al repositori: tot via config.php o variables d'entorn.
- Tests: PHPUnit per a la lògica i Behat per als fluxos crítics (login intern/extern, compra, emissió de diploma). La CI ha de passar abans de tancar cada fase.
- Abans de prendre una decisió que depengui de l'hospital (pagaments, facturació, criteris de treballador, allotjament), atura't i pregunta. No inventis valors: deixa'ls com a configuració amb un TODO clar.
- Fes commits petits i descriptius. Mantén actualitzat CHANGELOG.md.
- La plataforma s'instal·la en un servidor CloudPanel (Nginx + PHP-FPM, MariaDB o MySQL, sense Docker ni root) i s'actualitza per OTA, com la resta de plataformes de Soportic (referència: soportic-orb/torns-web, docs/OTA.md i docs/CLOUDPANEL.md). Tot plugin propi ha de poder-se substituir per OTA: cap pas manual al servidor, canvis de base de dades només via db/upgrade.php amb la versió de version.php incrementada, i mai una versió inferior a la ja publicada.

## Arquitectura de plugins propis

- theme_pspv: tema fill de Boost amb el sistema de disseny Tabler i la identitat de l'hospital (colors, tipografia, logotips, capçalera i peu). Vegeu la secció Disseny gràfic. Portada pública amb catàleg extern i doble accés "Sóc treballador/a" / "Sóc extern". WCAG 2.1 AA. Els colors i logotips han de ser configurables des de l'administració; mentre no tinguem el manual d'identitat, usa marcadors de posició.
- local_pspvaccess: en cada login (observer de l'event user_loggedin) determina si l'usuari és treballador segons una política configurable: tenant Entra ID, domini de correu corporatiu i/o llista de treballadors importada (CSV o API de RRHH). Afegeix o treu l'usuari de la cohort "Treballadors PSPV". Taula d'auditoria de canvis. Tasca programada de resincronització.
- local_pspvshop: catàleg públic (sense login) amb filtres per àrea, modalitat, crèdits, preu i data; fitxa de curs amb metadades SEO; cistella; codis de descompte; preus per tipus de client. Usa el subsistema de pagaments de Moodle (core_payment) i matricula via enrol_fee o un mètode d'inscripció propi quan el pagament es confirma.
- local_pspvinvoice: dades fiscals del comprador (particular o empresa), sèries i numeració correlativa, PDF de factura, abonaments, exportació comptable. Deixa una interfície (interface PHP) per connectar-hi Verifactu o l'ERP de l'hospital; no implementis cap integració fiscal fins que es confirmi.
- local_pspvaccredit: camps per curs (codi d'expedient CCFCPS, crèdits, hores, dates), criteris d'aprovació i informe exportable d'alumnes aptes.
- local_pspvmailing: campanyes a segments (cohort, curs, estat de compra, consentiment comercial), plantilles amb la imatge del tema, enviament per tasques adhoc, baixa amb un clic. Només envia comunicacions comercials a qui hagi acceptat la política corresponent a tool_policy.
- report_pspvdashboard: matrícules, finalització, vendes i ingressos per curs i període; formació interna per servei i categoria professional.
- paygw_redsys: només si l'hospital ho demana i no existeix cap plugin mantingut compatible amb 5.3.

Plugins de la comunitat previstos (verifica compatibilitat amb 5.3 abans d'afegir-los; si no ho són, informa'm): paygw_stripe, mod_customcert.

Core a configurar (no a reprogramar): auth_oauth2 amb Microsoft i Google, enrol_fee, enrol_cohort, enrol_self, tool_policy, tool_dataprivacy, report builder, H5P i SCORM.

## Model de permisos

- Categoria "Formació interna": visible només per a la cohort "Treballadors PSPV" (rol amb moodle/category:viewcourselist a nivell de categoria, denegat per defecte). Matrícula per enrol_cohort o enrol_self restringit a la cohort.
- Categoria "Formació externa": visible per a tothom, inclosos visitants; matrícula només per pagament.
- Rols addicionals: gestor/a de formació (gestiona cursos i usuaris, sense administració del lloc), gestor/a comercial (vendes, descomptes, factures), docent.

## Disseny gràfic (Tabler)

- Basa tot l'entorn gràfic en Tabler (https://github.com/tabler/tabler, llicència MIT): disseny clar i senzill, targetes, molt espai en blanc. Fes servir docs.tabler.io i preview.tabler.io com a referència visual.
- Tabler (1.5 o posterior; l'última versió publicada en començar el projecte és la 1.7) inclou Bootstrap dins el paquet i Moodle 5.3 ja carrega Bootstrap 5. No carreguis tabler.min.css sencer ni cap segon Bootstrap: porta a l'SCSS de theme_pspv els tokens de Tabler (colors, radis, ombres, tipografia, espaiats) i només els estils dels components que facis servir. Comprova abans les versions de Bootstrap de Moodle 5.3 i de Tabler i documenta-ho al CLAUDE.md.
- Sobreescriu les plantilles Mustache del tema per reproduir els patrons de Tabler: navbar, capçalera de pàgina, targetes de curs al catàleg i al tauler, estats buits, taules, badges i formularis. Aplica'ls també a local_pspvshop i report_pspvdashboard.
- Icones: només Tabler Icons (@tabler/icons-webfont, MIT). Implementa un sistema d'icones propi del tema que mapegi els identificadors d'icona de Moodle (core i plugins) a classes ti-*. On no hi hagi equivalent, Font Awesome com a reserva, i deixa'n la llista al README del tema.
- Fonts i icones allotjades dins el tema, sense CDN externs.
- Colors de l'hospital aplicats sobre els tokens primari i secundari de Tabler, configurables des de l'administració del tema. Contrast WCAG 2.1 AA a tots els components.
- Abans d'implementar el tema, genera tres maquetes HTML estàtiques amb Tabler (portada pública amb catàleg, tauler de l'alumne, fitxa de curs de pagament) i atura't perquè les aprovem.

## Entorn

- Local: docker-compose amb Moodle 5.3 LTS, MariaDB 11.4 (la mateixa família que producció), Redis (sessions i cache MUC), Mailpit per a correus en local, cron de Moodle.
- Staging i producció: CloudPanel. Staging a perevirgili.soportic.es. Un script idempotent (deploy/cloudpanel/install.sh) instal·la o actualitza el core i els plugins executat com l'usuari del lloc; la configuració surt d'un fitxer .env que no es versiona.
- Actualitzacions OTA: una GitHub Action empaqueta els plugins propis a cada push a main, signa el manifest amb Ed25519 i publica una release "ota-<versió>". local_pspvcore la detecta (tasca programada), verifica signatura i hash, fa còpia de seguretat, substitueix només carpetes de plugins pspv i deixa que l'actualització de Moodle faci els canvis de base de dades. Ha de poder-se desfer. El core de Moodle no s'actualitza per OTA: es canvia l'etiqueta a .moodle-version i s'executa l'script per SSH.
- Scripts make: up, down, install, test, behat, lint, backup.
- Estructura del repositori: el core de Moodle com a dependència fixada a una etiqueta (submòdul o descàrrega per script), i els nostres plugins al nostre codi. El repositori només versiona el nostre codi i la configuració.

## Forma de treballar

Fes només una fase per vegada. En acabar-la, resumeix què has fet, com provar-ho i què queda pendent, i atura't fins que t'ho aprovi.

Fase 1 — Fonaments. Crea CLAUDE.md amb aquestes regles resumides, l'entorn Docker, la instal·lació neta de Moodle 5.3, la CI (PHPUnit, Behat, moodle-plugin-ci o equivalent), el plugin local_pspvcore com a esquelet de referència i amb l'actualitzador OTA, i la guia i l'script d'instal·lació a CloudPanel.
Fase 2 — Accés i identitat: OAuth2, local_pspvaccess, cohorts, categories, rols, polítiques RGPD.
Fase 3 — maquetes Tabler per aprovar, theme_pspv amb Tabler i Tabler Icons, i portada pública.
Fase 4 — local_pspvshop, pagament en mode test, local_pspvinvoice.
Fase 5 — mod_customcert amb plantilles de diploma i QR de verificació, local_pspvaccredit, local_pspvmailing.
Fase 6 — report_pspvdashboard, proves de càrrega, còpies de seguretat, monitoratge i manual d'administració en català.

Comença per la fase 1. Abans d'escriure codi, presenta'm el pla de la fase i les preguntes que tinguis.
```

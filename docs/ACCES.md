# Accés i identitat

Qui veu què al Campus PSPV, com es decideix qui és treballador i com es
configuren els inicis de sessió amb Microsoft i Google. Ho fa el plugin
`local_pspvaccess`.

## Model

| Peça | Identificador | Per a què |
|---|---|---|
| Cohort «Treballadors PSPV» | `pspv-workers` | Els treballadors de l'hospital |
| Categoria «Formació interna» | `pspv-internal` | Només la veuen els membres de la cohort i qui la gestiona |
| Categoria «Formació externa» | `pspv-external` | Catàleg públic, visible sense iniciar sessió |
| Rol «Treballador/a PSPV» | `pspvworker` | Mostra la categoria interna; s'assigna sol als membres de la cohort |
| Rol «Gestor/a de formació» | `pspvtrainingmanager` | Gestiona cursos i usuaris, sense configuració del lloc |
| Rol «Gestor/a comercial» | `pspvsalesmanager` | Vendes, descomptes i factures (permisos a `docs/VENDA.md`) |

Els noms es poden canviar des de l'administració: el codi ho busca tot per
identificador.

La categoria interna s'amaga traient el permís
`moodle/category:viewcourselist` als rols de visitant i d'usuari autenticat
**només en aquella categoria**, i donant-lo al rol `pspvworker`. Un extern
no veu ni la categoria ni els seus cursos, tampoc a les cerques.

Per matricular en un curs intern es fa servir, a cada curs, la
**sincronització de cohort** (tothom matriculat) o l'**automatrícula
restringida a la cohort** «Treballadors PSPV».

## Qui és treballador

A **Administració del lloc → Connectors → Connectors locals → Accés del
Campus PSPV → Regles de treballador** hi ha tres regles. Totes estan
desactivades fins que es configurin: mentre no n'hi hagi cap, ningú entra a
la cohort automàticament.

| Regla | Quan es compleix | Com s'activa |
|---|---|---|
| Compte Microsoft corporatiu | L'usuari ha iniciat sessió amb el proveïdor restringit al tenant de l'hospital | Triant el proveïdor |
| Domini de correu corporatiu | El compte està confirmat i l'adreça és d'un dels dominis | Escrivint els dominis |
| Llista de treballadors | L'adreça és a la llista importada de RRHH | Marcant la casella |

Es pot triar si n'hi ha prou amb una regla o s'han de complir totes.

La comprovació es fa en cada inici de sessió, en crear-se o canviar un
compte, i cada nit per a tothom (tasca «Resincronitza la cohort de
treballadors»). Qui deixa de complir les regles surt de la cohort.

**Excepcions a mà.** Un membre afegit a mà a la cohort (per exemple, personal
en pràctiques sense compte corporatiu) no es treu mai automàticament: el
plugin només treu qui hi ha posat ell.

**Auditoria.** Cada alta i baixa automàtica queda a «Registre d'auditoria de
la cohort de treballadors», amb la regla i el motiu de la comprovació.

### Llista de treballadors

«Llista de treballadors» importa un CSV. La primera fila són els noms de les
columnes: `email` és obligatòria; `employeeid`, `department` (servei) i
`jobcategory` (categoria professional) són opcionals. Marcant «Treu els que
no siguin al fitxer», el fitxer substitueix tota la llista.

`department` i `jobcategory` es faran servir als informes de la fase 6.

## Inici de sessió amb Microsoft i Google

Els proveïdors es creen a partir de `.env`; sense credencials no es crea res.

```
OAUTH_GOOGLE_CLIENT_ID=
OAUTH_GOOGLE_CLIENT_SECRET=
OAUTH_MICROSOFT_CLIENT_ID=
OAUTH_MICROSOFT_CLIENT_SECRET=
OAUTH_MICROSOFT_TENANT=
```

Després d'omplir-les, com l'usuari del lloc:

```bash
php moodle/public/local/pspvaccess/cli/setup.php
```

(`deploy/cloudpanel/install.sh` i `make install` ja l'executen.)

### Què cal registrar

A totes dues aplicacions, l'URI de redirecció és
`https://<domini>/admin/oauth2callback.php`.

- **Google** (Google Cloud Console → APIs i serveis → Credencials → ID de
  client d'OAuth, tipus «Aplicació web»).
- **Microsoft** (portal d'Entra ID → Registres d'aplicacions). Una sola
  aplicació, amb «Comptes de qualsevol directori organitzatiu i comptes
  personals de Microsoft», serveix per als dos proveïdors que es creen:
  - **Microsoft**: obert a qualsevol compte, per als externs.
  - **Compte corporatiu PSPV**: restringit al tenant de `OAUTH_MICROSOFT_TENANT`.
    És el que comprova la regla «Compte Microsoft corporatiu».

`OAUTH_MICROSOFT_TENANT` és l'identificador del tenant de l'hospital (o un
domini verificat). Sense aquest valor el proveïdor corporatiu no es crea.

Els comptes nous creats amb Microsoft o Google han de confirmar l'adreça per
correu abans d'entrar. Això és el que permet fiar-se de la regla del domini:
ningú pot fer-se passar per una adreça que no és seva.

## RGPD

`local/pspvaccess/cli/setup.php` deixa `tool_policy` com a gestor de
polítiques i crea tres documents **en esborrany**, amb text provisional:

| Document | Tipus | Acceptació |
|---|---|---|
| Política de privacitat | Privacitat | Obligatòria |
| Condicions d'ús | Lloc | Obligatòria |
| Comunicacions comercials | Altres | Opcional (la farà servir el mailing) |

No s'activen sols: cal posar-hi el text del delegat de protecció de dades i
activar-los a **Administració del lloc → Usuaris → Privadesa i polítiques del lloc →
Gestiona les polítiques**.

També activa l'enllaç perquè els usuaris contactin amb el delegat de
protecció de dades des del seu perfil. Queda pendent indicar qui és
(Privadesa i polítiques del lloc → Paràmetres de la privadesa).

## Decisions pendents de l'hospital

El codi no en dona cap per feta; totes són configuració.

- Quines regles s'activen i si es combinen (una o totes).
- Identificador del tenant d'Entra ID i dominis de correu corporatius.
- Si l'accés corporatiu caduca quan fa temps que no s'hi inicia sessió
  («Dies de validesa d'un inici de sessió corporatiu»; 0 = no caduca).
- Què es fa amb el personal sense compte Microsoft corporatiu: afegir-lo a mà
  a la cohort o incloure'l a la llista de RRHH.
- Text de les tres polítiques i qui és el delegat de protecció de dades.
- Qui registra les aplicacions de Google i Microsoft (Soportic o l'hospital).

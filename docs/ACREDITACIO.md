# Acreditació i diplomes

Com es gestiona un curs acreditat (CCFCPS): les dades de l'acreditació, el
control d'assistència, qui és apte, l'informe per a la justificació i el
diploma verificable. Ho fa `local_pspvaccredit`; el diploma s'emet amb el
plugin de la comunitat `mod_customcert`.

## Dades de l'acreditació

Són **camps personalitzats del curs**, a la secció «Acreditació» de la
configuració de cada curs. Així viatgen amb les còpies de seguretat del
curs i es poden imprimir al diploma. El plugin els crea en instal·lar-se i
els busca sempre pel nom curt.

| Camp | Nom curt | Qui el veu |
|---|---|---|
| Activitat acreditada | `pspv_accredited` | Tothom |
| Codi d'expedient | `pspv_filecode` | Tothom |
| Crèdits | `pspv_credits` | Tothom |
| Hores | `pspv_hours` | Tothom |
| Acreditat des de / fins a | `pspv_accreditedfrom`, `pspv_accrediteduntil` | Docents |
| Assistència mínima (%) | `pspv_minattendance` | Docents |
| Nota mínima (%) | `pspv_mingrade` | Docents |

## Qui és apte

Un alumne és apte quan compleix **tots** els criteris que tingui el curs:

- **Compleció del curs**, si el curs té el seguiment de compleció activat
  amb criteris.
- **Nota mínima**: la nota total del curs, en percentatge de la màxima.
- **Assistència mínima**: percentatge del temps de les sessions a què ha
  assistit. Una sessió llarga pesa més que una de curta.

Un curs sense cap criteri no dona ningú per apte automàticament, i un curs
amb assistència mínima però sense sessions tampoc.

Quan un alumne passa a ser apte queda al **registre**, amb una còpia del
codi d'expedient, els crèdits, les hores, l'assistència i la nota d'aquell
moment: canviar després el curs no altera el que es va certificar. Es
comprova en completar el curs, en passar llista, cada nit (tasca
programada) i amb el botó «Recalcula els aptes». Ningú no perd el registre
automàticament; qui gestiona el curs pot registrar algú a mà (queda marcat
com a tal) o esborrar un registre.

## Sessions i assistència

A *Curs → Més → Acreditació* (els docents):

1. «Afegeix una sessió»: nom, data i durada en minuts.
2. «Passa llista»: es marca qui hi ha assistit. Es pot corregir tornant-hi.

## Informes

- **Per curs**, a la mateixa pàgina: alumnes amb assistència, nota,
  compleció i estat, i exportació dels aptes (CSV, Excel, ODS…).
- **De tot el lloc**, a *Administració → Plugins → Plugins locals → Campus
  PSPV acreditació → Registre d'alumnes aptes*: tots els registres, amb
  filtre de dates i exportació.

Columnes de l'exportació: cognoms, nom, document d'identitat, correu,
curs, codi d'expedient, crèdits, hores, assistència, nota i data.

## Diploma

Necessita `mod_customcert` (versió fixada a `.community-plugins`; no va per
OTA, s'instal·la amb `deploy/cloudpanel/install.sh`). Sense el plugin, tot
el que hi ha més amunt funciona igual i la pàgina ho avisa.

A la pàgina d'acreditació del curs, «Afegeix el diploma a aquest curs»
crea una activitat de certificat:

- feta a partir de la **plantilla del lloc** «Diploma PSPV» (A4 apaïsat:
  logotip, nom de l'alumne, curs, hores, crèdits, codi d'expedient, data,
  codi i QR de verificació);
- que **només poden obrir els aptes** (restricció d'accés pel grup
  `pspv-accredited`, que omple el plugin);
- **verificable per qualsevol** que tingui el codi o escanegi el QR, a
  `/mod/customcert/verify_certificate.php`, sense iniciar sessió.

La plantilla es crea un sol cop i des d'aleshores és dels administradors,
que la poden canviar a *Administració → Plugins → Mòduls d'activitat →
Certificat personalitzat → Gestiona les plantilles*. Cada activitat té la
seva còpia: canviar la plantilla del lloc no altera els diplomes de cursos
que ja la tenen.

## Permisos

| Permís | Qui el té | Per a què |
|---|---|---|
| `local/pspvaccredit:manage` | Docent amb edició, gestor | Sessions, recalcular, registrar a mà, afegir el diploma |
| `local/pspvaccredit:markattendance` | Docents, gestor | Passar llista |
| `local/pspvaccredit:viewreport` | Docents, gestor | Veure i exportar els aptes |

## Decisions pendents de l'hospital

| Què | On es configura | Estat |
|---|---|---|
| On es guarda el document d'identitat (NIF/NIE) dels alumnes | `local_pspvaccredit/idfield`: número d'identificació de l'usuari o un camp de perfil | TODO: sense triar, la columna surt buida |
| Model de l'informe que demana el CCFCPS | — | TODO: l'informe és genèric; s'ajustarà quan arribi el model |
| Qui signa el diploma, i imatge de la signatura | Plantilla «Diploma PSPV» | TODO: el diploma surt sense signatura |
| Criteris d'aptitud per defecte (assistència i nota mínimes) | A cada curs | No n'hi ha de predefinits |

## Dades personals

L'assistència i el registre d'aptes s'exporten i s'esborren amb la Privacy
API, per curs. Si l'hospital ha de conservar el registre per justificar
l'acreditació, cal definir-ne el període de retenció a *Privadesa i
polítiques → Registre de dades* abans d'aprovar una sol·licitud d'esborrat.

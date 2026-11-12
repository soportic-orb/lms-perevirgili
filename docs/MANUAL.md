# Manual d'administració del Campus PSPV

Per a l'equip que gestiona el campus dia a dia. Explica què es fa i on;
els detalls de cada àrea són als documents enllaçats.

Les rutes de menú comencen a **Administració del lloc** tret que es digui
una altra cosa.

## Qui fa què

| Rol | Què pot fer |
|---|---|
| Administrador/a | Tot, incloses la configuració i les actualitzacions |
| Gestor/a de formació | Crear i gestionar cursos i usuaris, acreditació, campanyes, quadre de comandament |
| Gestor/a comercial | Productes, preus, descomptes, comandes, factures, campanyes, quadre de comandament |
| Docent | Els seus cursos: continguts, notes, sessions i assistència |
| Treballador/a PSPV | Veu i fa la formació interna |
| Ciutadà/ana | Compra i fa la formació per a la ciutadania |

Els rols s'assignen a *Usuaris → Permisos → Assigna rols globals*.

## Usuaris

- **Treballadors**: no es donen d'alta a mà. Entren amb el seu compte de
  Microsoft de l'hospital i el campus els posa a la cohort «Treballadors
  PSPV» segons les regles configurades. Si deixen de complir-les, en
  surten. Tot queda a *Plugins locals → Campus PSPV accés → Registre d'auditoria*.
  → [`ACCES.md`](ACCES.md)
- **Llista de RRHH**: *Campus PSPV accés → Llista de treballadors*,
  important un CSV amb la columna `email` i, si es vol, `employeeid`,
  `department` i `jobcategory`. El servei i la categoria professional del
  quadre de comandament surten d'aquí.
- **Ciutadans**: es registren ells mateixos o entren amb Google.
- **Donar de baixa algú**: *Usuaris → Navega per la llista d'usuaris* →
  suspèn el compte (conserva l'historial). Esborrar-lo és per a les
  sol·licituds de supressió de dades.

## Cursos

1. *Cursos → Afegeix un curs nou*, a la categoria que toqui:
   - **Formació interna**: només la veuen els treballadors.
   - **Formació per a la ciutadania**: és el catàleg públic.
2. Matrícula:
   - interna: mètode «Sincronització de cohorts» (tots els treballadors) o
     «Automatrícula» restringida a la cohort;
   - per a la ciutadania: no cal tocar res, la botiga matricula qui paga.
3. Si el curs és **acreditat**, omple la secció «Acreditació» de la seva
   configuració. → [`ACREDITACIO.md`](ACREDITACIO.md)

## Vendre un curs

1. *Plugins locals → Campus PSPV botiga → Productes*: posa el curs a la
   venda amb el preu, les places i el període.
2. *Descomptes*: codis de descompte.
3. *Comandes*: consulta, cancel·lació i reemborsament. Reemborsar
   desmatricula i emet la factura rectificativa; el retorn dels diners es
   fa a la passarel·la.
4. *Campus PSPV facturació → Factures*: llistat i exportació per a
   comptabilitat.

→ [`VENDA.md`](VENDA.md)

## Acreditació i diplomes

A cada curs acreditat, *Curs → Més → Acreditació*:

- crea les sessions i passa llista;
- mira qui és apte i exporta'n la llista;
- «Afegeix el diploma a aquest curs» (un sol cop per curs).

El registre de tots els aptes és a *Plugins locals → Campus PSPV
acreditació → Registre d'alumnes aptes*. Qualsevol pot verificar un
diploma amb el codi o el QR. → [`ACREDITACIO.md`](ACREDITACIO.md)

## Campanyes de correu

*Plugins locals → Campus PSPV comunicació → Campanyes*. Escriu el
missatge, tria els destinataris, envia't una prova i envia-la o
programa-la. Marca-la com a comercial sempre que promocioni cursos.
→ [`COMUNICACIO.md`](COMUNICACIO.md)

## Quadre de comandament

*Informes → Quadre de comandament del Campus PSPV*: matrícules,
finalitzacions, aptes, comandes i ingressos del període triat; el detall
per curs, i la formació interna per servei i per categoria professional.
Cada taula es pot exportar.

- Els ingressos són els de les comandes pagades i no reemborsades.
- La formació interna per servei només compta els usuaris el correu dels
  quals és a la llista de RRHH.

## Privadesa

- *Usuaris → Privadesa i polítiques → Gestiona les polítiques*: textos que
  s'accepten en entrar. La de comunicacions comercials és opcional.
- *Sol·licituds de dades*: exportació i supressió de les dades d'un
  usuari. Les factures i les comandes pagades es conserven encara que
  s'aprovi una supressió. Abans d'aprovar-ne una, revisa què cal
  conservar del registre d'acreditació.

## Actualitzacions

- **Plugins del campus**: *Plugins locals → Actualitzacions del Campus
  PSPV*. Quan n'hi ha una de nova surt aquí; «Aplica» la instal·la i
  «Desfés» torna a l'anterior. Aplica-la fora d'hores de feina.
  → [`OTA.md`](OTA.md)
- **Moodle i plugins de la comunitat**: els fa Soportic per SSH.
  → [`CLOUDPANEL.md`](CLOUDPANEL.md)

## Que tot funcioni

- *Informes → Comprovacions d'estat*: cron, disc, còpies, actualitzacions
  i correu. Si alguna cosa passa a error, els administradors reben un
  avís. → [`MONITORATGE.md`](MONITORATGE.md)
- **Còpies de seguretat**: es fan cada nit al servidor.
  → [`COPIES.md`](COPIES.md)

## Problemes freqüents

| Què passa | Què mirar |
|---|---|
| Un treballador no veu la formació interna | *Campus PSPV accés → Registre d'auditoria*: si no hi surt, no compleix cap regla (compte personal en lloc del corporatiu, o no és a la llista de RRHH). Les regles es tornen a aplicar cada cop que entra |
| Algú ha pagat i no és matriculat | *Campus PSPV botiga → Comandes*: si la comanda és «pendent», la passarel·la no ha confirmat el pagament. No la marquis com a pagada sense comprovar-ho a la passarel·la |
| Un alumne no pot obrir el diploma | *Curs → Més → Acreditació*: ha de constar com a apte. Si compleix els criteris, «Recalcula els aptes» |
| No arriben els correus | *Informes → Comprovacions d'estat* → «Correu de sortida», i *Servidor → Tasques → Tasques programades* |
| Una campanya comercial no arriba a ningú | La política de comunicacions comercials encara és un esborrany, o els destinataris no l'han acceptada |
| La pàgina d'actualitzacions diu que no pot verificar-les | Falta `OTA_PUBLIC_KEY` a `.env` |
| El campus va lent | *Informes → Comprovacions d'estat* (disc i cua de tasques) i [`RENDIMENT.md`](RENDIMENT.md) |

## A qui trucar

TODO (hospital i Soportic): contactes de suport i horari, i qui rep els
avisos del monitor extern.

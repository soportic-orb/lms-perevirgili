# Campanyes de correu

Com s'envien comunicacions a grups d'usuaris del campus. Ho fa
`local_pspvmailing`, a *Administració → Plugins → Plugins locals → Campus
PSPV comunicació → Campanyes*.

## Una campanya

1. **Missatge**: nom intern, assumpte i cos. S'hi pot escriure
   `{{firstname}}`, `{{lastname}}`, `{{fullname}}` i `{{sitename}}`, i cada
   destinatari hi veu el seu.
2. **Destinataris** (segment), combinables:
   - membres d'una cohort;
   - matriculats en un curs (matrícula activa);
   - qui ha comprat algun curs, o qui no n'ha comprat mai (si hi ha la
     botiga).
3. **Tipus**: comunicació comercial o avís de servei (vegeu més avall).
4. La pàgina de la campanya mostra a quants usuaris arribaria avui, una
   previsualització amb la imatge de l'hospital, i permet **enviar-se una
   prova**, **enviar-la ara** o **programar-la**.

Els destinataris es calculen quan comença l'enviament, no quan s'escriu.
Una campanya enviada no es pot canviar ni tornar a enviar.

## Consentiment

| Tipus | A qui s'envia | Enllaç de baixa |
|---|---|---|
| **Comunicació comercial** (promoció de cursos) | Ciutadans que han acceptat la política de comunicacions comercials de `tool_policy` i no s'han donat de baixa. Treballadors: sempre, tret que s'hagin donat de baixa | Sí |
| **Avís de servei** (canvis en un curs on s'és matriculat, etc.) | Tothom del segment | No |

Mai no s'escriu a comptes suspesos, esborrats, sense confirmar o que han
desactivat el correu al seu perfil.

La política que dona el consentiment és, per defecte, la de «comunicacions
comercials» que crea `local_pspvaccess` com a esborrany: **mentre no es
publiqui, cap ciutadà l'ha acceptada** i les campanyes comercials només
arriben als treballadors.

## Baixa

Cada comunicació comercial porta un enllaç de baixa al peu i la capçalera
`List-Unsubscribe` (RFC 8058), que fa que els programes de correu mostrin
el seu propi botó «Dona'm de baixa». L'enllaç no demana iniciar sessió:
porta un codi que només aquest lloc pot calcular per a aquell usuari. La
pàgina confirma la baixa amb un clic i permet desfer-la.

La baixa afecta només les comunicacions comercials.

## Enviament

Una tasca programada (cada 5 minuts) comença les campanyes que toca, i
l'enviament es fa en **lots** amb tasques de la cua de Moodle
(`batchsize`, 100 missatges per execució per defecte), de manera que una
campanya gran no bloqueja el cron ni satura el servidor de correu. Una
campanya que s'està enviant es pot aturar.

## Permisos

`local/pspvmailing:manage`: administradors, i els rols «Gestor/a comercial»
i «Gestor/a de formació» si existeixen.

## Decisions pendents de l'hospital

| Què | On es configura | Estat |
|---|---|---|
| Que els treballadors rebin campanyes comercials sense consentiment exprés | `local_pspvmailing/workersalways` | Activat, tal com ha demanat l'hospital. TODO: ho ha de validar el delegat de protecció de dades |
| Text de la política de comunicacions comercials, i publicar-la | *Privadesa i polítiques → Gestiona les polítiques* | TODO: és un esborrany |
| Peu dels correus: identitat del remitent i text legal | `local_pspvmailing/footer` | TODO: buit, surt només el nom del campus |
| Adreça del remitent | Configuració de correu de sortida de Moodle (`noreplyaddress`) | La del lloc |

## Dades personals

Es guarda a qui s'ha enviat cada campanya i qui s'ha donat de baixa. Tot
s'exporta i s'esborra amb la Privacy API.

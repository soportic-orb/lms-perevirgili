# Venda de cursos i facturació

Com es venen els cursos de la formació externa i com se'n fan les factures.
Ho fan dos plugins: `local_pspvshop` (aparador, cistella, comandes i
matrícula) i `local_pspvinvoice` (dades fiscals, factures i abonaments), que
depèn del primer. La botiga funciona sola; la facturació es pot desactivar.

## Recorregut d'una compra

1. **Catàleg públic** (`/local/pspvshop/index.php`): es veu sense iniciar
   sessió, amb cerca i filtres per àrea, modalitat, preu i data d'inici.
2. **Fitxa del curs** (`product.php`): descripció, preu, places i
   metadades per a cercadors i xarxes socials (Open Graph i JSON-LD).
3. **Cistella** (`cart.php`): es pot omplir sense compte; en iniciar sessió
   es fusiona amb la del compte. Admet un codi de descompte.
4. **Dades de facturació** (`/local/pspvinvoice/billing.php`): particular o
   empresa, NIF, adreça. Només si la facturació és activa i la comanda no
   és gratuïta.
5. **Pagament** (`checkout.php`): amb el subsistema de pagaments de Moodle
   (`core_payment`), sigui quina sigui la passarel·la.
6. **Confirmació**: la comanda passa a pagada, el comprador queda matriculat
   (mètode d'inscripció manual del curs), rep un missatge i s'emet la
   factura en PDF.

Una comanda de 0 € (per exemple, amb un descompte del 100 %) es dona per
pagada sense passar per la passarel·la i no genera factura.

## Posar un curs a la venda

*Administració del lloc → Plugins → Plugins locals → Campus PSPV botiga →
Productes.* Només s'hi poden vendre els cursos de la categoria del catàleg
(per defecte `pspv-external`, la «Formació externa» de `local_pspvaccess`).
De cada curs es defineix:

- preu general, i preus per tipus de client (un per cohort; s'aplica el més
  baix dels que corresponen al comprador);
- tipus d'IVA propi, si és diferent del general;
- modalitat (en línia, presencial, mixta), places i període de venda.

Els **codis de descompte** (percentatge o import fix, amb dates, límit
d'usos i, si es vol, restringits a un curs) es gestionen a *Descomptes*,
i les **comandes** (consulta, cancel·lació i reemborsament) a *Comandes*.

## Factures

- Numeració correlativa per sèrie i any: `F-2026-0001`. Els abonaments tenen
  sèrie pròpia (`R`). Les sèries es configuren; canviar-ne una comença una
  numeració nova sense tocar l'anterior.
- La factura guarda una **còpia** de les dades de l'emissor, del comprador i
  de les línies en el moment d'emetre-la: canviar després la configuració o
  el perfil no altera una factura ja emesa.
- Reemborsar una comanda desmatricula el comprador i emet l'**abonament**
  que anul·la la factura. Una factura no s'edita ni s'esborra mai.
- *Campus PSPV facturació → Factures* llista els documents, filtra per dates
  i els **exporta** (CSV, Excel, ODS…) per a comptabilitat.
- El reemborsament dels diners es fa a la passarel·la; Moodle només el
  registra.

## Permisos

| Permís | Qui el té | Per a què |
|---|---|---|
| `local/pspvshop:manageproducts` | Gestor/a comercial, administrador | Productes i preus |
| `local/pspvshop:managecoupons` | Gestor/a comercial, administrador | Codis de descompte |
| `local/pspvshop:manageorders` | Gestor/a comercial, administrador | Comandes, cancel·lacions i reemborsaments |
| `local/pspvshop:simulatepayment` | Administrador | Simular un pagament en mode de proves |
| `local/pspvinvoice:manage` | Gestor/a comercial, administrador | Veure i exportar totes les factures |

## Mode de proves

Mentre no hi hagi passarel·la, amb `local_pspvshop/testmode` activat qui
tingui el permís `simulatepayment` veu a la pàgina de pagament el botó
«Simula el pagament», que marca la comanda com a pagada amb la passarel·la
`pspvtest`. Serveix per provar tot el recorregut, factura inclosa.
**Cal desactivar-lo abans d'obrir la venda.**

## Decisions pendents de l'hospital

No s'ha inventat cap valor: tot això és configuració i ara és buit.

| Què | On es configura | Estat |
|---|---|---|
| Passarel·la de pagament (Redsys, Stripe…) | *Pagaments → Comptes de pagament*, i `local_pspvshop/paymentaccount` | TODO: sense passarel·la, la pàgina de pagament avisa que el pagament en línia encara no és disponible |
| IVA: exempt (art. 20 LIVA) o 21 % | `local_pspvshop/taxrate` i `pricesincludetax`; es pot fixar per curs | TODO: buit = 0 % |
| Dades fiscals de l'emissor | `local_pspvinvoice/sellername`, `sellertaxid`, `selleraddress`, `footernote` | TODO: buides; les factures de prova surten sense emissor |
| Preu per als treballadors | Preu per a la cohort `pspv-workers` a cada producte | TODO: sense preu propi paguen el general |
| Verifactu o ERP | Vegeu «Integració fiscal» | TODO: cap integració |

## Integració fiscal

`local_pspvinvoice` no envia res enlloc. Deixa la interfície
`\local_pspvinvoice\fiscal\connector`: un altre plugin `pspv` pot
implementar-la i declarar-la amb la funció
`<plugin>_pspvinvoice_fiscal_connector()`, que retorna el connector. Cada
document emès se li passa a `submit()`, i l'estat i la referència que torna
es guarden a la factura (`fiscalstatus`, `fiscalref`) i surten al PDF.

## Punts d'extensió de la botiga

| Funció que implementa el plugin | Per a què |
|---|---|
| `<plugin>_pspvshop_checkout_step($order, $returnurl)` | Afegir un pas abans del pagament (torna una URL o `null`) |
| `<plugin>_pspvshop_order_links($order)` | Afegir enllaços a la pàgina d'una comanda |

Esdeveniments: `order_created`, `order_paid`, `order_cancelled`,
`order_refunded` (botiga), `invoice_issued` i `credit_note_issued`
(facturació). El tema demana a qui implementi
`<plugin>_theme_pspv_course_card($courseid)` i
`<plugin>_theme_pspv_catalogue_url()` el preu i l'enllaç de cada curs de la
portada; la botiga els implementa.

## Dades personals

Tots dos plugins implementen la Privacy API. Les comandes pagades i les
factures emeses són documents comptables i **es conserven** encara que
l'usuari demani l'esborrat (obligació legal de conservació); s'esborren la
cistella, les comandes que no s'han arribat a pagar i les dades de
facturació de comandes que no s'han facturat.

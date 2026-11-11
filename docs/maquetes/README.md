# Maquetes de la fase 3

Tres pantalles estàtiques fetes amb [Tabler](https://github.com/tabler/tabler)
1.7.4 i Tabler Icons 3.51 (totes dues amb llicència MIT), per aprovar abans
d'implementar `theme_pspv`.

| Pantalla | Fitxer | Escriptori | Mòbil |
|---|---|---|---|
| Portada pública amb catàleg | [`index.html`](index.html) | [captura](captures/index-desktop.png) | [captura](captures/index-mobile.png) |
| Tauler de l'alumne | [`tauler.html`](tauler.html) | [captura](captures/tauler-desktop.png) | [captura](captures/tauler-mobile.png) |
| Fitxa de curs de pagament | [`curs.html`](curs.html) | [captura](captures/curs-desktop.png) | [captura](captures/curs-mobile.png) |

Per veure-les en viu, obre `index.html` amb el navegador: no necessiten
servidor ni connexió (CSS, icones i tipografia són a `assets/`).

## Identitat

- **Logotip.** El del Parc Sanitari Pere Virgili, a la capçalera i al peu, al
  costat del nom «Campus». S'ha preparat a partir d'un JPG de 720 px: per al
  tema convé la versió vectorial (SVG) i, si existeix, la versió en negatiu
  per a fons foscos.
- **Colors.** Els dos blaus del logotip: `#0074D1` (primari) i `#009DE4`
  (secundari). El primari fa 4,75:1 sobre blanc, però no arriba al 4,5:1 que
  demana WCAG AA sobre els fons tenyits; per això el text i els enllaços fan
  servir un to més fosc del mateix blau, `#005FAD` (6,5:1). El secundari
  (3,0:1) només es fa servir en fons i detalls, mai per a text.
- Tot surt de les variables `--pspv-*` de `assets/maqueta.css`, que és el que
  es podrà configurar des de l'administració del tema.

## Què és provisional

- **Continguts.** Cursos, preus, dates, crèdits, noms i xifres són d'exemple.
  El que va entre claudàtors (`[Nom del docent]`, `[Tractament de l'IVA…]`)
  és informació que ha de donar l'hospital.
- **Colors de les àrees** a les portades dels cursos sense imatge.

## Tipografia

La mateixa que la web de Soportic: **Plus Jakarta Sans** per al text i
**Fraunces** per als titulars. Totes dues tenen llicència SIL Open Font
License i s'allotgen a `assets/fonts/`, sense CDN.

## Què cal aprovar

1. L'estructura de cada pantalla: quins blocs hi ha i en quin ordre.
2. El doble accés de la portada: «Sóc treballador/a» i «Sóc extern».
3. La targeta de curs (àrea, modalitat, hores, crèdits, data, preu, places).
4. La targeta de compra de la fitxa (tarifes, descompte, condicions).
5. L'estil general: targetes, espai en blanc, icones de línia.

## Diferència amb el tema final

Les maquetes carreguen `tabler.min.css` sencer per rapidesa. El tema **no**
ho farà: Moodle ja porta Bootstrap 5.3, i `theme_pspv` només hi afegirà els
tokens de Tabler i els components que es facin servir, a l'SCSS del tema.

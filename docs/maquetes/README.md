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

## Què és provisional

- **Colors.** El verd blavós (`#0b6b7a`) i el taronja són marcadors de
  posició. Tota la interfície surt de dues variables de
  `assets/maqueta.css` (`--pspv-primary` i `--pspv-secondary`), que és el que
  es podrà configurar des de l'administració del tema.
- **Logotip i tipografia.** Icona genèrica i la tipografia per defecte de
  Tabler, fins que arribi el manual d'identitat.
- **Continguts.** Cursos, preus, dates, crèdits, noms i xifres són d'exemple.
  El que va entre claudàtors (`[Nom del docent]`, `[Tractament de l'IVA…]`)
  és informació que ha de donar l'hospital.

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

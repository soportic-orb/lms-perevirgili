# theme_pspv — Campus PSPV

Tema fill de Boost amb el sistema de disseny [Tabler](https://github.com/tabler/tabler),
Tabler Icons i la identitat del Parc Sanitari Pere Virgili. No modifica Boost ni
el core: tot són variables SCSS, estils afegits, quatre plantilles i un sistema
d'icones.

## Què fa

- **Tokens de Tabler sobre Bootstrap.** `scss/pspv/_variables.scss` fixa les
  variables de Bootstrap abans que Boost es compili (colors neutres, radis,
  ombres, tipografia). No es carrega `tabler.min.css` ni un segon Bootstrap:
  Moodle 5.3 ja porta Bootstrap 5.3.
- **Identitat.** Logotip i els dos blaus del logotip (`#0074D1` i `#009DE4`),
  configurables a *Administració del lloc → Aparença → Campus PSPV*.
- **Contrast WCAG AA automàtic.** El text i els enllaços fan servir un to del
  color principal enfosquit fins a arribar a 4,5:1
  (`classes/local/colour.php`), sigui quin sigui el color que es triï.
- **Tipografia.** Plus Jakarta Sans (text) i Fraunces (titulars), a `fonts/`.
- **Portada pública.** Capçalera, doble accés «Sóc treballador/a» / «Sóc
  extern» i els cursos de la categoria del catàleg (`pspv-external`).
- **Inici de sessió.** El plafó esquerre porta el logotip i el missatge del
  campus en lloc de la promoció de Moodle.
- **Peu institucional** a totes les pàgines.
- **Sense CDN.** Fonts i icones se serveixen des del tema.

## Plantilles sobreescrites

| Plantilla | Canvi |
|---|---|
| `theme_boost/navbar` | La marca és el logotip i el nom «Campus». La resta és la de Boost. |
| `theme_boost/footer` | Afegeix el peu institucional abans del peu de Boost. |
| `core/login_panel` | Plafó de la pàgina d'inici de sessió. |

`templates/frontpage.mustache` i `templates/footer_institutional.mustache`
són pròpies. Les targetes, taules, botons, insígnies i formularis de la resta
de Moodle agafen l'aspecte de Tabler per CSS (`scss/pspv/_components.scss`),
sense tocar-ne les plantilles, perquè continuïn rebent les millores de Moodle.

En actualitzar Moodle a una versió nova cal comparar `theme_boost/navbar` i
`theme_boost/footer` amb les de Boost: són còpies amb un canvi cadascuna.

## Icones

`classes/output/icon_system_tabler.php` agafa el mapa d'icones de Moodle (core
i plugins, que apunta a Font Awesome) i canvia cada icona per l'equivalent de
Tabler. De les 498 icones de Moodle 5.3 amb els plugins del campus, 395 es
pinten amb Tabler i 103 amb Font Awesome, que Boost ja carrega.

Per mapar-ne una més, afegeix-la a `MAP` a `scripts/theme-icons.py` (a l'arrel
del repositori) i torna'l a executar: regenera `classes/output/tabler_map.php`,
`scss/pspv/_icons.scss` i `fonts/tabler-icons.woff2`.

### Icones que continuen amb Font Awesome

N'hi ha de dos tipus: les que tenen versió plena i de contorn amb significat
diferent (una estrella plena i una de buida), que no es canvien a propòsit, i
les que encara no tenen equivalent assignat.

`arrow-turn-up`, `audio-description`, `bell`, `book-bookmark`, `bookmark`, `check-double`, `circle`, `circle-check`, `circle-user`, `circle-xmark`, `clipboard`, `clone`, `comments`, `envelope`, `envelope-open`, `eye`, `eye-slash`, `face-frown`, `file`, `file-pdf`, `file-zipper`, `flag`, `image`, `key`, `pager`, `paperclip`, `pause`, `pen-to-square`, `people-arrows`, `person`, `rectangle-list`, `rectangle-xmark`, `right-to-bracket`, `share-from-square`, `smile`, `sort-down`, `sort-up`, `spacer`, `square`, `square-check`, `square-pen`, `star`, `table-cells`, `table-cells-large`, `thumbtack`, `trash-can-arrow-up`, `trash-o`, `trophy`, `user-lock`, `user-tag`, `user-tie`, `users-gear`, `window-restore`

## Fora de la instal·lació

Una instal·lació nova de Moodle reescriu els valors per defecte després
d'instal·lar els plugins, i desfà el que posa `db/install.php`. Per això
`cli/setup.php` torna a activar el tema i la pàgina d'inici del lloc; l'executen
`make install` i `deploy/cloudpanel/install.sh`, i només actua la primera vegada.

## Llicències de tercers

- Tabler i Tabler Icons: MIT.
- Plus Jakarta Sans i Fraunces: SIL Open Font License 1.1.

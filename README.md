# Campus PSPV

Plataforma d'e-learning del Parc Sanitari Pere Virgili sobre Moodle 5.3 LTS:
formació interna per als treballadors i catàleg extern de cursos de pagament.

Aquest repositori conté només el codi propi (plugins `pspv`) i la
configuració. El core de Moodle es descarrega a l'etiqueta fixada a
`.moodle-version` i no es modifica mai.

- Proposta i fases: [`docs/PROPOSTA.md`](docs/PROPOSTA.md)
- Instal·lació a CloudPanel: [`docs/CLOUDPANEL.md`](docs/CLOUDPANEL.md)
- Actualitzacions OTA: [`docs/OTA.md`](docs/OTA.md)
- Accés, treballadors i inici de sessió: [`docs/ACCES.md`](docs/ACCES.md)
- Regles de desenvolupament: [`CLAUDE.md`](CLAUDE.md)

## Entorn local

Cal Docker (amb Compose), Git i Make.

```bash
make up
```

```bash
make install
```

`make up` crea `.env` amb contrasenyes aleatòries, baixa Moodle a `moodle/` i
arrenca els contenidors. `make install` crea la base de dades, instal·la els
idiomes (català per defecte, castellà i anglès) i configura Redis.

| Què | On |
| --- | --- |
| Campus | http://localhost:8080 |
| Correu (Mailpit) | http://localhost:8025 |
| Administrador | `MOODLE_ADMIN_USER` / `MOODLE_ADMIN_PASS` de `.env` |

## Tests

```bash
make test
```

```bash
make behat
```

```bash
make lint
```

`make lint` necessita `composer install` a l'arrel del repositori (instal·la
l'estàndard de codi de Moodle). La CI (`.github/workflows/ci.yml`) executa
`moodle-plugin-ci` complet contra Moodle 5.3 amb PHP 8.3 i 8.4 i MariaDB 11.4.

## Estat

Fases 1 (fonaments), 2 (accés i identitat) i 3 (imatge gràfica) fetes. Vegeu [`CHANGELOG.md`](CHANGELOG.md).

## Llicència

GPL v3 o posterior, com Moodle. Vegeu [`LICENSE`](LICENSE).

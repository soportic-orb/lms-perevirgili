# Proves de càrrega

`scripts/loadtest.mjs` simula visitants que naveguen pel campus i dona els
temps de resposta. Només necessita Node 18 o superior.

```bash
node scripts/loadtest.mjs https://perevirgili.soportic.es --users 25 --seconds 60
```

Amb un usuari de proves, cada visitant inicia sessió i també obre el tauler
i els seus cursos:

```bash
LOADTEST_USER=usuari LOADTEST_PASS='contrasenya' node scripts/loadtest.mjs https://perevirgili.soportic.es --users 25 --seconds 60
```

| Opció | Per defecte | Què és |
|---|---|---|
| `--users` | 10 | Visitants alhora |
| `--seconds` | 30 | Durada |
| `--think` | 500 | Pausa mitjana entre pàgines, en ms |
| `--paths` | `/,/local/pspvshop/index.php,/login/index.php` | Pàgines públiques |

Acaba amb error si falla més de l'1 % de les peticions.

**Fes-la contra staging, mai contra producció en hores de feina**, i amb un
usuari de proves, no amb un administrador.

## Resultats

### Entorn local (12-11-2026)

Només com a referència que l'script funciona i que no hi ha cap pàgina
anòmalament lenta. **No diu res de producció**: és Docker en una màquina de
4 nuclis i 15 GB, amb Moodle en mode de depuració, sense OPcache ajustat i
amb un sol contenidor per a web i PHP.

| Escenari | Peticions/s | Errors | p50 | p95 |
|---|---|---|---|---|
| 10 visitants sense sessió, 20 s | 15,4 | 0 | 127 ms | 170 ms |
| 25 visitants amb sessió, 30 s | 22,9 | 0 | 504 ms | 1.060 ms |

Pàgina més lenta amb sessió: «Els meus cursos» (p95 1,2 s).

### Staging i producció

TODO: executar-la a `perevirgili.soportic.es` i al servidor de producció
abans d'obrir, i anotar aquí els resultats. Objectiu proposat: p95 per sota
d'1 segon amb el nombre d'usuaris simultanis que l'hospital esperi en una
convocatòria (TODO: l'hospital ha de dir quants).

## Si no arriba a l'objectiu

Per ordre de rendiment obtingut per esforç:

1. **OPcache** a la configuració de PHP del lloc: `opcache.memory_consumption=256`,
   `opcache.max_accelerated_files=20000`, `opcache.validate_timestamps=1`
   (les OTA canvien fitxers: no el desactivis).
2. **Redis** per a la memòria cau i les sessions (`REDIS_HOST` a `.env`;
   `make install` i `install.sh` ja el configuren si hi és).
3. **PHP-FPM**: `pm.max_children` segons la memòria (uns 60–80 MB per
   procés).
4. **MariaDB**: `innodb_buffer_pool_size` al voltant de la meitat de la
   memòria del servidor si és dedicat.
5. Que `APP_ENV` no sigui `local` (desactiva la depuració i activa la
   memòria cau de JavaScript i d'idiomes).

# Còpies de seguretat

Què es copia, com es programa i com es restaura. Les còpies les fa
`deploy/cloudpanel/backup.sh` al mateix servidor; cal complementar-les amb
una còpia **fora del servidor** (vegeu «Còpia remota»).

## Què es copia

| Què | Fitxer | Notes |
|---|---|---|
| Base de dades | `db.sql.gz` | Bolcat consistent sense aturar el campus |
| Fitxers de Moodle (`MOODLE_DATAROOT`) | `moodledata.tar.gz` | Sense memòries cau, sessions ni temporals, que es regeneren |
| Configuració | `env` | Còpia de `.env` (conté secrets: la carpeta de còpies només la pot llegir l'usuari del lloc) |
| Versió | `commit`, `moodle-version` | Amb quin codi es va fer la còpia |
| Comprovació | `SHA256SUMS` | Per detectar una còpia malmesa abans de restaurar-la |

El codi no es copia: és al repositori, i el core de Moodle es baixa a
l'etiqueta de `.moodle-version`.

## Posar-les en marxa

1. A `.env`:

   ```
   BACKUP_DIR=/home/<usuari>/backups/campus
   BACKUP_KEEP_DAYS=14
   ```

   La carpeta ha de ser fora de la carpeta web i fora de `MOODLE_DATAROOT`;
   l'script s'hi nega si no és així.

2. Prova-la a mà, com l'usuari del lloc:

   ```bash
   bash deploy/cloudpanel/backup.sh
   ```

3. A CloudPanel, **Cron Jobs** del lloc, cada nit:

   ```
   15 3 * * * bash /home/<usuari>/htdocs/perevirgili.soportic.es/deploy/cloudpanel/backup.sh >> /home/<usuari>/logs/backup.log 2>&1
   ```

Cada còpia és una carpeta amb la data (`20270112-031500`). Les de més de
`BACKUP_KEEP_DAYS` dies s'esborren soles. Si un pas falla, la còpia a
mitges s'esborra i l'script acaba amb error.

En acabar bé, l'script deixa `last-backup.json` a la carpeta: és el que
mira la comprovació «Còpies de seguretat del servidor» de l'informe
d'estat, que avisa els administradors si fa més de 36 hores que no n'acaba
cap ([`MONITORATGE.md`](MONITORATGE.md)).

## Còpia remota

Una còpia al mateix servidor no protegeix d'una avaria del servidor. Cal
activar a CloudPanel **Admin Area → Backups** les còpies remotes (S3,
SFTP, Dropbox…) i incloure-hi la carpeta `BACKUP_DIR`, o bé les còpies
completes del lloc que fa CloudPanel.

TODO (hospital/Soportic): triar el destí remot i la retenció que exigeixi
la política de l'hospital. Les 14 còpies diàries són una proposta.

## Restaurar

```bash
bash deploy/cloudpanel/restore.sh /home/<usuari>/backups/campus/20270112-031500
```

L'script:

1. comprova la còpia (`SHA256SUMS`) i no toca res si és malmesa;
2. demana que s'escrigui el nom de la base de dades per confirmar-ho;
3. posa el campus en manteniment;
4. substitueix la base de dades i els fitxers pels de la còpia; els fitxers
   actuals no s'esborren, es mouen a `<MOODLE_DATAROOT>.abans-<data>`;
5. buida les memòries cau, aplica les actualitzacions pendents si el codi
   és més nou que la còpia, i treu el manteniment.

No toca `.env`. Per restaurar en un **servidor nou**: segueix
[`CLOUDPANEL.md`](CLOUDPANEL.md) fins a tenir el codi i `.env` (pots
partir del fitxer `env` de la còpia), i executa `restore.sh` en lloc
d'`install.sh`; després, `install.sh` per deixar el codi a punt.

Quan hagis comprovat que tot funciona, esborra la carpeta
`<MOODLE_DATAROOT>.abans-*`.

## Prova de restauració

Una còpia que no s'ha restaurat mai no és una còpia. Cal provar-ho un cop
abans d'obrir producció i després periòdicament (proposta: cada sis
mesos), restaurant la darrera còpia en un lloc de proves o en un servidor
a part. **No restauris mai de prova sobre el campus real**: `restore.sh`
en substitueix les dades.

Els scripts s'han provat de punta a punta en local amb una base de dades
de prova: còpia, restauració, confirmació errònia, còpia malmesa, fallada
del bolcat i neteja de còpies antigues. Falta la prova al servidor real.

# Monitoratge

Com s'assabenta l'equip quan alguna cosa falla. Hi ha tres peces, totes a
`local_pspvcore`.

## 1. Informe d'estat

*Administració del lloc → Informes → Comprovacions d'estat* (l'informe de
Moodle) inclou les comprovacions del campus:

| Comprovació | Error quan… | Avís quan… |
|---|---|---|
| Cron del campus | fa més de 30 minuts que no s'executa | — |
| Espai lliure al disc | queda menys del 5 % | queda menys del 15 % |
| Còpies de seguretat del servidor | fa més de 36 hores que no n'acaba cap | — (no es vigila si `BACKUP_DIR` és buit) |
| Actualitzacions OTA | — | falta la clau pública, fa més de 2 dies que no es busquen actualitzacions o l'última es va desfer |
| Correu de sortida | el correu és desactivat | no hi ha SMTP o el correu es desvia |

A més de les de Moodle sobre la cua de tasques (tasques que fallen i cua
adhoc endarrerida).

## 2. Avisos per correu

Cada 15 minuts, una tasca programada passa les comprovacions. Quan alguna
passa a **error**, tots els administradors del lloc reben una notificació
(correu i campaneta) amb el que falla. No es repeteix mentre no canviï res,
i quan tot torna a funcionar n'arriba una altra dient-ho.

Cada administrador pot triar per on la rep a *Preferències → Preferències
de notificació → Errors de les comprovacions d'estat del campus*.

Limitació: si el que falla és el cron o el correu, aquest avís no pot
sortir. Per això cal també el monitor extern.

## 3. Monitor extern

`/local/pspvcore/health.php` torna l'estat en JSON:

- **HTTP 200** si no hi ha cap error (pot haver-hi avisos);
- **HTTP 503** si alguna comprovació és en error;
- **HTTP 404** sense el token correcte.

Per activar-lo, a `.env`:

```
HEALTH_TOKEN=<resultat de: openssl rand -hex 32>
```

El token s'envia a la capçalera `X-Health-Token` (recomanat: no queda als
registres d'accés) o al paràmetre `?token=`. Exemple:

```bash
curl -s -H "X-Health-Token: $HEALTH_TOKEN" https://<domini>/local/pspvcore/health.php
```

Configura un monitor (UptimeRobot, Uptime Kuma, Better Stack…) que el
consulti cada 5 minuts i avisi si no rep un 200. Com que la pàgina passa
per PHP i la base de dades, també detecta que el web o la base de dades
han caigut.

TODO (Soportic): triar el monitor extern i a qui avisa.

## Què mirar quan arriba un avís

| Avís | Què fer |
|---|---|
| Cron | A CloudPanel, **Cron Jobs** del lloc: que hi sigui la línia de `cron.php` i que l'usuari sigui el del lloc. Mira *Servidor → Tasques → Tasques en execució* |
| Disc | `du -sh` de `MOODLE_DATAROOT` i de `BACKUP_DIR`; abaixa `BACKUP_KEEP_DAYS` o amplia el disc |
| Còpies | Mira `~/logs/backup.log` i executa `backup.sh` a mà ([`COPIES.md`](COPIES.md)) |
| OTA | *Plugins locals → Actualitzacions del Campus PSPV* ([`OTA.md`](OTA.md)) |
| Correu | *Servidor → Correu electrònic → Configuració del correu de sortida*, i «Prova la configuració» |
| Tasques que fallen | *Servidor → Tasques → Tasques programades*: la columna «Retard per error» |

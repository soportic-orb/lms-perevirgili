<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for local_pspvcore, Catalan.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['check_backup'] = 'Còpies de seguretat del servidor';
$string['check_backup_last'] = 'L\'última còpia va acabar fa {$a}.';
$string['check_backup_never'] = 'No ha acabat cap còpia a la carpeta de còpies.';
$string['check_backup_off'] = 'La carpeta de còpies no és definida (BACKUP_DIR a .env): les còpies no es vigilen.';
$string['check_cron'] = 'Cron del campus';
$string['check_cron_last'] = 'El cron va arrencar per última vegada fa {$a}.';
$string['check_cron_never'] = 'El cron no s\'ha executat mai.';
$string['check_disk'] = 'Espai lliure al disc';
$string['check_disk_free'] = '{$a->free} lliures ({$a->percent} %) on Moodle guarda els fitxers.';
$string['check_disk_unknown'] = 'No es pot llegir l\'espai lliure en aquest servidor.';
$string['check_mail'] = 'Correu de sortida';
$string['check_mail_diverted'] = 'Tots els correus es desvien a {$a}.';
$string['check_mail_nosmtp'] = 'No hi ha cap servidor SMTP definit: el correu surt amb la funció mail de PHP, que sovint acaba al correu brossa.';
$string['check_mail_off'] = 'El correu és desactivat (noemailever): el campus no envia res.';
$string['check_mail_ok'] = 'El correu surt per {$a}.';
$string['check_ota'] = 'Actualitzacions OTA';
$string['check_ota_available'] = 'L\'actualització {$a} espera que s\'apliqui.';
$string['check_ota_nokey'] = 'La clau pública no és definida (OTA_PUBLIC_KEY a .env): les actualitzacions no es poden verificar.';
$string['check_ota_ok'] = 'El lloc està al dia.';
$string['check_ota_rolledback'] = 'L\'última actualització es va desfer.';
$string['check_ota_stale'] = 'Fa {$a} que no es busquen actualitzacions.';
$string['event_ota_applied'] = 'Actualització OTA aplicada';
$string['event_ota_rolled_back'] = 'Actualització OTA desfeta';
$string['health_alert_body'] = 'Aquestes comprovacions de {$a->site} fallen:

{$a->checks}

Informe d\'estat: {$a->url}';
$string['health_alert_subject'] = '{$a}: alguna cosa demana atenció';
$string['health_recovered_body'] = 'Totes les comprovacions de {$a->site} tornen a funcionar.

Informe d\'estat: {$a->url}';
$string['health_recovered_subject'] = '{$a}: tot torna a funcionar';
$string['health_report'] = 'Informe d\'estat';
$string['messageprovider:healthalert'] = 'Errors de les comprovacions d\'estat del campus';
$string['ota_action_applied'] = 'Aplicada';
$string['ota_action_rolledback'] = 'Desfeta';
$string['ota_applied'] = 'S\'ha aplicat la versió {$a}.';
$string['ota_apply'] = 'Aplica la versió {$a}';
$string['ota_available'] = 'Hi ha disponible la versió {$a}';
$string['ota_check'] = 'Cerca actualitzacions';
$string['ota_confirm_apply'] = 'La versió {$a} substituirà el codi dels plugins del Campus PSPV. Abans se\'n desa una còpia del codi actual. Si algun plugin canvia la seva base de dades, tot seguit s\'obrirà la pàgina d\'actualització de Moodle. Voleu continuar?';
$string['ota_confirm_rollback'] = 'Es tornaran a posar les carpetes dels plugins desades abans de l\'última actualització. Voleu continuar?';
$string['ota_current'] = 'Versió actual';
$string['ota_err_bundle'] = 'No es pot llegir el paquet de l\'actualització.';
$string['ota_err_downgrade'] = 'L\'actualització porta una versió de {$a} més antiga que la instal·lada. No s\'ha canviat res.';
$string['ota_err_download'] = 'No s\'ha pogut baixar el fitxer {$a} de l\'actualització.';
$string['ota_err_github'] = 'GitHub no ha retornat la llista de versions (HTTP {$a}).';
$string['ota_err_hash'] = 'El paquet de l\'actualització no coincideix amb el seu manifest signat. No s\'ha canviat res.';
$string['ota_err_locked'] = 'Hi ha una altra actualització en curs. Torneu-ho a provar d\'aquí a un minut.';
$string['ota_err_manifest'] = 'El manifest de l\'actualització no és vàlid.';
$string['ota_err_nobackup'] = 'No hi ha cap còpia a la qual tornar.';
$string['ota_err_nothing'] = 'No hi ha cap versió més nova per aplicar.';
$string['ota_err_path'] = 'L\'actualització conté un fitxer fora de les carpetes permeses ({$a}). No s\'ha canviat res.';
$string['ota_err_replay'] = 'El manifest signat és d\'una versió diferent de la publicada. No s\'ha canviat res.';
$string['ota_err_repository'] = 'El repositori de les OTA no està ben configurat (OTA_REPOSITORY ha de ser «propietari/nom»).';
$string['ota_err_requirements'] = 'En aquest servidor no es poden aplicar actualitzacions: {$a}';
$string['ota_err_rollback_upgraded'] = 'No es pot desfer l\'actualització: la base de dades de {$a} ja s\'ha actualitzat i Moodle no accepta codi més antic. Publiqueu una versió nova amb la correcció.';
$string['ota_err_root'] = 'L\'actualització vol substituir una carpeta que no és un plugin del Campus PSPV ({$a}). No s\'ha canviat res.';
$string['ota_err_signature'] = 'La signatura de l\'actualització no és vàlida. No s\'ha canviat res.';
$string['ota_err_swap'] = 'No s\'han pogut substituir els fitxers ({$a}). S\'ha conservat el codi anterior.';
$string['ota_err_version'] = 'La carpeta {$a} de l\'actualització no té un version.php vàlid.';
$string['ota_history'] = 'Historial';
$string['ota_intro'] = 'Els plugins del Campus PSPV (tema, accés, botiga, facturació, acreditació, mailing i informes) s\'actualitzen des del repositori del projecte. El core de Moodle, els plugins de la comunitat, la configuració i els fitxers pujats no es toquen mai.';
$string['ota_lastcheck'] = 'Última comprovació';
$string['ota_none_available'] = 'No s\'ha trobat cap versió més nova.';
$string['ota_problems'] = 'No es poden aplicar actualitzacions fins que això es resolgui:';
$string['ota_req_publickey'] = 'No s\'ha configurat la clau pública que verifica les actualitzacions (OTA_PUBLIC_KEY a .env).';
$string['ota_req_sodium'] = 'Falta l\'extensió sodium de PHP.';
$string['ota_req_writable'] = 'L\'usuari del servidor web no pot escriure a les carpetes del codi de Moodle.';
$string['ota_req_zip'] = 'Falta l\'extensió zip de PHP.';
$string['ota_rollback'] = 'Desfés l\'última actualització';
$string['ota_rolledback'] = 'S\'ha desfet l\'última actualització.';
$string['ota_title'] = 'Actualitzacions del Campus PSPV';
$string['ota_uptodate'] = 'El lloc està al dia.';
$string['ota_version_none'] = 'La de la instal·lació (cap actualització aplicada)';
$string['pluginname'] = 'Nucli del Campus PSPV';
$string['privacy:metadata'] = 'El plugin del nucli del Campus PSPV no desa cap dada personal.';
$string['pspvcore:manageupdates'] = 'Aplicar i desfer actualitzacions del Campus PSPV';
$string['task_checkupdates'] = 'Cerca actualitzacions del Campus PSPV';
$string['task_health_alert'] = 'Comprova l\'estat del campus i avisa els administradors';

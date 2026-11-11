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
 * Strings for local_pspvcore, Spanish.
 *
 * @package    local_pspvcore
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['event_ota_applied'] = 'Actualización OTA aplicada';
$string['event_ota_rolled_back'] = 'Actualización OTA deshecha';
$string['ota_action_applied'] = 'Aplicada';
$string['ota_action_rolledback'] = 'Deshecha';
$string['ota_applied'] = 'Se ha aplicado la versión {$a}.';
$string['ota_apply'] = 'Aplicar la versión {$a}';
$string['ota_available'] = 'Está disponible la versión {$a}';
$string['ota_check'] = 'Buscar actualizaciones';
$string['ota_confirm_apply'] = 'La versión {$a} sustituirá el código de los plugins del Campus PSPV. Antes se guarda una copia del código actual. Si algún plugin cambia su base de datos, a continuación se abrirá la página de actualización de Moodle. ¿Desea continuar?';
$string['ota_confirm_rollback'] = 'Se volverán a poner las carpetas de los plugins guardadas antes de la última actualización. ¿Desea continuar?';
$string['ota_current'] = 'Versión actual';
$string['ota_err_bundle'] = 'No se puede leer el paquete de la actualización.';
$string['ota_err_downgrade'] = 'La actualización trae una versión de {$a} más antigua que la instalada. No se ha cambiado nada.';
$string['ota_err_download'] = 'No se ha podido descargar el fichero {$a} de la actualización.';
$string['ota_err_github'] = 'GitHub no ha devuelto la lista de versiones (HTTP {$a}).';
$string['ota_err_hash'] = 'El paquete de la actualización no coincide con su manifiesto firmado. No se ha cambiado nada.';
$string['ota_err_locked'] = 'Hay otra actualización en curso. Vuelva a intentarlo dentro de un minuto.';
$string['ota_err_manifest'] = 'El manifiesto de la actualización no es válido.';
$string['ota_err_nobackup'] = 'No hay ninguna copia a la que volver.';
$string['ota_err_nothing'] = 'No hay ninguna versión más nueva que aplicar.';
$string['ota_err_path'] = 'La actualización contiene un fichero fuera de las carpetas permitidas ({$a}). No se ha cambiado nada.';
$string['ota_err_replay'] = 'El manifiesto firmado es de una versión distinta de la publicada. No se ha cambiado nada.';
$string['ota_err_repository'] = 'El repositorio de las OTA no está bien configurado (OTA_REPOSITORY debe ser «propietario/nombre»).';
$string['ota_err_requirements'] = 'En este servidor no se pueden aplicar actualizaciones: {$a}';
$string['ota_err_rollback_upgraded'] = 'No se puede deshacer la actualización: la base de datos de {$a} ya se ha actualizado y Moodle no acepta código más antiguo. Publique una versión nueva con la corrección.';
$string['ota_err_root'] = 'La actualización quiere sustituir una carpeta que no es un plugin del Campus PSPV ({$a}). No se ha cambiado nada.';
$string['ota_err_signature'] = 'La firma de la actualización no es válida. No se ha cambiado nada.';
$string['ota_err_swap'] = 'No se han podido sustituir los ficheros ({$a}). Se ha conservado el código anterior.';
$string['ota_err_version'] = 'La carpeta {$a} de la actualización no tiene un version.php válido.';
$string['ota_history'] = 'Historial';
$string['ota_intro'] = 'Los plugins del Campus PSPV (tema, acceso, tienda, facturación, acreditación, mailing e informes) se actualizan desde el repositorio del proyecto. El core de Moodle, los plugins de la comunidad, la configuración y los ficheros subidos no se tocan nunca.';
$string['ota_lastcheck'] = 'Última comprobación';
$string['ota_none_available'] = 'No se ha encontrado ninguna versión más nueva.';
$string['ota_problems'] = 'No se pueden aplicar actualizaciones hasta que esto se resuelva:';
$string['ota_req_publickey'] = 'No se ha configurado la clave pública que verifica las actualizaciones (OTA_PUBLIC_KEY en .env).';
$string['ota_req_sodium'] = 'Falta la extensión sodium de PHP.';
$string['ota_req_writable'] = 'El usuario del servidor web no puede escribir en las carpetas del código de Moodle.';
$string['ota_req_zip'] = 'Falta la extensión zip de PHP.';
$string['ota_rollback'] = 'Deshacer la última actualización';
$string['ota_rolledback'] = 'Se ha deshecho la última actualización.';
$string['ota_title'] = 'Actualizaciones del Campus PSPV';
$string['ota_uptodate'] = 'El sitio está al día.';
$string['ota_version_none'] = 'La de la instalación (ninguna actualización aplicada)';
$string['pluginname'] = 'Núcleo del Campus PSPV';
$string['privacy:metadata'] = 'El plugin del núcleo del Campus PSPV no guarda ningún dato personal.';
$string['pspvcore:manageupdates'] = 'Aplicar y deshacer actualizaciones del Campus PSPV';
$string['task_checkupdates'] = 'Buscar actualizaciones del Campus PSPV';

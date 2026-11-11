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
 * Strings for local_pspvaccess, Spanish.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['audit_action_added'] = 'Añadido';
$string['audit_action_removed'] = 'Quitado';
$string['audit_empty'] = 'Todavía no se ha registrado ningún cambio.';
$string['audit_intro'] = 'Cada vez que un usuario entra en la cohorte de trabajadores o sale de ella automáticamente, queda registrado aquí con la regla que se ha aplicado.';
$string['audit_origin'] = 'Comprobado en';
$string['audit_origin_login'] = 'Inicio de sesión';
$string['audit_origin_manual'] = 'Resincronización manual';
$string['audit_origin_profile'] = 'Cambio de perfil';
$string['audit_origin_task'] = 'Resincronización programada';
$string['audit_reason'] = 'Regla';
$string['audit_title'] = 'Registro de auditoría de la cohorte de trabajadores';
$string['category_external'] = 'Formación externa';
$string['category_external_desc'] = 'Cursos abiertos a profesionales externos y al público general.';
$string['category_internal'] = 'Formación interna';
$string['category_internal_desc'] = 'Formación para los trabajadores del Parc Sanitari Pere Virgili.';
$string['cohort_workers'] = 'Trabajadores PSPV';
$string['cohort_workers_desc'] = 'Trabajadores del Parc Sanitari Pere Virgili. La mantiene al día el plugin de acceso del Campus PSPV; los miembros añadidos a mano no se quitan nunca automáticamente.';
$string['deleteduser'] = 'Usuario eliminado';
$string['employees_count'] = 'Trabajadores en la lista';
$string['employees_err_csv'] = 'No se puede leer el fichero: {$a}';
$string['employees_err_noemail'] = 'El fichero no tiene ninguna columna «email».';
$string['employees_file'] = 'Fichero CSV';
$string['employees_file_help'] = 'La primera fila contiene los nombres de las columnas. «email» es obligatoria; «employeeid», «department» y «jobcategory» son opcionales. Las filas sin una dirección de correo válida se omiten.';
$string['employees_import'] = 'Importar la lista';
$string['employees_imported'] = 'Lista importada: {$a->created} nuevos, {$a->updated} actualizados, {$a->deleted} eliminados, {$a->skipped} filas omitidas. Cohorte de trabajadores: {$a->added} añadidos, {$a->removed} quitados.';
$string['employees_intro'] = 'La lista de trabajadores viene de RR. HH. Cuando la regla «lista de trabajadores» está activada, un usuario cuya dirección de correo está en la lista es trabajador.';
$string['employees_lastimport'] = 'Última importación';
$string['employees_listdisabled'] = 'La regla «lista de trabajadores» está desactivada: la lista se guarda pero no se usa.';
$string['employees_replace'] = 'Quitar los que no estén en el fichero';
$string['employees_replace_help'] = 'Si se marca, el fichero sustituye toda la lista: los trabajadores que no estén se eliminan y, en la siguiente comprobación, salen de la cohorte de trabajadores si no les aplica ninguna otra regla. Si no se marca, el fichero solo añade y actualiza.';
$string['employees_title'] = 'Lista de trabajadores';
$string['event_employee_list_imported'] = 'Lista de trabajadores importada';
$string['issuer_worker'] = 'Cuenta corporativa PSPV';
$string['pluginname'] = 'Acceso del Campus PSPV';
$string['policy_marketing'] = 'Comunicaciones comerciales';
$string['policy_placeholder'] = '<p>TODO: texto pendiente del delegado de protección de datos del hospital.</p>';
$string['policy_privacy'] = 'Política de privacidad';
$string['policy_revision'] = 'Borrador';
$string['policy_terms'] = 'Condiciones de uso';
$string['privacy:metadata:audit'] = 'Registro de auditoría de los cambios automáticos de la cohorte de trabajadores.';
$string['privacy:metadata:audit:action'] = 'Si el usuario se añadió o se quitó.';
$string['privacy:metadata:audit:actorid'] = 'El usuario que provocó la comprobación, si lo hay.';
$string['privacy:metadata:audit:origin'] = 'Qué desencadenó la comprobación.';
$string['privacy:metadata:audit:reason'] = 'Las reglas que se aplicaron.';
$string['privacy:metadata:audit:userid'] = 'El usuario añadido o quitado.';
$string['privacy:metadata:employee'] = 'Lista de trabajadores importada de RR. HH.';
$string['privacy:metadata:employee:department'] = 'Servicio o unidad.';
$string['privacy:metadata:employee:email'] = 'Dirección de correo del trabajador.';
$string['privacy:metadata:employee:employeeid'] = 'Identificador en el sistema de RR. HH.';
$string['privacy:metadata:employee:jobcategory'] = 'Categoría profesional.';
$string['privacy:metadata:member'] = 'Usuarios que el plugin ha puesto en la cohorte de trabajadores.';
$string['privacy:metadata:member:reason'] = 'Las reglas por las que el usuario es trabajador.';
$string['privacy:metadata:member:userid'] = 'El usuario.';
$string['privacy:metadata:preference:tenantlogin'] = 'Momento del último inicio de sesión con la cuenta corporativa del hospital.';
$string['privacy:metadata:timecreated'] = 'Cuándo se creó el registro.';
$string['privacy:path:audit'] = 'Cambios en la cohorte de trabajadores';
$string['privacy:path:employee'] = 'Lista de trabajadores';
$string['privacy:path:member'] = 'Condición de trabajador';
$string['pspvaccess:manageemployees'] = 'Gestionar la lista de trabajadores y resincronizar la cohorte';
$string['pspvaccess:viewaudit'] = 'Ver el registro de auditoría de la cohorte de trabajadores';
$string['resync_done'] = 'Cohorte de trabajadores resincronizada: {$a->checked} usuarios comprobados, {$a->added} añadidos, {$a->removed} quitados.';
$string['resync_now'] = 'Resincronizar la cohorte ahora';
$string['role_pspvsalesmanager'] = 'Gestor/a comercial';
$string['role_pspvsalesmanager_desc'] = 'Gestiona las ventas, los descuentos y las facturas de la formación externa.';
$string['role_pspvtrainingmanager'] = 'Gestor/a de formación';
$string['role_pspvtrainingmanager_desc'] = 'Gestiona cursos y usuarios, sin acceso a la configuración de administración del sitio.';
$string['role_pspvworker'] = 'Trabajador/a PSPV';
$string['role_pspvworker_desc'] = 'Se asigna automáticamente a los miembros de la cohorte de trabajadores en la categoría de formación interna, para que puedan verla. No lo asigne a mano: añada el usuario a la cohorte.';
$string['rule_domain'] = 'Dominio de correo corporativo';
$string['rule_list'] = 'Lista de trabajadores';
$string['rule_none'] = 'Ya no aplica ninguna regla';
$string['rule_tenant'] = 'Cuenta Microsoft corporativa';
$string['settings_emaildomains'] = 'Dominios de correo corporativos';
$string['settings_emaildomains_desc'] = 'Un dominio por línea, sin «@». Un usuario con la cuenta confirmada cuya dirección de correo es de uno de estos dominios es trabajador. Déjelo vacío para desactivar esta regla.';
$string['settings_intro'] = 'Estas reglas deciden quién es trabajador del hospital. Los trabajadores se ponen en la cohorte «Trabajadores PSPV», que es la que les muestra la formación interna. Todas las reglas están desactivadas hasta que se configuren aquí.';
$string['settings_norules'] = 'No hay ninguna regla de trabajador configurada, así que nadie entra automáticamente en la cohorte de trabajadores.';
$string['settings_rulemode'] = 'Cómo se combinan las reglas';
$string['settings_rulemode_all'] = 'Deben cumplirse todas las reglas activas';
$string['settings_rulemode_any'] = 'Basta con una regla activa';
$string['settings_rulemode_desc'] = 'Si un usuario debe cumplir una de las reglas activas o todas.';
$string['settings_tenantmaxage'] = 'Días de validez de un inicio de sesión corporativo';
$string['settings_tenantmaxage_desc'] = 'Un usuario deja de cumplir la regla de la cuenta corporativa cuando lleva este número de días sin iniciar sesión con ella. 0 significa que no caduca nunca: el usuario sigue siendo trabajador mientras la cuenta corporativa esté enlazada.';
$string['settings_title'] = 'Reglas de trabajador';
$string['settings_useemployeelist'] = 'Usar la lista de trabajadores';
$string['settings_useemployeelist_desc'] = 'Un usuario cuya dirección de correo está en la lista de trabajadores importada es trabajador.';
$string['settings_workerissuer'] = 'Proveedor de inicio de sesión de la cuenta corporativa';
$string['settings_workerissuer_desc'] = 'El proveedor OAuth 2 restringido al tenant de Microsoft Entra ID del hospital. Un usuario que ha iniciado sesión con él es trabajador. «Ninguno» desactiva esta regla.';
$string['task_resync'] = 'Resincronizar la cohorte de trabajadores';

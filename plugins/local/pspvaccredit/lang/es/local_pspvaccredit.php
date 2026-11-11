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
 * Strings for local_pspvaccredit, Spanish.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accreditation'] = 'Acreditación';
$string['attendance'] = 'Asistencia';
$string['attendance_legend'] = 'Marque quién ha asistido';
$string['attendance_mark'] = 'Pasar lista';
$string['attendance_of'] = 'Asistencia: {$a}';
$string['attendance_save'] = 'Guardar la asistencia';
$string['attendance_saved'] = 'Se ha guardado la asistencia.';
$string['award'] = 'Marcar como apto/a';
$string['award_done'] = 'Se ha registrado al alumno como apto.';
$string['completed'] = 'Curso completado';
$string['data'] = 'Datos de la acreditación';
$string['diploma'] = 'Diploma';
$string['diploma_activity'] = 'Diploma';
$string['diploma_add'] = 'Añadir el diploma a este curso';
$string['diploma_border'] = 'Borde';
$string['diploma_certifies'] = 'certifica que';
$string['diploma_code'] = 'Código de verificación';
$string['diploma_completed'] = 'ha superado la actividad formativa';
$string['diploma_course'] = 'Curso';
$string['diploma_date'] = 'Fecha de emisión';
$string['diploma_desc'] = 'Añade una actividad de certificado hecha a partir de la plantilla del sitio. Solo pueden abrirla los alumnos registrados como aptos, y lleva un código y un QR que cualquiera puede verificar.';
$string['diploma_exists'] = 'Este curso ya tiene el diploma.';
$string['diploma_logo'] = 'Logotipo';
$string['diploma_open'] = 'Abrir el diploma';
$string['diploma_owner'] = 'Institución';
$string['diploma_qr'] = 'QR de verificación';
$string['diploma_student'] = 'Nombre del alumno';
$string['diploma_template'] = 'Diploma PSPV';
$string['diploma_title'] = 'Diploma';
$string['diploma_unavailable'] = 'Los diplomas necesitan la actividad Certificado personalizado (mod_customcert), que no está instalada en este sitio.';
$string['diploma_verify'] = 'Este diploma se puede verificar en {$a}';
$string['editsettings'] = 'Edítelas en la configuración del curso';
$string['event_accreditation_awarded'] = 'Alumno apto en un curso acreditado';
$string['event_accreditation_revoked'] = 'Registro de acreditación borrado';
$string['event_attendance_marked'] = 'Asistencia registrada';
$string['export'] = 'Exportar los aptos como';
$string['field_pspv_accredited'] = 'Actividad acreditada';
$string['field_pspv_accredited_desc'] = 'Márquelo cuando el curso está acreditado. Los alumnos que cumplen los criterios quedan registrados como aptos.';
$string['field_pspv_accreditedfrom'] = 'Acreditado desde';
$string['field_pspv_accreditedfrom_desc'] = 'Primer día de la acreditación.';
$string['field_pspv_accrediteduntil'] = 'Acreditado hasta';
$string['field_pspv_accrediteduntil_desc'] = 'Último día de la acreditación.';
$string['field_pspv_credits'] = 'Créditos';
$string['field_pspv_credits_desc'] = 'Créditos concedidos por la entidad acreditadora.';
$string['field_pspv_filecode'] = 'Código de expediente';
$string['field_pspv_filecode_desc'] = 'Código del expediente de acreditación.';
$string['field_pspv_hours'] = 'Horas';
$string['field_pspv_hours_desc'] = 'Horas lectivas acreditadas.';
$string['field_pspv_minattendance'] = 'Asistencia mínima (%)';
$string['field_pspv_minattendance_desc'] = 'Parte del tiempo de las sesiones a la que debe asistir un alumno para ser apto. Déjelo vacío si no se exige asistencia.';
$string['field_pspv_mingrade'] = 'Nota mínima (%)';
$string['field_pspv_mingrade_desc'] = 'Nota del curso, en porcentaje de la máxima, que necesita un alumno para ser apto. Déjelo vacío si no se exige nota.';
$string['fieldcategory'] = 'Acreditación';
$string['filter_from'] = 'Aptos desde';
$string['filter_to'] = 'Aptos hasta';
$string['groupdescription'] = 'Alumnos registrados como aptos. Lo rellena el plugin de acreditación y da acceso al diploma.';
$string['groupname'] = 'Aptos';
$string['manual'] = 'a mano';
$string['minattendance'] = 'Asistencia mínima';
$string['mingrade'] = 'Nota mínima';
$string['nocriteria'] = 'Este curso no tiene ningún criterio para ser apto, y por tanto no se registra a nadie automáticamente. Defina una asistencia o una nota mínima en la configuración del curso, o active el seguimiento de finalización con criterios.';
$string['notaccredited'] = 'Este curso no está marcado como actividad acreditada.';
$string['notpassed'] = 'Pendiente';
$string['notrequired'] = 'No se exige';
$string['passed'] = 'Apto/a';
$string['pending_attendance'] = 'asistencia';
$string['pending_completion'] = 'finalización del curso';
$string['pending_grade'] = 'nota';
$string['pending_nocriteria'] = 'sin criterios';
$string['period'] = 'Periodo de acreditación';
$string['pluginname'] = 'Campus PSPV acreditación';
$string['present'] = 'Asistentes';
$string['privacy:metadata:attend'] = 'Quién ha asistido a cada sesión de un curso.';
$string['privacy:metadata:attend:markedby'] = 'Quién pasó lista.';
$string['privacy:metadata:attend:sessionid'] = 'La sesión.';
$string['privacy:metadata:attend:timemodified'] = 'Cuándo se registró.';
$string['privacy:metadata:attend:userid'] = 'El alumno que asistió.';
$string['privacy:metadata:record'] = 'Registro de los alumnos aptos de un curso acreditado.';
$string['privacy:metadata:record:attendance'] = 'Asistencia del alumno al registrarlo.';
$string['privacy:metadata:record:awardedby'] = 'Quién registró al alumno a mano, si procede.';
$string['privacy:metadata:record:coursename'] = 'Nombre del curso.';
$string['privacy:metadata:record:credits'] = 'Créditos del curso.';
$string['privacy:metadata:record:filecode'] = 'Código de expediente de la acreditación.';
$string['privacy:metadata:record:grade'] = 'Nota del alumno al registrarlo.';
$string['privacy:metadata:record:hours'] = 'Horas del curso.';
$string['privacy:metadata:record:timeawarded'] = 'Cuándo se registró al alumno como apto.';
$string['privacy:metadata:record:userid'] = 'El alumno.';
$string['pspvaccredit:manage'] = 'Gestionar la acreditación de un curso';
$string['pspvaccredit:markattendance'] = 'Pasar lista en las sesiones';
$string['pspvaccredit:viewreport'] = 'Ver y exportar los aptos';
$string['records'] = 'Registro de alumnos aptos';
$string['records_count'] = '{$a} registros';
$string['records_none'] = 'Nadie ha superado ningún curso acreditado en este periodo.';
$string['report_attendance'] = 'Asistencia (%)';
$string['report_credits'] = 'Créditos';
$string['report_date'] = 'Fecha de aptitud';
$string['report_document'] = 'Documento de identidad';
$string['report_filecode'] = 'Código de expediente';
$string['report_grade'] = 'Nota (%)';
$string['report_hours'] = 'Horas';
$string['revoke'] = 'Borrar el registro';
$string['revoke_done'] = 'Se ha borrado el registro.';
$string['session'] = 'Sesión';
$string['session_add'] = 'Añadir una sesión';
$string['session_date'] = 'Fecha y hora';
$string['session_delete'] = 'Borrar esta sesión';
$string['session_delete_confirm'] = '¿Quiere borrar la sesión «{$a}» y su asistencia?';
$string['session_deleted'] = 'Se ha borrado la sesión.';
$string['session_duration'] = 'Duración (minutos)';
$string['session_duration_help'] = 'La asistencia se cuenta sobre el tiempo de todas las sesiones, de modo que una sesión larga pesa más que una corta. Si ninguna sesión tiene duración, todas cuentan igual.';
$string['session_duration_invalid'] = 'La duración no puede ser negativa.';
$string['session_edit'] = 'Editar la sesión';
$string['session_name'] = 'Nombre';
$string['session_saved'] = 'Se ha guardado la sesión.';
$string['sessions'] = 'Sesiones';
$string['sessions_none'] = 'Este curso no tiene sesiones con control de asistencia.';
$string['setting_idfield'] = 'Documento de identidad de los alumnos';
$string['setting_idfield_desc'] = 'Campo del usuario que contiene el documento de identidad (NIF/NIE) que aparece en los informes para la entidad acreditadora. TODO: el hospital debe indicar dónde se guarda; hasta entonces la columna queda vacía.';
$string['setting_idfield_none'] = 'Ninguno';
$string['settings'] = 'Configuración de la acreditación';
$string['students'] = 'Alumnos';
$string['students_none'] = 'Este curso no tiene alumnos.';
$string['sync'] = 'Recalcular los aptos';
$string['sync_done'] = '{$a} alumnos nuevos registrados como aptos.';
$string['task_sync'] = 'Registrar los alumnos aptos de los cursos acreditados';

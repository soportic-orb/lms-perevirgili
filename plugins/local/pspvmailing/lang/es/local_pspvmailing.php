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
 * Strings for local_pspvmailing, Spanish.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['campaign'] = 'Campaña';
$string['campaign_add'] = 'Campaña nueva';
$string['campaign_body'] = 'Mensaje';
$string['campaign_cancelled'] = 'Se ha detenido la campaña.';
$string['campaign_commercial'] = 'Comunicación comercial';
$string['campaign_commercial_help'] = 'Márquelo cuando el mensaje promociona cursos o servicios. Entonces solo se envía a quien ha dado el consentimiento y no se ha dado de baja, y lleva el enlace de baja. Déjelo sin marcar solo para avisos de servicio, como un cambio en un curso en el que alguien está matriculado.';
$string['campaign_deleted'] = 'Se ha borrado la campaña.';
$string['campaign_edit'] = 'Editar la campaña';
$string['campaign_finished_on'] = 'Terminada el {$a}';
$string['campaign_message'] = 'Mensaje';
$string['campaign_name'] = 'Nombre de la campaña';
$string['campaign_name_help'] = 'Para uso interno: los destinatarios no lo ven.';
$string['campaign_placeholders'] = 'Puede escribir esto en el asunto y en el mensaje, y cada destinatario verá el suyo: {$a}';
$string['campaign_queued'] = 'La campaña empezará a enviarse en unos minutos.';
$string['campaign_saved'] = 'Se ha guardado la campaña.';
$string['campaign_schedule'] = 'Programar';
$string['campaign_scheduled'] = 'Se ha programado la campaña.';
$string['campaign_scheduled_for'] = 'Programada para el {$a}';
$string['campaign_segment'] = 'Destinatarios';
$string['campaign_sendnow'] = 'Enviar ahora';
$string['campaign_stop'] = 'Detener el envío';
$string['campaign_subject'] = 'Asunto';
$string['campaign_unschedule'] = 'Volver a borrador';
$string['campaign_when'] = 'Enviar el';
$string['campaign_when_help'] = 'Los destinatarios se calculan cuando empieza el envío, no ahora: se tiene en cuenta quién entra o sale del segmento mientras tanto.';
$string['campaigns'] = 'Campañas';
$string['campaigns_back'] = 'Volver a las campañas';
$string['campaigns_none'] = 'Aún no hay ninguna campaña.';
$string['email_test_prefix'] = '[PRUEBA]';
$string['email_unsubscribe_link'] = 'Darme de baja';
$string['email_unsubscribe_text'] = 'Para dejar de recibir estas comunicaciones: {$a}';
$string['email_unsubscribe_why'] = 'Recibe este mensaje porque tiene una cuenta en el campus.';
$string['error_notdraft'] = 'Solo se puede cambiar un borrador.';
$string['event_campaign_sent'] = 'Campaña enviada';
$string['event_user_unsubscribed'] = 'Usuario dado de baja de las comunicaciones comerciales';
$string['failed'] = 'No se han podido enviar';
$string['pending'] = 'Pendientes de enviar';
$string['pluginname'] = 'Campus PSPV comunicación';
$string['preview'] = 'Vista previa';
$string['privacy:metadata:campaign'] = 'Campañas escritas por los gestores.';
$string['privacy:metadata:campaign:usermodified'] = 'Quién cambió la campaña por última vez.';
$string['privacy:metadata:optout'] = 'Usuarios que no quieren comunicaciones comerciales.';
$string['privacy:metadata:optout:timecreated'] = 'Cuándo se dio de baja el usuario.';
$string['privacy:metadata:optout:userid'] = 'El usuario.';
$string['privacy:metadata:recipient'] = 'Qué campañas se han enviado a cada usuario.';
$string['privacy:metadata:recipient:campaignid'] = 'La campaña.';
$string['privacy:metadata:recipient:status'] = 'Si se envió.';
$string['privacy:metadata:recipient:timesent'] = 'Cuándo se envió.';
$string['privacy:metadata:recipient:userid'] = 'El destinatario.';
$string['pspvmailing:manage'] = 'Escribir y enviar campañas';
$string['recipients'] = 'Destinatarios';
$string['recipients_consent'] = 'De {$a} usuarios del segmento; el resto no han dado el consentimiento o se han dado de baja.';
$string['recipients_estimate'] = 'Hoy se enviaría a {$a} usuarios.';
$string['recipients_noconsentsource'] = 'No hay ninguna política de comunicaciones comerciales definida y los trabajadores no están exentos: una campaña comercial no llega a nadie. Revise la configuración del plugin.';
$string['segment_anycohort'] = 'Cualquiera';
$string['segment_cohort'] = 'Miembros de la cohorte';
$string['segment_course'] = 'Matriculados en el curso';
$string['segment_course_help'] = 'Solo los usuarios con una matrícula activa en este curso. Déjelo vacío para no restringir.';
$string['segment_everyone'] = 'Todos los usuarios del campus';
$string['segment_in_cohort'] = 'Miembros de la cohorte «{$a}»';
$string['segment_in_course'] = 'Matriculados en «{$a}»';
$string['segment_purchase'] = 'Compras';
$string['segment_purchase_any'] = 'Cualquiera';
$string['segment_purchase_buyers'] = 'Han comprado algún curso';
$string['segment_purchase_nonbuyers'] = 'No han comprado nunca ningún curso';
$string['sent'] = 'Enviados';
$string['setting_batchsize'] = 'Mensajes por ejecución';
$string['setting_batchsize_desc'] = 'Cuántos mensajes entrega al servidor de correo cada ejecución de la tarea de envío. Bájelo si el servidor de correo limita los que acepta.';
$string['setting_commercialpolicy'] = 'Política de comunicaciones comerciales';
$string['setting_commercialpolicy_default'] = 'La que crea el plugin de acceso';
$string['setting_commercialpolicy_desc'] = 'Las campañas comerciales solo se envían a los usuarios que han aceptado la versión vigente de esta política. Mientras sea un borrador no la ha aceptado nadie.';
$string['setting_footer'] = 'Pie de los correos';
$string['setting_footer_desc'] = 'Identidad del remitente y texto legal que aparece bajo cada mensaje. TODO: debe darlo el hospital.';
$string['setting_workercohort'] = 'Cohorte de los trabajadores';
$string['setting_workercohort_desc'] = 'Número de identificación de la cohorte a la que se refiere la opción anterior.';
$string['setting_workersalways'] = 'Los trabajadores reciben campañas comerciales sin consentimiento';
$string['setting_workersalways_desc'] = 'Si se marca, los miembros de la cohorte de trabajadores reciben las campañas comerciales aunque no hayan aceptado la política; pueden darse de baja igualmente. TODO: debe validarlo el delegado de protección de datos del hospital. Si no se marca, los trabajadores necesitan el mismo consentimiento que todos.';
$string['settings'] = 'Configuración de la comunicación';
$string['status_cancelled'] = 'Detenida';
$string['status_draft'] = 'Borrador';
$string['status_scheduled'] = 'Programada';
$string['status_sending'] = 'Enviándose';
$string['status_sent'] = 'Enviada';
$string['task_send_due'] = 'Empezar las campañas que toca enviar';
$string['test_failed'] = 'No se ha podido enviar la prueba a {$a}.';
$string['test_send'] = 'Enviarme una prueba ({$a})';
$string['test_sent'] = 'Se ha enviado la prueba a {$a}.';
$string['type'] = 'Tipo';
$string['type_commercial'] = 'Comercial';
$string['type_informative'] = 'Aviso de servicio';
$string['unsubscribe'] = 'Baja de las comunicaciones comerciales';
$string['unsubscribe_confirm'] = 'Darme de baja';
$string['unsubscribe_done'] = 'No recibirá más comunicaciones comerciales del campus. Los avisos de los cursos en los que está matriculado le seguirán llegando.';
$string['unsubscribe_invalid'] = 'Este enlace de baja no es válido.';
$string['unsubscribe_mistake'] = '¿Ha sido un error?';
$string['unsubscribe_question'] = '¿Quiere dejar de recibir comunicaciones comerciales del campus?';
$string['unsubscribe_undo'] = 'Volver a darme de alta';

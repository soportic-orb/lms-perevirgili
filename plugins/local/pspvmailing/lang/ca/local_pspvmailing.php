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
 * Strings for local_pspvmailing, Catalan.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['campaign'] = 'Campanya';
$string['campaign_add'] = 'Campanya nova';
$string['campaign_body'] = 'Missatge';
$string['campaign_cancelled'] = 'S\'ha aturat la campanya.';
$string['campaign_commercial'] = 'Comunicació comercial';
$string['campaign_commercial_help'] = 'Marqueu-ho quan el missatge promociona cursos o serveis. Aleshores només s\'envia a qui ha donat el consentiment i no s\'ha donat de baixa, i porta l\'enllaç de baixa. Deixeu-ho sense marcar només per a avisos de servei, com ara un canvi en un curs on algú és matriculat.';
$string['campaign_deleted'] = 'S\'ha esborrat la campanya.';
$string['campaign_edit'] = 'Edita la campanya';
$string['campaign_finished_on'] = 'Acabada el {$a}';
$string['campaign_message'] = 'Missatge';
$string['campaign_name'] = 'Nom de la campanya';
$string['campaign_name_help'] = 'Per a ús intern: els destinataris no el veuen.';
$string['campaign_placeholders'] = 'Podeu escriure això a l\'assumpte i al missatge, i cada destinatari hi veurà el seu: {$a}';
$string['campaign_queued'] = 'La campanya es començarà a enviar d\'aquí a uns minuts.';
$string['campaign_saved'] = 'S\'ha desat la campanya.';
$string['campaign_schedule'] = 'Programa';
$string['campaign_scheduled'] = 'S\'ha programat la campanya.';
$string['campaign_scheduled_for'] = 'Programada per al {$a}';
$string['campaign_segment'] = 'Destinataris';
$string['campaign_sendnow'] = 'Envia ara';
$string['campaign_stop'] = 'Atura l\'enviament';
$string['campaign_subject'] = 'Assumpte';
$string['campaign_unschedule'] = 'Torna a esborrany';
$string['campaign_when'] = 'Envia el';
$string['campaign_when_help'] = 'Els destinataris es calculen quan comença l\'enviament, no ara: es té en compte qui entra o surt del segment mentrestant.';
$string['campaigns'] = 'Campanyes';
$string['campaigns_back'] = 'Torna a les campanyes';
$string['campaigns_none'] = 'Encara no hi ha cap campanya.';
$string['email_test_prefix'] = '[PROVA]';
$string['email_unsubscribe_link'] = 'Dona\'m de baixa';
$string['email_unsubscribe_text'] = 'Per deixar de rebre aquestes comunicacions: {$a}';
$string['email_unsubscribe_why'] = 'Rebeu aquest missatge perquè teniu un compte al campus.';
$string['error_notdraft'] = 'Només es pot canviar un esborrany.';
$string['event_campaign_sent'] = 'Campanya enviada';
$string['event_user_unsubscribed'] = 'Usuari donat de baixa de les comunicacions comercials';
$string['failed'] = 'No s\'han pogut enviar';
$string['pending'] = 'Pendents d\'enviar';
$string['pluginname'] = 'Campus PSPV comunicació';
$string['preview'] = 'Previsualització';
$string['privacy:metadata:campaign'] = 'Campanyes escrites pels gestors.';
$string['privacy:metadata:campaign:usermodified'] = 'Qui va canviar la campanya per última vegada.';
$string['privacy:metadata:optout'] = 'Usuaris que no volen comunicacions comercials.';
$string['privacy:metadata:optout:timecreated'] = 'Quan es va donar de baixa l\'usuari.';
$string['privacy:metadata:optout:userid'] = 'L\'usuari.';
$string['privacy:metadata:recipient'] = 'Quines campanyes s\'han enviat a cada usuari.';
$string['privacy:metadata:recipient:campaignid'] = 'La campanya.';
$string['privacy:metadata:recipient:status'] = 'Si es va enviar.';
$string['privacy:metadata:recipient:timesent'] = 'Quan es va enviar.';
$string['privacy:metadata:recipient:userid'] = 'El destinatari.';
$string['pspvmailing:manage'] = 'Escriure i enviar campanyes';
$string['recipients'] = 'Destinataris';
$string['recipients_consent'] = 'De {$a} usuaris del segment; la resta no han donat el consentiment o s\'han donat de baixa.';
$string['recipients_estimate'] = 'Avui s\'enviaria a {$a} usuaris.';
$string['recipients_noconsentsource'] = 'No hi ha cap política de comunicacions comercials definida i els treballadors no en són exempts: una campanya comercial no arriba a ningú. Reviseu la configuració del plugin.';
$string['segment_anycohort'] = 'Qualsevol';
$string['segment_cohort'] = 'Membres de la cohort';
$string['segment_course'] = 'Matriculats al curs';
$string['segment_course_help'] = 'Només els usuaris amb una matrícula activa en aquest curs. Deixeu-ho buit per no restringir.';
$string['segment_everyone'] = 'Tots els usuaris del campus';
$string['segment_in_cohort'] = 'Membres de la cohort «{$a}»';
$string['segment_in_course'] = 'Matriculats a «{$a}»';
$string['segment_purchase'] = 'Compres';
$string['segment_purchase_any'] = 'Qualsevol';
$string['segment_purchase_buyers'] = 'Han comprat algun curs';
$string['segment_purchase_nonbuyers'] = 'No han comprat mai cap curs';
$string['sent'] = 'Enviats';
$string['setting_batchsize'] = 'Missatges per execució';
$string['setting_batchsize_desc'] = 'Quants missatges lliura al servidor de correu cada execució de la tasca d\'enviament. Abaixeu-ho si el servidor de correu limita els que accepta.';
$string['setting_commercialpolicy'] = 'Política de comunicacions comercials';
$string['setting_commercialpolicy_default'] = 'La que crea el plugin d\'accés';
$string['setting_commercialpolicy_desc'] = 'Les campanyes comercials només s\'envien als usuaris que han acceptat la versió vigent d\'aquesta política. Mentre sigui un esborrany no l\'ha acceptada ningú.';
$string['setting_footer'] = 'Peu dels correus';
$string['setting_footer_desc'] = 'Identitat del remitent i text legal que surt sota cada missatge. TODO: l\'ha de donar l\'hospital.';
$string['setting_workercohort'] = 'Cohort dels treballadors';
$string['setting_workercohort_desc'] = 'Número d\'identificació de la cohort a què es refereix l\'opció anterior.';
$string['setting_workersalways'] = 'Els treballadors reben campanyes comercials sense consentiment';
$string['setting_workersalways_desc'] = 'Si es marca, els membres de la cohort de treballadors reben les campanyes comercials encara que no hagin acceptat la política; es poden donar de baixa igualment. TODO: ho ha de validar el delegat de protecció de dades de l\'hospital. Si no es marca, els treballadors necessiten el mateix consentiment que tothom.';
$string['settings'] = 'Configuració de la comunicació';
$string['status_cancelled'] = 'Aturada';
$string['status_draft'] = 'Esborrany';
$string['status_scheduled'] = 'Programada';
$string['status_sending'] = 'Enviant-se';
$string['status_sent'] = 'Enviada';
$string['task_send_due'] = 'Comença les campanyes que toca enviar';
$string['test_failed'] = 'No s\'ha pogut enviar la prova a {$a}.';
$string['test_send'] = 'Envia\'m una prova ({$a})';
$string['test_sent'] = 'S\'ha enviat la prova a {$a}.';
$string['type'] = 'Tipus';
$string['type_commercial'] = 'Comercial';
$string['type_informative'] = 'Avís de servei';
$string['unsubscribe'] = 'Baixa de les comunicacions comercials';
$string['unsubscribe_confirm'] = 'Dona\'m de baixa';
$string['unsubscribe_done'] = 'No rebreu més comunicacions comercials del campus. Els avisos dels cursos on sou matriculat us continuaran arribant.';
$string['unsubscribe_invalid'] = 'Aquest enllaç de baixa no és vàlid.';
$string['unsubscribe_mistake'] = 'Ha estat un error?';
$string['unsubscribe_question'] = 'Voleu deixar de rebre comunicacions comercials del campus?';
$string['unsubscribe_undo'] = 'Torna\'m a donar d\'alta';

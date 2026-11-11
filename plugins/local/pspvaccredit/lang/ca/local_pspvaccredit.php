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
 * Strings for local_pspvaccredit, Catalan.
 *
 * @package    local_pspvaccredit
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accreditation'] = 'Acreditació';
$string['attendance'] = 'Assistència';
$string['attendance_legend'] = 'Marqueu qui hi ha assistit';
$string['attendance_mark'] = 'Passa llista';
$string['attendance_of'] = 'Assistència: {$a}';
$string['attendance_save'] = 'Desa l\'assistència';
$string['attendance_saved'] = 'S\'ha desat l\'assistència.';
$string['award'] = 'Marca com a apte/a';
$string['award_done'] = 'S\'ha registrat l\'alumne com a apte.';
$string['completed'] = 'Curs completat';
$string['data'] = 'Dades de l\'acreditació';
$string['diploma'] = 'Diploma';
$string['diploma_activity'] = 'Diploma';
$string['diploma_add'] = 'Afegeix el diploma a aquest curs';
$string['diploma_border'] = 'Vora';
$string['diploma_certifies'] = 'certifica que';
$string['diploma_code'] = 'Codi de verificació';
$string['diploma_completed'] = 'ha superat l\'activitat formativa';
$string['diploma_course'] = 'Curs';
$string['diploma_date'] = 'Data d\'emissió';
$string['diploma_desc'] = 'Afegeix una activitat de certificat feta a partir de la plantilla del lloc. Només la poden obrir els alumnes registrats com a aptes, i porta un codi i un QR que qualsevol pot verificar.';
$string['diploma_exists'] = 'Aquest curs ja té el diploma.';
$string['diploma_logo'] = 'Logotip';
$string['diploma_open'] = 'Obre el diploma';
$string['diploma_owner'] = 'Institució';
$string['diploma_qr'] = 'QR de verificació';
$string['diploma_student'] = 'Nom de l\'alumne';
$string['diploma_template'] = 'Diploma PSPV';
$string['diploma_title'] = 'Diploma';
$string['diploma_unavailable'] = 'Els diplomes necessiten l\'activitat Certificat personalitzat (mod_customcert), que no és instal·lada en aquest lloc.';
$string['diploma_verify'] = 'Aquest diploma es pot verificar a {$a}';
$string['editsettings'] = 'Editeu-les a la configuració del curs';
$string['event_accreditation_awarded'] = 'Alumne apte en un curs acreditat';
$string['event_accreditation_revoked'] = 'Registre d\'acreditació esborrat';
$string['event_attendance_marked'] = 'Assistència registrada';
$string['export'] = 'Exporta els aptes com a';
$string['field_pspv_accredited'] = 'Activitat acreditada';
$string['field_pspv_accredited_desc'] = 'Marqueu-ho quan el curs és acreditat. Els alumnes que compleixen els criteris queden registrats com a aptes.';
$string['field_pspv_accreditedfrom'] = 'Acreditat des de';
$string['field_pspv_accreditedfrom_desc'] = 'Primer dia de l\'acreditació.';
$string['field_pspv_accrediteduntil'] = 'Acreditat fins a';
$string['field_pspv_accrediteduntil_desc'] = 'Últim dia de l\'acreditació.';
$string['field_pspv_credits'] = 'Crèdits';
$string['field_pspv_credits_desc'] = 'Crèdits concedits per l\'entitat acreditadora.';
$string['field_pspv_filecode'] = 'Codi d\'expedient';
$string['field_pspv_filecode_desc'] = 'Codi de l\'expedient d\'acreditació.';
$string['field_pspv_hours'] = 'Hores';
$string['field_pspv_hours_desc'] = 'Hores lectives acreditades.';
$string['field_pspv_minattendance'] = 'Assistència mínima (%)';
$string['field_pspv_minattendance_desc'] = 'Part del temps de les sessions a què ha d\'assistir un alumne per ser apte. Deixeu-ho buit si no s\'exigeix assistència.';
$string['field_pspv_mingrade'] = 'Nota mínima (%)';
$string['field_pspv_mingrade_desc'] = 'Nota del curs, en percentatge de la màxima, que necessita un alumne per ser apte. Deixeu-ho buit si no s\'exigeix nota.';
$string['fieldcategory'] = 'Acreditació';
$string['filter_from'] = 'Aptes des de';
$string['filter_to'] = 'Aptes fins a';
$string['groupdescription'] = 'Alumnes registrats com a aptes. L\'omple el plugin d\'acreditació i dona accés al diploma.';
$string['groupname'] = 'Aptes';
$string['manual'] = 'a mà';
$string['minattendance'] = 'Assistència mínima';
$string['mingrade'] = 'Nota mínima';
$string['nocriteria'] = 'Aquest curs no té cap criteri per ser apte, i per tant no es registra ningú automàticament. Definiu una assistència o una nota mínima a la configuració del curs, o activeu el seguiment de compleció amb criteris.';
$string['notaccredited'] = 'Aquest curs no és marcat com a activitat acreditada.';
$string['notpassed'] = 'Pendent';
$string['notrequired'] = 'No s\'exigeix';
$string['passed'] = 'Apte/a';
$string['pending_attendance'] = 'assistència';
$string['pending_completion'] = 'compleció del curs';
$string['pending_grade'] = 'nota';
$string['pending_nocriteria'] = 'sense criteris';
$string['period'] = 'Període d\'acreditació';
$string['pluginname'] = 'Campus PSPV acreditació';
$string['present'] = 'Assistents';
$string['privacy:metadata:attend'] = 'Qui ha assistit a cada sessió d\'un curs.';
$string['privacy:metadata:attend:markedby'] = 'Qui va passar llista.';
$string['privacy:metadata:attend:sessionid'] = 'La sessió.';
$string['privacy:metadata:attend:timemodified'] = 'Quan es va registrar.';
$string['privacy:metadata:attend:userid'] = 'L\'alumne que hi va assistir.';
$string['privacy:metadata:record'] = 'Registre dels alumnes aptes d\'un curs acreditat.';
$string['privacy:metadata:record:attendance'] = 'Assistència de l\'alumne en registrar-lo.';
$string['privacy:metadata:record:awardedby'] = 'Qui va registrar l\'alumne a mà, si escau.';
$string['privacy:metadata:record:coursename'] = 'Nom del curs.';
$string['privacy:metadata:record:credits'] = 'Crèdits del curs.';
$string['privacy:metadata:record:filecode'] = 'Codi d\'expedient de l\'acreditació.';
$string['privacy:metadata:record:grade'] = 'Nota de l\'alumne en registrar-lo.';
$string['privacy:metadata:record:hours'] = 'Hores del curs.';
$string['privacy:metadata:record:timeawarded'] = 'Quan es va registrar l\'alumne com a apte.';
$string['privacy:metadata:record:userid'] = 'L\'alumne.';
$string['pspvaccredit:manage'] = 'Gestionar l\'acreditació d\'un curs';
$string['pspvaccredit:markattendance'] = 'Passar llista a les sessions';
$string['pspvaccredit:viewreport'] = 'Veure i exportar els aptes';
$string['records'] = 'Registre d\'alumnes aptes';
$string['records_count'] = '{$a} registres';
$string['records_none'] = 'Ningú no ha superat cap curs acreditat en aquest període.';
$string['report_attendance'] = 'Assistència (%)';
$string['report_credits'] = 'Crèdits';
$string['report_date'] = 'Data d\'aptitud';
$string['report_document'] = 'Document d\'identitat';
$string['report_filecode'] = 'Codi d\'expedient';
$string['report_grade'] = 'Nota (%)';
$string['report_hours'] = 'Hores';
$string['revoke'] = 'Esborra el registre';
$string['revoke_done'] = 'S\'ha esborrat el registre.';
$string['session'] = 'Sessió';
$string['session_add'] = 'Afegeix una sessió';
$string['session_date'] = 'Data i hora';
$string['session_delete'] = 'Esborra aquesta sessió';
$string['session_delete_confirm'] = 'Voleu esborrar la sessió «{$a}» i la seva assistència?';
$string['session_deleted'] = 'S\'ha esborrat la sessió.';
$string['session_duration'] = 'Durada (minuts)';
$string['session_duration_help'] = 'L\'assistència es compta sobre el temps de totes les sessions, de manera que una sessió llarga pesa més que una de curta. Si cap sessió no té durada, totes compten igual.';
$string['session_duration_invalid'] = 'La durada no pot ser negativa.';
$string['session_edit'] = 'Edita la sessió';
$string['session_name'] = 'Nom';
$string['session_saved'] = 'S\'ha desat la sessió.';
$string['sessions'] = 'Sessions';
$string['sessions_none'] = 'Aquest curs no té sessions amb control d\'assistència.';
$string['setting_idfield'] = 'Document d\'identitat dels alumnes';
$string['setting_idfield_desc'] = 'Camp de l\'usuari que conté el document d\'identitat (NIF/NIE) que surt als informes per a l\'entitat acreditadora. TODO: l\'hospital ha de dir on es guarda; fins aleshores la columna queda buida.';
$string['setting_idfield_none'] = 'Cap';
$string['settings'] = 'Configuració de l\'acreditació';
$string['students'] = 'Alumnes';
$string['students_none'] = 'Aquest curs no té alumnes.';
$string['sync'] = 'Recalcula els aptes';
$string['sync_done'] = '{$a} alumnes nous registrats com a aptes.';
$string['task_sync'] = 'Registra els alumnes aptes dels cursos acreditats';

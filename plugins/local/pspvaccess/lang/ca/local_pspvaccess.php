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
 * Strings for local_pspvaccess, Catalan.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['audit_action_added'] = 'Afegit';
$string['audit_action_removed'] = 'Tret';
$string['audit_empty'] = 'Encara no s\'ha registrat cap canvi.';
$string['audit_intro'] = 'Cada vegada que un usuari entra a la cohort de treballadors o en surt automàticament, queda registrat aquí amb la regla que s\'ha aplicat.';
$string['audit_origin'] = 'Comprovat en';
$string['audit_origin_login'] = 'Inici de sessió';
$string['audit_origin_manual'] = 'Resincronització manual';
$string['audit_origin_profile'] = 'Canvi de perfil';
$string['audit_origin_task'] = 'Resincronització programada';
$string['audit_reason'] = 'Regla';
$string['audit_title'] = 'Registre d\'auditoria de la cohort de treballadors';
$string['category_external'] = 'Formació per a la ciutadania';
$string['category_external_desc'] = 'Cursos oberts a tota la ciutadania.';
$string['category_internal'] = 'Formació interna';
$string['category_internal_desc'] = 'Formació per als treballadors del Parc Sanitari Pere Virgili.';
$string['cohort_workers'] = 'Treballadors PSPV';
$string['cohort_workers_desc'] = 'Treballadors del Parc Sanitari Pere Virgili. La manté al dia el plugin d\'accés del Campus PSPV; els membres afegits a mà no es treuen mai automàticament.';
$string['deleteduser'] = 'Usuari suprimit';
$string['employees_count'] = 'Treballadors a la llista';
$string['employees_err_csv'] = 'No es pot llegir el fitxer: {$a}';
$string['employees_err_noemail'] = 'El fitxer no té cap columna «email».';
$string['employees_file'] = 'Fitxer CSV';
$string['employees_file_help'] = 'La primera fila conté els noms de les columnes. «email» és obligatòria; «employeeid», «department» i «jobcategory» són opcionals. Les files sense una adreça de correu vàlida s\'ometen.';
$string['employees_import'] = 'Importa la llista';
$string['employees_imported'] = 'Llista importada: {$a->created} de nous, {$a->updated} d\'actualitzats, {$a->deleted} de suprimits, {$a->skipped} files omeses. Cohort de treballadors: {$a->added} d\'afegits, {$a->removed} de trets.';
$string['employees_intro'] = 'La llista de treballadors ve de RRHH. Quan la regla «llista de treballadors» està activada, un usuari l\'adreça de correu del qual és a la llista és treballador.';
$string['employees_lastimport'] = 'Última importació';
$string['employees_listdisabled'] = 'La regla «llista de treballadors» està desactivada: la llista es desa però no es fa servir.';
$string['employees_replace'] = 'Treu els que no siguin al fitxer';
$string['employees_replace_help'] = 'Si es marca, el fitxer substitueix tota la llista: els treballadors que no hi siguin se suprimeixen i, a la comprovació següent, surten de la cohort de treballadors si no els aplica cap altra regla. Si no es marca, el fitxer només afegeix i actualitza.';
$string['employees_title'] = 'Llista de treballadors';
$string['event_employee_list_imported'] = 'Llista de treballadors importada';
$string['issuer_worker'] = 'Compte corporatiu PSPV';
$string['pluginname'] = 'Accés del Campus PSPV';
$string['policy_marketing'] = 'Comunicacions comercials';
$string['policy_placeholder'] = '<p>TODO: text pendent del delegat de protecció de dades de l\'hospital.</p>';
$string['policy_privacy'] = 'Política de privacitat';
$string['policy_revision'] = 'Esborrany';
$string['policy_terms'] = 'Condicions d\'ús';
$string['privacy:metadata:audit'] = 'Registre d\'auditoria dels canvis automàtics de la cohort de treballadors.';
$string['privacy:metadata:audit:action'] = 'Si l\'usuari es va afegir o treure.';
$string['privacy:metadata:audit:actorid'] = 'L\'usuari que va provocar la comprovació, si n\'hi ha.';
$string['privacy:metadata:audit:origin'] = 'Què va desencadenar la comprovació.';
$string['privacy:metadata:audit:reason'] = 'Les regles que es van aplicar.';
$string['privacy:metadata:audit:userid'] = 'L\'usuari afegit o tret.';
$string['privacy:metadata:employee'] = 'Llista de treballadors importada de RRHH.';
$string['privacy:metadata:employee:department'] = 'Servei o unitat.';
$string['privacy:metadata:employee:email'] = 'Adreça de correu del treballador.';
$string['privacy:metadata:employee:employeeid'] = 'Identificador al sistema de RRHH.';
$string['privacy:metadata:employee:jobcategory'] = 'Categoria professional.';
$string['privacy:metadata:member'] = 'Usuaris que el plugin ha posat a la cohort de treballadors.';
$string['privacy:metadata:member:reason'] = 'Les regles per les quals l\'usuari és treballador.';
$string['privacy:metadata:member:userid'] = 'L\'usuari.';
$string['privacy:metadata:preference:tenantlogin'] = 'Moment de l\'últim inici de sessió amb el compte corporatiu de l\'hospital.';
$string['privacy:metadata:timecreated'] = 'Quan es va crear el registre.';
$string['privacy:path:audit'] = 'Canvis a la cohort de treballadors';
$string['privacy:path:employee'] = 'Llista de treballadors';
$string['privacy:path:member'] = 'Condició de treballador';
$string['pspvaccess:manageemployees'] = 'Gestionar la llista de treballadors i resincronitzar la cohort';
$string['pspvaccess:viewaudit'] = 'Veure el registre d\'auditoria de la cohort de treballadors';
$string['resync_done'] = 'Cohort de treballadors resincronitzada: {$a->checked} usuaris comprovats, {$a->added} d\'afegits, {$a->removed} de trets.';
$string['resync_now'] = 'Resincronitza la cohort ara';
$string['role_pspvsalesmanager'] = 'Gestor/a comercial';
$string['role_pspvsalesmanager_desc'] = 'Gestiona les vendes, els descomptes i les factures de la formació per a la ciutadania.';
$string['role_pspvtrainingmanager'] = 'Gestor/a de formació';
$string['role_pspvtrainingmanager_desc'] = 'Gestiona cursos i usuaris, sense accés a la configuració d\'administració del lloc.';
$string['role_pspvworker'] = 'Treballador/a PSPV';
$string['role_pspvworker_desc'] = 'S\'assigna automàticament als membres de la cohort de treballadors a la categoria de formació interna, perquè la puguin veure. No l\'assigneu a mà: afegiu l\'usuari a la cohort.';
$string['rule_domain'] = 'Domini de correu corporatiu';
$string['rule_list'] = 'Llista de treballadors';
$string['rule_none'] = 'Ja no aplica cap regla';
$string['rule_tenant'] = 'Compte Microsoft corporatiu';
$string['settings_emaildomains'] = 'Dominis de correu corporatius';
$string['settings_emaildomains_desc'] = 'Un domini per línia, sense «@». Un usuari amb el compte confirmat l\'adreça de correu del qual és d\'un d\'aquests dominis és treballador. Deixeu-ho buit per desactivar aquesta regla.';
$string['settings_intro'] = 'Aquestes regles decideixen qui és treballador de l\'hospital. Els treballadors es posen a la cohort «Treballadors PSPV», que és la que els mostra la formació interna. Totes les regles estan desactivades fins que es configurin aquí.';
$string['settings_norules'] = 'No hi ha cap regla de treballador configurada, de manera que ningú entra automàticament a la cohort de treballadors.';
$string['settings_rulemode'] = 'Com es combinen les regles';
$string['settings_rulemode_all'] = 'S\'han de complir totes les regles actives';
$string['settings_rulemode_any'] = 'N\'hi ha prou amb una regla activa';
$string['settings_rulemode_desc'] = 'Si un usuari ha de complir una de les regles actives o totes.';
$string['settings_tenantmaxage'] = 'Dies de validesa d\'un inici de sessió corporatiu';
$string['settings_tenantmaxage_desc'] = 'Un usuari deixa de complir la regla del compte corporatiu quan fa aquest nombre de dies que no hi inicia sessió. 0 vol dir que no caduca mai: l\'usuari continua sent treballador mentre el compte corporatiu estigui enllaçat.';
$string['settings_title'] = 'Regles de treballador';
$string['settings_useemployeelist'] = 'Fes servir la llista de treballadors';
$string['settings_useemployeelist_desc'] = 'Un usuari l\'adreça de correu del qual és a la llista de treballadors importada és treballador.';
$string['settings_workerissuer'] = 'Proveïdor d\'inici de sessió del compte corporatiu';
$string['settings_workerissuer_desc'] = 'El proveïdor OAuth 2 restringit al tenant de Microsoft Entra ID de l\'hospital. Un usuari que hi ha iniciat sessió és treballador. «Cap» desactiva aquesta regla.';
$string['task_resync'] = 'Resincronitza la cohort de treballadors';

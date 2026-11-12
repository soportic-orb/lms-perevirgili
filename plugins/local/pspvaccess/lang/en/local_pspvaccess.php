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
 * Strings for local_pspvaccess, English.
 *
 * @package    local_pspvaccess
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['audit_action_added'] = 'Added';
$string['audit_action_removed'] = 'Removed';
$string['audit_empty'] = 'No changes have been recorded yet.';
$string['audit_intro'] = 'Every time a user enters or leaves the workers cohort automatically, it is recorded here with the rule that applied.';
$string['audit_origin'] = 'Checked on';
$string['audit_origin_login'] = 'Login';
$string['audit_origin_manual'] = 'Manual re-synchronisation';
$string['audit_origin_profile'] = 'Profile change';
$string['audit_origin_task'] = 'Scheduled re-synchronisation';
$string['audit_reason'] = 'Rule';
$string['audit_title'] = 'Workers cohort audit log';
$string['category_external'] = 'Training for citizens';
$string['category_external_desc'] = 'Courses open to all citizens.';
$string['category_internal'] = 'Internal training';
$string['category_internal_desc'] = 'Training for the workers of Parc Sanitari Pere Virgili.';
$string['cohort_workers'] = 'PSPV workers';
$string['cohort_workers_desc'] = 'Workers of Parc Sanitari Pere Virgili. Kept up to date by the Campus PSPV access plugin; members added by hand are never removed automatically.';
$string['deleteduser'] = 'Deleted user';
$string['employees_count'] = 'Employees in the list';
$string['employees_err_csv'] = 'The file cannot be read: {$a}';
$string['employees_err_noemail'] = 'The file has no "email" column.';
$string['employees_file'] = 'CSV file';
$string['employees_file_help'] = 'The first row holds the column names. "email" is required; "employeeid", "department" and "jobcategory" are optional. Rows without a valid email address are skipped.';
$string['employees_import'] = 'Import the list';
$string['employees_imported'] = 'List imported: {$a->created} new, {$a->updated} updated, {$a->deleted} deleted, {$a->skipped} rows skipped. Workers cohort: {$a->added} added, {$a->removed} removed.';
$string['employees_intro'] = 'The employee list comes from HR. When the "employee list" rule is on, a user whose email address is in the list is a worker.';
$string['employees_lastimport'] = 'Last import';
$string['employees_listdisabled'] = 'The "employee list" rule is off: the list is stored but not used.';
$string['employees_replace'] = 'Remove those not in the file';
$string['employees_replace_help'] = 'If ticked, the file replaces the whole list: employees missing from it are deleted and, at the next check, leave the workers cohort unless another rule applies to them. If not ticked, the file only adds and updates.';
$string['employees_title'] = 'Employee list';
$string['event_employee_list_imported'] = 'Employee list imported';
$string['issuer_worker'] = 'PSPV corporate account';
$string['pluginname'] = 'Campus PSPV access';
$string['policy_marketing'] = 'Commercial communications';
$string['policy_placeholder'] = '<p>TODO: text pending from the hospital\'s data protection officer.</p>';
$string['policy_privacy'] = 'Privacy policy';
$string['policy_revision'] = 'Draft';
$string['policy_terms'] = 'Terms of use';
$string['privacy:metadata:audit'] = 'Audit log of the automatic changes of the workers cohort.';
$string['privacy:metadata:audit:action'] = 'Whether the user was added or removed.';
$string['privacy:metadata:audit:actorid'] = 'The user who caused the check, if any.';
$string['privacy:metadata:audit:origin'] = 'What triggered the check.';
$string['privacy:metadata:audit:reason'] = 'The rules that applied.';
$string['privacy:metadata:audit:userid'] = 'The user added or removed.';
$string['privacy:metadata:employee'] = 'Employee list imported from HR.';
$string['privacy:metadata:employee:department'] = 'Service or unit.';
$string['privacy:metadata:employee:email'] = 'Email address of the employee.';
$string['privacy:metadata:employee:employeeid'] = 'Identifier in the HR system.';
$string['privacy:metadata:employee:jobcategory'] = 'Professional category.';
$string['privacy:metadata:member'] = 'Users the plugin placed in the workers cohort.';
$string['privacy:metadata:member:reason'] = 'The rules by which the user is a worker.';
$string['privacy:metadata:member:userid'] = 'The user.';
$string['privacy:metadata:preference:tenantlogin'] = 'Time of the last sign-in with the hospital\'s corporate account.';
$string['privacy:metadata:timecreated'] = 'When the record was created.';
$string['privacy:path:audit'] = 'Workers cohort changes';
$string['privacy:path:employee'] = 'Employee list';
$string['privacy:path:member'] = 'Worker status';
$string['pspvaccess:manageemployees'] = 'Manage the employee list and re-synchronise the workers cohort';
$string['pspvaccess:viewaudit'] = 'View the workers cohort audit log';
$string['resync_done'] = 'Workers cohort re-synchronised: {$a->checked} users checked, {$a->added} added, {$a->removed} removed.';
$string['resync_now'] = 'Re-synchronise the cohort now';
$string['role_pspvsalesmanager'] = 'Sales manager';
$string['role_pspvsalesmanager_desc'] = 'Manages sales, discounts and invoices of the training for citizens.';
$string['role_pspvtrainingmanager'] = 'Training manager';
$string['role_pspvtrainingmanager_desc'] = 'Manages courses and users, without access to the site administration settings.';
$string['role_pspvworker'] = 'PSPV worker';
$string['role_pspvworker_desc'] = 'Assigned automatically to the members of the workers cohort in the internal training category, so they can see it. Do not assign it by hand: add the user to the cohort instead.';
$string['rule_domain'] = 'Corporate email domain';
$string['rule_list'] = 'Employee list';
$string['rule_none'] = 'No rule applies any more';
$string['rule_tenant'] = 'Corporate Microsoft account';
$string['settings_emaildomains'] = 'Corporate email domains';
$string['settings_emaildomains_desc'] = 'One domain per line, without "@". A user with a confirmed account whose email address is in one of these domains is a worker. Leave it empty to switch this rule off.';
$string['settings_intro'] = 'These rules decide who is a worker of the hospital. Workers are placed in the "PSPV workers" cohort, which is what shows them the internal training. All the rules are off until they are configured here.';
$string['settings_norules'] = 'No worker rule is configured, so nobody enters the workers cohort automatically.';
$string['settings_rulemode'] = 'How the rules combine';
$string['settings_rulemode_all'] = 'All the active rules must apply';
$string['settings_rulemode_any'] = 'One active rule is enough';
$string['settings_rulemode_desc'] = 'Whether a user needs to meet one of the active rules or all of them.';
$string['settings_tenantmaxage'] = 'Days a corporate sign-in stays valid';
$string['settings_tenantmaxage_desc'] = 'A user stops meeting the corporate account rule when they have not signed in with it for this many days. 0 means it never expires: the user stays a worker for as long as the corporate account is linked.';
$string['settings_title'] = 'Worker rules';
$string['settings_useemployeelist'] = 'Use the employee list';
$string['settings_useemployeelist_desc'] = 'A user whose email address is in the imported employee list is a worker.';
$string['settings_workerissuer'] = 'Corporate account sign-in provider';
$string['settings_workerissuer_desc'] = 'The OAuth 2 provider restricted to the hospital\'s Microsoft Entra ID tenant. A user who has signed in through it is a worker. "None" switches this rule off.';
$string['task_resync'] = 'Re-synchronise the workers cohort';

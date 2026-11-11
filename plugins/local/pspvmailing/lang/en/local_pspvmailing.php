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
 * Strings for local_pspvmailing, English.
 *
 * @package    local_pspvmailing
 * @copyright  2026 Soportic <https://soportic.es>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['campaign'] = 'Campaign';
$string['campaign_add'] = 'New campaign';
$string['campaign_body'] = 'Message';
$string['campaign_cancelled'] = 'The campaign has been stopped.';
$string['campaign_commercial'] = 'Commercial communication';
$string['campaign_commercial_help'] = 'Tick it when the message promotes courses or services. It is then sent only to who has given consent and has not unsubscribed, and it carries the link to unsubscribe. Leave it unticked only for service notices, such as a change in a course somebody is enrolled in.';
$string['campaign_deleted'] = 'The campaign has been deleted.';
$string['campaign_edit'] = 'Edit the campaign';
$string['campaign_finished_on'] = 'Finished on {$a}';
$string['campaign_message'] = 'Message';
$string['campaign_name'] = 'Name of the campaign';
$string['campaign_name_help'] = 'For your own reference: recipients do not see it.';
$string['campaign_placeholders'] = 'You can write these in the subject and in the message, and each recipient gets their own: {$a}';
$string['campaign_queued'] = 'The campaign will start being sent in a few minutes.';
$string['campaign_saved'] = 'Campaign saved.';
$string['campaign_schedule'] = 'Schedule';
$string['campaign_scheduled'] = 'The campaign has been scheduled.';
$string['campaign_scheduled_for'] = 'Scheduled for {$a}';
$string['campaign_segment'] = 'Recipients';
$string['campaign_sendnow'] = 'Send now';
$string['campaign_stop'] = 'Stop sending';
$string['campaign_subject'] = 'Subject';
$string['campaign_unschedule'] = 'Back to draft';
$string['campaign_when'] = 'Send on';
$string['campaign_when_help'] = 'The recipients are worked out when the sending starts, not now: who joins or leaves the segment in the meantime is taken into account.';
$string['campaigns'] = 'Campaigns';
$string['campaigns_back'] = 'Back to the campaigns';
$string['campaigns_none'] = 'There are no campaigns yet.';
$string['email_test_prefix'] = '[TEST]';
$string['email_unsubscribe_link'] = 'Unsubscribe';
$string['email_unsubscribe_text'] = 'To stop receiving these communications: {$a}';
$string['email_unsubscribe_why'] = 'You receive this message because you have an account on the campus.';
$string['error_notdraft'] = 'Only a draft can be changed.';
$string['event_campaign_sent'] = 'Campaign sent';
$string['event_user_unsubscribed'] = 'User unsubscribed from commercial communications';
$string['failed'] = 'Could not be sent';
$string['pending'] = 'Still to send';
$string['pluginname'] = 'Campus PSPV mailing';
$string['preview'] = 'Preview';
$string['privacy:metadata:campaign'] = 'Campaigns written by the managers.';
$string['privacy:metadata:campaign:usermodified'] = 'Who last changed the campaign.';
$string['privacy:metadata:optout'] = 'Users who do not want commercial communications.';
$string['privacy:metadata:optout:timecreated'] = 'When the user unsubscribed.';
$string['privacy:metadata:optout:userid'] = 'The user.';
$string['privacy:metadata:recipient'] = 'Which campaigns were sent to each user.';
$string['privacy:metadata:recipient:campaignid'] = 'The campaign.';
$string['privacy:metadata:recipient:status'] = 'Whether it was sent.';
$string['privacy:metadata:recipient:timesent'] = 'When it was sent.';
$string['privacy:metadata:recipient:userid'] = 'The recipient.';
$string['pspvmailing:manage'] = 'Write and send campaigns';
$string['recipients'] = 'Recipients';
$string['recipients_consent'] = 'Out of {$a} users in the segment; the rest have not given consent or have unsubscribed.';
$string['recipients_estimate'] = 'It would be sent to {$a} users today.';
$string['recipients_noconsentsource'] = 'No policy on commercial communications is set and workers are not exempted, so a commercial campaign reaches nobody. Check the settings of the plugin.';
$string['segment_anycohort'] = 'Any';
$string['segment_cohort'] = 'Members of the cohort';
$string['segment_course'] = 'Enrolled in the course';
$string['segment_course_help'] = 'Only users with an active enrolment in this course. Leave it empty for no restriction.';
$string['segment_everyone'] = 'Every user of the campus';
$string['segment_in_cohort'] = 'Members of the cohort "{$a}"';
$string['segment_in_course'] = 'Enrolled in "{$a}"';
$string['segment_purchase'] = 'Purchases';
$string['segment_purchase_any'] = 'Any';
$string['segment_purchase_buyers'] = 'Have bought a course';
$string['segment_purchase_nonbuyers'] = 'Have never bought a course';
$string['sent'] = 'Sent';
$string['setting_batchsize'] = 'Messages per run';
$string['setting_batchsize_desc'] = 'How many messages each run of the sending task hands to the mail server. Lower it if the mail server limits how many it accepts.';
$string['setting_commercialpolicy'] = 'Policy on commercial communications';
$string['setting_commercialpolicy_default'] = 'The one created by the access plugin';
$string['setting_commercialpolicy_desc'] = 'Commercial campaigns are only sent to users who have accepted the current version of this policy. While it is a draft nobody has accepted it.';
$string['setting_footer'] = 'Foot of the emails';
$string['setting_footer_desc'] = 'Identity of the sender and legal text shown under every message. TODO: the hospital has to provide it.';
$string['setting_workercohort'] = 'Cohort of the workers';
$string['setting_workercohort_desc'] = 'ID number of the cohort the option above refers to.';
$string['setting_workersalways'] = 'Workers receive commercial campaigns without consent';
$string['setting_workersalways_desc'] = 'If ticked, members of the cohort of workers receive commercial campaigns even if they have not accepted the policy; they can still unsubscribe. TODO: the data protection officer of the hospital has to validate this. If not ticked, workers need the same consent as everybody else.';
$string['settings'] = 'Mailing settings';
$string['status_cancelled'] = 'Stopped';
$string['status_draft'] = 'Draft';
$string['status_scheduled'] = 'Scheduled';
$string['status_sending'] = 'Sending';
$string['status_sent'] = 'Sent';
$string['task_send_due'] = 'Start the campaigns whose time has come';
$string['test_failed'] = 'The test could not be sent to {$a}.';
$string['test_send'] = 'Send me a test ({$a})';
$string['test_sent'] = 'Test sent to {$a}.';
$string['type'] = 'Type';
$string['type_commercial'] = 'Commercial';
$string['type_informative'] = 'Service notice';
$string['unsubscribe'] = 'Unsubscribe from commercial communications';
$string['unsubscribe_confirm'] = 'Unsubscribe me';
$string['unsubscribe_done'] = 'You will not receive any more commercial communications from the campus. Notices about the courses you are enrolled in will still reach you.';
$string['unsubscribe_invalid'] = 'This unsubscribe link is not valid.';
$string['unsubscribe_mistake'] = 'Was it a mistake?';
$string['unsubscribe_question'] = 'Do you want to stop receiving commercial communications from the campus?';
$string['unsubscribe_undo'] = 'Subscribe me again';

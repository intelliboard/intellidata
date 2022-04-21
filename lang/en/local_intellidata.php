<?php
// This file is part of the Local plans plugin
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
 * This plugin sends users a plans message after logging in
 * and notify a moderator a new user has been added
 * it has a settings page that allow you to configure the messages
 * send.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'IntelliData';
$string['intellidata:trackdata'] = 'IntelliData: Track Data';
$string['general'] = 'General';
$string['enabled'] = 'Enabled';
$string['inactivity'] = 'Inactivity';
$string['inactivity_desc'] = 'User inactivity time (in seconds)';
$string['trackadmin'] = 'Tracking Admins';
$string['trackadmin_desc'] = 'Enable Time Tracking for Admin Users (not recommended)';
$string['trackmedia'] = "Track HTML5 media";
$string['trackmedia_desc'] = "Track HTML5 video and audio";
$string['encryptionkey'] = "API Key";
$string['clientidentifier'] = "API Identifier";
$string['ajaxfrequency'] = 'Tracking Frequency';
$string['ajaxfrequency_desc'] = 'Session storing frequency via AJAX. 0 - AJAX disabled (in seconds)';
$string['failed_rename_tempfile'] = 'Failed renaming Temp File';
$string['filenotexists'] = 'File not exists';
$string['export_task'] = 'Export Task';
$string['cleaner_task'] = 'Cleaner Task';
$string['migration_task'] = 'Migration Task';
$string['cleaner_duration'] = 'Cleaner Duration';
$string['migrationrecordslimit'] = 'Migration processing limit';
$string['migrationrecordslimit_desc'] = 'The number of records which will be processed at once';
$string['migrationwriterecordslimit'] = 'Migration processing limit write to temp file';
$string['migrationwriterecordslimit_desc'] = 'The number of records which will be write to temp file at once, recommend 10000 when moodledata located on local ssd and 100000 when moodledata located on efs';
$string['resetmigrationprogress'] = 'Reset Migration Process';
$string['resetmigrationprogress_desc'] = 'Reset migration process and start from beginning';
$string['exportfilesduringmigration'] = 'Export files during migration';
$string['exportfilesduringmigration_desc'] = 'If enabled, IntelliData will export file to MoodleData after migration processed';
$string['exportdataformat'] = 'Export data format';
$string['exportdataformat_desc'] = 'Setting for migration files data format';
$string['exportlogs'] = 'Export Logs';
$string['intellidata:viewlogs'] = 'View Logs';
$string['datatype'] = 'Data Type';
$string['filename'] = 'File Name';
$string['filesize'] = 'File Size';
$string['created'] = 'Created';
$string['actions'] = 'Actions';
$string['migrations'] = 'Migrations';
$string['status'] = 'Status';
$string['progress'] = 'Progress';
$string['timestart'] = 'Time Start';
$string['timeend'] = 'Time End';
$string['status_completed'] = 'Completed';
$string['status_inprogress'] = 'In Progress';
$string['status_pending'] = 'Pending';
$string['datatype_tracking'] = 'User Tracking';
$string['datatype_migration_trackinglog'] = 'User Tracking Logs';
$string['datatype_migration_trackinglogdetail'] = 'User Tracking Log Details';
$string['datatype_users'] = 'Users';
$string['datatype_migration_userlogins'] = 'User Logins Migration';
$string['datatype_userlogins'] = 'User Logins';
$string['datatype_categories'] = 'Categories';
$string['datatype_courses'] = 'Courses';
$string['datatype_enrolments'] = 'Enrolments';
$string['datatype_roleassignments'] = 'Role Assignments';
$string['datatype_coursecompletions'] = 'Course Completions';
$string['datatype_activities'] = 'Activities';
$string['datatype_activitycompletions'] = 'Activity Completions';
$string['datatype_usergrades'] = 'Users Grades';
$string['datatype_roles'] = 'Roles';
$string['datatype_modules'] = 'Modules';
$string['datatype_forumdiscussions'] = 'Forum Discussions';
$string['datatype_forumposts'] = 'Forum Posts';
$string['datatype_quizattempts'] = 'Quiz Attempts';
$string['datatype_quizquestions'] = 'Quiz Questions';
$string['datatype_quizquestionrelations'] = 'Quiz question relations';
$string['datatype_quizquestionanswers'] = 'Quiz question answers';
$string['datatype_assignmentsubmissions'] = 'Assignment Submissions';
$string['datatype_intelliboardtracking'] = 'IntelliBoard Tracking';
$string['datatype_intelliboardlogs'] = 'IntelliBoard Logs';
$string['datatype_intelliboardtotals'] = 'IntelliBoard Totals';
$string['datatype_intelliboarddetails'] = 'IntelliBoard Details';
$string['datatype_cohorts'] = 'Cohorts';
$string['datatype_cohortmembers'] = 'Cohort Members';
$string['datatype_groups'] = 'Groups';
$string['datatype_groupmembers'] = 'Group Members';
$string['datatype_userinfofields'] = 'User Info Fields';
$string['datatype_userinfodatas'] = 'User Info Data';
$string['datatype_userinfocategories'] = 'User Info Categories';
$string['datatype_customfielddatas'] = 'Custom Field Data';
$string['datatype_customfieldfields'] = 'Custom Field Fields';
$string['datatype_customfieldcategories'] = 'Custom Field Categories';
$string['datatype_questionnairequestions'] = 'Questionnaire Questions';
$string['datatype_questionnairequestchoices'] = 'Questionnaire Quest Choices';
$string['datatype_questionnaireresponses'] = 'Questionnaire Responses';
$string['datatype_questionnairequestiontypes'] = 'Qestionnaire Question Types';
$string['datatype_gradecategories'] = 'Grade Categories';
$string['datatype_scormscoestracks'] = 'Scorm Scoes Tracks';
$string['datatype_ltisubmission'] = 'LTI Submissions';
$string['datatype_participation'] = 'User Participations';
$string['datatype_trackinglogdetail'] = 'User Tracking Log by Hour';
$string['datatype_trackinglog'] = 'User Tracking Log by Day';
$string['datatype_org'] = 'Org';
$string['datatype_orgtype'] = 'Org Types';
$string['datatype_orgtypeinfofield'] = 'Org Type Info Fields';
$string['datatype_pos'] = 'Pos';
$string['datatype_posassignment'] = 'Pos Assignments';
$string['datatype_migration_tracking'] = 'User Tracking Migration';
$string['datatype_migration_trackinglog'] = 'User Tracking Logs Migration';
$string['datatype_migration_trackinglogdetail'] = 'User Tracking Log Details Migration';
$string['datatype_migration_users'] = 'Users Migration';
$string['datatype_migration_categories'] = 'Categories Migration';
$string['datatype_migration_courses'] = 'Courses Migration';
$string['datatype_migration_enrolments'] = 'Enrolments Migration';
$string['datatype_migration_roleassignments'] = 'Role Assignments Migration';
$string['datatype_migration_coursecompletions'] = 'Course Completions Migration';
$string['datatype_migration_activities'] = 'Activities Migration';
$string['datatype_migration_activitycompletions'] = 'Activity Completions Migration';
$string['datatype_migration_usergrades'] = 'Users Grades Migration';
$string['datatype_migration_roles'] = 'Roles Migration';
$string['datatype_migration_modules'] = 'Modules Migration';
$string['datatype_migration_forumdiscussions'] = 'Forum Discussions Migration';
$string['datatype_migration_forumposts'] = 'Forum Posts Migration';
$string['datatype_migration_quizattempts'] = 'Quiz Attempts Migration';
$string['datatype_migration_quizquestions'] = 'Quiz Questions Migration';
$string['datatype_migration_quizquestionrelations'] = 'Quiz question relations Migration';
$string['datatype_migration_quizquestionanswers'] = 'Quiz question answers Migration';
$string['datatype_migration_assignmentsubmissions'] = 'Assignment Submissions Migration';
$string['datatype_migration_intelliboardtracking'] = 'IntelliBoard Tracking Migration';
$string['datatype_migration_intelliboardlogs'] = 'IntelliBoard Logs Migration';
$string['datatype_migration_intelliboardtotals'] = 'IntelliBoard Totals Migration';
$string['datatype_migration_intelliboarddetails'] = 'IntelliBoard Details Migration';
$string['datatype_migration_cohorts'] = 'Cohorts Migration';
$string['datatype_migration_cohortmembers'] = 'Cohort Members Migration';
$string['datatype_migration_groups'] = 'Groups Migration';
$string['datatype_migration_groupmembers'] = 'Group Members Migration';
$string['datatype_migration_userinfofields'] = 'User Info Fields Migration';
$string['datatype_migration_userinfodatas'] = 'User Info Data Migration';
$string['datatype_migration_userinfocategories'] = 'User Info Categories Migration';
$string['datatype_migration_customfielddatas'] = 'Custom Field Data Migration';
$string['datatype_migration_customfieldfields'] = 'Custom Field Fields Migration';
$string['datatype_migration_customfieldcategories'] = 'Custom Field Categories Migration';
$string['datatype_migration_questionnairequestions'] = 'Questionnaire Questions Migration';
$string['datatype_migration_questionnairequestchoices'] = 'Questionnaire Quest Choices Migration';
$string['datatype_migration_questionnaireresponses'] = 'Questionnaire Responses Migration';
$string['datatype_migration_questionnairequestiontypes'] = 'Qestionnaire Question Types Migration';
$string['datatype_migration_gradecategories'] = 'Grade Categories Migration';
$string['datatype_migration_gradeitems'] = 'Grade Items Migration';
$string['datatype_migration_scormscoestracks'] = 'Scorm Scoes Tracks Migration';
$string['datatype_migration_ltisubmission'] = 'LTI Submissions Migration';
$string['datatype_migration_orgtype'] = 'Org Types Migration';
$string['datatype_migration_org'] = 'Org Migration';
$string['datatype_migration_orgtypeinfofield'] = 'Org Type Info Fields Migration';
$string['datatype_migration_pos'] = 'Pos Migration';
$string['datatype_migration_posassignment'] = 'Pos Assignments Migration';
$string['datatype_migration_participation'] = 'User Participations Migration';
$string['deletefileconfirmation'] = 'Are you sure want to delete this file?';
$string['exportfiles'] = 'Export files';
$string['trackingstorage'] = 'Tracking Storage';
$string['file'] = 'File';
$string['database'] = 'Database';
$string['failed_write_file'] = 'Failed to write data in file "{$a}"';
$string['failed_remove_file'] = 'Failed to remove file "{$a}"';
$string['failed_zip_packing'] = 'An error was encountered while trying to zip file "{$a}"';
$string['required_data_format'] = 'Data format is required. Please configure admin setting page';

// BigBlueButton meetings.
$string['bbbapiendpoint'] = 'BBB API endpoint';
$string['bbbserversecret'] = 'BBB server secret';
$string['check_active_meetings'] = 'Check active meetings';
$string['bbbmeetings'] = 'BigBlueButton meetings';
$string['enablebbbmeetings'] = 'Enable monitoring of BigBlueButton meetings';
$string['enablebbbdebug'] = 'BigBlueButton debug mode';
$string['datatype_bbbattendees'] = 'BBB attendees';
$string['datatype_bbbmeetings'] = 'BBB meetings';
$string['datatype_bbbrecordings'] = 'BBB recordings';

// IB Next LTI.
$string['ltititle'] = 'IB Next LTI';
$string['lti_toggle_debug_data'] = 'Toggle Debug Data';
$string['lti_basiclti_endpoint'] = 'Lti Endpoint';
$string['lti_basiclti_parameters'] = 'Lti Parameters';
$string['ltitoolurl'] = 'Tool URL';
$string['lticonsumerkey'] = 'Key';
$string['ltisharedsecret'] = 'Secret';
$string['ltidebug'] = 'Debug mode';

$string['intellidata:viewlti'] = 'IntelliBoard LTI View';

// Tracking.
$string['compresstracking'] = 'Compress Tracking';
$string['compresstracking_desc'] = 'Write Time Tracking data to file or redis and transfer data to database with cron job, we are recommend to use Moodle Cache and Redis';
$string['do_not_use_compresstracking'] = 'Do not use Compress Tracking';
$string['cache_compresstracking'] = 'Save to Moodle Cache';
$string['file_compresstracking'] = 'Save to MoodleData';
$string['cachedef_tracking'] = 'IntelliData User Tracking data';
$string['tracklogs'] = 'Track Time by User - Daily';
$string['trackdetails'] = 'Track Time by User - Hourly';
$string['usertracking'] = 'User Tracking';

// SQL reports.
$string['sqlreports'] = 'SQL reports';
$string['sql_report'] = 'SQL report';
$string['sql_report_name'] = 'Report name';
$string['sql_report_status'] = 'Status';
$string['sql_report_date'] = 'Created On';
$string['sql_report_actions'] = 'Actions';
$string['sql_report_inactive'] = 'Deactivated';
$string['sql_report_active'] = 'Activated';
$string['sql_report_code'] = 'SQL';
$string['sql_report_remove_message'] = 'SQL report has been deleted';
$string['sql_report_delete_message'] = 'Delete SQL report?';
$string['sql_report_success_message'] = 'SQL report has been saved';

// Configuration.
$string['configuration'] = 'Configuration';
$string['intellidata:viewconfig'] = 'IntelliData View Configuration';
$string['intellidata:editconfig'] = 'IntelliData Edit Configuration';
$string['tabletype'] = 'Table Type';
$string['events_tracking'] = 'Events Tracking';
$string['timemodified_field'] = 'Timemodified Field';
$string['filterbyid'] = 'Filter by ID';
$string['rewritable'] = 'Rewritable';
$string['required'] = 'Required';
$string['optional'] = 'Optional';
$string['enabled'] = 'Enabled';
$string['disabled'] = 'Disabled';
$string['editconfigfor'] = 'Edit configuration for {$a}';
$string['wrongdatatype'] = 'Wrong Datatype';
$string['configurationsaved'] = 'Configuration Saved';
$string['resettodefault'] = 'Reset to Default';
$string['importconfig'] = 'Import Configuration';

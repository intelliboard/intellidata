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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 * @package    local_intellidata
 * @copyright  2022 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\services;

use local_intellidata\helpers\SettingsHelper;
use local_intellidata\repositories\export_log_repository;
use local_intellidata\repositories\logs_tables_repository;
use local_intellidata\services\dbschema_service;
use local_intellidata\services\export_service;
use local_intellidata\services\config_service;
use local_intellidata\persistent\datatypeconfig;

class datatypes_service {

    /**
     * @param $datatype
     * @return mixed|null
     */
    public static function init_migration($datatype, $forceformat = null, $init = true) {
        $migrationclass = '\\local_intellidata\\entities\\' . $datatype['migration'];

        if (class_exists($migrationclass)) {
            return new $migrationclass($datatype, $forceformat, $init);
        }

        return null;
    }

    /**
     * @param $entityname
     * @param $data
     * @return \local_intellidata\entities\custom\entity|mixed
     */
    public static function init_entity($datatype, $data) {

        $entityclass = self::get_datatype_entity_class(self::get_datatype_entity_path($datatype));

        return (class_exists($entityclass))
            ? new $entityclass($data)
            : new \local_intellidata\entities\custom\entity($datatype['name'], $data);
    }

    /**
     * @param $entity
     * @return string
     */
    public static function get_datatype_entity_class($entity = null) {
        return '\\local_intellidata\\entities\\' . $entity;
    }

    /**
     * @param $datatype
     * @return mixed|string
     */
    public static function get_datatype_entity_path($datatype) {
        return !empty($datatype['entity']) ? $datatype['entity'] : '';
    }

    /**
     * @return array|array[]
     */
    public static function get_datatypes($applyconfig = true) {

        // Get datatypes with default configuration.
        $defaultdatatypes = array_merge(
            self::get_required_datatypes(),
            self::get_optional_datatypes_for_export(),
            self::get_logs_datatypes()
        );

        // Apply configuration from database.
        if ($applyconfig) {
            $configservice = new config_service($defaultdatatypes);
            return $configservice->get_datatypes();
        } else {
            return $defaultdatatypes;
        }
    }

    /**
     * Get static datatypes.
     *
     * @return array|array[]
     */
    public static function get_static_datatypes($datatypes = []) {

        if (!count($datatypes)) {
            $datatypes = self::get_datatypes();
        }

        foreach ($datatypes as $key => $item) {
            if (empty($item['databaseexport'])) {
                unset($datatypes[$key]);
            }
        }

        return $datatypes;
    }

    /**
     * Get static datatypes.
     *
     * @return array|array[]
     */
    public static function get_events_datatypes($datatypes = []) {

        if (!count($datatypes)) {
            $datatypes = self::get_datatypes();
        }

        foreach ($datatypes as $key => $item) {
            if (!empty($item['databaseexport'])) {
                unset($datatypes[$key]);
            }
        }

        return $datatypes;
    }

    /**
     * @return array|array[]
     */
    public static function get_migrating_datatypes() {
        $datatypes = self::get_datatypes(false);

        foreach ($datatypes as $key => $item) {
            if (empty($item['migration'])) {
                unset($datatypes[$key]);
            }
        }

        return $datatypes;
    }

    /**
     * @return array|array[]
     */
    public static function get_all_datatypes() {

        $datatypes = array_merge(
            self::get_required_datatypes(),
            self::get_logs_datatypes(),
            self::get_all_optional_datatypes()
        );

        return $datatypes;
    }

    /**
     * @return array[]
     */
    public static function get_required_datatypes() {

        $datatypes = [
            'users' => [
                'name' => 'users',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'user',
                'migration' => 'users\migration',
                'entity' => 'users\user',
                'observer' => 'users\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'userlogins' => [
                'name' => 'userlogins',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'migration' => 'userlogins\migration',
                'entity' => 'userlogins\userlogin',
                'observer' => 'userlogins\observer',
                'rewritable' => false,
                'timemodified_field' => 'timecreated',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'categories' => [
                'name' => 'categories',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'course_categories',
                'migration' => 'categories\migration',
                'entity' => 'categories\category',
                'observer' => 'categories\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'courses' => [
                'name' => 'courses',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'course',
                'migration' => 'courses\migration',
                'entity' => 'courses\course',
                'observer' => 'courses\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'enrolments' => [
                'name' => 'enrolments',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'user_enrolments',
                'migration' => 'enrolments\migration',
                'entity' => 'enrolments\enrolment',
                'observer' => 'enrolments\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'roleassignments' => [
                'name' => 'roleassignments',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'role_assignments',
                'migration' => 'roles\ramigration',
                'entity' => 'roles\roleassignment',
                'observer' => 'roles\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'cohorts' => [
                'name' => 'cohorts',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'cohort',
                'migration' => 'cohorts\migration',
                'entity' => 'cohorts\cohort',
                'observer' => 'cohorts\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],

            'coursegroups' => [
                'name' => 'coursegroups',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'groups',
                'migration' => 'groups\migration',
                'entity' => 'groups\group',
                'observer' => 'groups\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'coursegroupmembers' => [
                'name' => 'coursegroupmembers',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'groups_members',
                'migration' => 'groupmembers\migration',
                'entity' => 'groupmembers\groupmember',
                'observer' => 'groupmembers\observer',
                'rewritable' => false,
                'timemodified_field' => 'timeadded',
                'filterbyid' => false,
                'databaseexport' => false
            ],

            'cohortmembers' => [
                'name' => 'cohortmembers',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'cohort_members',
                'migration' => 'cohortmembers\migration',
                'entity' => 'cohortmembers\cohortmember',
                'observer' => 'cohortmembers\observer',
                'rewritable' => false,
                'timemodified_field' => 'timeadded',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'coursecompletions' => [
                'name' => 'coursecompletions',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'migration' => 'coursecompletions\migration',
                'entity' => 'coursecompletions\coursecompletion',
                'observer' => 'coursecompletions\observer',
                'rewritable' => false,
                'timemodified_field' => false,
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'activities' => [
                'name' => 'activities',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'migration' => 'activities\migration',
                'entity' => 'activities\activity',
                'observer' => 'activities\observer',
                'rewritable' => false,
                'timemodified_field' => false,
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'activitycompletions' => [
                'name' => 'activitycompletions',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'course_modules_completion',
                'migration' => 'activitycompletions\migration',
                'entity' => 'activitycompletions\activitycompletion',
                'observer' => 'activitycompletions\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'usergrades' => [
                'name' => 'usergrades',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'grade_grades',
                'migration' => 'usergrades\migration',
                'entity' => 'usergrades\usergrade',
                'observer' => 'usergrades\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'gradeitems' => [
                'name' => 'gradeitems',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'grade_items',
                'migration' => 'gradeitems\migration',
                'entity' => 'gradeitems\gradeitem',
                'observer' => 'gradeitems\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => true
            ],
            'roles' => [
                'name' => 'roles',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'role',
                'migration' => 'roles\migration',
                'entity' => 'roles\role',
                'observer' => false,
                'rewritable' => true,
                'timemodified_field' => false,
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => true
            ],
            'modules' => [
                'name' => 'modules',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'modules',
                'migration' => 'modules\migration',
                'entity' => 'modules\module',
                'observer' => false,
                'rewritable' => true,
                'timemodified_field' => false,
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => true
            ],
            'forumdiscussions' => [
                'name' => 'forumdiscussions',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'forum_discussions',
                'migration' => 'forums\discussionsmigration',
                'entity' => 'forums\forumdiscussion',
                'observer' => 'forums\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'forumposts' => [
                'name' => 'forumposts',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'forum_posts',
                'migration' => 'forums\postsmigration',
                'entity' => 'forums\forumpost',
                'observer' => 'forums\observer',
                'rewritable' => false,
                'timemodified_field' => 'modified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'quizattempts' => [
                'name' => 'quizattempts',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'quiz_attempts',
                'migration' => 'quizzes\attemptsmigration',
                'entity' => 'quizzes\attempt',
                'observer' => 'quizzes\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'quizquestions' => [
                'name' => 'quizquestions',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'question',
                'migration' => 'quizquestions\migration',
                'entity' => 'quizquestions\quizquestion',
                'observer' => 'quizquestions\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => true
            ],
            'quizquestionrelations' => [
                'name' => 'quizquestionrelations',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'quiz_slots',
                'migration' => 'quizquestionrelations\migration',
                'entity' => 'quizquestionrelations\quizquestionrelation',
                'observer' => false,
                'rewritable' => false,
                'timemodified_field' => false,
                'filterbyid' => true,
                'databaseexport' => true,
                'exportids' => true
            ],
            'quizquestionattempts' => [
                'name' => 'quizquestionattempts',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'question_attempts',
                'migration' => 'quizquestionanswers\quamigration',
                'entity' => 'quizquestionanswers\quizquestionattempts',
                'observer' => false,
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => false
            ],
            'quizquestionattemptsteps' => [
                'name' => 'quizquestionattemptsteps',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'question_attempt_steps',
                'migration' => 'quizquestionanswers\qasmigration',
                'entity' => 'quizquestionanswers\quizquestionattemptsteps',
                'observer' => false,
                'rewritable' => false,
                'timemodified_field' => 'timecreated',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => false
            ],
            'quizquestionattemptstepsdata' => [
                'name' => 'quizquestionattemptstepsdata',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'question_attempt_step_data',
                'migration' => 'quizquestionanswers\qasdmigration',
                'entity' => 'quizquestionanswers\quizquestionattemptstepsdata',
                'observer' => false,
                'rewritable' => false,
                'timemodified_field' => false,
                'filterbyid' => true,
                'databaseexport' => true,
                'exportids' => false
            ],
            'assignmentsubmissions' => [
                'name' => 'assignmentsubmissions',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'assign_submission',
                'migration' => 'assignments\migration',
                'entity' => 'assignments\submission',
                'observer' => 'assignments\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'ltisubmittion' => [
                'name' => 'ltisubmittion',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'lti_submission',
                'migration' => 'lti\migration',
                'entity' => 'lti\submission',
                'rewritable' => false,
                'timemodified_field' => 'dateupdated',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => true
            ],
            'coursesections' => [
                'name' => 'coursesections',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'course_sections',
                'migration' => 'coursesections\migration',
                'entity' => 'coursesections\sections',
                'observer' => 'coursesections\observer',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'ltitypes' => [
                'name' => 'ltitypes',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'lti_types',
                'migration' => 'lti\ltitypesmigration',
                'entity' => 'lti\ltitypes',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => true
            ],
            'survey' => [
                'name' => 'survey',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'survey',
                'migration' => 'survey\surveymigration',
                'entity' => 'survey\survey',
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => true,
                'databaseexport' => true,
                'exportids' => true
            ],
            'surveyanswers' => [
                'name' => 'surveyanswers',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'survey_answers',
                'migration' => 'survey\surveyanswersmigration',
                'entity' => 'survey\surveyanswers',
                'rewritable' => false,
                'timemodified_field' => 'time',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => true
            ],
            'tracking' => [
                'name' => 'tracking',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'local_intellidata_tracking',
                'migration' => 'usertrackings\trackingsmigration',
                'entity' => 'usertrackings\tracking',
                'observer' => false,
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => false
            ],
            'trackinglog' => [
                'name' => 'trackinglog',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'local_intellidata_trlogs',
                'migration' => 'usertrackings\trackinglogsmigration',
                'entity' => 'usertrackings\trackinglog',
                'observer' => false,
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => false
            ],
            'trackinglogdetail' => [
                'name' => 'trackinglogdetail',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'local_intellidata_trdetails',
                'migration' => 'usertrackings\trackinglogdetailsmigration',
                'entity' => 'usertrackings\trackinglogdetail',
                'observer' => false,
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
                'filterbyid' => false,
                'databaseexport' => true,
                'exportids' => false
            ],
            'participation' => [
                'name' => 'participation',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'migration' => 'participations\migration',
                'entity' => 'participations\participation',
                'observer' => 'participations\observer',
                'rewritable' => false,
                'timemodified_field' => 'timecreated',
                'filterbyid' => false,
                'databaseexport' => false,
                'exportids' => false
            ],
            'userinfocategories' => [
                'name' => 'userinfocategories',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'migration' => 'userinfocategories\migration',
                'entity' => 'userinfocategories\userinfocategory',
                'observer' => 'userinfocategories\observer',
                'rewritable' => SettingsHelper::get_setting('eventstracking') ? false : true,
                'timemodified_field' => false,
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'userinfofields' => [
                'name' => 'userinfofields',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'migration' => 'userinfofields\migration',
                'entity' => 'userinfofields\userinfofield',
                'observer' => 'userinfofields\observer',
                'rewritable' => SettingsHelper::get_setting('eventstracking') ? false : true,
                'timemodified_field' => false,
                'filterbyid' => false,
                'databaseexport' => false
            ],
            'userinfodatas' => [
                'name' => 'userinfodatas',
                'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
                'table' => 'user_info_data',
                'migration' => 'userinfodatas\migration',
                'entity' => 'userinfodatas\userinfodata',
                'observer' => 'userinfodatas\observer',
                'rewritable' => false,
                'timemodified_field' => 'u.timemodified',
                'filterbyid' => false,
                'databaseexport' => false
            ]
        ];

        return self::format_required_datatypes($datatypes);
    }

    /**
     * @param $datatypename
     * @return array|array[]
     */
    public static function get_datatype($datatypename) {

        if (isset(self::get_required_datatypes()[$datatypename])) {
            return self::get_required_datatypes()[$datatypename];
        }

        $logsdatatypes = self::get_logs_datatypes();
        if (isset($logsdatatypes[$datatypename])) {
            return $logsdatatypes[$datatypename];
        }

        return self::format_optional_datatypes($datatypename);
    }

    /**
     * @return array
     */
    public static function get_all_optional_datatypes() {
        $dbschemaservice = new dbschema_service();
        $alltables = $dbschemaservice->get_optional_tableslist();

        $datatypes = [];

        if (count($alltables)) {
            foreach ($alltables as $table) {
                $datatypes[$table] = self::format_optional_datatypes($table);
            }
        }

        return $datatypes;
    }

    /**
     * @return array
     */
    public static function get_optional_datatypes_for_export() {
        $exportlogrepository = new export_log_repository();
        $customdatatypes = $exportlogrepository->get_optional_datatypes();

        $datatypes = [];

        if (count($customdatatypes)) {
            foreach ($customdatatypes as $datatype => $notneeded) {
                $datatypes[$datatype] = self::format_optional_datatypes($datatype);
            }
        }

        return $datatypes;
    }

    /**
     * Format required datatypes.
     *
     * @return array
     */
    private static function format_required_datatypes($datatypes) {

        if (count($datatypes)) {
            $params = [
                'eventstracking' => SettingsHelper::get_setting('eventstracking')
            ];

            foreach ($datatypes as $table => $datatype) {
                $datatype = self::format_required_datatype($datatype, $params);
                $datatypes[$table] = self::change_parameters_by_version($datatype);
            }
        }

        return $datatypes;
    }

    /**
     * Data type parameters changed by version.
     * @param array $datatype
     * @return array
     */
    private static function change_parameters_by_version($datatype) {
        $entityclass = self::get_datatype_entity_class(self::get_datatype_entity_path($datatype));
        if (class_exists($entityclass)) {
            $datatype = $entityclass::change_parameters_by_version($datatype);
        }

        return $datatype;
    }

    /**
     * Format required datatype.
     *
     * @return array
     */
    private static function format_required_datatype($datatype, $params) {
        // Switch events tracking to static export.
        if (!empty(empty($params['eventstracking']))) {

            $datatype['databaseexport'] = (!empty($datatype['timemodified_field']) || !empty($datatype['rewritable']))
                ? true : $datatype['databaseexport'];

            if (!isset($datatype['exportids'])) {
                $datatype['exportids'] = (!empty($datatype['timemodified_field']))
                    ? true : $datatype['databaseexport'];
            }
        }

        return $datatype;
    }

    /**
     * Format optional datatype.
     *
     * @return array
     */
    private static function format_optional_datatypes($datatype) {

        $timemodifiedfield = config_service::get_timemodified_field($datatype);
        $filterbyidconf = (empty($timemodifiedfield) && config_service::get_filterbyid_config($datatype)) ? true : false;
        $rewritableconf = ((!$timemodifiedfield && !$filterbyidconf) || config_service::get_rewritable_config($datatype))
            ? true : false;

        $data = [
            'name' => $datatype,
            'tabletype' => datatypeconfig::TABLETYPE_OPTIONAL,
            'table' => $datatype,
            'migration' => false,
            'entity' => false,
            'observer' => false,
            'timemodified_field' => $timemodifiedfield,
            'filterbyid' => $filterbyidconf,
            'rewritable' => $rewritableconf,
            'databaseexport' => true,
            'exportids' => true
        ];

        return $data;
    }

    /**
     * @return array
     */
    public static function get_logs_datatypes() {

        $exportlogrepository = new export_log_repository();
        $logsdatatypes = $exportlogrepository->get_logs_datatypes();

        $datatypes = [];

        if (count($logsdatatypes)) {
            foreach ($logsdatatypes as $datatype => $noneeded) {
                $datatypes[$datatype] = self::format_logs_datatypes($datatype);
            }
        }

        return $datatypes;
    }


    /**
     * Format datatype details for logs table.
     *
     * @return array
     */
    private static function format_logs_datatypes($datatype) {

        $data = [
            'name' => $datatype,
            'tabletype' => datatypeconfig::TABLETYPE_LOGS,
            'migration' => 'logs\migration',
            'entity' => 'logs\log',
            'observer' => 'logs\observer',
            'timemodified_field' => logs_tables_repository::TIMEMODIFIED_FIELD,
            'filterbyid' => false,
            'rewritable' => false,
            'databaseexport' => false
        ];

        return $data;
    }

    /**
     * Filter datatypes.
     *
     * @param $datatypes
     * @param $filter
     * @return array|array[]|mixed
     */
    public static function filter_datatypes($datatypes, $filter) {

        if ($filter == datatypeconfig::TABLETYPE_STATIC) {
            return self::get_static_datatypes($datatypes);
        } else if ($filter == datatypeconfig::TABLETYPE_EVENTS) {
            return self::get_events_datatypes($datatypes);
        }

        return $datatypes;
    }
}

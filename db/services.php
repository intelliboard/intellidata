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
 * Web service mod_brprojects external functions and service definitions.
 *
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.

defined('MOODLE_INTERNAL') || die;

$functions = [
    'local_intellidata_validate_credentials' => [
        'classname'     => 'local_intellidata_exportlib',
        'methodname'    => 'validate_credentials',
        'classpath'     => 'local/intellidata/classes/api/exportlib.php',
        'description'   => 'Validate plugin credentials',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'local_intellidata_export_data' => [
        'classname'     => 'local_intellidata_exportlib',
        'methodname'    => 'export_data',
        'classpath'     => 'local/intellidata/classes/api/exportlib.php',
        'description'   => 'Export Data',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'local_intellidata_get_data_files' => [
        'classname'     => 'local_intellidata_exportlib',
        'methodname'    => 'get_data_files',
        'classpath'     => 'local/intellidata/classes/api/exportlib.php',
        'description'   => 'Get Data Files',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'local_intelldata_save_tracking' => [
        'classname'     => 'local_intellidata_trackinglib',
        'methodname'    => 'save_tracking',
        'classpath'     => 'local/intellidata/classes/api/trackinglib.php',
        'description'   => 'Save Tracking',
        'type'          => 'write',
        'ajax'          => true,
    ],
    'local_intellidata_get_live_data' => [
        'classname'     => 'local_intellidata_exportlib',
        'methodname'    => 'get_live_data',
        'classpath'     => 'local/intellidata/classes/api/exportlib.php',
        'description'   => 'Get some data in real time',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'local_intellidata_get_bbcollsessions_data' => [
        'classname'     => 'local_intellidata_exportlib',
        'methodname'    => 'get_bbcollsessions',
        'classpath'     => 'local/intellidata/classes/api/exportlib.php',
        'description'   => 'Get get Blackboard Collaborate Sessions relations to Course',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'local_intellidata_course_get_categories' => [
        'classname' => 'core_course_external',
        'methodname' => 'get_categories',
        'classpath' => 'course/externallib.php',
        'description' => 'Return category details',
        'type' => 'read',
        'capabilities' => 'moodle/category:viewhiddencategories',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_intellidata_course_get_courses' => [
        'classname' => 'core_course_external',
        'methodname' => 'get_courses',
        'classpath' => 'course/externallib.php',
        'description' => 'Return course details',
        'type' => 'read',
        'capabilities' => 'moodle/course:view, moodle/course:update, moodle/course:viewhiddencourses',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_intellidata_course_get_courses_by_field' => [
        'classname' => 'core_course_external',
        'methodname' => 'get_courses_by_field',
        'classpath' => 'course/externallib.php',
        'description' => 'Get courses matching a specific field (id/s, shortname, idnumber, category)',
        'type' => 'read',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_intellidata_role_assign_roles' => [
        'classname' => 'core_role_external',
        'methodname' => 'assign_roles',
        'classpath' => 'enrol/externallib.php',
        'description' => 'Manual role assignments.',
        'type' => 'write',
        'capabilities' => 'moodle/role:assign'
    ],
    'local_intellidata_user_get_users_by_field' => [
        'classname' => 'core_user_external',
        'methodname' => 'get_users_by_field',
        'classpath' => 'user/externallib.php',
        'description' => 'Retrieve users\' information for a specified unique field - If you want to do a user search, use '
            . 'core_user_get_users()',
        'type' => 'read',
        'capabilities' => 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update',
        'ajax' => true,
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'local_intellidata_enrol_users' => [
        'classname'   => 'enrol_manual_external',
        'methodname'  => 'enrol_users',
        'classpath'   => 'enrol/manual/externallib.php',
        'description' => 'Manual enrol users',
        'capabilities' => 'enrol/manual:enrol',
        'type'        => 'write',
    ],
    'local_intellidata_unenrol_users' => [
        'classname'   => 'enrol_manual_external',
        'methodname'  => 'unenrol_users',
        'classpath'   => 'enrol/manual/externallib.php',
        'description' => 'Manual unenrol users',
        'capabilities' => 'enrol/manual:unenrol',
        'type'        => 'write',
    ],
    'local_intellidata_user_get_users' => [
        'classname' => 'core_user_external',
        'methodname' => 'get_users',
        'classpath' => 'user/externallib.php',
        'description' => 'search for users matching the parameters',
        'type' => 'read',
        'capabilities' => 'moodle/user:viewdetails, moodle/user:viewhiddendetails, moodle/course:useremail, moodle/user:update'
    ],
    'local_intellidata_get_users_roles' => [
        'classname'     => 'local_intellidata_accesslib',
        'methodname'    => 'get_users_roles',
        'classpath'     => 'local/intellidata/classes/api/accesslib.php',
        'description'   => 'Get Users Roles',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => false,
    ],
    'local_intellidata_get_roles_list' => [
        'classname' => 'local_intellidata_accesslib',
        'methodname' => 'get_roles_list',
        'classpath' => 'local/intellidata/classes/api/accesslib.php',
        'description' => 'Get Roles List',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => false,
    ],
    'local_intellidata_run_report' => [
        'classname'   => 'local_intellidata_sqlreportlib',
        'methodname'  => 'run_report',
        'classpath'   => 'local/intellidata/classes/api/sqlreportlib.php',
        'description' => 'Run Supernova SQL report',
        'type'        => 'read',
    ],
    'local_intellidata_save_report' => [
        'classname'   => 'local_intellidata_sqlreportlib',
        'methodname'  => 'save_report',
        'classpath'   => 'local/intellidata/classes/api/sqlreportlib.php',
        'description' => 'Save Supernova SQL report',
        'type'        => 'write',
    ],
    'local_intellidata_delete_report' => [
        'classname'   => 'local_intellidata_sqlreportlib',
        'methodname'  => 'delete_report',
        'classpath'   => 'local/intellidata/classes/api/sqlreportlib.php',
        'description' => 'Delete Supernova SQL report',
        'type'        => 'write',
    ],
    'local_intellidata_get_dbschema_custom' => [
        'classname'   => 'local_intellidata_dbschemalib',
        'methodname'  => 'get_dbschema_custom',
        'classpath'   => 'local/intellidata/classes/api/dbschemalib.php',
        'description' => 'Get Moodle DB Schema for Custom Tables',
        'type'        => 'read',
    ],
    'local_intellidata_get_dbschema_unified' => [
        'classname'   => 'local_intellidata_dbschemalib',
        'methodname'  => 'get_dbschema_unified',
        'classpath'   => 'local/intellidata/classes/api/dbschemalib.php',
        'description' => 'Get Moodle DB Schema for Unified Tables',
        'type'        => 'read',
    ],
    'local_intellidata_set_datatype' => [
        'classname'     => 'local_intellidata_exportlib',
        'methodname'    => 'set_datatype',
        'classpath'     => 'local/intellidata/classes/api/exportlib.php',
        'description'   => 'Set new or update existing datatype in Moodle.',
        'type'          => 'write',
    ],
];

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = [
    'IntelliData Service' => [
        'functions' => [
            'local_intellidata_validate_credentials',
            'local_intellidata_export_data',
            'local_intellidata_get_data_files',
            'local_intelldata_save_tracking',
            'local_intellidata_get_live_data',
            'local_intellidata_get_bbcollsessions_data',
            'local_intellidata_course_get_categories',
            'local_intellidata_course_get_courses',
            'local_intellidata_course_get_courses_by_field',
            'local_intellidata_role_assign_roles',
            'local_intellidata_user_get_users_by_field',
            'local_intellidata_enrol_users',
            'local_intellidata_unenrol_users',
            'local_intellidata_user_get_users',
            'local_intellidata_get_users_roles',
            'local_intellidata_get_roles_list',
            'local_intellidata_run_report',
            'local_intellidata_save_report',
            'local_intellidata_delete_report',
            'local_intellidata_get_dbschema_custom',
            'local_intellidata_get_dbschema_unified',
            'local_intellidata_set_datatype',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'downloadfiles' => 1
    ]
];

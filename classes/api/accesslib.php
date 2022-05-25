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


use local_intellidata\api\apilib;

defined('MOODLE_INTERNAL') || die();


/**
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_intellidata_accesslib extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function get_users_roles_parameters() {
        return new external_function_parameters(
            array(
                'data' => new external_single_structure(
                    array(
                        'courseid' => new external_value(PARAM_INT, 'id of course', VALUE_REQUIRED, 0),
                        'userid' => new external_value(PARAM_INT, 'id of user'),
                        'checkparentcontexts' => new external_value(PARAM_BOOL, 'if check parent contexts', VALUE_REQUIRED, true),
                    )
                )
            )
        );
    }

    /**
     * @param $data
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_users_roles($data) {
        global $CFG, $DB;
        require_once($CFG->libdir . "/adminlib.php");

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);

        $params = self::validate_parameters(
            self::get_users_roles_parameters(), [
                'data' => $data,
            ]
        );

        if ($params['data']['checkparentcontexts']) {
            $contextids = $context->get_parent_context_ids();
        } else {
            $contextids = array();
        }

        $contextids[] = $context->id;

        list($contextids, $contextparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED, 'con');

        $userid = $params['data']['userid'];
        $courseid = $params['data']['courseid'];

        if ($courseid > 1) {
            $where = 'AND c.contextlevel = ' . CONTEXT_COURSE . ' AND instanceid = ' . $courseid;
        } else {
            $where = 'AND c.contextlevel = ' . CONTEXT_SYSTEM;
        }

        $sql = "SELECT r.id, r.shortname, r.name
                  FROM {role_assignments} ra, {role} r, {context} c
                 WHERE ra.userid = $userid
                   AND ra.roleid = r.id
                   AND ra.contextid = c.id
                   AND ra.contextid $contextids $where";

        $data = $DB->get_records_sql($sql, $contextparams);

        foreach ($data as $item) {
            if (!$item->name) {
                $item->name = role_get_name($item, $context);
            }
        }

        if (is_siteadmin($userid)) {
            $data[0] = (object)[
                'id' => '0',
                'name' => 'Site Admin',
                'shortname' => 'siteadmin'
            ];
        }

        return [
            'data' => json_encode($data),
            'status' => apilib::STATUS_SUCCESS
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_users_roles_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'Response data'),
                'status' => new external_value(PARAM_TEXT, 'Response status'),
            )
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function get_roles_list_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_roles_list() {
        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);

        $roles = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINALANDSHORT);

        foreach ($roles as &$role) {
            $role = (object)array_intersect_key((array)$role, array_flip(['id', 'name', 'shortname', 'sortorder']));

            if (!$role->name) {
                $role->name = role_get_name($role, $context);
            }
        }

        return [
            'data' => json_encode($roles),
            'status' => apilib::STATUS_SUCCESS
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_roles_list_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'Response data'),
                'status' => new external_value(PARAM_TEXT, 'Response status'),
            )
        );
    }

}

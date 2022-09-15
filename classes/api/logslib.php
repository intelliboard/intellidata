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
use local_intellidata\services\encryption_service;
use local_intellidata\helpers\TasksHelper;
use local_intellidata\repositories\export_log_repository;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * @package    local_intellidata
 * @copyright  2022 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_intellidata_logslib extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function get_tasks_logs_parameters() {
        return new external_function_parameters([
            'data'   => new external_value(PARAM_RAW, 'Request params'),
        ]);
    }

    /**
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_tasks_logs($data) {

        try {
            apilib::check_auth();
        } catch (\moodle_exception $e) {
            return [
                'data' => $e->getMessage(),
                'status' => apilib::STATUS_ERROR
            ];
        }

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);

        $params = self::validate_parameters(
            self::get_tasks_logs_parameters(), [
                'data' => $data,
            ]
        );

        // Validate parameters.
        $params = apilib::validate_parameters($params['data'], [
            'timestart' => PARAM_INT,
            'timeend' => PARAM_INT,
            'taskname' => PARAM_TEXT,
        ]);

        $logs = TasksHelper::get_logs($params);
        $encryptionservice = new encryption_service();

        return [
            'data' => $encryptionservice->encrypt(
                json_encode($logs)
            ),
            'status' => apilib::STATUS_SUCCESS
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_tasks_logs_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'Encrypted Logs'),
                'status' => new external_value(PARAM_TEXT, 'Response status'),
            )
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function get_export_logs_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_export_logs() {

        try {
            apilib::check_auth();
        } catch (\moodle_exception $e) {
            return [
                'data' => $e->getMessage(),
                'status' => apilib::STATUS_ERROR
            ];
        }

        // Ensure the current user is allowed to run this function.
        $context = context_system::instance();
        self::validate_context($context);

        $encryptionservice = new encryption_service();
        $exportlogrepository = new export_log_repository();

        return [
            'data' => $encryptionservice->encrypt(json_encode($exportlogrepository->get_export_logs())),
            'status' => apilib::STATUS_SUCCESS
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_export_logs_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'Encrypted Logs'),
                'status' => new external_value(PARAM_TEXT, 'Response status'),
            )
        );
    }

}

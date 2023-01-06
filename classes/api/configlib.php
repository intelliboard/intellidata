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
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\ParamsHelper;
use local_intellidata\helpers\TasksHelper;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * @package    local_intellidata
 * @copyright  2022 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_intellidata_configlib extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function get_plugin_config_parameters() {
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
    public static function get_plugin_config($data) {

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
            self::get_plugin_config_parameters(), [
                'data' => $data,
            ]
        );

        $config = [
            'moodleconfig' => ParamsHelper::get_moodle_config(),
            'pluginversion' => ParamsHelper::get_plugin_version(),
            'pluginconfig' => SettingsHelper::get_plugin_settings(),
            'cronconfig' => TasksHelper::get_tasks_config()
        ];

        $encryptionservice = new encryption_service();

        return [
            'data' => $encryptionservice->encrypt(json_encode($config)),
            'status' => apilib::STATUS_SUCCESS
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_plugin_config_returns() {
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
    public static function set_plugin_config_parameters() {
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
    public static function set_plugin_config($data) {

        $status = apilib::STATUS_ERROR;
        $message = 'error';

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
            self::set_plugin_config_parameters(), [
                'data' => $data
            ]
        );

        // Validate parameters.
        $params = apilib::validate_parameters($params['data'], [
            'name' => PARAM_TEXT,
            'value' => PARAM_TEXT
        ]);

        if (SettingsHelper::is_setting_updatable($params['name'])) {
            SettingsHelper::set_setting($params['name'], $params['value']);

            $status = apilib::STATUS_SUCCESS;
            $message = 'updated';
        }

        $encryptionservice = new encryption_service();

        return [
            'data' => $encryptionservice->encrypt($message),
            'status' => $status
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function set_plugin_config_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'Encrypted Logs'),
                'status' => new external_value(PARAM_TEXT, 'Response status'),
            )
        );
    }

}

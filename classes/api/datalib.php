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
 *
 * @package    local_intellidata
 * @copyright  2022 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    http://intelliboard.net/
 */

use local_intellidata\api\apilib;
use local_intellidata\services\encryption_service;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\event\local_intellidata_sql_request;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 * @package    local_intellidata
 * @copyright  2022 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    http://intelliboard.net/
 */
class local_intellidata_datalib extends external_api {

    /**
     * Set LTI role parameters.
     *
     * @return external_function_parameters
     */
    public static function set_lti_role_parameters() {
        return new external_function_parameters([
            'data' => new external_value(PARAM_RAW, 'Request params'),
        ]);
    }

    /**
     * Set LTI role parameters.
     *
     * @param $data
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function set_lti_role($data) {
        global $DB;

        try {
            apilib::check_auth();
        } catch (\moodle_exception $e) {
            return [
                'data' => $e->getMessage(),
                'status' => apilib::STATUS_ERROR,
            ];
        }

        $context = context_system::instance();
        self::validate_context($context);

        $params = self::validate_parameters(self::set_lti_role_parameters(), ['data' => $data]);

        // Validate if credentials not empty.
        $encryptionservice = new encryption_service();

        if (!$encryptionservice->validate_credentials()) {
            return [
                'data' => 'emptycredentials',
                'status' => apilib::STATUS_ERROR,
            ];
        }

        // Validate parameters.
        $params = apilib::validate_parameters($params['data'], [
            'ids' => PARAM_RAW,
            'roles' => PARAM_RAW,
        ]);

        if (!\local_intellidata\services\lti_service::get_lti_role()) {
            return [
                'data' => $encryptionservice->encrypt('LTI role not configured.'),
                'status' => apilib::STATUS_SUCCESS,
            ];
        }

        $setltiroletask = new \local_intellidata\task\set_lti_role_adhoc_task();
        $setltiroletask->set_custom_data($params);
        \core\task\manager::queue_adhoc_task($setltiroletask);

        $encryptionservice = new encryption_service();

        return [
            'data' => $encryptionservice->encrypt('Lti role successfully assigned.'),
            'status' => apilib::STATUS_SUCCESS,
        ];
    }

    /**
     * Set LTI role returns.
     *
     * @return external_single_structure
     */
    public static function set_lti_role_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Response status.'),
            'data' => new external_value(PARAM_TEXT, 'Response message.'),
        ]);
    }

}

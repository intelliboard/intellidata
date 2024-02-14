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
 * @website    http://intelliboard.net/
 */

use local_intellidata\api\apilib;
use local_intellidata\services\encryption_service;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\event\local_intellidata_sql_request;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class local_intellidata_datalib extends external_api {

    /**
     * Parameters for get_data() method.
     *
     * @return external_function_parameters
     */
    public static function get_data_parameters() {
        return new external_function_parameters([
            'data' => new external_value(PARAM_RAW, 'Request params'),
        ]);
    }

    /**
     * Run sql report in Moodle.
     *
     * @param $data
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_data($data) {
        global $DB, $CFG;

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

        $params = self::validate_parameters(
            self::get_data_parameters(),
            ['data' => $data]
        );

        // Validate parameters.
        $params = (object) apilib::validate_parameters($params['data'], [
            'sqlcode' => PARAM_TEXT,
            'sqlparams' => PARAM_TEXT,
            'debug' => PARAM_INT,
            'start' => PARAM_INT,
            'length' => PARAM_INT,
        ]);

        if (!SettingsHelper::get_setting('datavalidationenabled')) {
            return [
                'data' => 'directsqldisabled',
                'status' => apilib::STATUS_ERROR,
            ];
        }

        if (empty($params->sqlcode)) {
            return [
                'data' => 'emptysqlcode',
                'status' => apilib::STATUS_ERROR,
            ];
        }

        // Enable debugging.
        if ($params->debug === 1) {
            $CFG->debug = (E_ALL | E_STRICT);
            $CFG->debugdisplay = 1;
        }

        $start = $length = 0;
        if (isset($params->start) && !empty($params->length) && $params->length != -1) {
            $start = $params->start;
            $length = $params->length;
        }

        if ($params->debug === 2) {
            $data = [$params->sqlcode, $params->sqlparams];
        } else {

            // Log the web service request.
            $event = local_intellidata_sql_request::create(['other' => (array)$params]);
            $event->trigger();

            try {
                $data = $DB->get_records_sql($params->sqlcode, $params->sqlparams, $start, $length);
            } catch (\Exception $e) {
                return [
                    'data' => $e->getMessage(),
                    'status' => apilib::STATUS_ERROR,
                ];
            }
        }

        $encryptionservice = new encryption_service();

        return [
            'status' => apilib::STATUS_SUCCESS,
            'data' => $encryptionservice->encrypt(json_encode($data)),
        ];
    }

    /**
     * Return for get_data() method.
     *
     * @return external_single_structure
     */
    public static function get_data_returns() {
        return new external_single_structure([
            'status' => new external_value(PARAM_TEXT, 'Response status'),
            'data' => new external_value(PARAM_RAW, 'Report data'),
        ]);
    }
}

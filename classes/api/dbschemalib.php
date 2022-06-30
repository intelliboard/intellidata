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
use local_intellidata\services\dbschema_service;
use local_intellidata\services\config_service;
use local_intellidata\services\datatypes_service;
use local_intellidata\repositories\required_tables_repository;
use local_intellidata\repositories\logs_tables_repository;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_intellidata_dbschemalib extends external_api {

    /**
     * @return external_function_parameters
     */
    public static function get_dbschema_custom_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_dbschema_custom() {

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
            self::get_dbschema_custom_parameters(), []
        );

        // Update config in database.
        $configservice = new config_service(datatypes_service::get_all_datatypes());
        $configservice->setup_config(false);

        $encryptionservice = new encryption_service();
        $dbschemaservice = new dbschema_service();

        return [
            'data' => $encryptionservice->encrypt(json_encode($dbschemaservice->export())),
            'status' => apilib::STATUS_SUCCESS
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_dbschema_custom_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'Encrypted DB Schema'),
                'status' => new external_value(PARAM_TEXT, 'Response status'),
            )
        );
    }

    /**
     * @return external_function_parameters
     */
    public static function get_dbschema_unified_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws restricted_context_exception
     */
    public static function get_dbschema_unified() {

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
            self::get_dbschema_unified_parameters(), []
        );

        $encryptionservice = new encryption_service();
        $reqrepository = new required_tables_repository();
        $ltrepository = new logs_tables_repository();

        $tables = $reqrepository->get_tables_fields();
        $logstables = $ltrepository->get_tables_fields();

        if (count($logstables)) {
            $tables = array_merge($tables, $logstables);
        }

        return [
            'data' => $encryptionservice->encrypt(json_encode($tables)),
            'status' => apilib::STATUS_SUCCESS
        ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_dbschema_unified_returns() {
        return new external_single_structure(
            array(
                'data' => new external_value(PARAM_TEXT, 'Encrypted DB Schema'),
                'status' => new external_value(PARAM_TEXT, 'Response status'),
            )
        );
    }



}

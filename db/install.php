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
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

use local_intellidata\helpers\SettingsHelper;
use local_intellidata\repositories\export_id_repository;
use local_intellidata\services\config_service;
use local_intellidata\services\datatypes_service;
use local_intellidata\services\intelliboard_service;
use local_intellidata\helpers\DBHelper;
use local_intellidata\helpers\DebugHelper;

function xmldb_local_intellidata_install() {

    // Set exportformat for csv.
    set_config('exportdataformat', 'csv', 'local_intellidata');

    // Setup config in database.
    $configservice = new config_service(datatypes_service::get_all_datatypes());
    $configservice->setup_config();

    // Setup database triggers.
    $datatypes = datatypes_service::get_datatypes();
    try {
        foreach ($datatypes as $datatype) {
            if (isset($datatype['table'])) {
                DBHelper::create_deleted_id_triger($datatype['name'], $datatype['table']);
            }
        }
        SettingsHelper::set_setting('trackingidsmode', export_id_repository::TRACK_IDS_MODE_TRIGGER);
    } catch (moodle_exception $e) {
        SettingsHelper::set_setting('trackingidsmode', export_id_repository::TRACK_IDS_MODE_REQUEST);
        DebugHelper::error_log($e->getMessage());
    }

    // Send IB prospects.
    try {
        $ibservice = new intelliboard_service();
        $ibservice->set_params_for_install();
        $ibservice->send_request();
    } catch (moodle_exception $e) {
        DebugHelper::error_log($e->getMessage());
    }
}

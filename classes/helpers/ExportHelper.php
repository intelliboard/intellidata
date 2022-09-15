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
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\helpers;

use local_intellidata\repositories\export_log_repository;
use local_intellidata\services\database_service;
use local_intellidata\services\encryption_service;
use local_intellidata\services\export_service;

class ExportHelper {

    /**
     * Process export files.
     *
     * @param export_service $exportservice
     * @param array $params
     * @return array
     */
    public static function process_export(export_service $exportservice, $params = []) {

        $services = [
            'encryptionservice' => new encryption_service(),
            'exportservice' => $exportservice,
            'exportlogrepository' => new export_log_repository()
        ];

        // Export static tables.
        $databaseservice = new database_service(true, $services);
        $databaseservice->export_tables($params);

        // Export files to moodledata.
        $exportservice->save_files();

        // Export migration files to moodledata.
        $exportservice->set_migration_mode();
        $exportservice->save_files();

        // Set last export date.
        SettingsHelper::set_lastexportdate();

        return $exportservice->get_files();
    }
}

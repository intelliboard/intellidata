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
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\tests;

class setup_helper {

    /**
     * Enable intellidata plugin.
     */
    public static function enable_plugin() {
        set_config('enabled', true, 'local_intellidata');
    }

    /**
     * Enable db storage.
     */
    public static function enable_db_storage() {
        set_config('trackingstorage', 1, 'local_intellidata');
    }

    /**
     * Enable files storage.
     */
    public static function enable_file_storage() {
        set_config('trackingstorage', 0, 'local_intellidata');
    }

    /**
     * Set json export format.
     */
    public static function setup_json_exportformat() {
        set_config('exportdataformat', 'json', 'local_intellidata');
    }

    /**
     * Set csv export format.
     */
    public static function setup_csv_exportformat() {
        set_config('exportdataformat', 'csv', 'local_intellidata');
    }
}

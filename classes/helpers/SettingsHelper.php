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

use local_intellidata\constants;
use local_intellidata\repositories\tracking\tracking_repository;

class SettingsHelper {

    const DEFAULT_VALUES = [
        // General settings.
        'enabled' => 1,
        'trackingstorage' => 0,
        'encryptionkey' => '',
        'clientidentifier' => '',
        'cleaner_duration' => DAYSECS * 14,
        'migrationrecordslimit' => '1000000',
        'migrationwriterecordslimit' => '10000',
        'exportfilesduringmigration' => 0,
        'resetmigrationprogress' => 0,
        'debugenabled' => 0,
        'exportdataformat' => 'csv',
        'defaultlayout' => 'standard',
        // User Tracking.
        'enabledtracking' => 1,
        'compresstracking' => tracking_repository::TYPE_CACHE,
        'tracklogs' => 1,
        'trackdetails' => 1,
        'inactivity' => '60',
        'ajaxfrequency' => '30',
        'trackadmin' => 0,
        'trackmedia' => 0,
        // BBB meetings.
        'enablebbbmeetings' => 0,
        'enablebbbdebug' => 0,
        'bbbapiendpoint' => '',
        'bbbserversecret' => '',
        // IB Next LTI.
        'ltitoolurl' => '',
        'lticonsumerkey' => '',
        'ltisharedsecret' => '',
        'ltititle' => '',
        'custommenuitem' => 0,
        'debug' => 0,
        // Internal settings.
        'lastmigrationdate' => 0,
        'resetmigrationprogress' => 0,
        'migrationstart' => 0,
        'migrationdatatype' => ''
    ];

    /**
     * Get config for export format.
     *
     * @param $datatype
     * @return database_storage_repository|file_storage_repository
     * @throws \dml_exception
     */
    public static function get_export_dataformat() {
        return self::get_setting('exportdataformat');
    }

    /**
     * Get default value for config.
     *
     * @param $configname
     * @return mixed|string
     */
    public static function get_defaut_config_value($configname) {
        return isset(self::DEFAULT_VALUES[$configname]) ? self::DEFAULT_VALUES[$configname] : '';
    }

    /**
     * Get config value.
     *
     * @param $configname
     * @return false|mixed|object|string
     * @throws \dml_exception
     */
    public static function get_setting($configname) {
        $config = get_config(constants::PLUGIN, $configname);

        // Config did not set or doesn't exist.
        if ($config === null || $config === false) {
            return self::get_defaut_config_value($configname);
        }

        return $config;
    }

    /**
     * @return false|\lang_string|mixed|object|string
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function get_lti_title() {
        if ($config = self::get_setting('ltititle')) {
            return $config;
        }

        return get_string('ltimenutitle', constants::PLUGIN);
    }

    /**
     * @return string[]
     */
    public static function get_layouts_options() {
        global $PAGE;

        $options = ['standard' => 'standard'];

        if (!empty($PAGE->theme->layouts)) {
            foreach (array_keys($PAGE->theme->layouts) as $layout) {
                $options[$layout] = $layout;
            }
        }

        return $options;
    }

    /**
     * @return string
     * @throws \dml_exception
     */
    public static function get_page_layout() {

        $defaultlayout = self::get_setting('defaultlayout');

        $layoutoptions = self::get_layouts_options();

        if (isset($layoutoptions[$defaultlayout])) {
            return $layoutoptions[$defaultlayout];
        }

        return 'standard';
    }
}

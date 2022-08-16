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

use local_intellidata\services\encryption_service;

class MigrationHelper {
    const MIGRATIONS_COMPLETED_STATUS = 'migrationcompleted';

    /**
     * @var string[]
     */
    private static $tasks = [
        '\local_intellidata\task\cleaner_task',
        '\local_intellidata\task\export_task',
        '\local_intellidata\task\migration_task',
        '\local_intellidata\task\track_bbb_meetings'
    ];

    /**
     * @param $tables
     * @param string $processingtable
     * @return int|string
     */
    public static function get_next_table($tables, $processingtable = '') {

        $nexttable = self::MIGRATIONS_COMPLETED_STATUS;
        $currenttable = false;

        foreach ($tables as $key => $datatype) {
            if ($currenttable) {
                $nexttable = $key;
                break;
            }
            if ($key == $processingtable) {
                $currenttable = true;
            }
        }

        return $nexttable;
    }

    /**
     * @param $migrationdatatype
     * @param int $migrationstart
     */
    public static function set_next_migration_params($migrationdatatype, $migrationstart = 0) {
        set_config('migrationdatatype', $migrationdatatype, 'local_intellidata');
        set_config('migrationstart', $migrationstart, 'local_intellidata');
    }

    /**
     * @param array $exclude
     */
    public static function disable_sheduled_tasks($exclude = []) {
        foreach (self::$tasks as $task) {
            if (!in_array($task, $exclude)) {
                self::set_disabled_sheduled_task($task, true);
            }
        }
    }

    /**
     * @param array $exclude
     */
    public static function enable_sheduled_tasks($exclude = []) {
        foreach (self::$tasks as $task) {
            if (!in_array($task, $exclude)) {
                self::set_disabled_sheduled_task($task, false);
            }
        }
    }

    /**
     * Send callback to IBN when export completed.
     *
     * @throws \dml_exception
     */
    public static function send_callback() {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $migrationcallbackurl = SettingsHelper::get_setting('migrationcallbackurl');

        // Send callback when files ready.
        if (!empty($migrationcallbackurl)) {
            $encryptionservice = new encryption_service();
            $client = new \curl();

            $client->post($migrationcallbackurl, [
                'data' => $encryptionservice->encrypt(json_encode(['exporttime' => time()]))
            ]);
        }
    }

    /**
     * @param string $classname
     * @param bool $status
     * @return bool
     * @throws \dml_exception
     */
    private static function set_disabled_sheduled_task(string $classname, bool $status) {
        global $DB;

        $taskrecord = $DB->get_record('task_scheduled', array('classname' => $classname));
        $task = \core\task\manager::scheduled_task_from_record($taskrecord);
        $task->set_disabled($status);
        return \core\task\manager::configure_scheduled_task($task);
    }
}

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
 * @see    http://intelliboard.net/
 */

namespace local_intellidata\helpers;

use local_intellidata\repositories\export_log_repository;
use local_intellidata\services\datatypes_service;
use local_intellidata\services\encryption_service;
use local_intellidata\services\export_service;

/**
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    http://intelliboard.net/
 */
class MigrationHelper {
    /**
     * Migrations completed status.
     */
    const MIGRATIONS_COMPLETED_STATUS = 'migrationcompleted';

    /**
     * @var array
     */
    private static $tasks = [
        '\local_intellidata\task\cleaner_task',
        TasksHelper::TASK_CLASS_EXPORT_DATA,
        TasksHelper::TASK_CLASS_EXPORT_FILES,
        TasksHelper::TASK_CLASS_MIGRATIONS,
    ];

    /**
     * Get next table.
     *
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
     * Set next migration params.
     *
     * @param $migrationdatatype
     * @param int $migrationstart
     * @return void
     */
    public static function set_next_migration_params($migrationdatatype, $migrationstart = 0) {
        SettingsHelper::set_setting('migrationdatatype', $migrationdatatype);
        SettingsHelper::set_setting('migrationstart', $migrationstart);
    }

    /**
     * Disable sheduled tasks.
     *
     * @param array $exclude
     * @return void
     */
    public static function disable_sheduled_tasks($exclude = []) {
        foreach (self::$tasks as $task) {
            if (!in_array($task, $exclude)) {
                self::set_disabled_sheduled_task($task, true);
            }
        }
    }

    /**
     * Enable sheduled tasks.
     *
     * @param array $exclude
     * @return void
     */
    public static function enable_sheduled_tasks($exclude = []) {
        foreach (self::$tasks as $task) {
            if (!in_array($task, $exclude)) {
                self::set_disabled_sheduled_task($task, false);
            }
        }
    }

    /**
     * Enabled migration task.
     *
     * @return void
     * @throws \dml_exception
     */
    public static function enabled_migration_task() {
        global $DB;

        $classname = TasksHelper::TASK_CLASS_MIGRATIONS;
        if ($DB->record_exists('task_scheduled', ['classname' => $classname])) {
            self::enable_sheduled_tasks();
            self::disable_sheduled_tasks([TasksHelper::TASK_CLASS_MIGRATIONS]);
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
                'data' => $encryptionservice->encrypt(json_encode(['exporttime' => time()])),
            ]);
        }
    }

    /**
     * Set disabled sheduled task.
     *
     * @param string $classname
     * @param bool $status
     * @return bool
     * @throws \dml_exception
     */
    private static function set_disabled_sheduled_task(string $classname, bool $status) {
        global $DB;

        $taskrecord = $DB->get_record('task_scheduled', ['classname' => $classname]);
        $task = \core\task\manager::scheduled_task_from_record($taskrecord);
        $task->set_disabled($status);
        return \core\task\manager::configure_scheduled_task($task);
    }

    /**
     * Reset migration details.
     *
     * @return void
     */
    public static function reset_migration_details() {
        SettingsHelper::set_setting('resetmigrationprogress', 0);
        SettingsHelper::set_setting('migrationdatatype', '');
        SettingsHelper::set_setting('migrationstart', 0);

        // Clean migrations logs database.
        $exportlogrepository = new export_log_repository();
        $exportlogrepository->clear_migrated();

        // Reset export process.
        ExportHelper::reset_export_details();
    }

    /**
     * Update migration files after the migration is complete.
     *
     * @return void
     */
    public static function change_migration_files() {
        $timemodified = time();

        $exportservice = new export_service();
        $exportservice->change_files_after_migration($timemodified);
    }

    /**
     * Calculate migration progress.
     *
     * @return void
     */
    public static function calculate_migration_progress($showlogs = true) {

        $datatypeservice = new datatypes_service();
        $datatypes = $datatypeservice->get_migrating_datatypes();

        $exportlogrepository = new export_log_repository();

        foreach ($datatypes as $datatype) {
            $starttime = microtime();

            $exportlogrepository->calculate_export_progress($datatype['name']);

            $difftime = microtime_diff($starttime, microtime());

            if ($showlogs) {
                mtrace("IntelliData: Calculation progress for '" . $datatype['name'] . "' completed. Execution took " . $difftime .
                    " seconds.");
            }
        }
    }

    /**
     * Validate if migration force disabled.
     *
     * @return bool
     */
    public static function migration_disabled() {
        return !empty(SettingsHelper::get_setting('forcedisablemigration'));
    }
}

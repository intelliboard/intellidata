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
 *
 * @package    local_intellidata
 * @category   task
 * @author     IntelliBoard Inc.
 * @copyright  2020 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intellidata\task;
defined('MOODLE_INTERNAL') || die();

use local_intellidata\helpers\ExportHelper;
use local_intellidata\helpers\MigrationHelper;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\DebugHelper;
use local_intellidata\helpers\TrackingHelper;
use local_intellidata\services\migration_service;
use local_intellidata\services\export_service;
use local_intellidata\repositories\export_log_repository;

/**
 * Task to process datafiles export.
 *
 * @package    local_intellidata
 * @author     IntelliBoard Inc.
 * @copyright  2020 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('migration_task', 'local_intellidata');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     * @return bool
     * @throws \dml_exception
     * @throws \moodle_exception
     * @throws \Exception
     */
    public function execute() {
        if (TrackingHelper::enabled()) {

            DebugHelper::enable_moodle_debug();

            $params = [];
            $exportservice = new export_service();

            // Reset migration process if enabled.
            if (SettingsHelper::get_setting('resetmigrationprogress')) {
                MigrationHelper::reset_migration_details();

                mtrace("IntelliData Cleaner CRON started!");

                // Delete all IntelliData files.
                $filesrecords = $exportservice->delete_files(['timemodified' => time()]);

                mtrace("IntelliData Cleaner: $filesrecords deleted.");
            }

            $migrationdatatype = SettingsHelper::get_setting('migrationdatatype');
            if ($migrationdatatype) {

                // Ignore if migration completed.
                if ($migrationdatatype == MigrationHelper::MIGRATIONS_COMPLETED_STATUS) {

                    // Export files to Moodledata.
                    ExportHelper::process_files_export($exportservice);

                    // Send callback to IBN.
                    MigrationHelper::send_callback();

                    // Сhange migration files.
                    MigrationHelper::change_migration_files();

                    // Disable scheduled migration task.
                    MigrationHelper::disable_sheduled_tasks();
                    MigrationHelper::enable_sheduled_tasks(['\local_intellidata\task\migration_task']);

                    return true;
                }

                $params['datatype'] = $migrationdatatype;
            }

            $migrationstart = (int) SettingsHelper::get_setting('migrationstart');
            $params['migrationstart'] = $migrationstart;
            $params['rewritable'] = false;

            mtrace("IntelliData Migration CRON started!");

            // Set migration time.
            SettingsHelper::set_lastmigrationdate();

            // Export tables.
            $exportservice->set_migration_mode();
            $migrationservice = new migration_service(null, $exportservice);
            $migrationservice->process($params, true);

            mtrace("IntelliData Migration CRON ended!");
        }

        return true;
    }

}

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


use local_intellidata\helpers\TasksHelper;
use local_intellidata\services\export_service;
use local_intellidata\helpers\TrackingHelper;
use local_intellidata\helpers\DebugHelper;
use local_intellidata\helpers\ExportHelper;


/**
 * Task to process data export.
 *
 * @package    local_intellidata
 * @author     IntelliBoard Inc.
 * @copyright  2020 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_data_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('export_data_task', 'local_intellidata');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        if (TrackingHelper::enabled()) {
            raise_memory_limit(MEMORY_HUGE);

            DebugHelper::enable_moodle_debug();

            $procresstasks = [
                TasksHelper::TASK_CLASS_EXPORT_DATA,
                TasksHelper::TASK_CLASS_MIGRATIONS,
            ];

            if (TasksHelper::tasks_in_process($procresstasks, TasksHelper::TASK_CLASS_EXPORT_DATA)) {

                $a = new \stdClass();
                $a->runningtasks = implode(",", $procresstasks);
                $a->taskname = 'export';
                mtrace(get_string('failedtaskinprogress', 'local_intellidata', $a));

                return true;
            }

            $params = ['cronprocessing' => true];
            if (TrackingHelper::new_tracking_enabled()) {
                $params['forceexport'] = 1;
                $params['rewritable'] = 1;
            }

            $exportservice = new export_service();
            ExportHelper::process_data_export($exportservice, $params);
        }
    }
}

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

namespace local_intellidata\helpers;

use local_intellidata\helpers\ParamsHelper;

class TasksHelper {
    const LOG_TABLE = 'task_log';

    /**
     * @param $table
     * @return bool
     * @throws \dml_exception
     */
    public static function validate_adhoc_tasks($table) {
        global $DB;

        // Ignore validation for Moodle 3.3.
        if (!method_exists('\\core\\task\\manager', 'get_running_tasks')) {
            return true;
        }

        $runningtasks = \core\task\manager::get_running_tasks();
        $adhoctasks = $DB->get_records('task_adhoc', ['classname' => '\local_intellidata\task\export_adhoc_task']);

        if (count($runningtasks)) {
            foreach ($runningtasks as $task) {

                if ($task->classname != '\local_intellidata\task\export_adhoc_task') {
                    continue;
                }

                if (!empty($adhoctasks[$task->id]->customdata)) {
                    $customdata = json_decode($adhoctasks[$task->id]->customdata);

                    // Validate datatype.
                    if (!empty($customdata->datatypes) && in_array($table, $customdata->datatypes)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get IntelliData tasks logs.
     *
     * @param array $params
     * @return array
     * @throws \dml_exception
     */
    public static function get_logs($params = []) {
        global $DB;

        $where = ['component = :component'];
        $sqlparams = [
            'component' => ParamsHelper::PLUGIN
        ];

        if (!empty($params['timestart'])) {
            $where[] = 'timestart >= :timestart';
            $sqlparams['timestart'] = $params['timestart'];
        }

        if (!empty($params['timeend'])) {
            $where[] = 'timeend <= :timeend';
            $sqlparams['timeend'] = $params['timeend'];
        }

        if (!empty($params['taskname'])) {
            $where[] = 'classname = :classname';
            $sqlparams['classname'] = 'local_intellidata\\task\\' . $params['taskname'];
        }

        $where = implode(' AND ', $where);

        return $DB->get_records_sql("
            SELECT *
              FROM {" . self::LOG_TABLE . "}
             WHERE $where", $sqlparams);
    }
}

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
 * Class for migration Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\activities;
use local_intellidata\helpers\DBHelper;

/**
 * Class for migration Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration extends \local_intellidata\entities\migration {

    public $entity      = '\local_intellidata\entities\activities\activity';
    public $eventname   = '\core\event\course_module_created';
    public $table       = 'course_modules';
    public $tablealias  = 'cm';

    /**
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = []) {
        $where = 'cm.deletioninprogress = :deletioninprogress';
        $select = ($count) ?
            "SELECT COUNT(cm.id) as recordscount" :
            "SELECT cm.id, cm.course AS courseid, m.name AS module, cm.instance, cm.section,
                cm.visible, cm.added AS timecreated, cm.completionexpected, cm.completion";

        $sql = "$select
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module
               WHERE $where";

        $params = [
            'deletioninprogress' => 0
        ];

        return $this->set_condition($condition, $conditionparams, $sql, $params);
    }

    /**
     * @param $records
     * @return \Generator
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function prepare_records_iterable($records) {
        global $DB;
        $data = [];

        $moduletypeinstances = array();
        foreach ($records as $cm) {
            if (!isset($moduletypeinstances[$cm->module])) {
                $moduletypeinstances[$cm->module] = array();
            }
            $moduletypeinstances[$cm->module][] = $cm->instance;
            $data[$cm->id] = $cm;
        }

        $moduleinstances = [];
        foreach ($moduletypeinstances as $modulename => $fullids) {
            foreach (array_chunk($fullids, 10000) as $ids) {
                list($insql, $inparams) = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);

                $sql = "SELECT *
                          FROM {" . $modulename . "} m
                         WHERE m.id $insql";

                $instances = $DB->get_records_sql($sql, $inparams);

                $moduleinstances[$modulename] = [];
                foreach ($instances as $instance) {
                    $moduleinstances[$modulename][$instance->id] = [
                        'name' => $instance->name,
                        'params' => observer::set_additional_params($modulename, $instance)
                    ];
                }
            }
        }

        foreach ($data as $cmid => &$cm) {
            if (!isset($moduleinstances[$cm->module][$cm->instance])) {
                unset($data[$cmid]);
                continue;
            }
            $instance = $moduleinstances[$cm->module][$cm->instance];
            $cm->instancename = $instance['name'];
            $cm->params = $instance['params'];
            $entity = new $this->entity($cm);
            $cmdata = $entity->export();

            yield $cmdata;
        }
    }
}

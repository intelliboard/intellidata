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
namespace local_intellidata\entities\coursecompletions;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for migration Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration extends \local_intellidata\entities\migration {

    public $entity      = '\local_intellidata\entities\coursecompletions\coursecompletion';
    public $eventname   = '\core\event\course_completed';
    public $table       = 'course_completions';
    public $tablealias  = 'cc';

    /**
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = []) {
        $where = 'cc.timecompleted > 0';
        $select = ($count) ?
            "SELECT COUNT(cc.id) as recordscount" :
            "SELECT cc.*";

        if ($condition) {
            $where .= " AND " . $condition;
        }

        $sql = "$select
                  FROM {".$this->table."} cc
                 WHERE $where";

        if (!$count) {
            $sql .= " ORDER BY cc.id";
        }

        return [$sql, $conditionparams];
    }

    /**
     * @param $records
     * @return \Generator
     */
    public function prepare_records_iterable($records) {
        foreach ($records as $record) {
            $record->courseid = $record->course;

            $entity = new $this->entity($record);
            $recorddata = $entity->export();
            $recorddata->eventname = $this->eventname;

            yield $recorddata;
        }
    }
}

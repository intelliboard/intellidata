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
namespace local_intellidata\entities\usergrades;

/**
 * Class for migration Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration extends \local_intellidata\entities\migration {

    public $entity      = '\local_intellidata\entities\usergrades\usergrade';
    public $eventname   = '\core\event\user_graded';
    public $table       = 'grade_grades';
    public $tablealias  = 'gg';

    /**
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = []) {

        $select = ($count) ?
            "SELECT COUNT(gg.id) as recordscount" :
            "SELECT gg.id, gg.itemid, gg.userid, gg.usermodified, gg.finalgrade, gg.feedback,
                    gg.hidden, gg.timemodified, gg.rawgrademax, gg.rawgrademin";

        $sql = "$select
                FROM {grade_grades} gg";

        return $this->set_condition($condition, $conditionparams, $sql, []);
    }

    /**
     * @param $records
     * @return \Generator
     */
    public function prepare_records_iterable($records) {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $gradeitems = \grade_item::fetch_all([]);

        foreach ($records as $gradeobject) {

            // Ignore record if gradeitem not exists.
            if (!isset($gradeitems[$gradeobject->itemid])) {
                continue;
            }

            $grade = usergrade::prepare_export_data($gradeobject);
            $entity = new $this->entity($grade);
            $gradedata = $entity->export();

            yield $gradedata;
        }
    }
}

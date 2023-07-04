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
namespace local_intellidata\entities\quizquestionanswers;



/**
 * Class for migration Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quamigration extends \local_intellidata\entities\migration {

    public $entity      = '\local_intellidata\entities\quizquestionanswers\quizquestionattempts';
    public $table       = 'question_attempts';
    public $tablealias  = 'qua';

    /**
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @param null $timestart
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = [], $timestart = null) {

        $select = ($count) ?
            "SELECT COUNT(qua.id) as recordscount" :
            "SELECT qua.id, qa.id AS attemptid, qua.questionid, qa.uniqueid, qa.timemodified, qua.maxmark, qua.slot";

        $sql = "$select
                FROM {" . $this->table . "} " . $this->tablealias . "
           LEFT JOIN {quiz_attempts} qa ON qa.uniqueid = " . $this->tablealias . ".questionusageid";

        return $this->set_condition($condition, $conditionparams, $sql, []);
    }
}

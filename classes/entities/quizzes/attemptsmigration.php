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
 * Class for migration quizz attempts.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\quizzes;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for migration quizz attempts.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attemptsmigration extends \local_intellidata\entities\migration {

    public $entity      = '\local_intellidata\entities\quizzes\attempt';
    public $eventname   = '\mod_quiz\event\attempt_started';
    public $table       = 'quiz_attempts';
    public $tablealias  = 'qa';


    /**
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = []) {
        $where = 'qa.id>0';
        $params = [];

        $select = ($count) ?
            "SELECT COUNT(qa.id) as recordscount" :
            "SELECT qa.id, qa.quiz, qa.userid, qa.attempt, qa.timestart, qa.timefinish, qa.state, (qa.sumgrades/q.sumgrades) * q.grade AS sumgrades";

        $sql = "$select
                FROM {quiz_attempts} qa
                JOIN {quiz} q ON q.id=qa.quiz
               WHERE $where";

        if ($condition) {
            $sql .= " AND " . $condition;
            $params += $conditionparams;
        }

        return [$sql, $params];
    }
}

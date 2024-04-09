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


/**
 * Class for migration quizz attempts.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attemptsmigration extends \local_intellidata\entities\migration {
    /** @var string */
    public $entity = '\local_intellidata\entities\quizzes\attempt';
    /** @var string */
    public $eventname = '\mod_quiz\event\attempt_started';
    /** @var string */
    public $table = 'quiz_attempts';
    /** @var string */
    public $tablealias = 'qa';

    /**
     * Prepare SQL query to get data from DB.
     *
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = []) {

        $select = ($count) ?
            "SELECT COUNT(qa.id) as recordscount" :
            "SELECT qa.id, qa.quiz, qa.userid, qa.attempt,
                    qa.timestart, qa.timefinish, qa.state,
                    CASE WHEN q.sumgrades > 0
                         THEN (qa.sumgrades/q.sumgrades) * q.grade
                         ELSE 0
                    END AS points,
                    CASE WHEN q.sumgrades > 0
                         THEN ((qa.sumgrades/q.sumgrades) * 100)
                         ELSE 0
                    END AS score";

        $sql = "$select
                FROM {quiz_attempts} qa
           LEFT JOIN {quiz} q ON q.id = qa.quiz";

        return $this->set_condition($condition, $conditionparams, $sql, []);
    }
}

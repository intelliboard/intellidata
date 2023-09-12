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
 * Class for migration Quiz Slots.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\quizquestionrelations;
use local_intellidata\helpers\DBHelper;
use local_intellidata\helpers\ParamsHelper;



/**
 * Class for migration Quiz Slots.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class migration extends \local_intellidata\entities\migration {

    public $entity      = '\local_intellidata\entities\quizquestionrelations\quizquestionrelation';
    public $table       = 'quiz_slots';
    public $tablealias  = 't';

    /**
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = []) {
        $release4 = ParamsHelper::get_release() >= 4.0;

        if ($release4) {
            $select = ($count) ?
                "SELECT COUNT(t.id) as recordscount " :
                "SELECT t.id, t.quizid, t.questionid, t.slot, t.type";

            $catidsql = DBHelper::get_operator('JSON_EXTRACT', 'qsr.filtercondition', ['path' => 'questioncategoryid']);

            $sql = "$select
                    FROM (
                        SELECT
                            qs.id,
                            qs.quizid,
                            MAX(qve.questionid) AS questionid,
                            qs.slot,
                            'q' AS type
                        FROM {quiz_slots} qs
                          JOIN {question_references} qre ON qre.itemid = qs.id
                          JOIN {question_versions} qve ON qve.questionbankentryid = qre.questionbankentryid
                        GROUP BY qs.id, qs.quizid, qs.slot

                        UNION

                        SELECT
                            qs.id,
                            qs.quizid,
                            qc.id AS questionid,
                            qs.slot,
                            'c' AS type
                        FROM {quiz_slots} qs
                          JOIN {question_set_references} qsr ON qsr.questionarea = 'slot' AND qsr.itemid = qs.id
                          JOIN {question_categories} qc ON qc.contextid = qsr.questionscontextid
                                                           AND qc.id = CAST({$catidsql} AS DECIMAL)
                    ) t ";
        } else {
            $select = ($count) ?
                "SELECT COUNT(t.id) as recordscount " :
                "SELECT t.id, t.quizid, t.questionid, t.slot, 'q' AS type";

            $sql = "$select
                FROM {quiz_slots} t";
        }

        return $this->set_condition($condition, $conditionparams, $sql, []);
    }
}

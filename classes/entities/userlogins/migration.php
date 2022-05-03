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
 * Class for migration User logins.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2021 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\userlogins;

defined('MOODLE_INTERNAL') || die();

class migration extends \local_intellidata\entities\migration {

    public $entity = '\local_intellidata\entities\userlogins\userlogin';
    public $eventname = '\core\event\user_loggedin';
    public $table = 'logstore_standard_log';

    /**
     * @param false $count
     * @param null $condition
     * @param array $conditionparams
     * @param null $timestart
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = [], $timestart = null) {
        $sqlwhere = '';
        if ($timestart > 0) {
            $sqlwhere = " AND timecreated > $timestart ";
        }

        if ($condition) {
            $sqlwhere .= " AND " . $condition;
        }

        if ($count) {
            $sql = "SELECT
                        COUNT(DISTINCT userid) as recordscount
                      FROM {logstore_standard_log}
                     WHERE contextid=1 AND eventname = '\\\\core\\\\event\\\\user_loggedin' $sqlwhere";
        } else {
            $sql = "SELECT
                        userid AS id,
                        COUNT(userid) AS logins
                      FROM {logstore_standard_log}
                     WHERE contextid=1 AND eventname = '\\\\core\\\\event\\\\user_loggedin' $sqlwhere
                  GROUP BY userid
                  ORDER BY userid";
        }

        return [$sql, $conditionparams];
    }

    /**
     * @param null $condition
     * @param array $sqlparams
     * @return int
     * @throws \dml_exception
     */
    public function get_records_count($lastrecordid = null) {
        global $DB;

        $condition = null; $conditionparams = [];

        if ($lastrecordid) {
            $condition = "userid <= :lastrecid";
            $conditionparams = ['lastrecid' => $lastrecordid];
        }

        list($sql, $sqlparams) = $this->get_sql(true, $condition, $conditionparams);

        return $DB->count_records_sql($sql, $sqlparams);
    }
}

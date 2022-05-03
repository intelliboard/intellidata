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
 * Class for migration Participations.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\participations;
defined('MOODLE_INTERNAL') || die();


class migration extends \local_intellidata\entities\migration {

    public $entity      = '\local_intellidata\entities\participations\participation';
    public $eventname   = '\generated\new_participation';
    public $table       = 'logstore_standard_log';

    public function get_sql($count = false, $condition = null, $conditionparams = []) {
        global $DB;

        list($insql, $params) = $DB->get_in_or_equal([CONTEXT_COURSE, CONTEXT_MODULE], SQL_PARAMS_NAMED);
        $where = "AND contextlevel $insql";

        if ($condition) {
            $where .= " AND " . $condition;
            $params += $conditionparams;
        }

        if ($count) {
            $sql = "SELECT
                        COUNT(DISTINCT concat(userid, '_', contextinstanceid, '_', contextlevel)) as recordscount
                    FROM {logstore_standard_log}
                   WHERE crud IN('c', 'u') AND userid > 0 AND contextinstanceid > 0 $where";
        } else {
            $sql = "SELECT
                    max(id) as id,
                    concat(userid, '_', contextinstanceid, '_', contextlevel) AS uid,
                    userid,
                    contextlevel,
                    contextinstanceid,
                    COUNT(contextinstanceid) AS participations
                FROM {logstore_standard_log}
               WHERE crud IN('c', 'u') AND userid>0 AND contextinstanceid>0 $where
            GROUP BY userid, contextinstanceid, contextlevel";
        }

        return [$sql, $params];
    }

    public function prepare_records_iterable($records) {
        foreach ($records as $record) {
            $record->type = ($record->contextlevel == CONTEXT_MODULE) ? 'activity' : 'course';
            $record->objectid = (int)$record->contextinstanceid;

            $entity = new $this->entity($record);
            $userdata = $entity->export();
            $userdata->eventname = $this->eventname;

            yield $userdata;
        }
    }
}

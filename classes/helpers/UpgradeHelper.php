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
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\helpers;

class UpgradeHelper {

    const SHOW_MESSAGE_ROWS = 10000;
    const TRACKING_TYPES = [PageParamsHelper::PAGETYPE_COURSE, PageParamsHelper::PAGETYPE_MODULE];

    /**
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function copy_intelliboard_tracking() {
        global $DB;

        $DB->delete_records('local_intellidata_tracking');
        list($sql, $conditions) = $DB->get_in_or_equal(self::TRACKING_TYPES);

        $DB->execute("INSERT INTO {local_intellidata_tracking}
                                        SELECT
                                            id,
                                            userid,
                                            courseid,
                                            page,
                                            param,
                                            visits,
                                            timespend,
                                            firstaccess,
                                            lastaccess,
                                            0 AS timemodified,
                                            CONCAT('{\"browser\":\"', useragent, '\",\"os\":\"', useros, '\"}') AS useragent,
                                            userip AS ip
                                        FROM {local_intelliboard_tracking}
                                        WHERE courseid IS NOT NULL AND page {$sql} ", $conditions);

        list($sql, $conditions) = $DB->get_in_or_equal(self::TRACKING_TYPES, SQL_PARAMS_NAMED, 'param', false);
        $DB->execute("
                        INSERT INTO {local_intellidata_tracking}
                        SELECT
                             MAX(id) AS id,
                             userid,
                             0 AS courseid,
                             'site' AS page,
                             1 AS param,
                             SUM(visits) AS visits,
                             SUM(timespend) AS timespend,
                             MIN(firstaccess) AS firstaccess,
                             MAX(lastaccess) AS lastaccess,
                             0 AS timemodified,
                             CONCAT('{\"browser\":\"', MAX(useragent), '\",\"os\":\"', MAX(useros), '\"}') AS useragent,
                             MAX(userip) AS ip
                        FROM {local_intelliboard_tracking}
                       WHERE page {$sql}
                    GROUP BY userid", $conditions);
    }

    /**
     * @param $trackingfixmapper
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function copy_intelliboard_logs() {
        global $DB;

        $DB->delete_records('local_intellidata_trlogs');
        $DB->execute("INSERT INTO {local_intellidata_trlogs} SELECT *, 0 AS timemodified FROM {local_intelliboard_logs}");

        list($sql, $conditions) = $DB->get_in_or_equal(self::TRACKING_TYPES, SQL_PARAMS_NAMED, 'param', false);
        $ids = DBHelper::get_operator('GROUP_CONCAT', 'id', ['separator' => ',']);
        $duplicates = $DB->get_records_sql("
                        SELECT
                             MAX(id) as id,
                             $ids as ids
                        FROM {local_intelliboard_tracking}
                       WHERE page {$sql}
                    GROUP BY userid
                      HAVING COUNT(id) > 1", $conditions);

        foreach ($duplicates as $record) {
            $ids = explode(',', $record->ids);
            $newid = $record->id;
            $duplicates = array_diff($ids, [$record->id]);

            list($sql, $conditions) = $DB->get_in_or_equal($duplicates, SQL_PARAMS_NAMED);
            $conditions['newid'] = $newid;

            $DB->execute("UPDATE {local_intellidata_trlogs} SET trackid=:newid WHERE trackid {$sql}", $conditions);
        }

        $unresolweditems = $DB->get_recordset_sql("SELECT l.id
                                        FROM {local_intellidata_trlogs} l
                                        LEFT JOIN {local_intellidata_tracking} t ON t.id=l.trackid
                                        WHERE t.id IS NULL");
        $items = [];
        $i = 1;
        foreach ($unresolweditems as $item) {
            $items[] = $item->id;

            if ($i >= 500) {
                list($sql, $conditions) = $DB->get_in_or_equal($items);
                $DB->execute("DELETE FROM {local_intellidata_trlogs} WHERE id {$sql}", $conditions);
                $items = [];
                $i = 1;
            }
            $i++;
        }

        if (!empty($items)) {
            list($sql, $conditions) = $DB->get_in_or_equal($items);
            $DB->execute("DELETE FROM {local_intellidata_trlogs} WHERE id {$sql}", $conditions);
        }
    }

    /**
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function copy_intelliboard_details() {
        global $DB;

        $DB->delete_records('local_intellidata_trdetails');
        $DB->execute("INSERT INTO {local_intellidata_trdetails} SELECT *, 0 AS timemodified FROM {local_intelliboard_details}");

        $unresolweditems = $DB->get_recordset_sql("SELECT d.id
                                        FROM {local_intellidata_trdetails} d
                                        LEFT JOIN {local_intellidata_trlogs} l ON l.id=d.logid
                                        WHERE l.id IS NULL");
        $items = [];
        $i = 1;
        foreach ($unresolweditems as $item) {
            $items[] = $item->id;

            if ($i >= 500) {
                list($sql, $conditions) = $DB->get_in_or_equal($items);
                $DB->execute("DELETE FROM {local_intellidata_trdetails} WHERE id {$sql}", $conditions);
                $items = [];
                $i = 1;
            }
            $i++;
        }

        if (!empty($items)) {
            list($sql, $conditions) = $DB->get_in_or_equal($items);
            $DB->execute("DELETE FROM {local_intellidata_trdetails} WHERE id {$sql}", $conditions);
        }
    }
}

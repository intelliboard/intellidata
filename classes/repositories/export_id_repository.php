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
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\repositories;

class export_id_repository {

    /**
     * @param $datatype
     * @param $table
     * @return array
     * @throws \dml_exception
     */
    public function filterids($datatype, $table) {
        global $DB;
        $deletedrecords = $DB->get_records_sql("
            SELECT dataid AS id
              FROM {local_intellidata_export_ids}
             WHERE datatype=:datatype AND dataid NOT IN(SELECT id FROM {{$table}})",
            ['datatype' => $datatype]);
        $createdrecords = $DB->get_records_sql("
            SELECT id FROM {{$table}}
             WHERE id NOT IN(SELECT dataid FROM {local_intellidata_export_ids} WHERE datatype=:datatype)",
            ['datatype' => $datatype]);

        $createdids = $deletedids = [];
        foreach ($createdrecords as $record) {
            $createdids[] = $record->id;
        }
        foreach ($deletedrecords as $record) {
            $deletedids[] = $record->id;
        }

        return array(
            'created' => $createdids,
            'deleted' => $deletedids,
        );
    }

    /**
     * @param $datatype
     * @param $filteredids
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function save($datatype, $filteredids) {
        global $DB;

        if (!empty($filteredids['deleted'])) {
            list($insql, $params) = $DB->get_in_or_equal($filteredids['deleted'], SQL_PARAMS_NAMED);
            $params['datatype'] = $datatype;
            $DB->execute("DELETE FROM {local_intellidata_export_ids} WHERE datatype=:datatype AND dataid {$insql}", $params);
        }

        if (!empty($filteredids['created'])) {
            $records = [];
            foreach ($filteredids['created'] as $filteredid) {
                $records[] = [
                    'datatype' => $datatype,
                    'dataid' => $filteredid,
                    'timecreated' => time()
                ];
            }

            $DB->insert_records('local_intellidata_export_ids', $records);
        }
    }
}

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

use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\StorageHelper;
use local_intellidata\helpers\EventsHelper;
use local_intellidata\services\datatypes_service;
use local_intellidata\services\encryption_service;
use local_intellidata\services\export_service;

class database_repository {

    public static $encriptionservice    = null;
    public static $exportservice        = null;

    /**
     * @param $datatype
     * @param $params
     * @return int
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    public static function export($datatype, $params) {
        global $DB;

        self::$encriptionservice = new encryption_service();
        self::$exportservice = new export_service();
        $exportlogrepository = new export_log_repository();

        list($lastexportedtime, $lastexportedid) = $exportlogrepository->get_last_processed_data($datatype['name']);

        $sqlparams = [];
        if ($datatype['timemodified_field']) {
            $where = $datatype['timemodified_field'] . ' > :timemodified';
            $sqlparams['timemodified'] = $lastexportedtime;
        } else if (!empty($datatype['filterbyid'])) {
            $where = 'id > ' . $lastexportedid;
        } else {
            $where = 'id > 0';
        }

        $records = null;
        if (isset($datatype['table'])) {

            $records = $DB->get_recordset_sql("
                SELECT *
                  FROM {" . $datatype['table'] . "}
                WHERE $where
                ORDER BY id", $sqlparams
            );

        } else if (isset($datatype['migration'])) {

            $migration = datatypes_service::init_migration($datatype['migration']);
            list($sql, $params) = $migration->get_sql(false, null, [], $lastexportedtime);

            $records = $DB->get_recordset_sql($sql, array_merge($sqlparams, $params));
        }

        $lastexportedtime = time();

        $recordsnum = 0;
        $record = new \stdClass();
        if ($records) {
            foreach ($records as $record) {
                self::save($datatype, $record);
                $recordsnum++;
            }
        }

        $exportlogrepository->save_last_processed_data($datatype['name'], $record, $lastexportedtime);

        return $recordsnum;
    }

    /**
     * @param $datatype
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    public static function export_ids($datatype) {

        $exportidrepository = new export_id_repository();

        if (isset($datatype['table'])) {
            $filteredids = $exportidrepository->filterids($datatype['name'], $datatype['table']);
            $exportidrepository->save($datatype['name'], $filteredids);
        } else if (isset($datatype['migration'])) {
            // Temporary disabled, currently we can not calculate deleted ids for large migration requests.
            return;
        }

        if ($deletedids = $filteredids['deleted']) {
            foreach ($deletedids as $id) {
                self::save($datatype, (object)['id' => $id, 'crud' => EventsHelper::CRUD_DELETED]);
            }
        }
    }

    /**
     * @param $datatype
     * @param $data
     * @param false $eventname
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    public static function save($datatype, $data, $eventname = false) {
        $entity = datatypes_service::init_entity($datatype, $data);
        $entitydata = $entity->after_export($entity->export());

        $prepareddata = StorageHelper::format_data(SettingsHelper::get_export_dataformat(), $entitydata);
        $exportservice = (!empty(self::$exportservice)) ?
            self::$exportservice : new export_service();

        $exportservice->store_data($datatype['name'], $prepareddata);
    }

}

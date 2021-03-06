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

    const LOGS_DISPLAY_PERIOD = 1000;

    public static $encriptionservice    = null;
    public static $exportservice        = null;
    public static $exportlogrepository  = null;
    public static $writerecordslimits   = null;

    /**
     * Init dependencies.
     *
     * @param $datatype
     */
    public static function init($services = null) {
        self::$encriptionservice = (!empty($services['encryptionservice']))
            ? $services['encryptionservice'] : new encryption_service();
        self::$exportservice = (!empty($services['exportservice']))
            ? $services['exportservice'] : new export_service();
        self::$exportlogrepository = (!empty($services['exportlogrepository']))
            ? $services['exportlogrepository'] : new export_log_repository();
        self::$writerecordslimits = (int) SettingsHelper::get_setting('migrationwriterecordslimit');
    }

    /**
     * @param $datatype
     * @param $params
     * @return int
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    public static function export($datatype, $params, $showlogs = false, $services = null) {

        // Init Services.
        self::init($services);

        $start = 0; $limit = (int)SettingsHelper::get_setting('exportrecordslimit');
        $overalexportedrecords = 0; $lastrecord = new \stdClass();

        while ($records = self::get_records($datatype, $start, $limit)) {

            // Stop export when no records.
            if (!$records->valid()) {
                break;
            }

            // Export records to storage.
            list($exportedrecords, $lastrecord) = self::export_records($datatype, $records, $showlogs);
            $overalexportedrecords += $exportedrecords;

            if ($showlogs) {
                mtrace("Datatype '" . $datatype['name'] . "' exported " . $overalexportedrecords . " rows " . date('r') . "...");
            }

            // Stop export in no limit.
            if (!$limit) {
                break;
            }
            $start += $limit;
        }

        self::$exportlogrepository->save_last_processed_data($datatype['name'], $lastrecord, time());

        return $overalexportedrecords;
    }

    /**
     * @param $datatype
     * @param int $start
     * @param int $limit
     * @return \moodle_recordset
     * @throws \dml_exception
     */
    public static function get_records($datatype, $start = 0, $limit = 0) {
        global $DB;

        list($sql, $sqlparams) = self::get_export_sql($datatype);

        return $DB->get_recordset_sql($sql, $sqlparams, $start, $limit);
    }

    /**
     * @param $datatype
     * @return array
     */
    public static function get_export_sql($datatype) {

        list($lastexportedtime, $lastexportedid) = self::$exportlogrepository->get_last_processed_data($datatype['name']);

        $sql = ''; $sqlparams = [];
        if ($datatype['timemodified_field']) {
            $where = $datatype['timemodified_field'] . ' > :timemodified';
            $sqlparams['timemodified'] = $lastexportedtime;
        } else if (!empty($datatype['filterbyid'])) {
            $where = 'id > ' . $lastexportedid;
        } else {
            $where = 'id > 0';
        }

        if (isset($datatype['table'])) {
            $sql = "SELECT *
                      FROM {" . $datatype['table'] . "}
                     WHERE $where
                  ORDER BY id";
        } else if (isset($datatype['migration'])) {

            $migration = datatypes_service::init_migration($datatype, null, false);
            list($sql, $params) = $migration->get_sql(false, null, [], $lastexportedtime);

            $sqlparams = array_merge($sqlparams, $params);
        }

        return [$sql, $sqlparams];
    }

    /**
     * @param $datatype
     * @param $records
     * @param false $showlogs
     * @return array
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    public static function export_records($datatype, $records, $showlogs = false) {

        $recordsnum = 0; $logscounter = 0; $cleanlogs = false;
        $record = new \stdClass();

        if ($records) {
            $data = [];
            $i = 0;

            if (empty(self::$exportservice)) {
                self::init();
            }

            $isprepareddata = false;
            if (!empty($datatype['migration'])) {
                $migration = datatypes_service::init_migration($datatype, null, false);
                $records = $migration->prepare_records_iterable($records);
                $isprepareddata = true;
            }

            foreach ($records as $record) {
                $data[] = self::prepare_entity_data($datatype, $record, $isprepareddata);

                // Export data by chanks.
                if ($i >= self::$writerecordslimits) {
                    // Save data into the file.
                    self::export_data($datatype['name'], $data);
                    $data = [];
                    $i = 0;

                    if ($showlogs) {
                        mtrace("");
                        mtrace("Complete $recordsnum records.");
                    }
                }
                $i++;

                $recordsnum++; $logscounter++;

                // Display export logs.
                if ($showlogs && $logscounter == self::LOGS_DISPLAY_PERIOD) {
                    mtrace('.', '');
                    $logscounter = 0; $cleanlogs = true;
                }
            }

            self::export_data($datatype['name'], $data);
        }

        if ($showlogs && $cleanlogs) {
            mtrace("");
        }

        return [$recordsnum, $record];
    }

    /**
     * @param $datatype
     * @param $data
     */
    private static function export_data($datatype, $data) {
        self::$exportservice->store_data($datatype, implode(PHP_EOL, $data));
    }

    /**
     * @param array $datatype
     * @param \stdClass $data
     * @param bool $isentitydata
     *
     * @return false|string
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    private static function prepare_entity_data($datatype, $data, $isentitydata = false) {
        if (!$isentitydata) {
            $entity = datatypes_service::init_entity($datatype, $data);
            $data = $entity->after_export($entity->export());
        }

        return StorageHelper::format_data(SettingsHelper::get_export_dataformat(), $data);
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

        if (empty(self::$exportservice)) {
            self::init();
        }
        self::$exportservice->store_data($datatype['name'], $prepareddata);
    }

}

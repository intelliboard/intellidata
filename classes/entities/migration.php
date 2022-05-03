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
 * Abstract class for IntelliData entities.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities;
use local_intellidata\helpers\StorageHelper;
use local_intellidata\repositories\export_log_repository;
use local_intellidata\services\encryption_service;
use local_intellidata\services\export_service;
use local_intellidata\services\migration_service;
use local_intellidata\helpers\DebugHelper;
use local_intellidata\helpers\SettingsHelper;

defined('MOODLE_INTERNAL') || die();

/**
 * Abstract class for core objects saved to the DB.
 *
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 */
abstract class migration {

    public $encryptionservice   = null;
    public $entity              = null;
    public $eventname           = null;
    public $table               = null;
    public $tablealias          = null;
    public $crud                = 'c';

    public function __construct(string $forceformat = null, $init = true) {
        if ($init) {
            $this->encryptionservice = new encryption_service();
            $this->migrationservice = new migration_service($forceformat);
            $this->writerecordslimits = (int)SettingsHelper::get_setting('migrationwriterecordslimit');
            $this->exportservice = new export_service(true);
        }
    }

    public function get_records($params) {
        global $DB;

        list($sql, $sqlparams) = $this->get_sql();

        $records = $DB->get_recordset_sql($sql, $sqlparams, $params['start'], $params['limit']);

        return $this->prepare_records($records);
    }

    public function export_records($params, $tablename) {
        global $DB;

        list($sql, $sqlparams) = $this->get_sql();

        $records = $DB->get_recordset_sql($sql, $sqlparams, $params['start'], $params['limit']);

        return $this->prepare_export_records($records, $tablename);
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
            $condition = (!empty($this->tablealias) ? $this->tablealias . "." : "") . "id <= :lastrecid";
            $conditionparams = ['lastrecid' => $lastrecordid];
        }

        list($sql, $sqlparams) = $this->get_sql(true, $condition, $conditionparams);

        return $DB->count_records_sql($sql, $sqlparams);
    }

    /**
     * @param false $count
     * @param null $condition
     * @param array $sqlparams
     * @return array
     */
    public function get_sql($count = false, $condition = null, $conditionparams = []) {
        $select = ($count) ?
            "SELECT COUNT(id) as recordscount" :
            "SELECT *";

        $sql = "$select
                  FROM {".$this->table."}";

        $sqlparams = [];
        if ($condition) {
            $sql .= " WHERE " . $condition;
            $sqlparams += $conditionparams;
        }

        return [$sql, $sqlparams];
    }

    protected function prepare_records($records) {
        $data = [];

        foreach ($this->prepare_records_iterable($records) as $entitydata) {
            $data[] = $this->preparedata($entitydata);
        }
        $records->close();

        $this->save_log((isset($entitydata)) ? $entitydata : null);

        return implode(PHP_EOL, $data);
    }

    public function prepare_records_iterable($records) {
        foreach ($records as $record) {
            $entity = new $this->entity($record);
            $entitydata = $entity->export();

            if ($this->eventname) {
                $entitydata->eventname = $this->eventname;
            }

            yield $entitydata;
        }
    }

    private function prepare_export_records($records, $tablename) {
        $data = [];
        $i = 0;
        $countrecords = 0;

        foreach ($this->prepare_records_iterable($records) as $entitydata) {
            $data[] = $this->preparedata($entitydata);

            if ($i >= $this->writerecordslimits) {
                mtrace("Complete $countrecords records.");
                // Save data into the file.
                $this->exportservice->store_data($tablename, implode(PHP_EOL, $data));
                $data = [];
                $i = 0;
            }
            $i++;
            $countrecords++;
        }
        $records->close();
        $this->exportservice->store_data($tablename, implode(PHP_EOL, $data));

        $this->save_log((isset($entitydata)) ? $entitydata : null);

        return true;
    }

    private function preparedata($data) {
        return StorageHelper::format_data(
            $this->migrationservice->exportdataformat, $data
        );
    }

    public function save_log($record) {
        $entity = new $this->entity();

        $this->exportlogrepository = new export_log_repository();
        $this->exportlogrepository->save_last_processed_data(
            $entity::TYPE,
            $record,
            (isset($record->recordtimecreated)) ? $record->recordtimecreated : time()
        );
    }

    public function can_migrate() {
        global $DB;
        $dbman = $DB->get_manager();

        return $dbman->table_exists($this->table);
    }

    public function set_migrated() {
        $entity = new $this->entity();
        $this->exportlogrepository = new export_log_repository();
        $this->exportlogrepository->save_migrated($entity::TYPE);
    }

    public static function table_exists($tablename) {
        global $DB;

        return $DB->get_manager()->table_exists($tablename);
    }
}

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

use local_intellidata\persistent\datatypeconfig;
use local_intellidata\persistent\export_logs;
use local_intellidata\services\dbschema_service;
use local_intellidata\services\datatypes_service;

class export_log_repository {

    /**
     * Get the number of migrated records for specific datatype.
     *
     * @param $datatype
     * @return int|mixed
     */
    private function get_migration_records_count($datatype, $lastrecordid = null, $migrationobject = null) {
        $datatypeconfig = datatypes_service::get_datatype($datatype);

        if (!empty($datatypeconfig['migration'])) {
            $migration = ($migrationobject) ?? datatypes_service::init_migration($datatypeconfig, null, false);

            return $migration->get_records_count($lastrecordid);
        }

        return 0;
    }

    /**
     * Get last processed datatype details.
     *
     * @param $datatype
     * @return array|int[]
     * @throws \coding_exception
     */
    public function get_last_processed_data($datatype) {

        $record = export_logs::get_record(['datatype' => $datatype]);

        if ($record) {
            return [$record->get('last_exported_time'), $record->get('last_exported_id')];
        }

        return [0, 0];
    }

    /**
     * Get export log for specific datatype.
     *
     * @param $datatype
     * @return \stdClass|null
     */
    public function get_datatype_export_log($datatype) {

        $record = export_logs::get_record(['datatype' => $datatype]);

        if ($record) {
            return $record->to_record();
        }

        return null;
    }

    /**
     * Update last processed record.
     *
     * @param $datatype
     * @param $lastrecord
     * @param $lastexportedtime
     * @throws \coding_exception
     */
    public function save_last_processed_data($datatype, $lastrecord, $lastexportedtime, $migration = null) {

        $record = export_logs::get_record(['datatype' => $datatype]);

        if (!$record) {
            $record = new export_logs();
            $record->set('datatype', $datatype);
            $record->set('migrated', 0);
        }

        if (!$record->get('timestart')) {
            $record->set('timestart', time());
        }

        $record->set('last_exported_time', $lastexportedtime);
        $record->set('recordscount', $this->get_migration_records_count($datatype, 0, $migration));

        if (isset($lastrecord->id)) {
            $record->set('last_exported_id', $lastrecord->id);
            $record->set('recordsmigrated', $this->get_migration_records_count($datatype, $lastrecord->id, $migration));
        }

        $record->save();
    }

    /**
     * Save datatype as migrated.
     *
     * @param $datatype
     * @throws \coding_exception
     */
    public function save_migrated($datatype) {

        $record = export_logs::get_record(['datatype' => $datatype]);

        if (!$record) {
            $record = new export_logs();
            $record->set('datatype', $datatype);
        }
        $record->set('migrated', 1);
        $record->save();
    }

    /**
     * Convert datatypes to assoc array.
     *
     * @param $key
     * @param array $params
     * @return array
     * @throws \coding_exception
     */
    public function get_assoc_datatypes($key, $params = array()) {
        $records = export_logs::get_records($params);
        $result = array();

        foreach ($records as $record) {
            $recordkey = $record->get($key);

            $result[$recordkey] = $record;
        }

        return $result;
    }

    /**
     * Get already migrated datatypes.
     *
     * @return int[]|string[]
     * @throws \dml_exception
     */
    public function get_migrated_datatypes() {
        global $DB;

        return array_keys(
            $DB->get_records_sql_menu(
                'SELECT datatype, id
                       FROM {local_intellidata_export_log}
                      WHERE migrated = :migrated
                        AND (tabletype = :tabletypeunified OR tabletype = :tabletypelogs)',
                [
                    'migrated' => 1,
                    'tabletypeunified' => export_logs::TABLE_TYPE_UNIFIED,
                    'tabletypelogs' => export_logs::TABLE_TYPE_LOGS
                ]
            )
        );
    }

    /**
     * Reset migration method.
     *
     * @return bool
     * @throws \dml_exception
     */
    public function clear_migrated() {
        global $DB;

        return $DB->execute('UPDATE {local_intellidata_export_log}
                                    SET migrated = 0,
                                        timestart = 0,
                                        recordsmigrated = 0,
                                        recordscount = 0,
                                        last_exported_id = 0,
                                        last_exported_time = 0');
    }

    /**
     * Get all optional datatypes to process.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_optional_datatypes() {
        $records = export_logs::get_records(['tabletype' => export_logs::TABLE_TYPE_CUSTOM]);
        $config = config_repository::get_optional_datatypes();

        $result = [];
        $dbschema = new dbschema_service();

        foreach ($records as $record) {
            $datatype = $record->get('datatype');

            // Exclude disabled datatypes.
            if (isset($config[$datatype]) && $config[$datatype]->status == datatypeconfig::STATUS_DISABLED) {
                continue;
            }

            // Exclude datatype if there is no tables in DB.
            if (!$dbschema->table_exists($datatype)) {
                continue;
            }

            $result[$datatype] = $record;
        }

        return $result;
    }

    /**
     * Get all logs datatypes to process.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_logs_datatypes() {
        $records = export_logs::get_records(['tabletype' => export_logs::TABLE_TYPE_LOGS]);
        $config = config_repository::get_logs_datatypes();

        $result = [];

        foreach ($records as $record) {
            $datatype = $record->get('datatype');

            // Exclude disabled datatypes.
            if (isset($config[$datatype]) && $config[$datatype]->status == datatypeconfig::STATUS_DISABLED) {
                continue;
            }

            $result[$datatype] = $record;
        }

        return $result;
    }

    /**
     * Get all logs datatypes to process.
     *
     * @return array
     * @throws \dml_exception
     */
    public function get_logs_datatypes_with_config() {
        $result = [];
        $records = export_logs::get_records(['tabletype' => export_logs::TABLE_TYPE_LOGS]);

        if (!count($records)) {
            return $result;
        }
        $config = config_repository::get_logs_datatypes();

        foreach ($records as $record) {
            $datatype = $record->get('datatype');

            // Exclude disabled datatypes.
            if (isset($config[$datatype]) && $config[$datatype]->status == datatypeconfig::STATUS_DISABLED) {
                continue;
            }

            $result[$datatype] = $record->to_record();
            $result[$datatype]->params = $config[$datatype]->params;
        }

        return $result;
    }

    /**
     * Insert or reset datatype in export logs.
     *
     * @param $datatype
     * @throws \coding_exception
     */
    public function insert_datatype($datatype, $tabletype = export_logs::TABLE_TYPE_CUSTOM, $forcereset = false) {

        $record = export_logs::get_record(['datatype' => $datatype]);

        if (!$record) {
            $record = new export_logs();
            $record->set('datatype', $datatype);
            $record->set('timestart', 0);
            $record->set('tabletype', $tabletype);
        }

        $record->set('migrated', 0);
        $record->set('last_exported_time', 0);
        $record->set('recordsmigrated', 0);
        $record->set('recordscount', 0);
        $record->set('last_exported_id', 0);

        // Force reset all data.
        if ($record && $forcereset) {
            $record->set('timestart', 0);
        }

        return $record->save();
    }

    /**
     * Reset datatype in export logs.
     *
     * @param $datatype
     * @throws \coding_exception
     */
    public function reset_datatype($datatype, $tabletype = export_logs::TABLE_TYPE_CUSTOM) {
        return self::insert_datatype($datatype, $tabletype, true);
    }

    /**
     * Remove datatype from export table.
     *
     * @param $datatype
     * @throws \coding_exception
     */
    public function remove_datatype($datatype) {

        if ($record = export_logs::get_record(['datatype' => $datatype])) {
            return $record->delete();
        }

        return false;
    }

    /**
     * Get all export logs from plugin.
     *
     * @return export_logs[]
     */
    public function get_export_logs() {
        $logs = [];

        foreach (export_logs::get_records() as $log) {
            $logs[] = $log->to_record();
        }

        return $logs;
    }
}

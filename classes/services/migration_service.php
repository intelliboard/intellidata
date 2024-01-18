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
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\services;

use local_intellidata\helpers\MigrationHelper;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\ParamsHelper;
use local_intellidata\services\datatypes_service;
use local_intellidata\services\export_service;
use local_intellidata\services\encryption_service;

class migration_service {
    const ACCEPTABLE_FORMAT_TYPES = ['json' => 'json', 'csv' => 'csv'];

    protected $recordslimits        = 0;
    protected $tables               = null;
    protected $encryptionservice    = null;
    protected $exportservice        = null;
    protected $exportfilesduringmigration = false;

    public $exportdataformat;

    public function __construct($exportformatkey = null, $exportservice = null) {
        $this->exportservice = ($exportservice) ?? new export_service(ParamsHelper::MIGRATION_MODE_ENABLED);
        $this->tables = $this->get_tables();
        $this->encryptionservice = new encryption_service();
        $this->recordslimits = (int)SettingsHelper::get_setting('migrationrecordslimit');
        $this->exportfilesduringmigration = (bool)SettingsHelper::get_setting('exportfilesduringmigration');

        $exportformatkey = $exportformatkey ?? SettingsHelper::get_export_dataformat();

        if (!$exportformat = @self::ACCEPTABLE_FORMAT_TYPES[$exportformatkey]) {
            throw new \moodle_exception("required_data_format", 'local_intellidata');
        }

        $this->exportdataformat = $exportformat;
    }

    /**
     * @param null $params
     * @param false $cronprocessing
     */
    public function process($params = null, $cronprocessing = false) {

        $alltables = $this->tables;
        $tables = (!empty($params['datatype']) && isset($alltables[$params['datatype']])) ?
            [$params['datatype'] => $alltables[$params['datatype']]] : $this->tables;

        if (count($tables)) {
            foreach ($tables as $table) {
                $this->export_table($table, $params, $cronprocessing);

                // If it is processing by cron, we need to allow only one table processing.
                if ($cronprocessing) {
                    break;
                }
            }
        }
    }

    /**
     * @return array|array[]
     */
    public function get_tables() {
        return datatypes_service::get_migrating_datatypes();
    }

    /**
     * @param $datatype
     * @param $params
     * @param $cronprocessing
     * @return false|void
     */
    public function export_table($datatype, $params, $cronprocessing) {

        $tablename = $datatype['name'];
        $migration = datatypes_service::init_migration($datatype, null, false);
        $migration->init_services([
            'migrationservice' => $this,
            'encryptionservice' => $this->encryptionservice,
            'exportservice' => $this->exportservice,
        ]);
        $params['limit'] = $this->recordslimits;

        if (!$migration->can_migrate()) {
            $migration->set_migrated();

            $migrationdatatype = MigrationHelper::get_next_table($this->tables, $tablename);
            MigrationHelper::set_next_migration_params($migrationdatatype);
            mtrace("Migration for table $tablename cannot be processed...");
            return false;
        }

        $starttime = microtime();
        // Get records count.
        $recordscount = $migration->get_records_count();
        $params['recordscount'] = $recordscount;

        mtrace("Migration started for table $tablename... Total records to migrate: $recordscount");

        if ($recordscount) {

            if ($cronprocessing) {
                $params['start'] = $params['migrationstart'];
                $this->export_data($migration, $tablename, $params);

                $migrationstart = $params['start'] + $params['limit'];
                $migrationdatatype = $tablename;
                if ($recordscount < $migrationstart) {
                    $migration->set_migrated();
                    $migrationstart = 0;
                    $migrationdatatype = MigrationHelper::get_next_table($this->tables, $tablename);
                }
                MigrationHelper::set_next_migration_params($migrationdatatype, $migrationstart);
            } else {
                for ($i = 0; $recordscount - $i * $this->recordslimits > 0; $i++) {
                    $params['start'] = $i * $this->recordslimits;
                    $this->export_data($migration, $tablename, $params);
                }
                $migration->set_migrated();
            }
        } else {
            $migration->set_migrated();

            if ($cronprocessing) {
                $migrationdatatype = MigrationHelper::get_next_table($this->tables, $tablename);
                MigrationHelper::set_next_migration_params($migrationdatatype);
            }
        }

        $difftime = microtime_diff($starttime, microtime());
        mtrace("Migration ended for table $tablename. Execution took " . $difftime . " seconds.");
        mtrace("-------------------------------------------");

        // Export file to moodledata.
        if ($this->exportfilesduringmigration) {
            $savefilesparams = ['datatype' => $tablename];
            if ($datatype['rewritable'] && $cronprocessing) {
                $savefilesparams['rewritable'] = false;
            }

            $this->exportservice->save_files($savefilesparams);
        }
    }

    /**
     * @param $migration
     * @param $tablename
     * @param $params
     */
    public function export_data($migration, $tablename, $params) {

        // Migrate records.
        $migration->export_records($params, $tablename);

        mtrace("Migrating records for '{$tablename}' from: {$params['start']}," .
            " limit: {$params['limit']}, total: {$params['recordscount']}.");
    }
}

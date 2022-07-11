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

use local_intellidata\repositories\database_repository;
use local_intellidata\repositories\export_log_repository;
use local_intellidata\repositories\tracking\tracking_repository;
use local_intellidata\services\datatypes_service;
use local_intellidata\helpers\TasksHelper;

class database_service {

    protected $repo             = null;
    protected $trackingrepo     = null;
    protected $tables           = null;
    protected $showlogs         = true;
    protected $adhoctask        = false;
    protected $services         = false;

    public function __construct($showlogs = true, $services = null) {
        $this->tables = $this->get_tables();
        $this->trackingrepo = new tracking_repository();
        $this->showlogs = $showlogs;
        $this->repo = new database_repository();
        $this->services = $services;
    }

    /**
     * @return array|array[]
     */
    public function get_tables() {
        return datatypes_service::get_static_datatypes();
    }

    /**
     * Set all datatypes list to adhoc tasks.
     */
    public function set_all_tables() {
        $this->tables += datatypes_service::get_required_datatypes();
        $this->tables += datatypes_service::get_logs_datatypes();
    }

    /**
     * @param null $params
     */
    public function export_tables($params = null) {

        if ($this->showlogs) {
            $starttime = microtime();
            mtrace("Export process for Static tables started at " . date('r') . "...");
            mtrace("-------------------------------------------");
        }

        $this->trackingrepo->export_records();

        // Get tables list to process.
        $alltables = $this->tables;
        $tables = (!empty($params['table']) and isset($alltables[$params['table']])) ?
            [$params['table'] => $alltables[$params['table']]] : $this->tables;

        // Process each table migration.
        if (count($tables)) {
            foreach ($tables as $key => $table) {

                // Validate the table can be migrated.
                if (!$this->validate($table)) {
                    continue;
                }

                $this->export($table, $params);
            }
        }

        if ($this->showlogs) {
            mtrace("Export process for Static tables completed at " . date('r') . ".");
            $difftime = microtime_diff($starttime, microtime());
            mtrace("Execution took ".$difftime." seconds.");
            mtrace("-------------------------------------------");
        }
    }

    /**
     * @param $datatype
     * @param null $params
     * @throws \core\invalid_persistent_exception
     * @throws \dml_exception
     */
    public function export($datatype, $params = null) {

        if ($this->showlogs) {
            $starttime = microtime();
            mtrace("Datatype '" . $datatype['name'] . "' export started at " . date('r') . "...");
        }

        // Export table records.
        $recordsexported = $this->repo->export($datatype, $params, $this->showlogs, $this->services);

        if ($this->showlogs) {
            mtrace("Store dtatype ids at " . date('r') . " ...");
        }

        // Sync deleted items.
        $this->repo->export_ids($datatype);

        if ($this->showlogs) {
            mtrace("Datatype '" . $datatype['name'] . "' export completed at " .
                date('r') . ". Exported '$recordsexported' records.");
            $difftime = microtime_diff($starttime, microtime());
            mtrace("Execution took ".$difftime." seconds.");
            mtrace("-------------------------------------------");
        }
    }

    /**
     * @param $value
     */
    public function set_adhoctask($value) {
        $this->adhoctask = $value;
    }

    /**
     * @param $datatype
     * @return bool
     */
    public function validate($datatype) {

        // Avoid export table when adhoc task in progress.
        if (!$this->adhoctask && !TasksHelper::validate_adhoc_tasks($datatype['name'])) {
            return false;
        }

        return true;
    }
}

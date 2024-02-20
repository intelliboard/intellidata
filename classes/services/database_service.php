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

use local_intellidata\helpers\ExportHelper;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\TrackingHelper;
use local_intellidata\repositories\database_repository;
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
     * @return array $params
     *
     * @return array|array[]
     */
    public function get_tables($params = []) {
        return $this->tables ?? datatypes_service::get_static_datatypes($params);
    }

    /**
     * Set all datatypes list to adhoc tasks.
     */
    public function set_all_tables() {
        $this->tables += datatypes_service::get_required_datatypes();
        $this->tables += datatypes_service::get_logs_datatypes();
    }

    /**
     * @param null|array $params
     */
    public function export_tables($params = null) {

        if ($this->showlogs) {
            $starttime = microtime();
            mtrace("Export Data process started at " . date('r') . "...");
            mtrace("-------------------------------------------");
        }

        // Apply specific params.
        $params = $this->get_params_for_process($params);

        // Get tables list to process.
        $datatypes = $this->get_tables_to_export($params);

        // Process each table migration.
        if (count($datatypes)) {
            foreach ($datatypes as $datatype) {

                // Validate the table can be migrated.
                if (!$this->validate($datatype)) {
                    continue;
                }

                $this->export($datatype, $params);
            }
        }

        if ($this->showlogs) {
            mtrace("Export Data process completed at " . date('r') . ".");
            $difftime = microtime_diff($starttime, microtime());
            mtrace("Export Data Execution took " . $difftime . " seconds.");
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

        // Export tracking records from cache/temp storage.
        // TODO: move this validation to entity class.
        if ($datatype['name'] == 'tracking') {
            $this->trackingrepo->export_records();
        }

        if ($this->showlogs) {
            $starttime = microtime();
            mtrace("Datatype '" . $datatype['name'] . "' export started at " . date('r') . "...");
        }

        // Export table records.
        $recordsexported = $this->repo->export($datatype, $params, $this->showlogs, $this->services);

        if (!TrackingHelper::new_tracking_enabled()) {
            // Sync deleted items.
            $this->repo->export_ids($datatype, $this->showlogs);
        }

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

    /**
     * Get list of tables to export.
     *
     * @param $params
     * @return bool
     */
    public function get_tables_to_export($params) {
        $tables = (!empty($params['table']) && isset($this->tables[$params['table']]))
            ? [$params['table'] => $this->tables[$params['table']]]
            : $this->tables;

        if (!empty($params['forceexport'])) {
            $tables = datatypes_service::get_static_datatypes([], $params);
        }

        return $tables;
    }

    /**
     * Get specific params for export.
     *
     * @param $params
     * @return bool
     */
    public function get_params_for_process($params) {

        // Validate dividing export by datatypes.
        if (empty($params['table']) && !empty($params['cronprocessing']) &&
            SettingsHelper::get_setting('divideexportbydatatype')) {

            $params['table'] = ExportHelper::get_export_table($this->tables);
            $params['nextexporttable'] = ExportHelper::get_next_table($this->tables, $params['table']);
        }

        return $params;
    }
}

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

use local_intellidata\helpers\SettingsHelper;
use local_intellidata\repositories\database_storage_repository;
use local_intellidata\repositories\export_id_repository;
use local_intellidata\repositories\export_log_repository;
use local_intellidata\repositories\file_storage_repository;
use local_intellidata\services\datatypes_service;
use local_intellidata\helpers\ParamsHelper;


class export_service {

    public $datatypes       = null;
    public $showlogs        = false;
    private $migrationmode  = false;

    /**
     * @param false $migrationmode
     */
    public function __construct($migrationmode = ParamsHelper::MIGRATION_MODE_DISABLED, $applyconfig = true, $showlogs = true) {
        $this->datatypes = $this->get_datatypes($applyconfig);
        $this->showlogs = $showlogs;
        $this->migrationmode = $migrationmode;
    }

    /**
     * Set migration mode.
     */
    public function set_migration_mode() {
        $this->migrationmode = ParamsHelper::MIGRATION_MODE_ENABLED;
    }

    /**
     * @return array|array[]
     */
    public function get_datatypes($applyconfig = true) {
        return datatypes_service::get_datatypes($applyconfig);
    }

    /**
     * @param array $params
     * @return array
     */
    public function save_files($params = []) {

        $files = [];
        $datatypes = $this->filter_datatypes($params);

        if (count($datatypes)) {
            foreach ($datatypes as $key => $datatype) {

                // Setup correct migration parameters.
                $datatype = $this->setup_migration_params($datatype, $params);

                $storageservice = new storage_service($datatype);
                $starttime = microtime();

                if ($file = $storageservice->save_file()) {
                    $files[] = $file;

                    if ($this->showlogs) {
                        $difftime = microtime_diff($starttime, microtime());
                        mtrace("File {$key} exported at " . date('r') . ".");
                        mtrace("Execution took " . $difftime . " seconds.");
                        mtrace("-------------------------------------------");
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Filter datatypes by params.
     *
     * @param $params
     * @return array|array[]|mixed|null
     */
    protected function filter_datatypes($params) {

        if (!empty($params['datatype']) && isset($this->datatypes[$params['datatype']])) {
            $datatypes = [$params['datatype'] => $this->datatypes[$params['datatype']]];
        } else if (!empty($params['tabletype'])) {
            $datatypes = datatypes_service::filter_datatypes($this->datatypes, $params['tabletype']);
        } else {
            $datatypes = $this->datatypes;
        }

        return $datatypes;
    }

    /**
     * @param $datatype
     * @return mixed
     */
    private function setup_migration_params($datatype, $params = []) {

        if ($this->migrationmode == ParamsHelper::MIGRATION_MODE_ENABLED) {
            $datatype['name'] = $this->get_migration_name($datatype);
        }

        $datatype['migrationmode'] = $this->migrationmode;

        if (!empty($datatype['rewritable']) && isset($params['rewritable']) && !$params['rewritable']) {
            $datatype['rewritable'] = false;
        }

        return $datatype;
    }

    /**
     * @param array $params
     * @return array
     */
    public function get_files($params = []) {

        $files = [];
        $datatypes = $this->filter_datatypes($params);

        if (count($datatypes)) {
            foreach ($datatypes as $key => $datatype) {

                // Get events and static files.
                $storageservice = new storage_service($datatype);
                $files[$key] = $storageservice->get_files($params);

                // Ger migration files.
                $datatype['name'] = $this->get_migration_name($datatype);
                $storageservice = new storage_service($datatype);
                $files[$datatype['name']] = $storageservice->get_files($params);
            }
        }

        return $files;
    }

    /**
     * @param int $timemodified
     * @param array $params
     *
     * @return void
     */
    public function change_files_after_migration($timemodified, $params = []) {
        $datatypes = $this->filter_datatypes($params);
        if (count($datatypes)) {
            foreach ($datatypes as $datatype) {
                $storageservice = new storage_service($datatype);
                $storageservice->update_timemodified_files($timemodified);

                $datatype['name'] = $this->get_migration_name($datatype);
                $storageservice = new storage_service($datatype);
                $storageservice->update_timemodified_files($timemodified);
            }
        }
    }

    /**
     * Clear all records and storage.
     *
     * @params array $params
     *
     * @return int
     * @throws \dml_exception
     */
    public function delete_all_files($params = []) {
        // Clear all storage records.
        database_storage_repository::delete_records();

        // Clear file storage.
        return (new file_storage_repository())->delete_files($params);
    }

    /**
     * @param array $params
     * @param array $exclude
     * @return int
     */
    public function delete_files($params = [], $exclude = []) {
        $filesdeleted = 0;
        $alldatatypes = $this->datatypes;

        if (!empty($params['datatypes'])) {
            $datatypes = $params['datatypes'];
        } else if (!empty($params['datatype']) && isset($alldatatypes[$params['datatype']])) {
            $datatypes = [$params['datatype'] => $alldatatypes[$params['datatype']]];
        } else if (!empty($params['datatype']) && !isset($alldatatypes[$params['datatype']])) {
            $datatypes = [];
        } else {
            $datatypes = $this->datatypes;
        }

        if (count($datatypes)) {
            foreach ($datatypes as $key => $datatype) {

                // Exclude datatatypes.
                if (count($exclude) && in_array($key, $exclude)) {
                    continue;
                }

                $storageservice = new storage_service($datatype);
                $filesdeleted += $storageservice->delete_files($params);

                $datatype['name'] = $this->get_migration_name($datatype);
                $storageservice = new storage_service($datatype);
                $filesdeleted += $storageservice->delete_files($params);
                $storageservice->delete_temp_files();
            }
        }

        return $filesdeleted;
    }

    /**
     * @param $datatype
     * @return array|mixed
     */
    public function get_datatype($datatype) {
        return $this->datatypes[$datatype];
    }

    /**
     * @param $datatype
     * @param $data
     */
    public function store_data($datatype, $data) {

        // Prepare datatype.
        $datatype = $this->get_datatype($datatype);

        // Apply migration params.
        $datatype = $this->setup_migration_params($datatype);

        $storageservice = new storage_service($datatype);
        return $storageservice->save_data($data);
    }

    /**
     * @param $datatype
     * @return string
     */
    public function get_migration_name($datatype) {
        return 'migration_' . $datatype['name'];
    }

}

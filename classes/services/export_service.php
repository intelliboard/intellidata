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

use local_intellidata\repositories\export_log_repository;
use local_intellidata\services\datatypes_service;


class export_service {

    public $datatypes       = null;
    private $migrationmode  = false;

    /**
     * @param false $migrationmode
     */
    public function __construct($migrationmode = false) {
        $this->migrationmode = $migrationmode;
        $this->datatypes = $this->get_datatypes();
    }

    /**
     * @return array|array[]
     */
    public function get_datatypes() {
        return datatypes_service::get_datatypes();
    }

    /**
     * @param array $params
     * @return array
     */
    public function save_files($params = []) {

        $files = [];
        $alldatatypes = $this->datatypes;
        $datatypes = (!empty($params['datatype']) and isset($alldatatypes[$params['datatype']])) ?
            [$params['datatype'] => $alldatatypes[$params['datatype']]] : $this->datatypes;

        if (count($datatypes)) {
            foreach ($datatypes as $key => $datatype) {
                if ($this->migrationmode == true) {
                    $datatype['name'] = $this->get_migration_name($datatype);
                    $datatype['migrationmode'] = $this->migrationmode;
                }
                $storageservice = new storage_service($datatype);
                if ($file = $storageservice->save_file()) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * @param array $params
     * @return array
     */
    public function get_files($params = []) {

        $files = [];
        $alldatatypes = $this->datatypes;
        $datatypes = (!empty($params['datatype']) and isset($alldatatypes[$params['datatype']])) ?
            [$params['datatype'] => $alldatatypes[$params['datatype']]] : $this->datatypes;

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
     * @param array $params
     * @param array $exclude
     * @return int|void
     */
    public function delete_files($params = [], $exclude = []) {

        $filesdeleted = 0;
        $alldatatypes = $this->datatypes;
        $datatypes = (!empty($params['datatype']) and isset($alldatatypes[$params['datatype']])) ?
            [$params['datatype'] => $alldatatypes[$params['datatype']]] : $this->datatypes;

        if (count($datatypes)) {
            foreach ($datatypes as $key => $datatype) {

                // Exclude datatatypes.
                if (count($exclude) and in_array($key, $exclude)) {
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
        $datatype = $this->get_datatype($datatype);
        if ($this->migrationmode == true) {
            $datatype['name'] = $this->get_migration_name($datatype);
            $datatype['migrationmode'] = $this->migrationmode;
        }
        $storageservice = new storage_service($datatype);
        return $storageservice->save_data($data);
    }

    /**
     * @param $datatype
     * @return string
     */
    protected function get_migration_name($datatype) {
        return 'migration_' . $datatype['name'];
    }

}

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

class database_service {

    protected $repo             = null;
    protected $trackingrepo     = null;
    protected $tables           = null;
    protected $showlogs         = true;

    public function __construct($showlogs = true) {
        $this->tables = $this->get_tables();
        $this->repo = new database_repository();
        $this->trackingrepo = new tracking_repository();
        $this->showlogs = $showlogs;
    }

    /**
     * @return array|array[]
     */
    public function get_tables() {
        return datatypes_service::get_static_datatypes();
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

        $alltables = $this->tables;
        $tables = (!empty($params['table']) and isset($alltables[$params['table']])) ?
            [$params['table'] => $alltables[$params['table']]] : $this->tables;

        if (count($tables)) {
            foreach ($tables as $key => $table) {
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

        $recordsexported = $this->repo->export($datatype, $params);
        $this->repo->export_ids($datatype);

        if ($this->showlogs) {
            mtrace("Datatype '" . $datatype['name'] . "' export completed at " .
                date('r') . ". Exported '$recordsexported' records.");
            $difftime = microtime_diff($starttime, microtime());
            mtrace("Execution took ".$difftime." seconds.");
            mtrace("-------------------------------------------");
        }
    }
}

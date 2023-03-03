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
use local_intellidata\helpers\StorageHelper;

class storage_service {

    protected $repo    = null;

    public function __construct($datatype) {
        $this->repo = StorageHelper::get_storage_service($datatype);
    }

    /**
     * @param $data
     */
    public function save_data($data) {
        $this->repo->save_data($data);
    }

    /**
     * @return \stored_file|null
     */
    public function save_file() {
        return $this->repo->save_file();
    }

    /**
     * @param int $timemodified
     * @return void
     */
    public function update_timemodified_files($timemodified) {
        $this->repo->update_timemodified_files($timemodified);
    }

    /**
     * @param array $params
     * @return array
     */
    public function get_files($params = []) {
        return $this->repo->get_files($params);
    }

    /**
     * @param array $params
     * @return int|void
     */
    public function delete_files($params = []) {
        return $this->repo->delete_files($params);
    }

    /**
     * @return bool
     */
    public function delete_temp_files() {
        return $this->repo->delete_temp_files();
    }
}

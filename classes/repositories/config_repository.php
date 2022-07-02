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
 * @copyright  2022 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\repositories;

use local_intellidata\persistent\datatypeconfig;

class config_repository {
    /**
     * @param array $params
     * @return config[]
     */
    public function get_config($params = []) {
        $config = [];
        $dbconfig = datatypeconfig::get_records($params);

        if (count($dbconfig)) {
            foreach ($dbconfig as $conf) {
                $confdata = $conf->to_record();
                $confdata->params = $conf->get('params');

                $config[$conf->get('datatype')] = $confdata;
            }
        }

        return $config;
    }

    /**
     * @param $datatype
     * @param $data
     * @return mixed
     */
    public function save($datatype, $data) {

        $recordid = 0;
        if ($record = datatypeconfig::get_record(['datatype' => $datatype])) {
            $recordid = $record->get('id');
        }

        $config = new datatypeconfig($recordid, $data);
        $config->save();

        return $config->to_record();
    }

    /**
     * @param array $params
     * @return config[]
     */
    public static function get_optional_datatypes($status = null) {
        $config = [];
        $params = ['tabletype' => datatypeconfig::TABLETYPE_OPTIONAL];

        if ($status !== null) {
            $params['status'] = $status;
        }

        $dbconfig = datatypeconfig::get_records($params);

        if (count($dbconfig)) {
            foreach ($dbconfig as $conf) {
                $config[$conf->get('datatype')] = $conf->to_record();
            }
        }

        return $config;
    }

    /**
     * @param array $params
     * @return config[]
     */
    public static function get_logs_datatypes($status = null) {
        $config = [];
        $params = ['tabletype' => datatypeconfig::TABLETYPE_LOGS];

        if ($status !== null) {
            $params['status'] = $status;
        }

        $dbconfig = datatypeconfig::get_records($params);

        if (count($dbconfig)) {
            foreach ($dbconfig as $conf) {
                $configdata = $conf->to_record();
                $configdata->params = $conf->get('params');

                $config[$conf->get('datatype')] = $configdata;
            }
        }

        return $config;
    }
}

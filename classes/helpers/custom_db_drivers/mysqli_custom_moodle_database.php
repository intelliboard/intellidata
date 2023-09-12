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
 * @copyright  2023 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__.'/../../../../../lib/dml/mysqli_native_moodle_database.php');

/**
 * Custom mysqli class representing moodle database interface.
 *
 * @package    local_intellidata
 * @copyright  2023 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */
class mysqli_custom_moodle_database extends \mysqli_native_moodle_database {

    public function get_recordset_sql($sql, array $params = null, $limitfrom = 0, $limitnum = 0) {
        list($limitfrom, $limitnum) = $this->normalise_limit_from_num($limitfrom, $limitnum);

        if ($limitfrom || $limitnum) {
            if ($limitnum < 1) {
                $limitnum = "18446744073709551615";
            }
            $sql .= " LIMIT $limitfrom, $limitnum";
        }

        list($sql, $params, $type) = $this->fix_sql_params($sql, $params);
        $rawsql = $this->emulate_bound_params($sql, $params);

        $this->query_start($sql, $params, SQL_QUERY_SELECT);
        // No MYSQLI_USE_RESULT here, it would block write ops on affected tables.
        $result = $this->mysqli->query($rawsql, MYSQLI_USE_RESULT);
        $this->query_end($result);

        return $this->create_recordset($result);
    }
}

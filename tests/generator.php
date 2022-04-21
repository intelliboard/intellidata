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
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\tests;

use phpunit_util;

class generator {
    private static function data_generator() {
        if (test_helper::is_new_phpunit()) {
            return \advanced_testcase::getDataGenerator();
        }

        return phpunit_util::get_data_generator();
    }

    public static function create_user(array $data = array()): \stdClass {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');

        if (test_helper::is_new_phpunit()) {
            return self::data_generator()->create_user($data);
        }

        $data['id'] = user_create_user($data);
        return (object)$data;
    }

    public static function create_cohort(array $data): \stdClass {
        return self::data_generator()->create_cohort($data);
    }

    public static function create_category(array $data) {
        return self::data_generator()->create_category($data);
    }

    public static function get_category(int $id) {
        if (test_helper::is_new_phpunit()) {
            return \core_course_category::get($id);
        }

        return \coursecat::get($id);
    }

    public static function create_group(array $data) {
        return self::data_generator()->create_group($data);
    }

    public static function create_course(array $data = array()) {
        return self::data_generator()->create_course($data);
    }

    public static function enrol_user(array $data) {
        return self::data_generator()->enrol_user($data['userid'], $data['courseid']);
    }

    public static function get_plugin_generator($component) {
        return self::data_generator()->get_plugin_generator($component);
    }

    public static function create_role(array $data) {
        return self::data_generator()->create_role($data);
    }

    public static function create_module(string $modulename, array $data) {
        return self::data_generator()->create_module($modulename, $data);
    }
}
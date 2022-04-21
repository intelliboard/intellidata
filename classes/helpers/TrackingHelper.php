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
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\helpers;

class TrackingHelper
{
    public static function enabled() {
        return (get_config('local_intellidata', 'enabled')) ? true : false;
    }

    public static function tracking_enabled() {
        $tracking = get_config('local_intellidata', 'enabled');

        if ($tracking && !CLI_SCRIPT && !AJAX_SCRIPT) {
            return true;
        }

        return false;
    }

    public static function enable() {
        return set_config('local_intellidata', true, 'enabled');
    }
    public static function disable() {
        return set_config('local_intellidata', false, 'enabled');
    }
}
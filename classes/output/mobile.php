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
 * Renderer file.
 *
 * @package    local_intellidata
 * @subpackage intellidata
 * @copyright  2024 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    http://intelliboard.net/
 */

namespace local_intellidata\output;

use local_intellidata\helpers\PageParamsHelper;
use local_intellidata\helpers\SettingsHelper;

/**
 * Mobile output class for intellidata.
 *
 * @package    local_intellidata
 * @subpackage intellidata
 * @copyright  2024 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see    http://intelliboard.net/
 */
class mobile {

    /**
     * Registration of JavaScript tracking during plugin initialization in mobile app.
     *
     * @return array HTML, javascript and other data
     */
    public static function init_tracking() {
        global $CFG, $USER;

        $otherdata = [];
        $javascript = '';
        if (SettingsHelper::get_setting('enabledtracking')) {
            $otherdata = [
                'token' => SettingsHelper::get_setting('webservicetoken'),
                'api_url' => $CFG->wwwroot . '/webservice/rest/server.php',
                'ajaxfrequency' => (int) SettingsHelper::get_setting('ajaxfrequency'),
                'inactivity' => (int) SettingsHelper::get_setting('inactivity'),
                'mediatrack' => SettingsHelper::get_setting('trackmedia'),
                'pagetype_site' => PageParamsHelper::PAGETYPE_SITE,
                'pagetype_course' => PageParamsHelper::PAGETYPE_COURSE,
                'pagetype_module' => PageParamsHelper::PAGETYPE_MODULE,
                'user_id' => $USER->id,
            ];

            $javascript = file_get_contents($CFG->dirroot . '/local/intellidata/appjs/mobile.initialization.js');
        }

        return [
            'templates' => [],
            'otherdata' => $otherdata,
            'javascript' => $javascript,
        ];
    }
}

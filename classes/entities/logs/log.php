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
 * Class for preparing data for Roles Assignment.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\logs;

defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for Role Assignment.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class log extends \local_intellidata\entities\entity {

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'eventname' => [
                'type' => PARAM_RAW,
                'description' => 'Event name.',
                'default' => ''
            ],
            'component' => [
                'type' => PARAM_TEXT,
                'description' => 'Component.',
                'default' => 0,
            ],
            'action' => [
                'type' => PARAM_TEXT,
                'description' => 'Action.',
                'default' => 0,
            ],
            'target' => [
                'type' => PARAM_TEXT,
                'description' => 'Target.',
                'default' => 0,
            ],
            'objecttable' => [
                'type' => PARAM_TEXT,
                'description' => 'Object Table.',
                'default' => 0,
            ],
            'objectid' => [
                'type' => PARAM_INT,
                'description' => 'Event Object ID.',
                'default' => 0,
            ],
            'crud' => [
                'type' => PARAM_TEXT,
                'description' => 'Event crud.',
                'default' => 'с',
                'null' => false
            ],
            'contextinstanceid' => [
                'type' => PARAM_INT,
                'description' => 'Instance ID.',
                'default' => 0,
            ],
            'courseid' => [
                'type' => PARAM_INT,
                'description' => 'Course ID.',
                'default' => 0,
            ],
            'relateduserid' => [
                'type' => PARAM_INT,
                'description' => 'Related User ID.',
                'default' => 0,
            ],
            'other' => [
                'type' => PARAM_RAW,
                'description' => 'Other event details.',
                'default' => '',
            ],
        ];
    }

}

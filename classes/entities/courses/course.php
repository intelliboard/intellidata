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
 * Class for preparing data for Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\courses;


/**
 * Class for preparing data for Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'courses';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
                'description' => 'Course ID.',
                'default' => 0,
            ],
            'idnumber' => [
                'type' => PARAM_TEXT,
                'description' => 'Course External ID.',
                'default' => '',
            ],
            'fullname' => [
                'type' => PARAM_RAW,
                'description' => 'Course name.',
                'default' => '',
            ],
            'startdate' => [
                'type' => PARAM_INT,
                'description' => 'Timestamp when course will start.',
                'default' => 0,
            ],
            'enddate' => [
                'type' => PARAM_INT,
                'description' => 'Timestamp when course will end.',
                'default' => 0,
            ],
            'timecreated' => [
                'type' => PARAM_INT,
                'description' => 'Timestamp when course was created.',
                'default' => 0,
            ],
            'visible' => [
                'type' => PARAM_INT,
                'description' => 'Course status.',
                'default' => 0,
            ],
            'format' => [
                'type' => PARAM_TEXT,
                'description' => 'Course format.',
                'default' => '',
            ],
            'sortorder' => [
                'type' => PARAM_INT,
                'description' => 'Course ordering.',
                'default' => 0,
            ],
            'category' => [
                'type' => PARAM_INT,
                'description' => 'Course category.',
                'default' => 0,
            ],
        ];
    }
}

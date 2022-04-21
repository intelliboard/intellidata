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
 * Class for preparing data for User Tracking.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2021 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\usertrackings;
defined('MOODLE_INTERNAL') || die();

class tracking extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'tracking';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Record ID.',
                'default' => 0,
            ),
            'userid' => array(
                'type' => PARAM_INT,
                'description' => 'User ID.',
                'default' => 0,
            ),
            'courseid' => array(
                'type' => PARAM_INT,
                'description' => 'Course ID.',
                'default' => 0,
            ),
            'page' => array(
                'type' => PARAM_TEXT,
                'description' => 'Page identifier.',
                'default' => '',
            ),
            'param' => array(
                'type' => PARAM_INT,
                'description' => 'Additional Parameters.',
                'default' => 0,
            ),
            'visits' => array(
                'type' => PARAM_INT,
                'description' => 'Users Visits.',
                'default' => 0,
            ),
            'timespend' => array(
                'type' => PARAM_INT,
                'description' => 'User timespend.',
                'default' => 0,
            ),
            'firstaccess' => array(
                'type' => PARAM_INT,
                'description' => 'User firstaccess.',
                'default' => 0,
            ),
            'lastaccess' => array(
                'type' => PARAM_INT,
                'description' => 'User lastaccess.',
                'default' => 0,
            ),
            'useragent' => array(
                'type' => PARAM_TEXT,
                'description' => 'User agent.',
                'default' => '',
            ),
            'ip' => array(
                'type' => PARAM_TEXT,
                'description' => 'User IP.',
                'default' => '',
            ),
        );
    }

}

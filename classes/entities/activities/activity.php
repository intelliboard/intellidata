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
 * Class for preparing data for Activities.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\activities;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for Activities.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'activities';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Course Module ID.',
                'default' => 0,
            ),
            'courseid' => array(
                'type' => PARAM_INT,
                'description' => 'Course ID.',
                'default' => 0,
            ),
            'module' => array(
                'type' => PARAM_TEXT,
                'description' => 'Module type.',
                'default' => '',
            ),
            'instance' => array(
                'type' => PARAM_INT,
                'description' => 'Course module instance ID.',
                'default' => 0,
            ),
            'instancename' => array(
                'type' => PARAM_RAW,
                'description' => 'Course module instance title.',
                'default' => '',
            ),
            'visible' => array(
                'type' => PARAM_INT,
                'description' => 'Course module status',
                'default' => 1,
            ),
            'timecreated' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp when course module created.',
                'default' => 0,
            ),
            'completion' => array(
                'type' => PARAM_INT,
                'description' => 'Completion tracking.',
                'default' => 0,
            ),
            'completionexpected' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp when course module created.',
                'default' => 0,
            ),
            'params' => array(
                'type' => PARAM_RAW,
                'description' => 'Additional instance parameters.',
                'default' => '',
            ),
        );
    }

}

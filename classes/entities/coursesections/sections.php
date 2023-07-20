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
 * Class for preparing data for Course Sections.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2022 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\coursesections;


/**
 * Class for preparing data for Course Sections.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2022 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sections extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'coursesections';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
                'description' => 'Section ID.',
            ],
            'course' => [
                'type' => PARAM_INT,
                'description' => 'Course ID.',
                'default' => 0,
            ],
            'section' => [
                'type' => PARAM_INT,
                'description' => 'Section number',
                'default' => 0,
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'description' => 'Section Name.',
                'default' => '',
            ],
            'sequence' => [
                'type' => PARAM_TEXT,
                'description' => 'Activity sequence.',
                'default' => '',
            ],
            'visible' => [
                'type' => PARAM_INT,
                'description' => 'Course visible status.',
                'default' => 0,
            ],
            'timemodified' => [
                'type' => PARAM_INT,
                'description' => 'Time modified',
                'default' => 0,
            ],
        ];
    }
}

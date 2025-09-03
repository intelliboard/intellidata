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
 * Class for preparing data for Course Group member.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2023 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\rubrics;


/**
 * Class for preparing data for Course Group member.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2023 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class rubric extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'rubrics';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
                'description' => 'ID.',
                'default' => 0,
            ],
            'name' => [
                'type' => PARAM_TEXT,
                'description' => 'Rubric Name.',
                'default' => 0,
            ],
            'description' => [
                'type' => PARAM_TEXT,
                'description' => 'Rubric Description.',
                'default' => 0,
            ],
            'instanceid' => [
                'type' => PARAM_INT,
                'description' => 'Instance related id.',
                'default' => 0,
            ],
        ];
    }
}

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
 * Class for preparing data for Course Completions.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\gradeitems;


/**
 * Class for preparing data for Course Completions.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradeitem extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'gradeitems';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'id' => [
                'type' => PARAM_INT,
                'description' => 'Grade Item ID.',
                'default' => 0,
            ],
            'courseid' => [
                'type' => PARAM_INT,
                'description' => 'Course ID.',
                'default' => 0,
            ],
            'iteminstance' => [
                'type' => PARAM_INT,
                'description' => 'Activity ID.',
                'default' => 0,
            ],
            'itemtype' => [
                'type' => PARAM_ALPHANUMEXT,
                'description' => 'Grade Item Type.',
                'default' => 0,
            ],
            'itemmodule' => [
                'type' => PARAM_ALPHANUMEXT,
                'description' => 'Activity Type.',
                'default' => '',
            ],
            'itemname' => [
                'type' => PARAM_TEXT,
                'description' => 'Grade Item Name.',
                'default' => '',
            ],
            'hidden' => [
                'type' => PARAM_INT,
                'description' => 'Grade Item State.',
                'default' => 0,
            ],
            'grademax' => [
                'type' => PARAM_FLOAT,
                'description' => 'Max Grade.',
                'default' => 0,
            ],
        ];
    }

    /**
     * @param $record
     * @return mixed
     */
    public function after_export($record) {
        $record->event = '\core\event\grade_item_created';
        return $record;
    }
}

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
            'categoryid' => [
                'type' => PARAM_INT,
                'description' => 'Category ID.',
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
            'aggregationcoef' => [
                'type' => PARAM_FLOAT,
                'description' => 'Grade Item Weight.',
                'default' => 0,
            ],
            'aggregationcoef2' => [
                'type' => PARAM_FLOAT,
                'description' => 'Grade Item Weight.',
                'default' => 0,
            ],
        ];
    }

    /**
     * Hook to execute after an export.
     *
     * @param $record
     * @return mixed
     */
    public function after_export($record) {
        $record->event = '\core\event\grade_item_created';
        return $record;
    }

    /**
     * Prepare entity data for export.
     *
     * @param \stdClass $object
     * @param array $fields
     * @return null
     * @throws invalid_persistent_exception
     */
    public static function prepare_export_data($object, $fields = [], $table = '') {
        global $DB;

        if (!empty($object->itemtype)) {
            $itemname = !empty($object->itemname) ? $object->itemname : '';
            switch ($object->itemtype) {
                case 'course':
                    if (!empty($object->courseid) && ($course = $DB->get_record('course', ['id' => $object->courseid]))) {
                        $itemname = $course->fullname;
                    }
                    break;
                case 'category':
                    $gradec = $DB->get_record('grade_categories', ['id' => $object->iteminstance]);
                    if (!empty($object->iteminstance) && $gradec) {
                        $itemname = $gradec->fullname;
                    }
                    break;
            }

            $object->itemname = $itemname;
        }

        return $object;
    }
}

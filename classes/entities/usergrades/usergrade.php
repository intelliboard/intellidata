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
namespace local_intellidata\entities\usergrades;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for Course Completions.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usergrade extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'usergrades';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Grade ID.',
                'default' => 0,
            ),
            'gradeitemid' => array(
                'type' => PARAM_INT,
                'description' => 'Grade Item ID.',
                'default' => 0,
            ),
            'userid' => array(
                'type' => PARAM_INT,
                'description' => 'User ID.',
                'default' => 0,
            ),
            'letter' => array(
                'type' => PARAM_RAW_TRIMMED,
                'description' => 'Letter Grade.',
                'default' => 0,
            ),
            'score' => array(
                'type' => PARAM_RAW,
                'description' => 'Percentage Grade.',
                'default' => 0,
            ),
            'point' => array(
                'type' => PARAM_RAW,
                'description' => 'Real Grade.',
                'default' => 0,
            ),
            'feedback' => array(
                'type' => PARAM_RAW,
                'description' => 'Grade Comment.',
                'default' => '',
            ),
            'hidden' => array(
                'type' => PARAM_INT,
                'description' => 'Grade Status.',
                'default' => 0,
            ),
            'timemodified' => array(
                'type' => PARAM_INT,
                'description' => 'Last Graded Time.',
                'default' => 0,
            ),
            'usermodified' => array(
                'type' => PARAM_INT,
                'description' => 'User Grader ID.',
                'default' => 0,
            ),
        );
    }
}

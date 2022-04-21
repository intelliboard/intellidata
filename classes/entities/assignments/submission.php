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
 * Class for preparing data for Assignments Submissions.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\assignments;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for Assignments Submissions.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class submission extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'assignmentsubmissions';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Submission ID.',
                'default' => 0,
            ),
            'assignment' => array(
                'type' => PARAM_INT,
                'description' => 'Assignment ID.',
                'default' => 0,
            ),
            'userid' => array(
                'type' => PARAM_INT,
                'description' => 'User ID.',
                'default' => 0,
            ),
            'timemodified' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp when submission created or modified.',
                'default' => 0,
            ),
            'status' => array(
                'type' => PARAM_TEXT,
                'description' => 'Submission status.',
                'default' => '',
            ),
            'attemptnumber' => array(
                'type' => PARAM_INT,
                'description' => 'Submission attempt.',
                'default' => '',
            ),
            'grade' => array(
                'type' => PARAM_TEXT,
                'description' => 'Submission grade.',
                'default' => '',
            ),
            'feedback' => array(
                'type' => PARAM_RAW,
                'description' => 'Submission feedback.',
                'default' => '',
            ),
            'feedback_at' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp when submission greaded.',
                'default' => 0,
            ),
            'feedback_by' => array(
                'type' => PARAM_INT,
                'description' => 'Grader User Id.',
                'default' => 0,
            ),
            'submission_type' => array(
                'type' => PARAM_TEXT,
                'description' => 'Submission Type.',
                'default' => '',
            ),
        );
    }

}

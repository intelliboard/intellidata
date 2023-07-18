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
namespace local_intellidata\entities\quizquestionrelations;


/**
 * Class for preparing data for Activities.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizquestionrelation extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'quizquestionrelations';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Quiz Slot ID.',
                'default' => 0,
            ),
            'quizid' => array(
                'type' => PARAM_INT,
                'description' => 'Quiz ID.',
                'default' => 0,
            ),
            'questionid' => array(
                'type' => PARAM_INT,
                'description' => 'Question ID.',
                'default' => 0,
            ),
            'slot' => array(
                'type' => PARAM_INT,
                'description' => 'Slot Number.',
                'default' => 0,
            ),
            'type' => array(
                'type' => PARAM_TEXT,
                'description' => 'Question Type.',
                'default' => '',
            ),
        );
    }

}

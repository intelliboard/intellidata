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
 * Class for preparing data for Participations.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2021 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\participations;
defined('MOODLE_INTERNAL') || die();

class participation extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'participation';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Log ID.',
                'default' => 0,
            ),
            'userid' => array(
                'type' => PARAM_INT,
                'description' => 'User ID.',
            ),
            'type' => array(
                'type' => PARAM_TEXT,
                'description' => 'Object type.',
                'default' => '',
            ),
            'objectid' => array(
                'type' => PARAM_INT,
                'description' => 'Object ID.',
                'default' => '',
            ),
            'participations' => array(
                'type' => PARAM_INT,
                'description' => 'Count of participations.',
                'default' => 1,
            ),
            'last_participation' => array(
                'type' => PARAM_INT,
                'description' => 'Date of last participation.',
                'default' => 0,
            ),
        );
    }

}

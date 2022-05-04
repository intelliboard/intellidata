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
 * Class for preparing data for Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\users;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for Users.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'users';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'User ID.',
                'default' => 0,
            ),
            'username' => array(
                'type' => PARAM_RAW,
                'description' => 'User username.',
                'default' => '',
            ),
            'fullname' => array(
                'type' => PARAM_RAW,
                'description' => 'User fullname.',
                'default' => '',
            ),
            'timecreated' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp when user was created.',
                'default' => 0,
            ),
            'email' => array(
                'type' => PARAM_RAW_TRIMMED,
                'description' => 'User Email.',
                'default' => '',
            ),
            'lang' => array(
                'type' => PARAM_TEXT,
                'description' => 'User locale.',
                'default' => '',
            ),
            'country' => array(
                'type' => PARAM_TEXT,
                'description' => 'User country.',
                'default' => '',
            ),
            'firstaccess' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp users first access.',
                'default' => 0,
            ),
            'lastaccess' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp users last access.',
                'default' => 0,
            ),
            'lastlogin' => array(
                'type' => PARAM_INT,
                'description' => 'Timestamp users last login.',
                'default' => 0,
            ),
            'state' => array(
                'type' => PARAM_INT,
                'description' => 'User status.',
                'default' => 1,
            ),
        );
    }

}

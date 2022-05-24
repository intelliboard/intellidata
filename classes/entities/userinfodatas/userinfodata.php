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
 * Class for preparing data for UserInfoDatas.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\userinfodatas;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for UserInfoDatas.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userinfodata extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'userinfodatas';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Info Data ID.',
                'default' => 0,
            ),
            'userid' => array(
                'type' => PARAM_INT,
                'description' => 'User ID.',
                'default' => 0,
            ),
            'fieldid' => array(
                'type' => PARAM_INT,
                'description' => 'Field ID.',
                'default' => 0,
            ),
            'data' => array(
                'type' => PARAM_RAW,
                'description' => 'Data Body.',
                'default' => '',
            ),
            'dataformat' => array(
                'type' => PARAM_INT,
                'description' => 'Data Format.',
                'default' => 0,
            ),
        );
    }


    /**
     * @param $record
     * @return mixed
     */
    public function after_export($record) {
        $record->event = '\core\event\user_created';
        return $record;
    }

}
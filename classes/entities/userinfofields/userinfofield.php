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
 * Class for preparing data for UserInfoFields.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_intellidata\entities\userinfofields;
defined('MOODLE_INTERNAL') || die();

/**
 * Class for preparing data for UserInfoFields.
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userinfofield extends \local_intellidata\entities\entity {

    /**
     * Entity type.
     */
    const TYPE = 'userinfofields';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'id' => array(
                'type' => PARAM_INT,
                'description' => 'Info Field ID.',
                'default' => 0,
            ),
            'shortname' => array(
                'type' => PARAM_TEXT,
                'description' => 'Info Field Short Name.',
                'default' => '',
            ),
            'name' => array(
                'type' => PARAM_TEXT,
                'description' => 'Info Field Name.',
                'default' => '',
            ),
            'categoryid' => array(
                'type' => PARAM_INT,
                'description' => 'Category ID.',
                'default' => 0,
            ),
            'sortorder' => array(
                'type' => PARAM_INT,
                'description' => 'Sort Order.',
                'default' => 0,
            ),
            'visible' => array(
                'type' => PARAM_INT,
                'description' => 'Visible Status.',
                'default' => 0,
            ),
        );
    }

    /**
     * @param $record
     * @return mixed
     */
    public function after_export($record) {
        $record->event = '\core\event\user_info_field_created';
        return $record;
    }

}

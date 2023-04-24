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
 * Class storage
 *
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\persistent;

use local_intellidata\persistent\base;

/**
 * Class storage
 *
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */
class datatypeconfig extends base {

    /** The table name. */
    const TABLE = 'local_intellidata_config';

    /** @var int The table type. */
    const TABLETYPE_REQUIRED = 0;
    const TABLETYPE_OPTIONAL = 1;
    const TABLETYPE_LOGS = 2;

    /** @var int The tables export types. */
    const TABLETYPE_EVENTS = 1;
    const TABLETYPE_STATIC = 2;

    /** @var int The datatype status. */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties() {
        return array(
            'tabletype' => [
                'type' => PARAM_INT,
                'description' => 'Table type.',
                'default' => self::TABLETYPE_OPTIONAL,
                'choices' => [
                    self::TABLETYPE_REQUIRED,
                    self::TABLETYPE_OPTIONAL,
                    self::TABLETYPE_LOGS
                ]
            ],
            'datatype' => [
                'type' => PARAM_TEXT,
                'description' => 'Datatype.',
            ],
            'status' => [
                'type' => PARAM_INT,
                'description' => 'Status.',
                'default' => self::STATUS_ENABLED,
                'choices' => [self::STATUS_ENABLED, self::STATUS_DISABLED]
            ],
            'timemodified_field' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
                'description' => 'Timemodified field name.',
            ],
            'rewritable' => [
                'type' => PARAM_INT,
                'default' => self::STATUS_DISABLED,
                'choices' => [self::STATUS_ENABLED, self::STATUS_DISABLED]
            ],
            'filterbyid' => [
                'type' => PARAM_INT,
                'default' => self::STATUS_DISABLED,
                'choices' => [self::STATUS_ENABLED, self::STATUS_DISABLED]
            ],
            'events_tracking' => [
                'type' => PARAM_INT,
                'default' => self::STATUS_ENABLED,
                'choices' => [self::STATUS_ENABLED, self::STATUS_DISABLED]
            ],
            'usermodified' => [
                'type' => PARAM_INT,
                'default' => 0,
                'description' => 'Record modufied by user.',
            ],
            'timecreated' => [
                'type' => PARAM_INT,
                'default' => 0,
                'description' => 'Record create time.',
            ],
            'timemodified' => [
                'type' => PARAM_INT,
                'default' => 0,
                'description' => 'Record modify time.',
            ],
            'params' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
                'description' => 'Additional configuration for datatype.',
            ],
            'tableindex' => [
                'type' => PARAM_TEXT,
                'description' => 'Database Index.',
                'null' => NULL_ALLOWED,
                'default' => '',
            ]
        );
    }

    /**
     * Get list of tables types.
     *
     * @return array
     * @throws \coding_exception
     */
    public static function get_tabletypes() {
        return [
            self::TABLETYPE_REQUIRED => get_string('required', 'local_intellidata'),
            self::TABLETYPE_OPTIONAL => get_string('optional', 'local_intellidata'),
            self::TABLETYPE_LOGS => get_string('logs', 'local_intellidata')
        ];
    }

    /**
     * Return unserialized params array.
     *
     * @return mixed|string
     * @throws \coding_exception
     */
    protected function get_params() {
        return !empty($this->raw_get('params')) ? json_decode($this->raw_get('params')) : [];
    }
}

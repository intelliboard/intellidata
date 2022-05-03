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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\services;
use local_intellidata\repositories\config_repository;
use local_intellidata\persistent\datatypeconfig;
use local_intellidata\repositories\system_tables_repository;

class config_service {

    protected $repo    = null;
    protected $dbschema    = null;
    private $datatypes = [];
    private $config = [];

    public function __construct($datatypes = []) {
        $this->repo = new config_repository();
        $this->datatypes = $datatypes;
        $this->config = $this->repo->get_config();
        $this->dbschema = new dbschema_service();
    }

    /**
     * @return config[]
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * @return array|mixed
     */
    public function get_datatypes() {

        if (count($this->datatypes)) {
            foreach ($this->datatypes as $datatypename => $defaultconfig) {
                $this->apply_config($datatypename, $defaultconfig);
            }
        }

        return $this->datatypes;
    }

    /**
     * @return array|mixed
     */
    public function setup_config($forceresetconfig = true) {
        if (count($this->datatypes)) {
            foreach ($this->datatypes as $datatypename => $defaultconfig) {
                $this->apply_config($datatypename, $defaultconfig, $forceresetconfig);
            }
        }
    }

    /**
     * @param $datatypename
     * @param $defaultconfig
     */
    public function apply_config($datatypename, $defaultconfig, $forceresetconfig = false) {

        // Setup config if not exists.
        if (!isset($this->config[$datatypename]) || $forceresetconfig) {
            $this->config[$datatypename] = $this->create_config($datatypename, $defaultconfig);
        }

        $config = $this->config[$datatypename];

        // Remove disabled datatype.
        if (empty($config->status)) {
            unset($this->datatypes[$datatypename]);
            return;
        }

        // Rewrite events tracking.
        if (!empty($this->datatypes[$datatypename]['observer']) && empty($config->events_tracking)) {
            $this->datatypes[$datatypename]['observer'] = false;
        }

        // Rewrite timemodified field.
        if (isset($config->timemodified_field)) {
            if (!empty($config->timemodified_field)) {
                if ($config->tabletype == datatypeconfig::TABLETYPE_OPTIONAL) {
                    $this->datatypes[$datatypename]['timemodified_field'] = (
                        $this->dbschema->column_exists($datatypename, $config->timemodified_field)
                    ) ? $config->timemodified_field : '';
                } else {
                    $this->datatypes[$datatypename]['timemodified_field'] = $config->timemodified_field;
                }
            } else {
                $this->datatypes[$datatypename]['timemodified_field'] = '';
            }
        }

        // Set filterbyid param.
        $this->datatypes[$datatypename]['filterbyid'] = empty($this->datatypes[$datatypename]['timemodified_field']) &&
            !empty($config->filterbyid);

        // Set table rewritable.
        if ($config->tabletype == datatypeconfig::TABLETYPE_OPTIONAL) {
            $this->datatypes[$datatypename]['rewritable'] = !$config->filterbyid &&
                (!empty($config->rewritable) || empty($this->datatypes[$datatypename]['timemodified_field']));
        }
    }

    /**
     * @param $datatypename
     * @param $defaultconfig
     * @return mixed
     */
    public function create_config($datatypename, $defaultconfig) {
        $config = new \stdClass();
        $config->tabletype = $defaultconfig['tabletype'];
        $config->datatype = $datatypename;
        $config->status = self::get_config_status($defaultconfig);
        $config->timemodified_field = ($defaultconfig['timemodified_field'] === false) ? '' : $defaultconfig['timemodified_field'];
        $config->rewritable = ($defaultconfig['rewritable'])
            ? datatypeconfig::STATUS_ENABLED : datatypeconfig::STATUS_DISABLED;
        $config->filterbyid = ($defaultconfig['filterbyid'])
            ? datatypeconfig::STATUS_ENABLED : datatypeconfig::STATUS_DISABLED;
        $config->events_tracking = (!empty($defaultconfig['observer']))
            ? datatypeconfig::STATUS_ENABLED : datatypeconfig::STATUS_DISABLED;

        return $this->repo->save($datatypename, $config);
    }

    /**
     * @param $conf
     * @return int
     */
    public static function get_config_status($conf) {

        if ($conf['tabletype'] == datatypeconfig::TABLETYPE_REQUIRED) {
            return datatypeconfig::STATUS_ENABLED;
        }

        if (count(system_tables_repository::get_excluded_tables([$conf['name']]))) {
            return datatypeconfig::STATUS_DISABLED;
        }

        return datatypeconfig::STATUS_ENABLED;
    }

    /**
     * @param $datatype
     * @return array|false
     */
    public static function get_timemodified_field($datatype) {
        $dbschema = new dbschema_service();
        $predefinedconfig = self::get_predefined_config($datatype);

        // Validate predefined timemodified field.
        if (isset($predefinedconfig['timemodified_field'])
            && $dbschema->column_exists($datatype, $predefinedconfig['timemodified_field'])) {
            return $predefinedconfig['timemodified_field'];
        }

        return $dbschema->get_updated_fieldname($datatype);
    }

    /**
     * @param $datatype
     * @return array|false
     */
    public static function get_available_timemodified_fields($datatype) {
        return (new dbschema_service())->get_available_updates_fieldnames($datatype);
    }

    /**
     * @param $datatype
     * @return array|false
     */
    public static function get_filterbyid_config($datatype) {

        $predefinedconfig = self::get_predefined_config($datatype);

        // Validate predefined filterbyid config.
        if (isset($predefinedconfig['filterbyid'])) {
            return $predefinedconfig['filterbyid'];
        }

        return false;
    }

    /**
     * @param $datatype
     * @return array|false
     */
    public static function get_rewritable_config($datatype) {

        $predefinedconfig = self::get_predefined_config($datatype);

        // Validate predefined rewritable config.
        if (isset($predefinedconfig['rewritable'])) {
            return $predefinedconfig['rewritable'];
        }

        return false;
    }

    /**
     * @param null $datatype
     * @return array|mixed
     */
    public static function get_predefined_config($datatype = null) {

        $config = [
            'badge_issued' => [
                'timemodified_field' => 'dateissued'
            ],
            'badge_manual_award' => [
                'timemodified_field' => 'datemet'
            ],
            'cohort_members' => [
                'timemodified_field' => 'timeadded'
            ],
            'course_format_options' => [
                'filterbyid' => true,
                'rewritable' => false
            ],
            'feedback_value' => [
                'filterbyid' => true,
                'rewritable' => false
            ],
            'comments' => [
                'timemodified_field' => 'timecreated'
            ],
            'my_pages' => [
                'filterbyid' => true,
                'rewritable' => false
            ],
            'local_intellicart_checkout' => [
                'timemodified_field' => 'timeupdated'
            ],
            'notifications' => [
                'timemodified_field' => 'timecreated'
            ]
        ];

        return ($datatype && !empty($config[$datatype])) ? $config[$datatype] : $config;
    }

}

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

use local_intellidata\helpers\EventsHelper;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\DBHelper;
use local_intellidata\repositories\config_repository;
use local_intellidata\persistent\datatypeconfig;
use local_intellidata\repositories\export_log_repository;
use local_intellidata\repositories\system_tables_repository;
use local_intellidata\task\delete_index_adhoc_task;
use local_intellidata\task\export_adhoc_task;

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
     * Returns configuration.
     *
     * @return config[]
     */
    public function get_config() {
        return $this->config;
    }

    /**
     * Returns datatypes list with configuration.
     *
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
     * Setup config for all datatypes.
     *
     * @return array|mixed
     */
    public function setup_config($forceresetconfig = true) {
        if (count($this->datatypes)) {
            foreach ($this->datatypes as $datatypename => $defaultconfig) {
                $this->apply_config($datatypename, $defaultconfig, $forceresetconfig);
            }
        }

        $this->delete_missed_tables_config();

        // Insert deleted tables events.
        $this->apply_optional_tables_events();
    }

    public function reset_config_datatype($record) {
        $exportlogrepository = new export_log_repository();
        // Reset export logs.
        $exportlogrepository->reset_datatype($record->get('datatype'));

        // Delete old export files.
        $exportservice = new export_service();
        $exportservice->delete_files([
            'datatype' => $record->get('datatype'),
            'timemodified' => time()
        ]);

        // Add task to migrate records.
        if ($record->is_required_by_default()) {
            $exporttask = new export_adhoc_task();
            $exporttask->set_custom_data([
                'datatypes' => [$record->get('datatype')]
            ]);
            \core\task\manager::queue_adhoc_task($exporttask);
        }
    }

    /**
     * Generate config for specific datatype.
     *
     * @param $datatypename
     * @param $defaultconfig
     */
    private function apply_config($datatypename, $defaultconfig, $forceresetconfig = false) {

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

        $isoptional = datatypes_service::is_optional($datatypename, $config->tabletype);
        // Rewrite timemodified field.
        if (isset($config->timemodified_field)) {
            if (!empty($config->timemodified_field)) {
                if ($isoptional) {
                    $this->datatypes[$datatypename]['timemodified_field'] = (
                        $this->dbschema->column_exists($this->datatypes[$datatypename]['table'], $config->timemodified_field)
                    ) ? $config->timemodified_field : '';
                } else {
                    $this->datatypes[$datatypename]['timemodified_field'] = $config->timemodified_field;
                }
            } else {
                $this->datatypes[$datatypename]['timemodified_field'] = (datatypes_service::is_required_by_default($datatypename) &&
                    !empty($this->datatypes[$datatypename]['timemodified_field']))
                        ? $this->datatypes[$datatypename]['timemodified_field'] : '';
            }
        }

        // Set filterbyid param.
        $this->datatypes[$datatypename]['filterbyid'] = (bool)$config->filterbyid;

        // Set tabletype param.
        $this->datatypes[$datatypename]['tabletype'] = (int)$config->tabletype;

        // Set table rewritable.
        if ($isoptional) {
            $this->datatypes[$datatypename]['rewritable'] = !$config->filterbyid &&
                (!empty($config->rewritable) || empty($this->datatypes[$datatypename]['timemodified_field']));
        }

        // Set deleted event param.
        $this->datatypes[$datatypename]['deletedevent'] = $config->deletedevent;

        $this->datatypes[$datatypename]['params'] = $config->params;
    }

    /**
     * Creates new config record.
     *
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
     * @param \local_intellidata\persistent\datatypeconfig $recordconfig
     * @param \stdClass $dataconfig
     * @return void
     */
    public function save_config($recordconfig, $dataconfig) {
        // Delete index for old timemodified_field.
        if (!empty($recordconfig->get('tableindex')) &&
            $dataconfig->timemodified_field != $recordconfig->get('timemodified_field')) {
            $this->create_delete_index_adhoc_task($recordconfig);
            $dataconfig->tableindex = '';
        }

        if (!$recordconfig->is_required_by_default()) {
            $timemodifiedfields = self::get_available_timemodified_fields($recordconfig->get('datatype'));
            // Validate export rules.
            if (!empty($dataconfig->timemodified_field)) {
                if (!isset($timemodifiedfields[$dataconfig->timemodified_field])) {
                    $dataconfig->timemodified_field = '';
                    $dataconfig->filterbyid = datatypeconfig::STATUS_DISABLED;
                    $dataconfig->rewritable = datatypeconfig::STATUS_ENABLED;
                } else {
                    $dataconfig->filterbyid = datatypeconfig::STATUS_DISABLED;
                    $dataconfig->rewritable = datatypeconfig::STATUS_DISABLED;
                }
            } else if ($dataconfig->filterbyid) {
                $dataconfig->timemodified_field = '';
                $dataconfig->rewritable = datatypeconfig::STATUS_DISABLED;
            } else if ($dataconfig->rewritable) {
                $dataconfig->timemodified_field = '';
                $dataconfig->filterbyid = datatypeconfig::STATUS_DISABLED;
            } else {
                $dataconfig->rewritable = datatypeconfig::STATUS_ENABLED;
            }

            $recordconfig->set('events_tracking', (!empty($dataconfig->events_tracking))
                ? datatypeconfig::STATUS_ENABLED : datatypeconfig::STATUS_DISABLED);
            $recordconfig->set('timemodified_field', $dataconfig->timemodified_field);
            $recordconfig->set('filterbyid', $dataconfig->filterbyid);
            $recordconfig->set('rewritable', $dataconfig->rewritable);
            $recordconfig->set('status', $dataconfig->status);
            $recordconfig->set('tableindex', $dataconfig->tableindex);
        } else {
            $recordconfig->set('tabletype', $dataconfig->tabletype);
        }

        $recordconfig->save();

        if (!$recordconfig->is_required_by_default()) {
            // Process export log.
            $this->export_log($recordconfig, $dataconfig);
        }
    }

    /**
     * @param \local_intellidata\persistent\datatypeconfig $recordconfig
     * @param \stdClass $dataconfig
     * @return void
     */
    private function export_log($recordconfig, $dataconfig) {
        $datatype = $recordconfig->get('datatype');
        $exportlogrepository = new export_log_repository();
        $exportlog = $exportlogrepository->get_datatype_export_log($datatype);
        if ((!$dataconfig->enableexport || !$dataconfig->status) && !empty($exportlog)) {
            // Remove datatype from the export logs table.
            $exportlogrepository->remove_datatype($datatype);
        } else if (empty($exportlog) && $dataconfig->enableexport) {
            // Add datatype to the export logs table.
            $exportlogrepository->insert_datatype($datatype);
        }
    }

    /**
     * @param \local_intellidata\persistent\datatypeconfig $recordconfig
     * @return mixed
     */
    private function create_delete_index_adhoc_task($recordconfig) {
        $deleteindextask = new delete_index_adhoc_task();
        $deleteindextask->set_custom_data([
            'datatype' => $recordconfig->get('datatype'),
            'tableindex' => $recordconfig->get('tableindex')
        ]);
        \core\task\manager::queue_adhoc_task($deleteindextask);
    }

    /**
     * Returns config record status.
     *
     * @param $conf
     * @return int
     */
    public static function get_config_status($conf) {

        if ($conf['tabletype'] == datatypeconfig::TABLETYPE_REQUIRED) {
            return datatypeconfig::STATUS_ENABLED;
        }

        if (count(system_tables_repository::get_excluded_tables([$conf['table']]))) {
            return datatypeconfig::STATUS_DISABLED;
        }

        return datatypeconfig::STATUS_ENABLED;
    }

    /**
     * Returns timemodified field.
     *
     * @param $datatype
     * @return array|false
     */
    public static function get_timemodified_field($datatype) {
        $dbschema = new dbschema_service();
        $predefinedconfig = self::get_predefined_config($datatype);

        // Validate predefined timemodified field.
        if (isset($predefinedconfig['timemodified_field'])) {
            if (empty($predefinedconfig['timemodified_field'])) {
                return '';
            } else if ($dbschema->column_exists($datatype, $predefinedconfig['timemodified_field'])) {
                return $predefinedconfig['timemodified_field'];
            }
        }

        return $dbschema->get_updated_fieldname($datatype);
    }

    /**
     * Returns timemodified field based on DB table.
     *
     * @param string $datatypetable
     * @return array|false
     */
    public static function get_available_timemodified_fields($datatypetable) {
        return (new dbschema_service())->get_available_updates_fieldnames(
            datatypes_service::get_optional_table($datatypetable)
        );
    }

    /**
     * Returns filterbyid value.
     *
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
     * Returns rewritable configuration.
     *
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
     * Returns exportids configuration.
     *
     * @param $datatype
     * @return array|false
     */
    public static function get_exportids_config_optional($datatype = null) {

        // Do not export deleted records when globally disabled.
        if (!SettingsHelper::get_setting('exportids')) {
            return false;
        }

        // Do not export deleted records when deleted event exists and is tracking.
        if ((int)SettingsHelper::get_setting('exportdeletedrecords') == SettingsHelper::EXPORTDELETED_TRACKEVENTS
            && !empty($datatype->deletedevent)) {
            return false;
        }

        // Rewritable will export all records each time and do not need to track deleted records.
        if (!empty($datatype->rewritable)) {
            return false;
        }

        return true;
    }

    /**
     * Returns predefined configuration.
     *
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
            ],
            'user_lastaccess' => [
                'timemodified_field' => 'timeaccess'
            ],
            'lesson_answers' => [
                'timemodified_field' => '',
                'filterbyid' => false,
                'rewritable' => true
            ],
            'tenant' => [
                'filterbyid' => true,
                'rewritable' => false
            ],
            'tool_tenant' => [
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
            ],
            'tool_tenant_user' => [
                'rewritable' => false,
                'timemodified_field' => 'timemodified',
            ]
        ];

        return ($datatype && !empty($config[$datatype])) ? $config[$datatype] : $config;
    }

    /**
     * Delete config records for not existing tables.
     *
     * @throws \coding_exception
     */
    private function delete_missed_tables_config() {

        if (count($this->config)) {
            foreach ($this->config as $config) {

                // Delete only optional datatypes. Ignore required and logs datatypes.
                if ($config->tabletype != datatypeconfig::TABLETYPE_OPTIONAL) {
                    continue;
                }

                // Delete missed tables.
                $datatype = datatypes_service::get_optional_table($config->datatype);
                if (!$this->dbschema->table_exists($datatype)) {
                    $this->repo->delete($config->datatype);
                }
            }
        }
    }

    /**
     * Apply events tracking for optional tables.
     *
     * @throws \coding_exception
     */
    private function apply_optional_tables_events() {
        if (count($this->config)) {
            $eventslist = EventsHelper::deleted_eventslist();

            foreach ($this->config as $config) {
                if ($config->tabletype != datatypeconfig::TABLETYPE_OPTIONAL) {
                    continue;
                }

                $table = datatypes_service::get_optional_table($config->datatype);
                if (isset($eventslist[$table])) {
                    $config->deletedevent = $eventslist[$table];
                    unset($config->params);

                    $this->repo->save($config->datatype, $config);
                }
            }
        }
    }
}

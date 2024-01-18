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
 * @copyright  2023 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\services;

use local_intellidata\helpers\RolesHelper;
use local_intellidata\helpers\TrackingHelper;

class new_export_service {

    public $entityclass = null;

    /**
     * @param string $table
     * @param array $params
     *
     * @return void
     */
    public function insert_record_event($table, $params) {
        if (!TrackingHelper::new_tracking_enabled()) {
            return;
        }

        $this->get_datatype_observer($table);
        if (!$this->entityclass || !TrackingHelper::enabled()) {
            return;
        }

        if (!$this->filter($table, $params)) {
            return;
        }

        $params = $this->entityclass::prepare_export_data($params);
        $params->crud = 'c';
        $entity = new $this->entityclass($params);
        $data = $entity->export();

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

    /**
     * @param string $table
     * @param array $dataobjects
     *
     * @return void
     */
    public function insert_records_event($table, $dataobjects) {
        global $DB;

        if (!TrackingHelper::new_tracking_enabled()) {
            return;
        }

        $this->get_datatype_observer($table);
        if (!$this->entityclass || !TrackingHelper::enabled()) {
            return;
        }

        foreach ($dataobjects as $dataobject) {
            $record = $DB->get_record($table, $dataobject);
            if (!$this->filter($table, $record)) {
                continue;
            }
            $record = $this->entityclass::prepare_export_data($record);
            $record->crud = 'c';
            $entity = new $this->entityclass($record);
            $data = $entity->export();

            $tracking = new events_service($entity::TYPE);
            $tracking->track($data);
        }
    }

    /**
     * @param string $table
     * @param array $params
     *
     * @return void
     */
    public function update_record_event($table, $params) {
        global $DB;

        if (!TrackingHelper::new_tracking_enabled()) {
            return;
        }

        $this->get_datatype_observer($table);
        if (!$this->entityclass || !TrackingHelper::enabled()) {
            return;
        }

        if (!isset($params->id)) {
            return;
        }

        if (!$record = $DB->get_record($table, ['id' => $params->id])) {
            return;
        }

        if (!$this->filter($table, $record)) {
            return;
        }

        $record = $this->entityclass::prepare_export_data($record);
        $record->crud = 'u';
        $entity = new $this->entityclass($record);
        $data = $entity->export();

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

    /**
     * @param string $table
     * @param array $params
     *
     * @return void
     */
    public function delete_record_event($table, $params) {
        if (!TrackingHelper::new_tracking_enabled()) {
            return;
        }

        $this->get_datatype_observer($table);
        if (!$this->entityclass || !TrackingHelper::enabled()) {
            return;
        }
        $data = (object)$params;
        $data->crud = 'd';
        $entity = new $this->entityclass($data);
        $data = $entity->export();

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

    /**
     * @param string $table
     * @param string $field
     * @param array $values
     *
     * @return void
     */
    public function delete_records_event($table, $field, $values) {
        global $DB;

        if (!TrackingHelper::new_tracking_enabled()) {
            return;
        }

        $this->get_datatype_observer($table);
        if (!$this->entityclass || !TrackingHelper::enabled()) {
            return;
        }

        foreach ($values as $value) {
            if (!$field == 'id') {
                if (!$record = $DB->get_record($table, [$field => $value])) {
                    return;
                }
                $value = $record->id;
                if (!$this->filter($table, $record)) {
                    return;
                }
            }

            $params = new \stdClass;
            $params->id = $value;
            $params->crud = 'd';
            $entity = new $this->entityclass($params);
            $data = $entity->export();

            $tracking = new events_service($entity::TYPE);
            $tracking->track($data);
        }
    }

    /**
     * @param string $table
     * @param string $select
     * @param array $params
     *
     * @return void
     */
    public function delete_records_select_event($table, $select, $params = []) {
        global $DB;

        if (!TrackingHelper::new_tracking_enabled()) {
            return;
        }

        $this->get_datatype_observer($table);
        if (!$this->entityclass || !TrackingHelper::enabled()) {
            return;
        }

        $sql = "SELECT id FROM {" . $table . "} WHERE $select";
        $ids = array_keys($DB->get_records_sql($sql, $params));

        foreach ($ids as $id) {
            $params = new \stdClass;
            $params->id = $id;
            $params->crud = 'd';
            $entity = new $this->entityclass($params);
            $data = $entity->export();

            $tracking = new events_service($entity::TYPE);
            $tracking->track($data);
        }
    }

    /**
     * @param string $table
     * @return string
     */
    public function get_datatype_observer($table) {
        $datatypes = datatypes_service::get_required_datatypes();
        $rdatatype = null;
        foreach ($datatypes as $data) {
            if (!isset($data['table'])) {
                continue;
            }
            if ($data['table'] == $table) {
                $rdatatype = $data;
                break;
            }
        }

        if (!$rdatatype) {
            return null;
        }

        $entityclass = $this->get_entity_by_datatype($rdatatype);
        if (!class_exists($entityclass)) {
            return null;
        }

        $this->entityclass = $entityclass;
    }

    private function get_entity_by_datatype($rdatatype) {
        if (!is_array($rdatatype)) {
            $rdatatype = datatypes_service::get_required_datatypes()[$rdatatype];
        }
        return datatypes_service::get_datatype_entity_class(datatypes_service::get_datatype_entity_path($rdatatype));
    }

    /**
     * @param string $table
     * @param \stdClass $data
     * @return bool
     */
    private function filter($table, $data) {
        global $DB;

        $access = true;
        switch ($table) {
            case 'logstore_standard_log':
                $this->entityclass = $this->get_entity_by_datatype('participation');
                if (!in_array($data->crud, ['c', 'u']) || !$data->userid ||
                    !in_array($data->contextlevel, [CONTEXT_COURSE, CONTEXT_MODULE])) {
                    $access = false;
                }

                if (isset($data->eventname) && ($data->eventname == '\core\event\user_loggedin') && $data->contextid == 1) {
                    $access = true;
                    $this->entityclass = $this->get_entity_by_datatype('userlogins');
                }

                break;
            case 'role_assignments':
                list($insql, $params) = $DB->get_in_or_equal(array_keys(RolesHelper::CONTEXTLIST), SQL_PARAMS_NAMED);
                if (!$DB->record_exists_sql("SELECT id FROM {context} WHERE contextlevel " . $insql, $params)) {
                    $access = false;
                }

                break;
            case 'question_attempt_step_data':
                if ($data->name != 'answer') {
                    $access = false;
                }

                break;
        }

        return $access;
    }
}

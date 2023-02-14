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

use local_intellidata\persistent\export_ids;
use local_intellidata\services\config_service;
use local_intellidata\services\datatypes_service;
use local_intellidata\services\export_service;
use local_intellidata\repositories\export_log_repository;
use local_intellidata\persistent\datatypeconfig;
use local_intellidata\persistent\export_logs;
use local_intellidata\task\export_adhoc_task;
use local_intellidata\helpers\DebugHelper;
use local_intellidata\helpers\DBHelper;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\repositories\export_id_repository;

function xmldb_local_intellidata_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2020091002) {
        $table = new xmldb_table('local_intellidata_export_log');

        // Adding fields to table local_intellidata_export_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('last_exported_time', XMLDB_TYPE_INTEGER, '11', null, null, null);
        $table->add_field('last_exported_id', XMLDB_TYPE_INTEGER, '11', null, null, null);

        // Adding keys to table local_intellidata_export_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_intellidata_export_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2020091002, 'local', 'intellidata');
    }

    if ($oldversion < 2020091400) {
        $table = new xmldb_table('local_intellidata_logs');

        // Adding fields to table local_intellidata_export_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('details', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        // Adding keys to table local_intellidata_logs.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_intellidata_logs.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2020091400, 'local', 'intellidata');
    }

    if ($oldversion < 2020100700) {
        $table = new xmldb_table('local_intellidata_export_log');
        $field = new xmldb_field('migrated', XMLDB_TYPE_INTEGER, '1', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2020100700, 'local', 'intellidata');
    }

    if ($oldversion < 2020102300) {
        $table = new xmldb_table('local_intellidata_storage');

        // Adding fields to table local_intellidata_storage.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        // Adding keys to table local_intellidata_storage.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_intellidata_storage.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2020102300, 'local', 'intellidata');
    }

    if ($oldversion < 2021011200) {

        // Define table local_intellidata_bbb_rec_tr to be created.
        $table = new xmldb_table('local_intellidata_bbb_rec_tr');

        // Adding fields to table local_intellidata_bbb_rec_tr.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('session_id', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('track_at', XMLDB_TYPE_INTEGER, '11', null, null, null, null);
        $table->add_field('tracked_at', XMLDB_TYPE_INTEGER, '11', null, null, null, null);

        // Adding keys to table local_intellidata_bbb_rec_tr.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_intellidata_bbb_rec_tr.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Intellidata savepoint reached.
        upgrade_plugin_savepoint(true, 2021011200, 'local', 'intellidata');
    }

    if ($oldversion < 2021020400) {

        // Define table local_intellidata_tracked_bb to be delete.
        $table = new xmldb_table('local_intellidata_tracked_bb');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Intellidata savepoint reached.
        upgrade_plugin_savepoint(true, 2021020400, 'local', 'intellidata');
    }

    if ($oldversion < 2021020409) {
        $table = new xmldb_table('local_intellidata_export_log');
        $field = new xmldb_field('timestart', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('recordscount', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2021020409, 'local', 'intellidata');
    }

    if ($oldversion < 2021041303) {

        // Define table local_intellidata_export_ids to be created.
        $table = new xmldb_table('local_intellidata_export_ids');

        // Adding fields to table local_intellidata_export_ids.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ids', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        // Adding keys to table local_intellidata_export_ids.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_intellidata_export_ids.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Intellidata savepoint reached.
        upgrade_plugin_savepoint(true, 2021041303, 'local', 'intellidata');
    }

    if ($oldversion < 2021043001) {
        $table = new xmldb_table('local_intellidata_export_log');
        $field = new xmldb_field('timestart', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('recordscount', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2021043001, 'local', 'intellidata');
    }

    if ($oldversion < 2021111010) {
        $table = new xmldb_table('local_intellidata_tracking');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('page', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('param', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('firstaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('useragent', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('ip', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for local_intelliboard_tracking.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add index to local_intellidata_tracking.
        $table = new xmldb_table('local_intellidata_tracking');
        $index = new xmldb_index('userid_page_param_idx', XMLDB_INDEX_NOTUNIQUE, array('userid', 'page', 'param'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('local_intellidata_trlogs');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('trackid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timepoint', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('trackid', XMLDB_KEY_FOREIGN, ['trackid'], 'local_intellidata_tracking', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add index to local_intellidata_trlogs.
        $table = new xmldb_table('local_intellidata_trlogs');
        $index = new xmldb_index('trackid_timepoint_idx', XMLDB_INDEX_NOTUNIQUE, array('trackid', 'timepoint'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        $table = new xmldb_table('local_intellidata_trdetails');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timepoint', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('logid', XMLDB_KEY_FOREIGN, ['logid'], 'local_intellidata_trlogs', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add index to local_intelliboard_details.
        $table = new xmldb_table('local_intellidata_trdetails');
        $index = new xmldb_index('logid_timepoint_idx', XMLDB_INDEX_NOTUNIQUE, array('logid', 'timepoint'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2021111010, 'local', 'intellidata');
    }

    if ($oldversion < 2021111900) {
        // Define table local_intellidata_export_ids to be created.
        $table = new xmldb_table('local_intellidata_export_ids');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Adding fields to table local_intellidata_export_ids.
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        // Adding keys to table local_intellidata_export_ids.
        $table->add_key('datatype_dataid_unique', XMLDB_KEY_UNIQUE, ['datatype', 'dataid']);

        // Conditionally launch create table for local_intellidata_export_ids.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Intellidata savepoint reached.
        upgrade_plugin_savepoint(true, 2021111900, 'local', 'intellidata');
    }

    if ($oldversion < 2022011800) {
        $items = $DB->get_records_sql("SELECT t.id
                                         FROM {local_intellidata_tracking} t
                                    LEFT JOIN {user} u ON u.id=t.userid
                                    LEFT JOIN {course} c ON c.id=t.courseid
                                    LEFT JOIN {course_modules} cm ON cm.id=t.param AND t.page='module'
                                        WHERE u.id IS NULL OR c.id IS NULL OR cm.id IS NULL");

        foreach ($items as $item) {
            $logs = $DB->get_records("local_intellidata_trlogs", ['trackid' => $item->id]);

            foreach ($logs as $log) {
                $DB->delete_records('local_intellidata_trdetails', [
                    'logid' => $log->id,
                ]);
            }
            $DB->delete_records('local_intellidata_trlogs', [
                'trackid' => $item->id,
            ]);
            $DB->delete_records('local_intellidata_tracking', [
                'id' => $item->id,
            ]);
        }

        // Intellidata savepoint reached.
        upgrade_plugin_savepoint(true, 2022011800, 'local', 'intellidata');
    }

    if ($oldversion < 2022020305) {

        // Define table local_intellidata_reports to be created.
        $table = new xmldb_table('local_intellidata_reports');

        // Adding fields to table local_intellidata_reports.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sqlcode', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('service', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('external_identifier', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_intellidata_reports.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_intellidata_reports.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Intellidata savepoint reached.
        upgrade_plugin_savepoint(true, 2022020305, 'local', 'intellidata');
    }

    if ($oldversion < 2022032200) {
        $table = new xmldb_table('local_intellidata_export_log');
        $field = new xmldb_field('tabletype', XMLDB_TYPE_INTEGER, '1', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022032200, 'local', 'intellidata');
    }

    if ($oldversion < 2022032400) {
        $table = new xmldb_table('local_intellidata_config');

        // Adding fields to table local_intellidata_config.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('tabletype', XMLDB_TYPE_INTEGER, '1', null, null, null, 1);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, null, null, 1);
        $table->add_field('timemodified_field', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('rewritable', XMLDB_TYPE_INTEGER, '1', null, null, null, 0);
        $table->add_field('events_tracking', XMLDB_TYPE_INTEGER, '1', null, null, null, 1);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        // Adding keys to table local_intellidata_config.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_intellidata_config.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022032400, 'local', 'intellidata');
    }

    if ($oldversion < 2022032800) {
        $table = new xmldb_table('local_intellidata_config');
        $field = new xmldb_field('filterbyid', XMLDB_TYPE_INTEGER, '1', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022032800, 'local', 'intellidata');
    }

    if ($oldversion < 2022033000) {
        // Setup config in database.
        $configservice = new config_service(datatypes_service::get_all_datatypes());
        $configservice->setup_config();

        upgrade_plugin_savepoint(true, 2022033000, 'local', 'intellidata');
    }

    if ($oldversion < 2022050300) {
        // Define table local_intellidata_export_ids to be created.
        $table = new xmldb_table('local_intellidata_export_ids');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Adding fields to table local_intellidata_export_ids.
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        // Adding keys to table local_intellidata_export_ids.
        $table->add_key('datatype_dataid_unique', XMLDB_KEY_UNIQUE, ['datatype', 'dataid']);

        // Conditionally launch create table for local_intellidata_export_ids.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add column to track migration progress.
        $table = new xmldb_table('local_intellidata_export_log');
        $field = new xmldb_field('recordsmigrated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2022050300, 'local', 'intellidata');
    }

    if ($oldversion < 2022051902) {

        // Setup config in database.
        $configservice = new config_service(datatypes_service::get_all_datatypes());
        $configservice->setup_config();

        // Add new datatypes to export ad-hoc task..
        $exporttask = new export_adhoc_task();
        $exporttask->set_custom_data([
            'datatypes' => ['userinfocategories', 'userinfofields', 'userinfodatas']
        ]);
        \core\task\manager::queue_adhoc_task($exporttask);

        upgrade_plugin_savepoint(true, 2022051902, 'local', 'intellidata');
    }

    if ($oldversion < 2022052500) {

        // Set exportdataformat to the csv.
        set_config('exportdataformat', 'csv', 'local_intellidata');

        upgrade_plugin_savepoint(true, 2022052500, 'local', 'intellidata');
    }

    if ($oldversion < 2022053100) {
        // Add primary key, $dbman->add_key not working, error 'Primary Keys can be added at table create time only'.
        $DB->execute('ALTER TABLE {local_intellidata_export_ids}
                           ADD CONSTRAINT {local_intellidata_export_ids_primary}
                           PRIMARY KEY (datatype, dataid)');
        upgrade_plugin_savepoint(true, 2022053100, 'local', 'intellidata');
    }

    if ($oldversion < 2022053105) {

        // Update local_intellidata_export_log table.
        $table = new xmldb_table("local_intellidata_export_log");
        $field = new xmldb_field('recordsmigrated', XMLDB_TYPE_INTEGER, '11', null, false, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timestart', XMLDB_TYPE_INTEGER, '11', null, false, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('migrated', XMLDB_TYPE_INTEGER, '1', null, false, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('recordsmigrated', XMLDB_TYPE_INTEGER, '11', null, false, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('recordscount', XMLDB_TYPE_INTEGER, '11', null, false, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        // Update local_intellidata_storage table.
        $table = new xmldb_table("local_intellidata_storage");
        $field = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, false, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        // Update local_intellidata_tracking table.
        $table = new xmldb_table("local_intellidata_tracking");

        // Remove index from local_intellidata_tracking table.
        $index = new xmldb_index('userid_page_param_idx', XMLDB_INDEX_NOTUNIQUE, ['userid', 'page', 'param']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        // Remove key from local_intellidata_tracking table.
        $key = new xmldb_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $dbman->drop_key($table, $key);

        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('page', XMLDB_TYPE_CHAR, '100', null, false, null, '');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('param', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('firstaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        // Add key and index for local_intellidata_tracking table.
        $dbman->add_key($table, $key);
        $dbman->add_index($table, $index);

        // Update local_intellidata_trlogs table.
        $table = new xmldb_table("local_intellidata_trlogs");

        // Remove key from local_intellidata_trlogs table.
        $key = new xmldb_key('trackid', XMLDB_KEY_FOREIGN, ['trackid'], 'local_intellidata_tracking', ['id']);
        $dbman->drop_key($table, $key);

        // Remove index from local_intellidata_trlogs table.
        $index = new xmldb_index('trackid_timepoint_idx', XMLDB_INDEX_NOTUNIQUE, array('trackid', 'timepoint'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field('trackid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timepoint', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        // Add key and index for local_intellidata_trlogs table.
        $dbman->add_key($table, $key);
        $dbman->add_index($table, $index);

        // Update local_intellidata_trdetails table.
        $table = new xmldb_table("local_intellidata_trdetails");

        // Remove key from local_intellidata_trdetails table.
        $key = new xmldb_key('logid', XMLDB_KEY_FOREIGN, ['logid'], 'local_intellidata_trlogs', ['id']);
        $dbman->drop_key($table, $key);

        // Remove index from local_intellidata_trdetails table.
        $index = new xmldb_index('logid_timepoint_idx', XMLDB_INDEX_NOTUNIQUE, ['logid', 'timepoint']);
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $field = new xmldb_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('visits', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timespend', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        $field = new xmldb_field('timepoint', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        try {
            $dbman->change_field_type($table, $field);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        // Add key and index for local_intellidata_trdetails table.
        $dbman->add_key($table, $key);
        $dbman->add_index($table, $index);

        upgrade_plugin_savepoint(true, 2022053105, 'local', 'intellidata');
    }

    if ($oldversion < 2022053107) {

        $table = new xmldb_table("local_intellidata_export_ids");

        try {
            $key = new xmldb_key('datatype_dataid_unique', XMLDB_KEY_UNIQUE, ['datatype', 'dataid']);
            // Remove key from local_intellidata_export_ids table.
            $dbman->drop_key($table, $key);

            $field = new xmldb_field('datatype', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $dbman->change_field_type($table, $field);

            $field = new xmldb_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $dbman->change_field_type($table, $field);

            // Add key and index for local_intellidata_export_ids table.
            $dbman->add_key($table, $key);
        } catch (moodle_exception $e) {
            DebugHelper::error_log($e->getMessage());
        }

        // Adding keys to table local_intellidata_config.
        $table = new xmldb_table("local_intellidata_config");
        $key = new xmldb_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2022053107, 'local', 'intellidata');
    }

    if ($oldversion < 2022062800) {
        $table = new xmldb_table('local_intellidata_config');
        $field = new xmldb_field('params', XMLDB_TYPE_TEXT, null, null, false, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022062800, 'local', 'intellidata');
    }

    // Remove CURF from Optional tables, as it moved to Required tables.
    if ($oldversion < 2022063000) {

        $exportlogrepository = new export_log_repository();
        $exportservice = new export_service();

        $datatypestodelete = [
            'user_info_category',
            'user_info_data',
            'user_info_field'
        ];

        foreach ($datatypestodelete as $datatype) {

            // Delete old export files.
            $exportservice->delete_files([
                'datatype' => $datatype,
                'timemodified' => time()
            ]);

            // Delete export logs.
            if ($exportlogrepository->get_datatype_export_log($datatype)) {
                $exportlogrepository->remove_datatype($datatype);
            }

            // Delete configuration.
            if ($conf = datatypeconfig::get_record(['datatype' => $datatype])) {
                $conf->delete();
            }
        }

        upgrade_plugin_savepoint(true, 2022063000, 'local', 'intellidata');
    }

    // Divide quizquestionanswers to few datatypes.
    if ($oldversion < 2022080401) {

        $exportlogrepository = new export_log_repository();
        $exportservice = new export_service();

        $datatype = 'quizquestionanswers';

        // Delete old export files.
        $exportservice->delete_files([
            'datatype' => $datatype,
            'timemodified' => time()
        ]);

        // Delete export logs.
        if ($exportlogrepository->get_datatype_export_log($datatype)) {
            $exportlogrepository->remove_datatype($datatype);
        }

        // Delete configuration.
        if ($conf = datatypeconfig::get_record(['datatype' => $datatype])) {
            $conf->delete();
        }

        upgrade_plugin_savepoint(true, 2022080401, 'local', 'intellidata');
    }

    // Enable plugin by default for all existing connections.
    if ($oldversion < 2022080402) {

        set_config('ispluginsetup', 1, 'local_intellidata');

        upgrade_plugin_savepoint(true, 2022080402, 'local', 'intellidata');
    }

    if ($oldversion < 2022081000) {

        $exportlogrepository = new export_log_repository();
        $exportservice = new export_service();

        // Delete duplicated datatypes.
        $datatypestodelete = [
            'quizquestionattempt',
            'quizquestionattemptstep',
            'quizquestionattemptstepdata',
            'ltisubmissions'
        ];

        foreach ($datatypestodelete as $datatype) {
            $exportservice->delete_files([
                'datatype' => $datatype,
                'timemodified' => time()
            ]);

            // Delete export logs.
            if ($exportlogrepository->get_datatype_export_log($datatype)) {
                $exportlogrepository->remove_datatype($datatype);
            }

            // Delete configuration.
            if ($conf = datatypeconfig::get_record(['datatype' => $datatype])) {
                $conf->delete();
            }
        }

        // Add new LTI datatype.
        $exportlogrepository = new export_log_repository();
        $datatype = 'ltisubmittion';

        // Insert or update log record for datatype.
        $exportlogrepository->insert_datatype($datatype, export_logs::TABLE_TYPE_UNIFIED, true);

        // Add new datatypes to export ad-hoc task.
        $exporttask = new export_adhoc_task();
        $exporttask->set_custom_data([
            'datatypes' => [$datatype]
        ]);
        \core\task\manager::queue_adhoc_task($exporttask);

        upgrade_plugin_savepoint(true, 2022081000, 'local', 'intellidata');
    }

    // Reset and add new datatypes to the export.
    if ($oldversion < 2022081001) {

        $exportlogrepository = new export_log_repository();

        // Add new datatypes to the plugin config and export.
        $datatypes = [
            'quizquestionattempts',
            'quizquestionattemptsteps',
            'quizquestionattemptstepsdata'
        ];

        foreach ($datatypes as $datatype) {

            // Insert or update log record for datatype.
            $exportlogrepository->insert_datatype($datatype, export_logs::TABLE_TYPE_UNIFIED, true);

            // Add new datatypes to export ad-hoc task.
            $exporttask = new export_adhoc_task();
            $exporttask->set_custom_data([
                'datatypes' => [$datatype]
            ]);
            \core\task\manager::queue_adhoc_task($exporttask);
        }

        upgrade_plugin_savepoint(true, 2022081001, 'local', 'intellidata');
    }

    // Add new datatypes to the export.
    if ($oldversion < 2022103101) {

        // Add new LTI Types datatypes datatype.
        $exportlogrepository = new export_log_repository();
        $datatype = 'ltitypes';

        // Insert or update log record for datatype.
        $exportlogrepository->insert_datatype($datatype, export_logs::TABLE_TYPE_UNIFIED, true);

        // Add new datatypes to export ad-hoc task.
        $exporttask = new export_adhoc_task();
        $exporttask->set_custom_data([
            'datatypes' => [$datatype]
        ]);
        \core\task\manager::queue_adhoc_task($exporttask);

        upgrade_plugin_savepoint(true, 2022103101, 'local', 'intellidata');
    }

    // Reset and add new survey to the export.
    if ($oldversion < 2022110400) {

        $exportlogrepository = new export_log_repository();

        // Add new datatypes to the plugin config and export.
        $datatypes = [
            'survey',
            'surveyanswers',
        ];

        foreach ($datatypes as $datatype) {
            // Insert or update log record for datatype.
            $exportlogrepository->insert_datatype($datatype, export_logs::TABLE_TYPE_UNIFIED, true);

            // Add new datatypes to export ad-hoc task.
            $exporttask = new export_adhoc_task();
            $exporttask->set_custom_data([
                'datatypes' => [$datatype]
            ]);
            \core\task\manager::queue_adhoc_task($exporttask);
        }

        upgrade_plugin_savepoint(true, 2022110400, 'local', 'intellidata');
    }

    // Reset and add new survey to the export.
    if ($oldversion < 2022112905) {
        // Define table local_intellidata_export_ids to be created.
        $table = new xmldb_table('local_intellidata_export_ids');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        // Adding fields to table local_intellidata_export_ids.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('datatype', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, null, null, 0);

        // Adding keys to table local_intellidata_reports.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for local_intellidata_export_ids.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022112905, 'local', 'intellidata');
    }

    // Add indexes for tracking tables.
    if ($oldversion < 2022121500) {

        $tables = [
            'local_intellidata_tracking',
            'local_intellidata_trdetails',
            'local_intellidata_trlogs'
        ];

        // Add index to tables.
        foreach ($tables as $table) {
            $table = new xmldb_table($table);
            $index = new xmldb_index('timemodified_idx', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        upgrade_plugin_savepoint(true, 2022121500, 'local', 'intellidata');
    }

    if ($oldversion < 2022121501) {
        $table = new xmldb_table('local_intellidata_config');
        $field = new xmldb_field('tableindex', XMLDB_TYPE_CHAR, '100', null, null, null, null);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2022121501, 'local', 'intellidata');
    }

    // Reset and add new survey to the export.
    if ($oldversion < 2023010612) {

        $datatypes = datatypes_service::get_datatypes();
        try {
            foreach ($datatypes as $datatype) {
                if (isset($datatype['table'])) {
                    DBHelper::create_deleted_id_triger($datatype['name'], $datatype['table']);
                }
            }
            SettingsHelper::set_setting('trackingidsmode', export_id_repository::TRACK_IDS_MODE_TRIGGER);
        } catch (moodle_exception $e) {
            SettingsHelper::set_setting('trackingidsmode', export_id_repository::TRACK_IDS_MODE_REQUEST);
            DebugHelper::error_log($e->getMessage());
        }

        upgrade_plugin_savepoint(true, 2023010612, 'local', 'intellidata');
    }

    // Add new datatypes to the export.
    if ($oldversion < 2023020701) {

        // Add new LTI Types datatypes datatype.
        $exportlogrepository = new export_log_repository();
        $datatype = 'coursesections';

        // Insert or update log record for datatype.
        $exportlogrepository->insert_datatype($datatype, export_logs::TABLE_TYPE_UNIFIED, true);

        // Add new datatypes to export ad-hoc task.
        $exporttask = new export_adhoc_task();
        $exporttask->set_custom_data([
            'datatypes' => [$datatype]
        ]);
        \core\task\manager::queue_adhoc_task($exporttask);

        upgrade_plugin_savepoint(true, 2023020701, 'local', 'intellidata');
    }

    return true;
}

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
 * IntelliData migration report.
 *
 * @package    local_intellidata
 * @subpackage intellidata
 * @copyright  2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_intellidata\helpers\MigrationHelper;
use local_intellidata\services\export_service;
use local_intellidata\output\tables\migrations_table;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\TasksHelper;

require('../../../config.php');

$datatype     = optional_param('datatype', '', PARAM_ALPHA);
$action       = optional_param('action', '', PARAM_ALPHA);

require_login();

$context = context_system::instance();
require_capability('local/intellidata:viewlogs', $context);

$pageurl = new \moodle_url('/local/intellidata/migrations/index.php');
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout(SettingsHelper::get_page_layout());

if (!empty($action)) {
    require_sesskey();
}

if ($action == 'enablemigration') {
    // Reset migration.
    set_config('resetmigrationprogress', 1, 'local_intellidata');

    // Enable cron task.
    MigrationHelper::enabled_migration_task();

    redirect($pageurl, get_string('migrationenabled', 'local_intellidata'));
} else if ($action == 'calculateprogress') {
    TasksHelper::init_refresh_export_progress_adhoc_task();
    redirect($pageurl, get_string('calculateprogresssuccessmsg', 'local_intellidata'));
}

$title = get_string('migrations', 'local_intellidata');
$filenamefordownload = $title;

$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$exportservice = new export_service();
$datafiles = $exportservice->get_files();
$datatypes = $exportservice->datatypes;

$migrationstable = new migrations_table();
$migrationstable->generate($datafiles, $datatypes);

echo $migrationstable->out();

echo $OUTPUT->footer();


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
 * @package    local
 * @subpackage intellidata
 * @copyright  2020
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_intellidata\output\tables\exportfiles_table;
use local_intellidata\helpers\StorageHelper;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\services\database_service;
use local_intellidata\services\export_service;

require('../../../config.php');

$id           = optional_param('id', 0, PARAM_INT);
$action       = optional_param('action', '', PARAM_ALPHA);
$download     = optional_param('download', '', PARAM_ALPHA);
$query        = optional_param('query', '', PARAM_TEXT);

require_login();

if (!empty($action) || !empty($query)) {
    require_sesskey();
}

$context = context_system::instance();
require_capability('local/intellidata:viewlogs', $context);

$pageurl = new \moodle_url('/local/intellidata/logs/index.php', ['query' => $query]);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout(SettingsHelper::get_page_layout());

if ($id && $action == 'delete') {

    // Delete file by id.
    StorageHelper::delete_file($id);
    redirect($pageurl);

} else if ($action == 'export') {

    // Export static tables.
    $databaseservice = new database_service();
    $databaseservice->export_tables();

    // Export files to moodledata.
    $exportservice = new export_service();
    $exportservice->save_files();

    redirect($pageurl);
}

$title = get_string('exportfiles', 'local_intellidata');
$filenamefordownload = $title;

$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$params = ['download' => $download, 'query' => $query];
$table = new exportfiles_table('exportfiles_table', $params);
$table->is_downloading($download, $filenamefordownload, $filenamefordownload);

if ($download) {
    $table->out(20, true);
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$table->out(20, true);

echo $OUTPUT->footer();

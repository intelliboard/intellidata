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
 * SQL reports.
 *
 * @package    local_intellidata
 * @subpackage intellidata
 * @copyright  2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');

use local_intellidata\output\tables\sql_reports_table;
use local_intellidata\helpers\SettingsHelper;

require_login();

if (!is_siteadmin()) {
    throw new moodle_exception('invalidaccess', 'error');
}

$title = get_string('sqlreports', 'local_intellidata');
$pageurl = new \moodle_url('/local/intellidata/sql_reports/index.php');

$PAGE->set_url($pageurl);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout(SettingsHelper::get_page_layout());
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$table = new sql_reports_table('reports_table');
$table->show_download_buttons_at([]);
$table->is_downloading(false);
$table->is_collapsible = false;

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('sqlreports', 'local_intellidata'));

$table->out(20, true);

echo $OUTPUT->footer();

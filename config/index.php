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

use local_intellidata\output\tables\config_table;
use local_intellidata\services\config_service;
use local_intellidata\services\datatypes_service;
use local_intellidata\helpers\SettingsHelper;

require('../../../config.php');

$action = optional_param('action', '', PARAM_TEXT);

require_login();

$context = context_system::instance();
require_capability('local/intellidata:viewconfig', $context);
$pageurl = new \moodle_url('/local/intellidata/config/index.php');

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout(SettingsHelper::get_page_layout());

// Validate config table setup.
if (!empty($action)) {
    $configservice = new config_service(datatypes_service::get_all_datatypes());
    $configservice->setup_config($action == 'reset');
    redirect($pageurl, get_string('configurationsaved', 'local_intellidata'));
}

$title = get_string('configuration', 'local_intellidata');

$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$table = new config_table('config_table');

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

$table->out(30, true);

echo $OUTPUT->footer();

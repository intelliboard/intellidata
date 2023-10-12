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
 *
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://intelliboard.net/
 */

use local_intellidata\helpers\DebugHelper;
use local_intellidata\persistent\datatypeconfig;
use local_intellidata\services\datatypes_service;

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$longoptions = [
    'help'  => false
];
list($options, $unrecognized) = cli_get_params($longoptions, ['h' => 'help']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    // The indentation of this string is "wrong" but this is to avoid a extra whitespace in console output.
    $help = <<<EOF
Delete outdated records from intellidata storage and exported IDs.

The script is prepared for 1.128.0 upgrade to avoid deletion during upgrade.

Options:
-h, --help            Print out this help

Example:
\$ sudo -u www-data /usr/bin/php local/intellidata/cli/upgrade_1_128_0.php

EOF;

    echo $help;
    exit(0);
}

DebugHelper::enable_moodle_debug();

$notoptionalparams = [
    'tabletype' => datatypeconfig::TABLETYPE_REQUIRED,
    'tabletype2' => datatypeconfig::TABLETYPE_LOGS,
];

$requireddatatypes = $DB->get_records_sql("SELECT datatype
                                                 FROM {local_intellidata_config}
                                                WHERE tabletype = :tabletype OR tabletype = :tabletype2",
                                            $notoptionalparams);

$optionaldatatypes = $DB->get_records_sql("SELECT datatype
                                                 FROM {local_intellidata_config}
                                                WHERE tabletype != :tabletype AND tabletype != :tabletype2",
                                            $notoptionalparams);

$olddatatypes = array_map(function ($item) use ($requireddatatypes) {
    $datatype = datatypes_service::get_optional_table($item->datatype);
    return in_array($datatype, array_keys($requireddatatypes)) ? null : $datatype;
}, $optionaldatatypes);

$olddatatypes = array_filter($olddatatypes);

// Include user tracking.
$trackingdatatypes = ['tracking' => 'tracking', 'trackinglog' => 'trackinglog', 'trackinglogdetail' => 'trackinglogdetail'];

$olddatatypes = array_merge($olddatatypes, $trackingdatatypes);

[$datatypesinsql, $datatypesinparams] = $DB->get_in_or_equal($olddatatypes);

mtrace("Cleaning local_intellidata_export_ids table started...");
$difftime = microtime_diff(microtime(), microtime());
$DB->delete_records_select('local_intellidata_export_ids', "datatype $datatypesinsql", $datatypesinparams);
mtrace("Cleaning local_intellidata_export_ids table completed.");
mtrace("Execution took " . $difftime . " seconds.");
mtrace("-------------------------------------------");

mtrace("Cleaning local_intellidata_storage table started...");
$difftime = microtime_diff(microtime(), microtime());
$DB->delete_records_select('local_intellidata_storage', "datatype $datatypesinsql", $datatypesinparams);
mtrace("Cleaning local_intellidata_storage table completed.");
mtrace("Execution took " . $difftime . " seconds.");
mtrace("-------------------------------------------");

exit(0);

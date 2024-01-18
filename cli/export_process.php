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

use local_intellidata\helpers\ExportHelper;
use local_intellidata\helpers\DebugHelper;
use local_intellidata\helpers\TrackingHelper;
use local_intellidata\services\export_service;

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

raise_memory_limit(MEMORY_HUGE);

$longoptions = [
    'datatype' => '',
    'help'  => false,
];
list($options, $unrecognized) = cli_get_params($longoptions, ['h' => 'help']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    // The indentation of this string is "wrong" but this is to avoid a extra whitespace in console output.
    $help = <<<EOF
Invalidates Moodle internal caches

Specific caches can be defined (alone or in combination) using arguments. If none are specified,
all caches will be purged.

Options:
-h, --help            Print out this help
    --datatype        Datatype name to export data

Example:
\$ sudo -u www-data /usr/bin/php local/intellidata/cli/export_process.php

EOF;

    echo $help;
    exit(0);
}

// Validate if plugin is enabled and configured.
if (!TrackingHelper::enabled()) {
    mtrace(get_string('pluginnotconfigured', 'local_intellidata'));
    exit(0);
}

DebugHelper::enable_moodle_debug();

$params = [];
if (!empty($options['datatype'])) {
    $params['datatype'] = $options['datatype'];
}

// Export static tables.
$exportparams = null;
if (!empty($params['datatype'])) {
    $params['table'] = $params['datatype'];
}

$exportservice = new export_service();
ExportHelper::process_export($exportservice, $params);

mtrace("Export process complete.");

exit(0);

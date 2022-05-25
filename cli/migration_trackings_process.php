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
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://intelliboard.net/
 */

use local_intellidata\services\export_service;
use local_intellidata\services\migration_service;
use local_intellidata\repositories\export_log_repository;
use local_intellidata\helpers\MigrationHelper;
use local_intellidata\helpers\DebugHelper;
use local_intellidata\helpers\TrackingHelper;

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$longoptions = [
    'help'      => false
];
list($options, $unrecognized) = cli_get_params($longoptions, ['h' => 'help']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized), 2);
}

if ($options['help']) {
    // The indentation of this string is "wrong" but this is to avoid a extra whitespace in console output.
    $help = <<<EOF
InvaliData Copy Intelliboard tracking data to IntelliData tables

Specific caches can be defined (alone or in combination) using arguments. If none are specified,
all caches will be purged.

Options:
-h, --help            Print out this help

Example:
\$ sudo -u www-data /usr/bin/php local/intellidata/cli/migration_trackings_process.php

EOF;

    echo $help;
    exit(0);
}

DebugHelper::enable_moodle_debug();
TrackingHelper::disable_tracking();

mtrace("Start import user trackings from IntelliBoard plugin!");
$trackingfixmapper = local_intellidata\helpers\UpgradeHelper::copy_intelliboard_tracking();
mtrace("Tracking table imported!");

mtrace("Start import user tracking logs from IntelliBoard plugin!");
local_intellidata\helpers\UpgradeHelper::copy_intelliboard_logs($trackingfixmapper);
mtrace("Tracking logs table imported!");

mtrace("Start import user tracking log details from IntelliBoard plugin!");
local_intellidata\helpers\UpgradeHelper::copy_intelliboard_details();
mtrace("Tracking log details table imported!");

TrackingHelper::enable_tracking();

exit(0);

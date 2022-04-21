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

use local_intellidata\output\forms\local_intellidata_edit_config;
use local_intellidata\persistent\datatypeconfig;
use local_intellidata\services\datatypes_service;
use local_intellidata\services\config_service;

require('../../../config.php');

$datatype = required_param('datatype', PARAM_TEXT);

require_login();

$context = context_system::instance();
require_capability('local/intellidata:editconfig', $context);

$returnurl = new \moodle_url('/local/intellidata/config/index.php');
$pageurl = new \moodle_url('/local/intellidata/config/edit.php', ['datatype' => $datatype]);
$PAGE->set_url($pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_context($context);

$record = datatypeconfig::get_record(['datatype' => $datatype]);

if (!$record || $record->get('tabletype') == datatypeconfig::TABLETYPE_REQUIRED) {
    print_error('wrongdatatype', 'local_intellidata');
}

$datatypeconfig = datatypes_service::get_datatype($datatype);
$datatypeconfig['timemodifiedfields'] = config_service::get_available_timemodified_fields($datatype);

$title = get_string('editconfigfor', 'local_intellidata', $datatype);

$PAGE->navbar->add(get_string('configuration', 'local_intellidata'), $returnurl);
$PAGE->navbar->add($title);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$editform = new local_intellidata_edit_config(null, ['data' => $record->to_record(), 'config' => (object)$datatypeconfig]);

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {

    if (!empty($data->reset)) {
        $data = (new config_service)->create_config($datatype, $datatypeconfig);
        $returnurl = $pageurl;
    } else {
        // Validate export rules.
        if (!empty($data->timemodified_field)) {
            if (!isset($datatypeconfig['timemodifiedfields'][$data->timemodified_field])) {
                $data->timemodified_field = '';
                $data->filterbyid = datatypeconfig::STATUS_DISABLED;
                $data->rewritable = datatypeconfig::STATUS_ENABLED;
            } else {
                $data->filterbyid = datatypeconfig::STATUS_DISABLED;
                $data->rewritable = datatypeconfig::STATUS_DISABLED;
            }
        } else if ($data->filterbyid) {
            $data->timemodified_field = '';
            $data->rewritable = datatypeconfig::STATUS_DISABLED;
        } else if ($data->rewritable) {
            $data->timemodified_field = '';
            $data->filterbyid = datatypeconfig::STATUS_DISABLED;
        } else {
            $data->rewritable = datatypeconfig::STATUS_ENABLED;
        }

        $record->set('events_tracking', (!empty($data->events_tracking)) ? datatypeconfig::STATUS_ENABLED : datatypeconfig::STATUS_DISABLED);
        $record->set('timemodified_field', $data->timemodified_field);
        $record->set('filterbyid', $data->filterbyid);
        $record->set('rewritable', $data->rewritable);
        $record->set('status', $data->status);
        $record->save();
    }

    redirect($returnurl, get_string('configurationsaved', 'local_intellidata'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($title);

echo $editform->display();

echo $OUTPUT->footer();
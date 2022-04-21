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
 * Observer
 *
 * @package    local_intellidata
 * @author     IntelliBoard
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intellidata\entities\roles;

defined('MOODLE_INTERNAL') || die();

use local_intellidata\entities\roles\roleassignment;
use local_intellidata\helpers\TrackingHelper;
use local_intellidata\helpers\RolesHelper;
use local_intellidata\services\events_service;

/**
 * Event observer for transcripts.
 */
class observer {

    /**
     * Triggered when 'role_assigned' event is triggered.
     *
     * @param \core\event\role_assigned $event
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        if (TrackingHelper::enabled()) {
            self::export_event($event);
        }
    }

    /**
     * Triggered when 'role_unassigned' event is triggered.
     *
     * @param \core\event\role_unassigned $event
     */
    public static function role_unassigned(\core\event\role_unassigned $event) {
        if (TrackingHelper::enabled()) {
            self::export_event($event);
        }
    }

    private static function export_event($event) {
        $eventdata = $event->get_data();
        $context = $event->get_context();

        if (!in_array($context->contextlevel, array_keys(RolesHelper::CONTEXTLIST)) or
            !isset($eventdata['other']['id'])) {
            return;
        }

        $roleassignments = $event->get_record_snapshot('role_assignments', $eventdata['other']['id']);

        $ra = new \stdClass();
        $ra->id = $roleassignments->id;
        $ra->courseid = $eventdata['contextinstanceid'];
        $ra->userid = $eventdata['relateduserid'];
        $ra->roleid = $eventdata['objectid'];
        $ra->crud = $eventdata['crud'];
        $ra->contexttype = RolesHelper::get_contexttype($context->contextlevel);

        $entity = new roleassignment($ra, []);
        $data = $entity->export();
        $data->eventname = $eventdata['eventname'];

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

}


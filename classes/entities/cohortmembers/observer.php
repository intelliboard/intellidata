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

namespace local_intellidata\entities\cohortmembers;

defined('MOODLE_INTERNAL') || die();

use \local_intellidata\helpers\TrackingHelper;
use \local_intellidata\services\events_service;

/**
 * Event observer for transcripts.
 */
class observer {

    /**
     * Triggered when 'cohort_member_added' event is triggered.
     *
     * @param \core\event\cohort_member_added $event
     */
    public static function cohort_member_added(\core\event\cohort_member_added $event) {
        if (TrackingHelper::eventstracking_enabled()) {
            $eventdata = $event->get_data();

            $cohortmember = new \stdClass();
            $cohortmember->cohortid = $eventdata['objectid'];
            $cohortmember->userid = $eventdata['relateduserid'];
            $cohortmember->timeadded = $eventdata['timecreated'];

            self::export_event($cohortmember, $eventdata);
        }
    }

    /**
     * Triggered when 'cohort_member_removed' event is triggered.
     *
     * @param \core\event\cohort_member_removed $event
     */
    public static function cohort_member_removed(\core\event\cohort_member_removed $event) {
        if (TrackingHelper::eventstracking_enabled()) {
            $eventdata = $event->get_data();

            $cohortmember = new \stdClass();
            $cohortmember->cohortid = $eventdata['objectid'];
            $cohortmember->userid = $eventdata['relateduserid'];

            self::export_event($cohortmember, $eventdata);
        }
    }

    private static function export_event($cohortmemberdata, $eventdata, $fields = []) {
        $cohortmemberdata->crud = $eventdata['crud'];

        $entity = new cohortmember($cohortmemberdata, $fields);
        $data = $entity->export();
        $data->eventname = $eventdata['eventname'];

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

}


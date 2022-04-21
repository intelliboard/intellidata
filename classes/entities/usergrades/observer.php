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

namespace local_intellidata\entities\usergrades;

defined('MOODLE_INTERNAL') || die();

use local_intellidata\helpers\TrackingHelper;
use local_intellidata\services\events_service;

/**
 * Event observer for transcripts.
 */
class observer {

    /**
     * Triggered when 'course_completed' event is triggered.
     *
     * @param \core\event\user_graded $event
     */
    public static function user_graded(\core\event\user_graded $event) {
        global $CFG;

        if (TrackingHelper::enabled()) {

            require_once($CFG->libdir . '/gradelib.php');

            $eventdata = $event->get_data();

            $gradeobject = $event->get_record_snapshot($eventdata['objecttable'], $eventdata['objectid']);
            $gradeitem = \grade_item::fetch(array('id' => $gradeobject->itemid));

            $data = self::prepare_data($gradeobject, $gradeitem);

            self::export_event($data, $eventdata);
        }
    }

    public static function prepare_data($gradeobject, $gradeitem) {
        // Each user have own grade max and grade min.
        $gradeitem->grademax = $gradeobject->rawgrademax;
        $gradeitem->grademin = $gradeobject->rawgrademin;

        $score = grade_format_gradevalue($gradeobject->finalgrade, $gradeitem, true, GRADE_DISPLAY_TYPE_PERCENTAGE);
        $data = new \stdClass();
        $data->id = $gradeobject->id;
        $data->gradeitemid = $gradeobject->itemid;
        $data->userid = $gradeobject->userid;
        $data->usermodified = $gradeobject->usermodified;
        $data->letter = grade_format_gradevalue($gradeobject->finalgrade, $gradeitem, true, GRADE_DISPLAY_TYPE_LETTER);
        $data->score = str_replace(' %', '', $score);
        $data->point = ($gradeitem->gradetype == GRADE_TYPE_SCALE) ?
                            $gradeitem->bounded_grade($gradeobject->finalgrade) :
                            grade_format_gradevalue($gradeobject->finalgrade, $gradeitem, true, GRADE_DISPLAY_TYPE_REAL);
        $data->feedback = $gradeobject->feedback;
        $data->hidden = $gradeobject->hidden;
        $data->timemodified = $gradeobject->timemodified;

        return $data;
    }

    private static function export_event($data, $eventdata, $fields = []) {
        $data->crud = $eventdata['crud'];

        $entity = new usergrade($data, $fields);
        $data = $entity->export();
        $data->eventname = $eventdata['eventname'];

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

}


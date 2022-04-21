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

namespace local_intellidata\entities\assignments;

defined('MOODLE_INTERNAL') || die();

use \local_intellidata\entities\assignments\submission;
use \local_intellidata\helpers\TrackingHelper;
use \local_intellidata\services\events_service;

/**
 * Event observer for transcripts.
 */
class observer {

    /**
     * Triggered when 'submission_created' event is triggered.
     *
     * @param \mod_assign\event\submission_created $event
     */
    public static function submission_created(\mod_assign\event\submission_created $event) {
        global $DB;

        if (TrackingHelper::enabled()) {
            $eventdata = $event->get_data();
            $submission = $DB->get_record('assign_submission', ['id' => $eventdata['other']['submissionid']]);
            $submission->submission_type = str_replace('assignsubmission_', '', $eventdata['objecttable']);

            self::export_event($eventdata, $submission);
        }
    }

    /**
     * Triggered when 'submission_updated' event is triggered.
     *
     * @param \mod_assign\event\submission_updated $event
     */
    public static function submission_updated(\mod_assign\event\submission_updated $event) {
        global $DB;

        if (TrackingHelper::enabled()) {
            $eventdata = $event->get_data();

            $submission = $DB->get_record('assign_submission', ['id' => $eventdata['other']['submissionid']]);
            $submission->submission_type = str_replace('assignsubmission_', '', $eventdata['objecttable']);

            self::export_event($eventdata, $submission);
        }
    }

    /**
     * Triggered when 'submission_duplicated' event is triggered.
     *
     * @param \mod_assign\event\submission_duplicated $event
     */
    public static function submission_duplicated(\mod_assign\event\submission_duplicated $event) {
        global $DB;

        if (TrackingHelper::enabled()) {
            $eventdata = $event->get_data();

            $submission = $event->get_record_snapshot($eventdata['objecttable'], $eventdata['objectid']);

            self::export_event($eventdata, $submission);
        }
    }

    /**
     * Triggered when 'assessable_submitted' event is triggered.
     *
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assessable_submitted(\mod_assign\event\assessable_submitted $event) {
        if (TrackingHelper::enabled()) {
            $eventdata = $event->get_data();

            $submission = $event->get_record_snapshot($eventdata['objecttable'], $eventdata['objectid']);

            self::export_event($eventdata, $submission);
        }
    }

    /**
     * Triggered when 'submission_graded' event is triggered.
     *
     * @param \mod_assign\event\submission_graded $event
     */
    public static function submission_graded(\mod_assign\event\submission_graded $event) {
        global $DB, $USER;

        if (TrackingHelper::enabled()) {

            $eventdata = $event->get_data();
            $gradedata = $event->get_record_snapshot($eventdata['objecttable'], $eventdata['objectid']);
            $submission = $DB->get_record('assign_submission', [
                'assignment' => $gradedata->assignment,
                'userid' => $gradedata->userid,
                'attemptnumber' => $gradedata->attemptnumber
            ]);
            $submission->grade = ((float)$gradedata->grade > 0) ? $gradedata->grade : 0;
            $submission->feedback_at = $gradedata->timemodified;
            $submission->feedback_by = $gradedata->grader;

            $feedback = $DB->get_record('assignfeedback_comments', [
                'assignment' => $gradedata->assignment,
                'grade' => $gradedata->id
            ]);
            if (!empty($feedback->commenttext)) {
                $submission->feedback = $feedback->commenttext;
            }

            self::export_event($eventdata, $submission);
        }
    }

    /**
     * Triggered when 'submission_status_updated' event is triggered.
     *
     * @param \mod_assign\event\submission_status_updated $event
     */
    public static function submission_status_updated(\mod_assign\event\submission_status_updated $event) {
        global $DB;

        if (TrackingHelper::enabled()) {

            $eventdata = $event->get_data();
            $submission = $event->get_record_snapshot($eventdata['objecttable'], $eventdata['objectid']);
            $gradedata = $DB->get_record('assign_grades', [
                'assignment' => $submission->assignment,
                'userid' => $submission->userid,
                'attemptnumber' => $submission->attemptnumber
            ]);

            if (!empty($gradedata->grade)) {
                $submission->grade = $gradedata->grade;
                $submission->feedback_at = $gradedata->timemodified;
                $submission->feedback_by = $gradedata->grader;

                $feedback = $DB->get_record('assignfeedback_comments', [
                    'assignment' => $gradedata->assignment,
                    'grade' => $gradedata->id
                ]);
                if (!empty($feedback->commenttext)) {
                    $submission->feedback = $feedback->commenttext;
                }
            }

            self::export_event($eventdata, $submission);
        }
    }

    private static function export_event($eventdata, $submission, $fields = []) {
        $entity = new submission($submission, $fields);
        $data = $entity->export();
        $data->eventname = $eventdata['eventname'];
        $data->crud = $eventdata['crud'];

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

}


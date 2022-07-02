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

namespace local_intellidata\entities\quizzes;

defined('MOODLE_INTERNAL') || die();

use \local_intellidata\entities\quizzes\attempt;
use \local_intellidata\helpers\TrackingHelper;
use \local_intellidata\services\events_service;

/**
 * Event observer for transcripts.
 */
class observer {

    /**
     * Triggered when 'attempt_started' event is triggered.
     *
     * @param \mod_quiz\event\attempt_started $event
     */
    public static function attempt_started(\mod_quiz\event\attempt_started $event) {
        if (TrackingHelper::enabled()) {
            self::export_event($event);
        }
    }

    /**
     * Triggered when 'attempt_submitted' event is triggered.
     *
     * @param \mod_quiz\event\attempt_submitted $event
     */
    public static function attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        if (TrackingHelper::enabled()) {
            self::export_event($event);
        }
    }

    private static function export_event($event, $fields = []) {
        $eventdata = $event->get_data();
        $attempt = $event->get_record_snapshot($eventdata['objecttable'], $eventdata['objectid']);
        $quiz = $event->get_record_snapshot('quiz', $attempt->quiz);
        $attempt->crud = $eventdata['crud'];
        $attempt->sumgrades = ($quiz->sumgrades)
            ? (($attempt->sumgrades / $quiz->sumgrades) * $quiz->grade)
            : 0;

        $entity = new attempt($attempt, $fields);
        $data = $entity->export();
        $data->eventname = $eventdata['eventname'];

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

}


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
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\repositories;

class bbb_repository {
    const RECORDS_TRACKING_PERIOD = DAYSECS; // Seconds.

    public function meeting_records_tracking($meetingid) {
        global $DB;

        return $DB->get_record_sql(
            "SELECT *
               FROM {local_intellidata_bbb_rec_tr}
              WHERE session_id = ? AND tracked_at IS NULL",
            [$meetingid]
        );
    }

    public function create_meeting_records_tracking($meetingid) {
        global $DB;

        $utcdate = new \DateTime('now UTC');

        $DB->insert_record('local_intellidata_bbb_rec_tr', [
            'session_id' => $meetingid,
            'track_at' => $utcdate->getTimestamp() + self::RECORDS_TRACKING_PERIOD,
        ]);
    }

    public function renew_meeting_records_tracking($id) {
        global $DB;

        $utcdate = new \DateTime('now UTC');

        $DB->update_record('local_intellidata_bbb_rec_tr', (object) [
            'id' => $id,
            'track_at' => $utcdate->getTimestamp() + self::RECORDS_TRACKING_PERIOD,
        ]);
    }

    public function get_meetings_for_records_tracking() {
        global $DB;

        $utcdate = new \DateTime('now UTC');

        return $DB->get_records_sql(
            "SELECT *
               FROM {local_intellidata_bbb_rec_tr}
              WHERE track_at < ? AND tracked_at IS NULL",
            [$utcdate->getTimestamp()]
        );
    }

    public function track_meeting_records($id) {
        global $DB;

        $utcdate = new \DateTime('now UTC');

        $DB->update_record('local_intellidata_bbb_rec_tr', (object) [
            'id' => $id,
            'tracked_at' => $utcdate->getTimestamp(),
        ]);
    }
}

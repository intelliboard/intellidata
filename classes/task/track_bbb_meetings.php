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

namespace local_intellidata\task;

use local_intellidata\services\export_service;
use local_intellidata\adapters\big_blue_button_adapter;
use local_intellidata\repositories\bbb_repository;

class track_bbb_meetings extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('check_active_meetings', 'local_intellidata');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;

        if (!get_config('local_intellidata', 'enablebbbmeetings')) {
            return false;
        }

        $adapter = new big_blue_button_adapter();
        $bbbrepository = new bbb_repository();
        $exportservice = new export_service();

        $activemeetings = $adapter->get_active_meetings();

        foreach ($activemeetings as $meeting) {
            // Meeting ID without course id and cmid.
            $puremeetingid = explode('-', $meeting->meetingID->__toString())[0];

            // Skip if meeting not created from Moodle system.
            if (!$DB->record_exists('bigbluebuttonbn', ['meetingid' => $puremeetingid])) {
                continue;
            }

            // Save meeting data.
            $exportservice->store_data('bbbmeetings', json_encode([
                'id' => $meeting->meetingID->__toString(),
                'name' => $meeting->meetingName->__toString(),
                'create_time' => $meeting->createTime->__toString(),
                'create_date' => $meeting->createDate->__toString(),
                'has_user_joined' => $meeting->hasUserJoined->__toString(),
                'recording' => $meeting->recording->__toString(),
                'start_time' => $meeting->startTime->__toString(),
                'participant_count' => $meeting->participantCount->__toString(),
                'listener_count' => $meeting->listenerCount->__toString(),
                'voice_participant_count' => $meeting->voiceParticipantCount->__toString(),
                'video_count' => $meeting->videoCount->__toString(),
                'moderator_count' => $meeting->moderatorCount->__toString(),
                'track_time' => time(),
            ]));

            $attendees = [];

            foreach ($meeting->attendees->attendee as $attendee) {
                $attendees[] = json_encode([
                    'meeting_id' => $meeting->meetingID->__toString(),
                    'user_id' => $attendee->userID->__toString(),
                    'role' => $attendee->role->__toString(),
                    'is_presenter' => $attendee->isPresenter->__toString(),
                    'is_listening_only' => $attendee->isListeningOnly->__toString(),
                    'has_joined_voice' => $attendee->hasJoinedVoice->__toString(),
                    'has_video' => $attendee->hasVideo->__toString(),
                    'client_type' => $attendee->clientType->__toString(),
                    'track_time' => time(),
                ]);
            }

            $exportservice->store_data('bbbattendees', implode(PHP_EOL, $attendees));

            $recordingstracking = $bbbrepository->meeting_records_tracking($meeting->meetingID->__toString());

            if (!$recordingstracking) {
                $bbbrepository->create_meeting_records_tracking($meeting->meetingID->__toString());
            } else {
                $bbbrepository->renew_meeting_records_tracking($recordingstracking->id);
            }
        }

        // Track meetings recordings.
        $forrecordstracking = $bbbrepository->get_meetings_for_records_tracking();

        foreach ($forrecordstracking as $item) {
            $recordings = $adapter->get_meetings_records($item->session_id);
            $recordingsformatted = [];

            foreach ($recordings->recordings->recording as $recording) {
                $recordingsformatted[] = json_encode([
                    'id' => $recording->recordID->__toString(),
                    'meeting_id' => $recording->meetingID->__toString(),
                    'name' => $recording->name->__toString(),
                    'size' => $recording->size->__toString(),
                    'url' => $recording->playback->format->url->__toString()
                ]);
            }

            if ($recordingsformatted) {
                $exportservice->store_data('bbbrecordings', implode(PHP_EOL, $recordingsformatted));
            }

            $bbbrepository->track_meeting_records($item->id);
        }
    }
}

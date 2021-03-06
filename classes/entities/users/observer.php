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

namespace local_intellidata\entities\users;

defined('MOODLE_INTERNAL') || die();

use \local_intellidata\entities\users\user;
use \local_intellidata\helpers\TrackingHelper;
use \local_intellidata\helpers\ParamsHelper;
use \local_intellidata\services\events_service;

/**
 * Event observer for transcripts.
 */
class observer {

    /**
     * Triggered when 'user_created' event is triggered.
     *
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event) {
        if (TrackingHelper::enabled()) {
            $eventdata = $event->get_data();

            $user = $event->get_record_snapshot('user', $eventdata['objectid']);
            $user->fullname = fullname($user);
            $user->state = ($user->confirmed && !$user->suspended) ?
                ParamsHelper::STATE_ACTIVE : ParamsHelper::STATE_INACTIVE;
            $user->lastlogin = max($user->lastlogin, $user->currentlogin);

            self::export_event($user, $eventdata);
        }
    }

    /**
     * Triggered when 'user_updated' event is triggered.
     *
     * @param \core\event\user_updated $event
     */
    public static function user_updated(\core\event\user_updated $event) {
        if (TrackingHelper::enabled()) {
            $eventdata = $event->get_data();

            $user = $event->get_record_snapshot('user', $eventdata['objectid']);
            $user->fullname = fullname($user);
            $user->state = ($user->confirmed && !$user->suspended) ?
                ParamsHelper::STATE_ACTIVE : ParamsHelper::STATE_INACTIVE;
            $user->lastlogin = max($user->lastlogin, $user->currentlogin);

            self::export_event($user, $eventdata);
        }
    }

    /**
     * Triggered when 'user_deleted' event is triggered.
     *
     * @param \core\event\user_deleted $event
     */
    public static function user_deleted(\core\event\user_deleted $event) {
        if (TrackingHelper::enabled()) {
            $eventdata = $event->get_data();

            $user = new \stdClass();
            $user->id = $eventdata['objectid'];

            self::export_event($user, $eventdata);
        }
    }

    private static function export_event($userdata, $eventdata, $fields = []) {
        $userdata->crud = $eventdata['crud'];

        $entity = new user($userdata, $fields);
        $data = $entity->export();
        $data->eventname = $eventdata['eventname'];

        $tracking = new events_service($entity::TYPE);
        $tracking->track($data);
    }

}


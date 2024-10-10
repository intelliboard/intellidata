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
 * @copyright  2024 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intellidata\entities\syslogs;

use local_intellidata\repositories\export_log_repository;
use local_intellidata\services\events_service;

/**
 * Event observer for transcripts.
 */
class observer {

    /**
     * Export event.
     *
     * @param string $message
     * @throws \core\invalid_persistent_exception
     */
    public static function export_event($message) {
        if ((new export_log_repository())->get_datatype_export_log(syslogs::TYPE)) {
            $data = new \stdClass();
            $data->timecreated = time();
            $data->message = $message;

            $entity = new syslogs($data);
            $data = $entity->export();

            $tracking = new events_service($entity::TYPE);
            $tracking->track($data);
        }
    }
}


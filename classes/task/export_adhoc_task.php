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
 *
 * @package    local_intellidata
 * @category   task
 * @author     IntelliBoard Inc.
 * @copyright  2022 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intellidata\task;
defined('MOODLE_INTERNAL') || die();

use local_intellidata\services\encryption_service;
use local_intellidata\services\export_service;
use local_intellidata\services\database_service;
use local_intellidata\helpers\TrackingHelper;

/**
 * Task to process datafiles export for specific datatype.
 *
 * @package    local_intellidata
 * @author     IntelliBoard Inc.
 * @copyright  2022 IntelliBoard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_adhoc_task extends \core\task\adhoc_task {

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {

        if (TrackingHelper::enabled()) {
            mtrace("IntelliData Data Files Export CRON started!");

            $data = $this->get_custom_data();
            $encryptionservice = new encryption_service();
            $exportservice = new export_service();

            foreach ($data->datatypes as $datatype) {
                // Delete old files.
                $exportservice->delete_files([
                    'datatype' => $datatype,
                    'timemodified' => time()
                ]);
            }

            // Export static tables.
            $databaseservice = new database_service();
            $databaseservice->export_tables();

            // Export files to moodledata.
            $exportservice->save_files();

            // Send callback when files ready.
            if (!empty($data->callbackurl)) {
                $client = new \curl();
                $client->post($data->callbackurl, ['data' => $encryptionservice->encrypt(json_encode(['datatypes' => $data->datatypes]))]);
            }

            mtrace("IntelliData Data Files Export CRON ended!");
        }
    }
}

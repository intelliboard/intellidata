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
 *
 * @package    local_intellidata
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\repositories\tracking;


class tracking_storage_repository {

    const STORAGE_FOLDER_NAME       = 'local_intellidata/tracking';
    const STORAGE_FILES_COMPONENT   = 'local_intellidata';
    const STORAGE_FILE_TYPE         = 'csv';

    public $storagefolder   = null;
    public $storagefile     = null;
    protected $userid       = null;

    public function __construct($userid = null) {
        $this->userid = $userid;
        $this->storagefolder = self::prepare_storage_folder();
        $this->storagefile = self::get_storage_file();
    }

    protected function prepare_storage_folder() {
        return make_temp_directory(self::STORAGE_FOLDER_NAME);
    }

    public function get_storage_folder() {
        return $this->storagefolder;
    }

    public function get_storage_file() {
        return $this->storagefolder . '/' . $this->userid . '.' . self::STORAGE_FILE_TYPE;
    }

    public function save_data($data) {
        file_put_contents($this->storagefile, $data . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function get_files($params = []) {
        return array_diff(scandir($this->storagefolder), ['..', '.', '.DS_Store']);
    }

    public function delete_file($filename) {
        unlink($this->storagefolder . '/' . $filename);
    }

    public function delete_filepath($filepath) {
        unlink($filepath);
    }

    public function rename_file($filename) {
        if (rename($this->storagefolder . '/' . $filename, $this->storagefolder . '/' . $filename . '_temp')) {
            return $this->storagefolder . '/' . $filename . '_temp';
        }
        return null;
    }

    /**
     * Read and return data from file.
     *
     * @param $tempfilepath
     * @return array
     */
    public function get_usersdata_from_file($filename, $showdebug = true) {

        $data = [];

        list($userid, $extension) = explode('.', $filename);

        if (!is_numeric($userid) || $extension != self::STORAGE_FILE_TYPE) {
            // Something wrong.
            if ($showdebug) {
                mtrace("Incorrect file " . $filename);
            }
            $this->delete_file($filename);
            return $data;
        }

        $tempfilepath = $this->rename_file($filename);
        if (!$tempfilepath) {
            // Something wrong.
            if ($showdebug) {
                mtrace("Error rename file " . $filename);
            }
            return $data;
        }

        $handle = @fopen($tempfilepath, "r");
        if ($handle) {
            while (($buffer = fgets($handle)) !== false) {
                $record = json_decode($buffer);

                if ($record->table == 'tracking') {
                    if (isset($data[$record->userid][$record->page][$record->param][$record->table])) {
                        $item = &$data[$record->userid][$record->page][$record->param][$record->table];
                        if (isset($record->visits)) {
                            @$item['visits'] += $record->visits;
                        }
                        $item['timespend'] += $record->timespend;
                        $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                    } else {
                        $data[$record->userid][$record->page][$record->param][$record->table] = (array) $record;
                    }
                } else if ($record->table == 'logs') {
                    if (isset($data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint])) {
                        $item = &$data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint];
                        if (isset($record->visits)) {
                            @$item['visits'] += $record->visits;
                        }
                        $item['timespend'] += $record->timespend;
                        $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                    } else {
                        $data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint] =
                            (array)$record;
                    }
                } else if ($record->table == 'details') {
                    $recordtable = isset($data[$record->userid][$record->page][$record->param][$record->table]) ?
                        $data[$record->userid][$record->page][$record->param][$record->table] : [];
                    if (isset($recordtable[$record->currentstamp][$record->timepoint])) {
                        $item = &
                            $recordtable[$record->currentstamp][$record->timepoint];
                        if (isset($record->visits)) {
                            @$item['visits'] += $record->visits;
                        }
                        $item['timespend'] += $record->timespend;
                        $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                    } else {
                        $recordtable[$record->currentstamp][$record->timepoint] = (array)$record;
                    }
                }
            }
            if (!feof($handle) && $showdebug) {
                mtrace("Error reading file " . $filename);
            }
            fclose($handle);
        }

        // Delete temp file.
        $this->delete_filepath($tempfilepath);

        return $data;
    }

    /**
     * Prepare userdata array.
     *
     * @param $userdata
     * @return array
     */

    public function prepare_usersdata($userdata) {

        $data = [];

        foreach ($userdata as $pagerecords) {
            foreach ($pagerecords as $recordarr) {
                foreach ($recordarr as $record) {
                    $data[] = $record;
                }
            }
        }

        return $data;
    }
}

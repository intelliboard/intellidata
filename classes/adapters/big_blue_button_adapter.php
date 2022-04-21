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
 * @package    local_intellidata
 * @copyright  2021 Intelliboard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    https://intelliboard.net/
 */

namespace local_intellidata\adapters;

class big_blue_button_adapter {
    private $apiendpoint;
    private $bbbserversecret;

    public function __construct() {
        global $CFG;

        require_once($CFG->dirroot . '/lib/filelib.php');

        $this->apiendpoint = rtrim(get_config('local_intellidata', 'bbbapiendpoint'), '/') . '/api';
        $this->bbbserversecret = trim(get_config('local_intellidata', 'bbbserversecret'));

        if (!$this->apiendpoint or !$this->bbbserversecret) {
            throw new \Exception('Please set BBB server secret and endpoint');
        }
    }

    /**
     * Return XML objects - list of active meetings
     * @return \SimpleXMLElement
     * @throws \dml_exception
     */
    public function get_active_meetings() {
        $requestaction = 'getMeetings';
        $meetings = [];

        $requeststring = "";
        $checksum = sha1($requestaction . $requeststring . $this->bbbserversecret);

        if (get_config('local_intellidata', 'bbb_debug')) {
            ob_start();
            $curl = new \curl(['debug' => true]);
            $out = fopen('php://output', 'w');
            $options['CURLOPT_VERBOSE'] = true;
            $options['CURLOPT_STDERR'] = $out;
        } else {
            $curl = new \curl();
        }

        $res = $curl->get($this->apiendpoint . "/{$requestaction}", [
            'checksum' => $checksum
        ]);

        if (get_config('local_intellidata', 'bbb_debug')) {
            fclose($out);
            echo '<pre>';
            var_dump(ob_get_clean());
        }

        $xml = simplexml_load_string($res);

        if (isset($xml->meetings->meeting)) {
            $meetings = $xml->meetings->meeting;
        }

        return $meetings;
    }

    public function get_meetings_records($meetingid = null) {
        $requestaction = 'getRecordings';
        $requeststring = $meetingid ? "meetingID={$meetingid}" : "";
        $checksum = sha1($requestaction . $requeststring . $this->bbbserversecret);

        $curl = new \curl();
        $requestparams = [
            'checksum' => $checksum
        ];
        if ($meetingid) {
            $requestparams['meetingID'] = $meetingid;
        }
        $res = $curl->get($this->apiendpoint . "/{$requestaction}", $requestparams);

        $xml = simplexml_load_string($res);

        return $xml;
    }
}
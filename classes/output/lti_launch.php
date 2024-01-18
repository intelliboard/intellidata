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
 * Class containing data of "View Lti" page
 *
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\output;



use renderable;
use templatable;
use renderer_base;

class lti_launch implements renderable, templatable {
    public $params = [];
    public $endpoint = '';
    public $debug = false;

    public function __construct($params, $endpoint, $debug=false) {
        $this->params = $params;
        $this->endpoint = $endpoint;
        $this->debug = $debug;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function export_for_template(renderer_base $output) {

        $inpunts = [];
        foreach ($this->params as $key => $value) {
            $key = htmlspecialchars($key, ENT_QUOTES);
            $value = htmlspecialchars($value, ENT_QUOTES);
            $input = [];

            if ( $key == "ext_submit" ) {
                $input['type'] = "submit";
            } else {
                $input['type'] = "hidden";
                $input['name'] = $key;
            }
            $input['value'] = $value;

            $inpunts[] = $input;
        }

        return [
            'endpoint' => $this->endpoint,
            'inputs' => $inpunts,
            'debug' => $this->debug,
        ];
    }
}

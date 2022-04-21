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
 * local_intellidata
 *
 * @package    local_intellidata
 * @author     IntelliBoard Inc.
 * @copyright  2020 intelliboard.net
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace local_intellidata\output\tables;

defined('MOODLE_INTERNAL') || die();

use html_writer;
use local_intellidata\helpers\StorageHelper;

require_once($CFG->libdir.'/tablelib.php');

class exportlogs_table extends \table_sql {

    public $download    = false;
    public $fields      = [];
    protected $prefs      = [];

    public function __construct($uniqueid, $params) {
        global $PAGE, $DB;

        parent::__construct($uniqueid);
        $this->download = $params['download'];
        $this->fields = $this->get_fields();
        $sqlparams = [];

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->is_collapsible = false;

        $this->define_columns(array_keys($this->fields));
        $this->define_headers($this->get_headers());

        $fields = "f.*";
        $from = "{files} f";

        $where = 'f.id > 0 AND f.component = :component AND f.mimetype IS NOT NULL';
        $sqlparams['component'] = 'local_intellidata';

        $this->set_sql($fields, $from, $where, $sqlparams);
        $this->define_baseurl($PAGE->url);
    }

    public function get_fields() {
        $fields = [
            'filearea' => [
                'label' => get_string('datatype', 'local_intellidata'),
            ],
            'filename' => [
                'label' => get_string('filename', 'local_intellidata'),
            ],
            'filesize' => [
                'label' => get_string('filesize', 'local_intellidata'),
            ],
            'timecreated' => [
                'label' => get_string('created', 'local_intellidata'),
            ],
            'actions' => [
                'label' => get_string('actions', 'local_intellidata'),
            ],
        ];

        return $fields;
    }

    public function get_headers() {

        $headers = [];

        if (count($this->fields)) {
            foreach ($this->fields as $field => $options) {
                $headers[] = $options['label'];
            }
        }

        $headers[] = get_string('actions', 'local_intellidata');

        return$headers;
    }

    public function col_timecreated($values) {
        return ($values->timecreated) ? userdate($values->timecreated, get_string('strftimedatetime', 'langconfig')) : '-';
    }

    public function col_filearea($values) {
        if (get_string_manager()->string_exists('datatype_' . $values->filearea, 'local_intellidata')) {
            return get_string('datatype_' . $values->filearea, 'local_intellidata');
        } else {
            return $values->filearea;
        }
    }

    public function col_filesize($values) {
        return StorageHelper::convert_filesize($values->filesize);
    }

    public function col_actions($values) {
        global $OUTPUT;

        $buttons = array();

        $urlparams = ['id' => $values->id];

        // Action download.
        $aurl = StorageHelper::make_pluginfile_url($values)->out(false);
        $buttons[] = $OUTPUT->action_icon(
            $aurl,
            new \pix_icon('t/download', get_string('download'), 'core', array('class' => 'iconsmall'))
        );

        $aurl = new \moodle_url('/local/intellidata/logs/index.php', $urlparams + array('action' => 'delete'));
        $buttons[] = $OUTPUT->action_icon($aurl, new \pix_icon('t/delete', get_string('delete'),
            'core', array('class' => 'iconsmall')), null,
            ['onclick' => "if (!confirm('".get_string('deletefileconfirmation', 'local_intellidata')."')) return false;"]
        );

        return implode(' ', $buttons);
    }

    public function start_html() {

        echo html_writer::start_tag('div', array('class' => 'custom-filtering-table'));

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        // Do we need to print initial bars?
        $this->print_initials_bar();

        if (in_array(TABLE_P_TOP, $this->showdownloadbuttonsat)) {
            echo $this->download_buttons();
        }

        $this->wrap_html_start();
        // Start of main data table.

        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::start_tag('table', $this->attributes);

    }

    /**
     * Get the html for the download buttons
     *
     * Usually only use internally
     */
    public function download_buttons() {
        global $OUTPUT;

        $output = '';

        if ($this->is_downloadable() && !$this->is_downloading()) {
            $output = $OUTPUT->download_dataformat_selector(get_string('downloadas', 'table'),
                $this->baseurl->out_omit_querystring(), 'download', $this->baseurl->params());

            $exporturl = new \moodle_url('/local/intellidata/logs/index.php', ['action' => 'export']);
            $output .= \html_writer::link($exporturl, get_string('exportfiles', 'local_intellidata'),
                ['class' => 'btn btn-primary']);
        }

        return $output;
    }

}


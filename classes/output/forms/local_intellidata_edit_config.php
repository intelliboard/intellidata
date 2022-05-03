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
 * @package    local
 * @subpackage intellidata
 * @copyright  2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intellidata\output\forms;

use local_intellidata\persistent\datatypeconfig;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Edit config form.
 */
class local_intellidata_edit_config extends \moodleform {

    /**
     * @throws \coding_exception
     */
    public function definition() {
        $mform = $this->_form;
        $data = $this->_customdata['data'];
        $config = $this->_customdata['config'];

        $mform->addElement('select', 'timemodified_field',
            get_string('timemodified_field', 'local_intellidata'), ['' => ''] + $config->timemodifiedfields);
        $mform->setType('timemodified_field', PARAM_ALPHA);

        if (!empty($config->observer)) {
            $mform->addElement('advcheckbox', 'events_tracking', get_string('events_tracking', 'local_intellidata'));
            $mform->setType('events_tracking', PARAM_INT);
        }

        $mform->addElement('advcheckbox', 'filterbyid', get_string('filterbyid', 'local_intellidata'));
        $mform->setType('filterbyid', PARAM_INT);
        $mform->disabledIf('filterbyid', 'timemodified_field', 'neq', '');

        $mform->addElement('advcheckbox', 'rewritable', get_string('rewritable', 'local_intellidata'));
        $mform->setType('rewritable', PARAM_INT);
        $mform->disabledIf('rewritable', 'timemodified_field', 'neq', '');
        $mform->disabledIf('rewritable', 'filterbyid', 'checked');

        $options = [
            datatypeconfig::STATUS_ENABLED => get_string('enabled', 'local_intellidata'),
            datatypeconfig::STATUS_DISABLED => get_string('disabled', 'local_intellidata'),
        ];
        $mform->addElement('select', 'status', get_string('status', 'local_intellidata'), $options);
        $mform->setType('status', PARAM_INT);

        $mform->addElement('hidden', 'datatype');
        $mform->setType('datatype', PARAM_ALPHA);

        $this->add_action_buttons();
        $this->set_data($data);
    }

    /**
     * @param bool $cancel
     * @param null $submitlabel
     * @throws \coding_exception
     */
    public function add_action_buttons($cancel = true, $submitlabel = null) {
        if (is_null($submitlabel)) {
            $submitlabel = get_string('savechanges');
        }
        $mform =& $this->_form;

        // When two elements we need a group.
        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', $submitlabel);
        $buttonarray[] = &$mform->createElement('submit', 'reset', get_string('resettodefault', 'local_intellidata'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
        $mform->closeHeaderBefore('buttonar');
    }
}

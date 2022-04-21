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
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intellidata\tests;

use local_intellidata\helpers\StorageHelper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/intellidata/tests/setup_helper.php');
require_once($CFG->dirroot . '/local/intellidata/tests/generator.php');
require_once($CFG->dirroot . '/local/intellidata/tests/test_helper.php');


/**
 * Cohort migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class local_intellidata_cohortmember_tracking_testcase extends \advanced_testcase {
    public function setUp(): void {
        $this->setAdminUser();

        setup_helper::enable_plugin();
        setup_helper::enable_db_storage();
    }

    public function test_create() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        $userdata = [
            'firstname' => 'ibuser1',
            'username' => 'ibuser1',
            'password' => 'Ibuser1!',
        ];

        $user = generator::create_user($userdata);

        $cohortdata = [
            'name' => 'ibcohort1',
            'contextid' => '1',
        ];

        $cohort = generator::create_cohort($cohortdata);

        $data = [
            'cohortid' => $cohort->id,
            'userid' => $user->id,
        ];

        // Create cohortmember.
        cohort_add_member($data['cohortid'], $data['userid']);

        $entity = new \local_intellidata\entities\cohortmembers\cohortmember((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'cohortmembers']);

        $datarecord = $storage->get_log_entity_data('cohort_member_added', $data);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    public function test_delete() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        } else {
            $this->test_create();
        }

        $userdata = [
            'firstname' => 'ibuser1',
            'username' => 'ibuser1',
        ];

        $user = $DB->get_record('user', $userdata);

        $cohortdata = [
            'name' => 'ibcohort1',
            'contextid' => '1',
        ];

        $cohort = $DB->get_record('cohort', $cohortdata);

        $data = [
            'cohortid' => $cohort->id,
            'userid' => $user->id,
        ];

        cohort_remove_member($cohort->id, $user->id);

        $entity = new \local_intellidata\entities\cohortmembers\cohortmember((object)$data);
        $entitydata = $entity->export();

        $storage = StorageHelper::get_storage_service(['name' => 'cohortmembers']);

        $datarecord = $storage->get_log_entity_data('cohort_member_removed', $data);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = json_decode($datarecord->data);
        $this->assertEquals($entitydata->cohortid, $datarecorddata->cohortid);
        $this->assertEquals($entitydata->userid, $datarecorddata->userid);
    }
}

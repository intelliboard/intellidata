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

namespace local_intellidata;

use local_intellidata\helpers\StorageHelper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/intellidata/tests/setup_helper.php');
require_once($CFG->dirroot . '/local/intellidata/tests/generator.php');
require_once($CFG->dirroot . '/local/intellidata/tests/test_helper.php');


/**
 * Role migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class role_tracking_test extends \advanced_testcase {

    public function setUp():void {
        $this->setAdminUser();

        setup_helper::setup_tests_config();
    }

    /**
     * @covers \local_intellidata\entities\roles\roleassignment
     * @covers \local_intellidata\entities\roles\ramigration
     * @covers \local_intellidata\entities\roles\observer::role_assigned
     */
    public function test_assign() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        $userdata = [
            'firstname' => 'ibuser1',
            'username' => 'ibuser1',
            'password' => 'Ibuser1!',
        ];

        $user = generator::create_user($userdata);

        $coursedata = [
            'fullname' => 'ibcoursecompletion1',
            'idnumber' => '1111111',
        ];

        $course = generator::create_course($coursedata);

        $roledata = [
            'shortname' => 'ibrole1',
            'name' => 'ibrole1',
        ];

        $roleid = generator::create_role($roledata);

        $data = [
            'roleid' => $roleid,
            'userid' => $user->id,
            'courseid' => $course->id,
        ];

        role_assign($data['roleid'], $data['userid'], \context_course::instance($course->id));

        $entity = new \local_intellidata\entities\roles\roleassignment((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'roleassignments']);
        $datarecord = $storage->get_log_entity_data('c');

        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @covers \local_intellidata\entities\roles\roleassignment
     * @covers \local_intellidata\entities\roles\ramigration
     * @covers \local_intellidata\entities\roles\observer::role_unassigned
     */
    public function test_unassign() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        } else {
            $this->test_assign();
        }

        $userdata = [
            'firstname' => 'ibuser1',
            'username' => 'ibuser1',
        ];

        $user = $DB->get_record('user', $userdata);

        $coursedata = [
            'fullname' => 'ibcoursecompletion1',
            'idnumber' => '1111111',
        ];

        $course = $DB->get_record('course', $coursedata);

        $roledata = [
            'shortname' => 'ibrole1',
            'name' => 'ibrole1',
        ];

        $role = $DB->get_record('role', $roledata);

        $data = [
            'roleid' => $role->id,
            'userid' => $user->id,
            'courseid' => $course->id,
        ];

        $context = \context_course::instance($course->id);

        role_unassign($data['roleid'], $data['userid'], $context->id);

        $entity = new \local_intellidata\entities\roles\roleassignment((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'roleassignments']);
        $datarecord = $storage->get_log_entity_data('d', $data);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }
}

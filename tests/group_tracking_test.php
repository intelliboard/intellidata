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
 * @copyright  2023
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
 * Course groups migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2023
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class group_tracking_test extends \advanced_testcase {

    public function setUp(): void {
        $this->setAdminUser();

        setup_helper::setup_tests_config();
    }

    /**
     * @covers \local_intellidata\entities\groups\group
     * @covers \local_intellidata\entities\groups\migration
     * @covers \local_intellidata\entities\groups\observer::group_created
     */
    public function test_create() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        $data = [
            'fullname' => 'ibcourse1',
            'idnumber' => '1111111'
        ];

        // Create course.
        $course = generator::create_course($data);

        $gdata = [
            'name' => 'testgroup1',
            'courseid' => $course->id
        ];

        // Create group.
        $group = generator::create_group($gdata);

        $entity = new \local_intellidata\entities\groups\group($group);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $gdata);

        $storage = StorageHelper::get_storage_service(['name' => 'coursegroups']);

        $datarecord = $storage->get_log_entity_data('group_created', ['id' => $group->id]);
        $this->assertNotEmpty($datarecord);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $gdata);

        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @covers \local_intellidata\entities\groupmembers\groupmember
     * @covers \local_intellidata\entities\groupmembers\migration
     * @covers \local_intellidata\entities\groupmembers\observer::group_member_added
     */
    public function test_create_member() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        $gdata = [
            'name' => 'testgroup1'
        ];
        $group = $DB->get_record('groups', $gdata);

        $userdata = [
            'firstname' => 'ibuser1',
            'username' => 'ibuser1',
            'password' => 'Ibuser1!'
        ];

        // Create user.
        $user = generator::create_user($userdata);

        $data = [
            'userid' => $user->id,
            'courseid' => $group->courseid
        ];

        // Enrol user.
        generator::enrol_user($data);

        $gmdata = [
            'groupid' => $group->id,
            'userid' => $user->id
        ];

        // Assign user to group.
        generator::create_group_member($gmdata);

        $groupm = $DB->get_record('groups_members', $gmdata);

        $entity = new \local_intellidata\entities\groupmembers\groupmember($groupm);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $gmdata);

        $storage = StorageHelper::get_storage_service(['name' => 'coursegroupmembers']);

        $datarecord = $storage->get_log_entity_data('group_member_added', ['id' => $groupm->id]);
        $this->assertNotEmpty($datarecord);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $gmdata);

        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @covers \local_intellidata\entities\groups\group
     * @covers \local_intellidata\entities\groups\migration
     * @covers \local_intellidata\entities\groups\observer::group_updated
     */
    public function test_update() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        } else {
            $this->test_create();
        }

        $gdata = [
            'name' => 'testgroup1'
        ];
        $group = $DB->get_record('groups', $gdata);
        $group->name = 'testgroupupdate';
        $gdata['name'] = $group->name;

        groups_update_group($group);

        $entity = new \local_intellidata\entities\groups\group($group);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $gdata);

        $storage = StorageHelper::get_storage_service(['name' => 'coursegroups']);

        $datarecord = $storage->get_log_entity_data('group_updated', ['id' => $group->id]);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $gdata);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @covers \local_intellidata\entities\groupmembers\groupmember
     * @covers \local_intellidata\entities\groupmembers\migration
     * @covers \local_intellidata\entities\groupmembers\observer::group_member_removed
     */
    public function test_delete_member() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        } else {
            $this->test_create();
        }

        $gdata = [
            'name' => 'testgroupupdate'
        ];
        $group = $DB->get_record('groups', $gdata);

        $userdata = [
            'firstname' => 'ibuser1',
            'username' => 'ibuser1'
        ];
        $user = $DB->get_record('user', $userdata);

        groups_remove_member($group->id, $user->id);

        $groupm = [
            'userid' => $user->id,
            'groupid' => $group->id,
        ];
        $entity = new \local_intellidata\entities\groupmembers\groupmember($groupm);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $groupm);

        $storage = StorageHelper::get_storage_service(['name' => 'coursegroupmembers']);

        $datarecord = $storage->get_log_entity_data('group_member_removed', $groupm);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $groupm);

        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @covers \local_intellidata\entities\groups\group
     * @covers \local_intellidata\entities\groups\migration
     * @covers \local_intellidata\entities\groups\observer::group_deleted
     */
    public function test_delete() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        } else {
            $this->test_create();
        }

        $gdata = [
            'name' => 'testgroupupdate'
        ];
        $group = $DB->get_record('groups', $gdata);

        groups_delete_group($group);

        $entity = new \local_intellidata\entities\groups\group($group);
        $entitydata = $entity->export();

        $storage = StorageHelper::get_storage_service(['name' => 'coursegroups']);

        $datarecord = $storage->get_log_entity_data('group_deleted', ['id' => $group->id]);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = json_decode($datarecord->data);
        $this->assertEquals($entitydata->id, $datarecorddata->id);
    }
}

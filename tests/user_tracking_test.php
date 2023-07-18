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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/intellidata/tests/setup_helper.php');
require_once($CFG->dirroot . '/local/intellidata/tests/generator.php');
require_once($CFG->dirroot . '/local/intellidata/tests/test_helper.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_intellidata\helpers\StorageHelper;

/**
 * User migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class user_tracking_test extends \advanced_testcase {
    public function setUp():void {
        $this->setAdminUser();

        setup_helper::setup_tests_config();
    }

    /**
     * @covers \local_intellidata\entities\users\user
     * @covers \local_intellidata\entities\users\migration
     * @covers \local_intellidata\entities\users\observer::user_created
     */
    public function test_create() {
        global $DB;
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        $data = [
            'firstname' => 'unit test create user',
            'username' => 'unittest_create_user',
            'password' => 'Unittest_User1!',
        ];

        // Create user.
        $user = generator::create_user($data);

        $entity = new \local_intellidata\entities\users\user($user);
        $entitydata = $entity->export();

        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'users']);
        $datarecord = $storage->get_log_entity_data('c', ['id' => $user->id]);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @covers \local_intellidata\entities\users\user
     * @covers \local_intellidata\entities\users\migration
     * @covers \local_intellidata\entities\users\observer::user_updated
     */
    public function test_update() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        } else {
            $this->test_create();
        }

        $data = [
            'username' => 'unittest_create_user'
        ];

        $user = $DB->get_record('user', $data);
        $user->firstname = 'unit test update user';
        $data['firstname'] = $user->firstname;

        user_update_user($user, false);

        $entity = new \local_intellidata\entities\users\user($user);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'users']);
        $datarecord = $storage->get_log_entity_data('u', ['id' => $user->id]);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @covers \local_intellidata\entities\users\user
     * @covers \local_intellidata\entities\users\migration
     * @covers \local_intellidata\entities\users\observer::user_deleted
     */
    public function test_delete() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        } else {
            $this->test_create();
        }

        $data = [
            'username' => 'unittest_create_user'
        ];

        $user = $DB->get_record('user', $data);

        user_delete_user($user);

        $entity = new \local_intellidata\entities\users\user($user);
        $entitydata = $entity->export();

        $storage = StorageHelper::get_storage_service(['name' => 'users']);
        $datarecord = $storage->get_log_entity_data('d', ['id' => $user->id]);

        $datarecorddata = json_decode($datarecord->data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata->id, $datarecorddata->id);
    }
}

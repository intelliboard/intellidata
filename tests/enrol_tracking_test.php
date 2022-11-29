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
 * Enrol migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class local_intellidata_enrol_tracking_testcase extends \advanced_testcase {
    public function setUp(): void {
        $this->setAdminUser();

        setup_helper::setup_tests_config();
    }

    public function test_create() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        $userdata = [
            'firstname' => 'unit test enrol user',
            'username' => 'unittest_enrol_user',
            'password' => 'Unittest_User1!',
        ];

        // Create user.
        $user = generator::create_user($userdata);

        $coursedata = [
            'fullname' => 'unit test enrol course',
            'idnumber' => '111222',
        ];

        // Create course.
        $course = generator::create_course($coursedata);

        $data = [
            'userid' => $user->id,
            'courseid' => $course->id,
        ];

        // Enrol user.
        generator::enrol_user($data);

        $entity = new \local_intellidata\entities\enrolments\enrolment((object)$data);
        $entitydata = $entity->export();

        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'enrolments']);
        $datarecord = $storage->get_log_entity_data('user_enrolment_created');
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    public function test_update() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        } else {
            $this->test_create();
        }

        $userdata = [
            'firstname' => 'unit test enrol user',
            'username' => 'unittest_enrol_user',
        ];

        $user = $DB->get_record('user', $userdata);

        $coursedata = [
            'fullname' => 'unit test enrol course',
            'idnumber' => '111222',
        ];

        $course = $DB->get_record('course', $coursedata);

        $userenroldata = [
            'userid' => $user->id,
        ];

        $userenrol = $DB->get_record('user_enrolments', $userenroldata);

        $enroldata = [
            'id' => $userenrol->enrolid,
            'courseid' => $course->id,
        ];

        $enrol = $DB->get_record('enrol', $enroldata);

        $data = [
            'userid' => $user->id,
            'courseid' => $course->id,
        ];

        $plugin = enrol_get_plugin('manual');

        $plugin->update_user_enrol($enrol, $user->id, ENROL_USER_ACTIVE, time(), time());

        $entity = new \local_intellidata\entities\enrolments\enrolment((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'enrolments']);
        $datarecord = $storage->get_log_entity_data('user_enrolment_updated', $data);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
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
            'firstname' => 'unit test enrol user',
            'username' => 'unittest_enrol_user',
        ];

        $user = $DB->get_record('user', $userdata);

        $coursedata = [
            'fullname' => 'unit test enrol course',
            'idnumber' => '111222',
        ];

        $course = $DB->get_record('course', $coursedata);

        $userenroldata = [
            'userid' => $user->id,
        ];

        $userenrol = $DB->get_record('user_enrolments', $userenroldata);

        $enroldata = [
            'id' => $userenrol->enrolid,
            'courseid' => $course->id,
        ];

        $enrol = $DB->get_record('enrol', $enroldata);

        $plugin = enrol_get_plugin('manual');

        $plugin->unenrol_user($enrol, $user->id);

        $entity = new \local_intellidata\entities\enrolments\enrolment($userenrol);
        $entitydata = $entity->export();

        $storage = StorageHelper::get_storage_service(['name' => 'enrolments']);
        $datarecord = $storage->get_log_entity_data('user_enrolment_deleted', ['id' => $userenrol->id]);
        $datarecorddata = json_decode($datarecord->data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata->id, $datarecorddata->id);
    }
}

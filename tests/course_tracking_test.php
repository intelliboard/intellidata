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
class local_intellidata_course_tracking_testcase extends \advanced_testcase {
    public function setUp(): void {
        $this->setAdminUser();

        setup_helper::setup_tests_config();
    }

    public function test_create() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        $data = [
            'fullname' => 'ibcourse1',
            'idnumber' => '1111111',
        ];

        // Create course.
        $course = generator::create_course($data);

        $entity = new \local_intellidata\entities\courses\course($course);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'courses']);

        $datarecord = $storage->get_log_entity_data('course_created', ['id' => $course->id]);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    public function test_update() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        } else {
            $this->test_create();
        }

        $data = [
            'fullname' => 'ibcourse1'
        ];

        $course = $DB->get_record('course', $data);
        $course->idnumber = '2222222';
        $data['idnumber'] = $course->idnumber;

        update_course($course);

        $entity = new \local_intellidata\entities\courses\course($course);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'courses']);

        $datarecord = $storage->get_log_entity_data('course_updated', ['id' => $course->id]);
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

        $data = [
            'fullname' => 'ibcourse1'
        ];

        $course = $DB->get_record('course', $data);

        delete_course($course, false);

        $entity = new \local_intellidata\entities\courses\course($course);
        $entitydata = $entity->export();

        $storage = StorageHelper::get_storage_service(['name' => 'courses']);

        $datarecord = $storage->get_log_entity_data('course_deleted', ['id' => $course->id]);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = json_decode($datarecord->data);
        $this->assertEquals($entitydata->id, $datarecorddata->id);
    }
}

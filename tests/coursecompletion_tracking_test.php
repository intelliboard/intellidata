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

use completion_completion;
use context_course;
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
class local_intellidata_coursecompletion_tracking_testcase extends \advanced_testcase {
    public function setUp(): void {
        $this->setAdminUser();

        setup_helper::enable_plugin();
        setup_helper::enable_db_storage();
        setup_helper::setup_json_exportformat();
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

        $coursedata = [
            'fullname' => 'ibcoursecompletion1',
            'idnumber' => '1111111',
        ];

        $course = generator::create_course($coursedata);

        $data = [
            'userid' => $user->id,
            'course' => $course->id,
        ];

        // Create coursecompletion.
        $coursecompletion = new completion_completion($data);
        $coursecompletion->mark_complete(time());

        unset($data['course']);
        $data['courseid'] = $course->id;

        $entity = new \local_intellidata\entities\coursecompletions\coursecompletion((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'coursecompletions']);
        $datarecord = $storage->get_log_entity_data('course_completed', $data);
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

        $coursedata = [
            'fullname' => 'ibcoursecompletion1',
            'idnumber' => '1111111',
        ];

        $course = $DB->get_record('course', $coursedata);

        $data = [
            'courseid' => $course->id,
            'context' => context_course::instance($course->id),
        ];

        \core\event\course_completion_updated::create($data)->trigger();

        unset($data['context']);

        $entity = new \local_intellidata\entities\coursecompletions\coursecompletion((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'coursecompletions']);
        $datarecord = $storage->get_log_entity_data('course_completion_updated', $data);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }
}

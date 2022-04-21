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
require_once($CFG->dirroot . '/mod/quiz/tests/events_test.php');

/**
 * Attempt migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class local_intellidata_attempt_tracking_testcase extends \mod_quiz_events_testcase {
    public function setUp(): void {
        $this->setAdminUser();

        setup_helper::enable_plugin();
        setup_helper::enable_db_storage();
    }

    public function test_start() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        }

        list(, , $attempt) = $this->prepare_quiz_data();

        $data = [
            'quiz' => $attempt->quiz,
            'userid' => $attempt->userid,
        ];

        $entity = new \local_intellidata\entities\quizzes\attempt($attempt);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'quizattempts']);
        $datarecord = $storage->get_log_entity_data('attempt_started');
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    public function test_submit() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        }

        list(, , $attempt) = $this->prepare_quiz_data();
        $attemptobj = \quiz_attempt::create($attempt->id);
        $attemptobj->process_finish(time(), false);

        $data = [
            'quiz' => $attempt->quiz,
            'userid' => $attempt->userid,
        ];

        $entity = new \local_intellidata\entities\quizzes\attempt($attempt);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'quizattempts']);
        $datarecord = $storage->get_log_entity_data('attempt_submitted');
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }
}

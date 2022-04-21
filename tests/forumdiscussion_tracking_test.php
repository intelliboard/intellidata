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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/intellidata/tests/setup_helper.php');
require_once($CFG->dirroot . '/local/intellidata/tests/generator.php');
require_once($CFG->dirroot . '/local/intellidata/tests/test_helper.php');
require_once($CFG->dirroot . '/mod/forum/externallib.php');

use local_intellidata\helpers\StorageHelper;

/**
 * User migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2021
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class local_intellidata_furumdiscussion_tracking_test extends \advanced_testcase {
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
            'firstname' => 'ibforumuserforumdiscussion1',
            'username' => 'ibforumuserforumdiscussion1',
            'password' => 'Ibforumuserforumdiscussion1!',
        ];
        $user = generator::create_user($userdata);

        $coursedata = [
            'fullname' => 'ibcourseforumdiscussion1',
            'idnumber' => '44444444',
        ];
        $course = generator::create_course($coursedata);

        $forumdata = [
            'course' => $course->id
        ];
        $forum = generator::create_module('forum', $forumdata);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $user->id;
        $discussion = generator::get_plugin_generator('mod_forum')->create_discussion($record);

        $context = forum_get_context($forum->id);

        $params = array(
            'context' => $context,
            'objectid' => $discussion->id,
            'other' => ['forumid' => $forum->id],
        );

        // Create the event.
        $event = \mod_forum\event\discussion_created::create($params);
        $event->trigger();

        $data = [
            'id' => $discussion->id,
            'forum' => $forum->id,
        ];

        $entity = new \local_intellidata\entities\forums\forumdiscussion((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'forumdiscussions']);
        $datarecord = $storage->get_log_entity_data('discussion_created', $data);
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
            'username' => 'ibforumuserforumdiscussion1',
        ];
        $user = $DB->get_record('user', $userdata);

        $coursedata = [
            'fullname' => 'ibcourseforumdiscussion1',
            'idnumber' => '44444444',
        ];
        $course = $DB->get_record('course', $coursedata);

        $forumdata = [
            'course' => $course->id
        ];
        $forum = $DB->get_record('forum', $forumdata);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $user->id;
        $discussion = generator::get_plugin_generator('mod_forum')->create_discussion($record);

        $context = forum_get_context($forum->id);

        $params = array(
            'context' => $context,
            'objectid' => $discussion->id,
            'other' => ['forumid' => $forum->id],
        );

        // Create the event.
        $event = \mod_forum\event\discussion_updated::create($params);
        $event->trigger();

        $data = [
            'id' => $discussion->id,
            'forum' => $forum->id,
        ];

        $entity = new \local_intellidata\entities\forums\forumdiscussion((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'forumdiscussions']);
        $datarecord = $storage->get_log_entity_data('discussion_updated', $data);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    public function test_move() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        } else {
            $this->test_create();
        }

        $userdata = [
            'username' => 'ibforumuserforumdiscussion1',
        ];
        $user = $DB->get_record('user', $userdata);

        $fromcoursedata = [
            'fullname' => 'ibcourseforumdiscussion1',
            'idnumber' => '44444444',
        ];
        $fromcourse = $DB->get_record('course', $fromcoursedata);

        $fromforumdata = [
            'course' => $fromcourse->id
        ];
        $fromforum = $DB->get_record('forum', $fromforumdata);

        $tocourse = generator::create_course();

        $toforumdata = [
            'course' => $tocourse->id
        ];
        $toforum = generator::create_module('forum', $toforumdata);

        // Add a discussion.
        $record = array();
        $record['course'] = $fromcourse->id;
        $record['forum'] = $fromforum->id;
        $record['userid'] = $user->id;
        $discussion = generator::get_plugin_generator('mod_forum')->create_discussion($record);

        $context = forum_get_context($toforum->id);

        $params = array(
            'context' => $context,
            'objectid' => $discussion->id,
            'other' => array('fromforumid' => $fromforum->id, 'toforumid' => $toforum->id)
        );

        // Create the event.
        $event = \mod_forum\event\discussion_moved::create($params);
        $event->trigger();

        $data = [
            'id' => $discussion->id,
            'forum' => $toforum->id,
        ];

        $entity = new \local_intellidata\entities\forums\forumdiscussion((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'forumdiscussions']);
        $datarecord = $storage->get_log_entity_data('discussion_moved', $data);
        $datarecorddata = json_decode($datarecord->data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata->id, $datarecorddata->id);
    }

    public function test_delete() {
        global $DB;

        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        } else {
            $this->test_create();
        }

        $userdata = [
            'username' => 'ibforumuserforumdiscussion1',
        ];
        $user = $DB->get_record('user', $userdata);

        $coursedata = [
            'fullname' => 'ibcourseforumdiscussion1',
            'idnumber' => '44444444',
        ];
        $course = $DB->get_record('course', $coursedata);

        $forumdata = [
            'course' => $course->id
        ];
        $forum = $DB->get_record('forum', $forumdata);

        // Add a discussion.
        $record = array();
        $record['course'] = $course->id;
        $record['forum'] = $forum->id;
        $record['userid'] = $user->id;
        $discussion = generator::get_plugin_generator('mod_forum')->create_discussion($record);

        $context = forum_get_context($forum->id);

        $params = array(
            'context' => $context,
            'objectid' => $discussion->id,
            'other' => ['forumid' => $forum->id],
        );

        // Create the event.
        $event = \mod_forum\event\discussion_deleted::create($params);
        $event->trigger();

        $data = [
            'id' => $discussion->id,
        ];

        $entity = new \local_intellidata\entities\forums\forumdiscussion((object)$data);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'forumdiscussions']);
        $datarecord = $storage->get_log_entity_data('discussion_deleted', $data);
        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);

        $this->assertNotEmpty($datarecord);
        $this->assertEquals($entitydata, $datarecorddata);
    }
}

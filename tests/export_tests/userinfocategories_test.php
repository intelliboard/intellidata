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

namespace local_intellidata\export_tests;

use local_intellidata\entities\userinfocategories\userinfocategory;
use local_intellidata\helpers\ParamsHelper;
use local_intellidata\helpers\SettingsHelper;
use local_intellidata\helpers\StorageHelper;
use local_intellidata\generator;
use local_intellidata\setup_helper;
use local_intellidata\test_helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/intellidata/tests/setup_helper.php');
require_once($CFG->dirroot . '/local/intellidata/tests/generator.php');
require_once($CFG->dirroot . '/local/intellidata/tests/test_helper.php');

/**
 * User info categories migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2023
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class userinfocategories_test extends \advanced_testcase {

    private $newexportavailable;
    private $release;

    public function setUp(): void {
        $this->setAdminUser();

        setup_helper::setup_tests_config();

        $this->release = ParamsHelper::get_release();
        $this->newexportavailable = $this->release >= 3.8;
    }

    /**
     * @covers \local_intellidata\entities\userinfocategories\userinfocategory
     * @covers \local_intellidata\entities\userinfocategories\migration
     * @covers \local_intellidata\entities\userinfocategories\observer::user_info_category_created
     */
    public function test_create() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        }

        if ($this->newexportavailable) {
            SettingsHelper::set_setting('newtracking', 1);
            $this->create_user_info_category_test(1);
            SettingsHelper::set_setting('newtracking', 0);
        }

        $this->create_user_info_category_test(0);
    }

    /**
     * @covers \local_intellidata\entities\userinfocategories\userinfocategory
     * @covers \local_intellidata\entities\userinfocategories\migration
     * @covers \local_intellidata\entities\userinfocategories\observer::user_info_category_updated
     */
    public function test_update() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(false);
        } else {
            $this->test_create();
        }

        if ($this->newexportavailable) {
            SettingsHelper::set_setting('newtracking', 1);
            $this->update_user_info_category_test(1);
            SettingsHelper::set_setting('newtracking', 0);
        }

        $this->update_user_info_category_test(0);
    }

    /**
     * @covers \local_intellidata\entities\userinfocategories\userinfocategory
     * @covers \local_intellidata\entities\userinfocategories\migration
     * @covers \local_intellidata\entities\userinfocategories\observer::user_info_category_deleted
     */
    public function test_delete() {
        if (test_helper::is_new_phpunit()) {
            $this->resetAfterTest(true);
        } else {
            $this->test_create();
        }

        if ($this->newexportavailable) {
            SettingsHelper::set_setting('newtracking', 1);
            $this->delete_user_info_category_test(1);
            SettingsHelper::set_setting('newtracking', 0);
        }

        $this->delete_user_info_category_test(0);
    }

    /**
     * @param int $tracking
     *
     * @return void
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function delete_user_info_category_test($tracking) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/user/profile/definelib.php');

        $userinfocategory = $DB->get_record('user_info_category', ['name' => 'test user category update ' . $tracking]);

        if ($tracking == 0) {
            // Create category, because we can not delete last category.
            generator::create_profile_field_category(['name' => 'last category'], true);
        }

        profile_delete_category($userinfocategory->id);

        $storage = StorageHelper::get_storage_service(['name' => 'userinfocategories']);
        $datarecord = $storage->get_log_entity_data('d', ['id' => $userinfocategory->id]);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = json_decode($datarecord->data);
        $this->assertEquals($userinfocategory->id, $datarecorddata->id);
    }

    /**
     * @param int $tracking
     *
     * @return void
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function update_user_info_category_test($tracking) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/user/profile/definelib.php');

        $userinfocategory = $DB->get_record('user_info_category', ['name' => 'test user category create ' . $tracking]);

        $userinfocategory->name = 'test user category update ' . $tracking;
        // Create category.
        if ($this->release >= 4.0) {
            // Create category.
            profile_save_category($userinfocategory);
        } else {
            $DB->update_record('user_info_category', $userinfocategory);
            \core\event\user_info_category_updated::create_from_category($userinfocategory)->trigger();
        }

        $data['id'] = $userinfocategory->id;
        $data['sortorder'] = $userinfocategory->sortorder;

        $entity = new userinfocategory($userinfocategory);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'userinfocategories']);

        $datarecord = $storage->get_log_entity_data('u', ['id' => $userinfocategory->id]);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);
        $this->assertEquals($entitydata, $datarecorddata);
    }

    /**
     * @param int $tracking
     *
     * @return void
     * @throws \invalid_parameter_exception
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    private function create_user_info_category_test($tracking) {
        global $DB, $CFG;

        require_once($CFG->dirroot.'/user/profile/definelib.php');

        $data = [
            'name' => 'test user category create ' . $tracking,
        ];

        if ($this->release >= 4.0) {
            // Create category.
            profile_save_category((object)$data);
        } else {
            generator::create_profile_field_category($data, true);
        }

        $userinfocategory = $DB->get_record('user_info_category', ['name' => $data['name']]);

        $data['id'] = $userinfocategory->id;

        $entity = new userinfocategory($userinfocategory);
        $entitydata = $entity->export();
        $entitydata = test_helper::filter_fields($entitydata, $data);

        $storage = StorageHelper::get_storage_service(['name' => 'userinfocategories']);

        $datarecord = $storage->get_log_entity_data('c', ['id' => $userinfocategory->id]);
        $this->assertNotEmpty($datarecord);

        $datarecorddata = test_helper::filter_fields(json_decode($datarecord->data), $data);
        $this->assertEquals($entitydata, $datarecorddata);
    }
}

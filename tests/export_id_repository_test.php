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
 * Export_id_repository migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_intellidata\tests;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/intellidata/tests/generator.php');

use local_intellidata\repositories\export_id_repository;
use local_intellidata\persistent\export_ids;
use local_intellidata\persistent\tracking;

/**
 * Export_id_repository migration test case.
 *
 * @package    local
 * @subpackage intellidata
 * @copyright  2022
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class local_intellidata_export_id_repository_testcase extends \advanced_testcase {

    /**
     * Test save() method.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_save() {
        $this->resetAfterTest(true);

        $exportidrepository = new export_id_repository();
        $this->assertInstanceOf('local_intellidata\repositories\export_id_repository', $exportidrepository);

        // Validate empty table.
        $this->assertEquals(0, export_ids::count_records());

        $exportidrepository->save([]);
        $this->assertEquals(0, export_ids::count_records());

        // Validate record creation.
        $records = [];
        $records[] = [
            'datatype' => 'tracking',
            'dataid' => 1,
            'timecreated' => time()
        ];
        $records[] = [
            'datatype' => 'tracking',
            'dataid' => 2,
            'timecreated' => time()
        ];
        $exportidrepository->save($records);
        $this->assertEquals(count($records), export_ids::count_records());

        // Validate duplications.
        $this->expectException('dml_write_exception');
        $exportidrepository->save([[
            'datatype' => 'tracking',
            'dataid' => 2,
            'timecreated' => time()
        ]]);
    }

    /**
     * Test for clean_deleted_ids() method.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_clean_deleted_ids() {
        $this->resetAfterTest(true);

        $exportidrepository = new export_id_repository();
        $this->assertInstanceOf('local_intellidata\repositories\export_id_repository', $exportidrepository);

        // Validate empty table.
        $this->assertEquals(0, export_ids::count_records());

        // Validate record creation.
        $records = []; $recordsnum = 10; $datatype = 'tracking';
        $idstodelete = [1, 2, 3, 4, 5];
        for ($i = 1; $i <= $recordsnum; $i++) {
            $records[] = [
                'datatype' => $datatype,
                'dataid' => $i,
                'timecreated' => time()
            ];
        }

        $exportidrepository->save($records);
        $this->assertEquals($recordsnum, export_ids::count_records(['datatype' => $datatype]));

        // Validate empty ids list.
        $exportidrepository->clean_deleted_ids($datatype, []);
        $this->assertEquals($recordsnum, export_ids::count_records(['datatype' => $datatype]));

        // Validate other datatype deletion.
        $exportidrepository->clean_deleted_ids('users', $idstodelete);
        $this->assertEquals($recordsnum, export_ids::count_records(['datatype' => $datatype]));

        // Validate deletion.
        $exportidrepository->clean_deleted_ids(
            $datatype,
            $idstodelete
        );
        $this->assertEquals(($recordsnum - count($idstodelete)), export_ids::count_records());
    }

    /**
     * Test for get_created_ids() method.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_created_ids() {
        $this->resetAfterTest(true);

        $exportidrepository = new export_id_repository();
        $this->assertInstanceOf('local_intellidata\repositories\export_id_repository', $exportidrepository);

        // Validate empty table.
        $this->assertEquals(0, export_ids::count_records());

        // Create user.
        $user = generator::create_user();

        // Create tracking.
        $tracking = generator::create_tracking(['userid' => $user->id]);

        $datatype = 'tracking'; $datatypetable = tracking::TABLE;
        $createdrecords = $exportidrepository->get_created_ids($datatype, $datatypetable);

        // Validate new records.
        $this->assertNotFalse($createdrecords->valid());
        foreach ($createdrecords as $record) {
            $this->assertEquals($record->id, $tracking->id);
        }

        // Validate existing records.
        $exportidrepository->save([[
            'datatype' => $datatype,
            'dataid' => $tracking->id,
            'timecreated' => time()
        ]]);
        $records = $exportidrepository->get_created_ids($datatype, $datatypetable);
        $this->assertFalse($records->valid());
    }

    /**
     * Test for get_deleted_ids() method.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function test_get_deleted_ids() {
        $this->resetAfterTest(true);

        $exportidrepository = new export_id_repository();
        $this->assertInstanceOf('local_intellidata\repositories\export_id_repository', $exportidrepository);

        // Validate empty table.
        $this->assertEquals(0, export_ids::count_records());

        // Validate record creation.
        $datatype = 'tracking'; $datatypetable = tracking::TABLE;
        $records = []; $recordsnum = 5;
        for ($i = 1; $i <= $recordsnum; $i++) {
            $records[] = [
                'datatype' => $datatype,
                'dataid' => $i,
                'timecreated' => time()
            ];
        }

        // Validate not existing deleted IDs.
        $deletedids = $exportidrepository->get_deleted_ids($datatype, $datatypetable);
        $this->assertFalse($deletedids->valid());

        // Validate deleted IDs.
        $exportidrepository->save($records);
        $this->assertEquals($recordsnum, export_ids::count_records(['datatype' => $datatype]));

        $deletedrecords = $exportidrepository->get_deleted_ids($datatype, $datatypetable);
        $this->assertTrue($deletedrecords->valid());

        $deletedidscount = 0;
        foreach ($deletedrecords as $noneed) {
            $deletedidscount++;
        }
        $this->assertEquals($deletedidscount, $recordsnum);
    }
}

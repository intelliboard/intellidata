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
 * This plugin provides access to Moodle data in form of analytics and reports in real time.
 *
 *
 * @package    local_intellidata
 * @copyright  2020 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\helpers;

use local_intellidata\persistent\logs;
use local_intellidata\repositories\database_storage_repository;
use local_intellidata\repositories\file_storage_repository;
use local_intellidata\services\encryption_service;

class StorageHelper
{

    /**
     * @param $datatype
     * @return database_storage_repository|file_storage_repository
     * @throws \dml_exception
     */
    public static function get_storage_service($datatype) {

        if (empty($datatype['migrationmode']) and
            !empty(get_config('local_intellidata', 'trackingstorage'))) {
            return new database_storage_repository($datatype);
        } else {
            return new file_storage_repository($datatype);
        }

    }

    /**
     * @param $params
     * @return \stored_file|null
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \file_exception
     * @throws \moodle_exception
     * @throws \stored_file_creation_exception
     */
    public static function save_file($params) {

        $fs = get_file_storage();
        $context = \context_system::instance();

        $filename   = $params['filename'];
        $tempdir    = $params['tempdir'];
        $tempfile   = $params['tempfile'];

        if (!file_exists($tempfile)) {
            DebugHelper::error_log('File is not exists or empty ' . $tempfile);
            return null;
        }

        // Firstly zip temp file.
        $zipfilename = $filename.'.zip';
        $tempzipfilepath = $tempdir . '/dec_'.$zipfilename;
        $zipfilepath = $tempdir . '/'.$zipfilename;
        $zippacker   = get_file_packer('application/zip');

        // Zip file.
        $result = $zippacker->archive_to_pathname(
            [$filename => $tempfile], $tempzipfilepath
        );

        if ($result === false) {
            if (!@unlink($tempzipfilepath)) {
                throw new \moodle_exception("failed_remove_file", 'local_intellidata', '', $tempzipfilepath);
            }
            throw new \moodle_exception("failed_zip_packing", 'local_intellidata', '', $filename);
        }
        DebugHelper::error_log('Created Temp Zip File ' . $tempzipfilepath);

        $encriptionservice = new encryption_service();
        if (!$encriptionservice->encrypt_file($tempzipfilepath, $zipfilepath)) {
            @unlink($zipfilepath);
            DebugHelper::error_log('Error encrypt file: ' . $zipfilepath);
            throw new \moodle_exception('can_not_encrypt_file', 'local_intellidata');
        }

        $component = 'local_intellidata';
        $filearea = $params['datatype'];

        // Save file.
        $filerecord = array(
            'component' => $component,
            'filearea' => $filearea,
            'contextid' => $context->id,
            'filepath' => '/',
            'filename' => $zipfilename
        );
        $filerecord['itemid'] = self::get_new_itemid($filerecord);

        $file = $fs->create_file_from_pathname($filerecord, $zipfilepath);
        DebugHelper::error_log('Created Data File ' . $zipfilename . ' - ' . $file->get_id());

        if (!unlink($tempfile)) {
            throw new \moodle_exception("failed_remove_file", 'local_intellidata', '', $tempfile);
        }
        DebugHelper::error_log('Removed Temp File ' . $tempfile);

        if (!unlink($tempzipfilepath)) {
            throw new \moodle_exception("failed_remove_file", 'local_intellidata', '', $tempzipfilepath);
        }
        DebugHelper::error_log('Removed Temp Zip File ' . $tempzipfilepath);

        if (!unlink($zipfilepath)) {
            throw new \moodle_exception("failed_remove_file", 'local_intellidata', '', $zipfilepath);
        }
        DebugHelper::error_log('Removed Temp encrypted Zip File ' . $zipfilepath);

        // Save log when file exported.
        $logs = new logs(0, [
            'datatype'  => $params['datatype'],
            'type'      => logs::TYPE_FILE_EXPORT,
            'action'    => logs::ACTION_CREATED,
            'details'   => json_encode($filerecord)
        ]);
        $logs->save();

        return $file;
    }

    /**
     * @param $filerecord
     * @return int
     * @throws \coding_exception
     */
    public static function get_new_itemid($filerecord) {

        $itemid = 0;
        $fs = get_file_storage();
        $filerecord = (object)$filerecord;

        $files = $fs->get_area_files(
            $filerecord->contextid,
            $filerecord->component,
            $filerecord->filearea,
            false,
            "itemid",
            true
        );

        if (count($files)) {
            $file = end($files);
            $itemid = $file->get_itemid() + 1;
        }

        DebugHelper::error_log('The new itemid for file ' . $filerecord->filearea . ' is ' . $itemid);

        return $itemid;
    }

    /**
     * @param $source
     * @param $destination
     * @return bool
     * @throws \moodle_exception
     */
    public static function rename_file($source, $destination) {

        if (!file_exists($source)) {
            DebugHelper::error_log('Error in renaming Temp File. File ' . $source . ' not exists');
            throw new \moodle_exception('filenotexists', 'local_intellidata');
        }

        if (!rename($source, $destination)) {
            DebugHelper::error_log('Error in renaming Temp File ' . $source . ' to ' . $destination);
            throw new \moodle_exception('failed_rename_tempfile', 'local_intellidata');
        }

        DebugHelper::error_log('Temp File renamed from ' . $source . ' to ' . $destination);
        return true;
    }

    /**
     * @param int $length
     * @return false|string
     */
    public static function generate_filename($length = 16) {
        $bytes = openssl_random_pseudo_bytes($length, $cstrong);
        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * @param $file
     * @return \moodle_url
     */
    public static function make_pluginfile_url($file) {
        return \moodle_url::make_pluginfile_url(
            $file->contextid,
            $file->component,
            $file->filearea,
            $file->itemid,
            $file->filepath,
            $file->filename
        );
    }

    /**
     * @param $id
     * @return bool
     * @throws \dml_exception
     */
    public static function delete_file($id) {
        global $DB;

        $fs = get_file_storage();

        if ($filerecord = $DB->get_record('files', ['id' => $id])) {
            $fs->get_file_instance($filerecord)->delete();
        }

        return true;
    }

    /**
     * @param $bytes
     * @return string
     */
    public static function convert_filesize($bytes) {
        $i = floor(log($bytes) / log(1024));
        $sizes = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        return sprintf('%.02F', $bytes / pow(1024, $i)) * 1 . ' ' . $sizes[$i];
    }

    public static function save_in_file($storagefile, $data) {
        $line = (file_exists($storagefile)) ? PHP_EOL . $data : $data;

        if (file_put_contents($storagefile, $line, FILE_APPEND | LOCK_EX) === false) {
            throw new \moodle_exception("failed_write_file", 'local_intellidata', '', $storagefile);
        }
    }

    /**
     * @param $path
     * @return bool
     */
    public static function delete_all_files($path) {
        $files = glob($path . DIRECTORY_SEPARATOR . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * @param $format
     * @param $data
     * @return false|string
     */
    public static function format_data($format, $data) {
        $eventdata = '';

        switch ($format) {
            case 'json':
                $eventdata = json_encode($data);
                break;
            case 'csv':
                $stream = fopen('php://memory', 'r+');

                fputcsv($stream, (array)$data);
                rewind($stream);

                $eventdata = rtrim(stream_get_contents($stream));
                break;
        }

        return $eventdata;
    }
}
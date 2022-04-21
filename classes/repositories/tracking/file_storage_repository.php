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
 * @copyright  2021 IntelliBoard, Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @website    http://intelliboard.net/
 */

namespace local_intellidata\repositories\tracking;


class file_storage_repository extends storage_repository {

    public function save_data($trackdata) {
        global $USER;

        $trackingstorage = new tracking_storage_repository($USER->id);

        $data = $this->get_default_file_tracking($trackdata);
        $trackingstorage->save_data(json_encode($data));

        $tracklogs = get_config('local_intellidata', 'tracklogs');
        $trackdetails = get_config('local_intellidata', 'trackdetails');

        $currentstamp = strtotime('today');
        if ($tracklogs) {
            $log = $this->get_default_file_log($trackdata, $currentstamp);
            $trackingstorage->save_data(json_encode($log));

            if ($trackdetails) {
                $currenthour = date('G');
                $detail = $this->get_default_file_log_detail($trackdata, $currenthour, $currentstamp);
                $trackingstorage->save_data(json_encode($detail));
            }
        }
    }

    protected function get_default_file_tracking($trackdata) {
        $data = new \stdClass();
        $data->userid = $trackdata->userid;
        $data->courseid = $trackdata->courseid;
        $data->page = $trackdata->page;
        $data->param = $trackdata->param;
        $data->timespend = $trackdata->timespend;
        $data->firstaccess = time();
        $data->useragent = $trackdata->useragent;
        $data->ip = $trackdata->ip;
        $data->table = 'tracking';
        $data->ajaxrequest = $this->ajaxrequest;
        $data->timemodified = time();

        if (!$this->ajaxrequest) {
            $data->visits = 1;
            $data->lastaccess = time();
        }

        return $data;
    }

    protected function get_default_file_log($trackdata, $currentstamp) {
        $log = new \stdClass();
        $log->visits = ($this->ajaxrequest) ? 0 : 1;
        $log->timespend = $trackdata->timespend;
        $log->timepoint = $currentstamp;
        $log->timemodified = time();
        $log->table = 'logs';
        $log->ajaxrequest = $this->ajaxrequest;
        $log->userid = $trackdata->userid;
        $log->page = $trackdata->page;
        $log->param = $trackdata->param;

        return $log;
    }

    protected function get_default_file_log_detail($trackdata, $currenthour, $currentstamp) {
        $detail = new \stdClass();
        $detail->visits = (!$this->ajaxrequest) ? 1 : 0;
        $detail->timespend = $trackdata->timespend;
        $detail->timepoint = $currenthour;
        $detail->currentstamp = $currentstamp;
        $detail->table = 'details';
        $detail->ajaxrequest = $this->ajaxrequest;
        $detail->userid = $trackdata->userid;
        $detail->page = $trackdata->page;
        $detail->param = $trackdata->param;
        $detail->timemodified = time();

        return $detail;
    }

    public function export_data() {
        global $DB;

        mtrace("IntelliData Tracking Files Export started!");
        $trackingstorage = new tracking_storage_repository();
        $files = $trackingstorage->get_files();

        foreach ($files as $filename) {
            list($userid, $extension) = explode('.', $filename);

            if (!is_numeric($userid) || $extension != $trackingstorage::STORAGE_FILE_TYPE) {
                // Something wrong.
                mtrace("Incorrect file " . $filename);
                $trackingstorage->delete_file($filename);
                continue;
            }

            $tempfilepath = $trackingstorage->rename_file($filename);

            if (!$tempfilepath) {
                // Something wrong.
                mtrace("Error rename file " . $filename);
                continue;
            }

            $data = [];
            $handle = @fopen($tempfilepath, "r");
            if ($handle) {
                while (($buffer = fgets($handle)) !== false) {
                    $record = json_decode($buffer);

                    if ($record->table == 'tracking') {
                        if (isset($data[$record->userid][$record->page][$record->param][$record->table])) {
                            $item = &$data[$record->userid][$record->page][$record->param][$record->table];
                            if (isset($record->visits)) {
                                @$item['visits'] += $record->visits;
                            }
                            $item['timespend'] += $record->timespend;
                            $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                        } else {
                            $data[$record->userid][$record->page][$record->param][$record->table] = (array)$record;
                        }
                    } else if ($record->table == 'logs') {
                        if (isset($data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint])) {
                            $item = &$data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint];
                            if (isset($record->visits)) {
                                @$item['visits'] += $record->visits;
                            }
                            $item['timespend'] += $record->timespend;
                            $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                        } else {
                            $data[$record->userid][$record->page][$record->param][$record->table][$record->timepoint] = (array)$record;
                        }
                    } else if ($record->table == 'details') {
                        if (isset($data[$record->userid][$record->page][$record->param][$record->table][$record->currentstamp][$record->timepoint])) {
                            $item = &$data[$record->userid][$record->page][$record->param][$record->table][$record->currentstamp][$record->timepoint];
                            if (isset($record->visits)) {
                                @$item['visits'] += $record->visits;
                            }
                            $item['timespend'] += $record->timespend;
                            $item['ajaxrequest'] = min($item['ajaxrequest'], $record->ajaxrequest);

                        } else {
                            $data[$record->userid][$record->page][$record->param][$record->table][$record->currentstamp][$record->timepoint] = (array)$record;
                        }
                    }
                }
                if (!feof($handle)) {
                    mtrace("Error reading file " . $filename);
                }
                fclose($handle);
            }

            try {
                $transaction = $DB->start_delegated_transaction();

                foreach ($data as $user) {
                    foreach ($user as $page) {
                        foreach ($page as $param) {
                            $trrecord = (object)$param['tracking'];
                            $trparams = array(
                                'userid' => $trrecord->userid,
                                'page' => $trrecord->page,
                                'param' => $trrecord->param
                            );
                            $trfields = 'id, visits, timespend, lastaccess';

                            if ($tracking = $DB->get_record('local_intellidata_tracking', $trparams, $trfields)) {
                                if ($tracking->lastaccess < strtotime('today') || $trrecord->ajaxrequest == 0) {
                                    $tracking->lastaccess = $trrecord->lastaccess;
                                }
                                if (isset($trrecord->visits)) {
                                    $tracking->visits += $trrecord->visits;
                                }
                                $tracking->timespend += $trrecord->timespend;
                                $tracking->useragent = $trrecord->useragent;
                                $tracking->ip = $trrecord->ip;
                                $tracking->timemodified = time();
                                $DB->update_record('local_intellidata_tracking', $tracking);
                            } else {
                                $tracking = new \stdClass();
                                $tracking->id = $DB->insert_record('local_intellidata_tracking', $trrecord, true);
                            }

                            $logrecords = $param['logs'];
                            foreach ($logrecords as $logrecord) {
                                $logrecord = (object)$logrecord;
                                $logparams = array(
                                    'trackid' => $tracking->id,
                                    'timepoint' => $logrecord->timepoint
                                );
                                if ($log = $DB->get_record('local_intellidata_trlogs', $logparams)) {
                                    if (isset($logrecord->visits)) {
                                        $log->visits += $logrecord->visits;
                                    }
                                    $log->timespend += $logrecord->timespend;
                                    $log->timemodified = time();
                                    $DB->update_record('local_intellidata_trlogs', $log);
                                } else {
                                    $log = new \stdClass();
                                    $log->trackid = $tracking->id;
                                    $log->visits = $logrecord->visits;
                                    $log->timespend = $logrecord->timespend;
                                    $log->timepoint = $logrecord->timepoint;
                                    $log->timemodified = time();
                                    $log->id = $DB->insert_record('local_intellidata_trlogs', $log, true);
                                }

                                $detailrecords = $param['details'][$logrecord->timepoint];
                                foreach ($detailrecords as $detailrecord) {
                                    $detailrecord = (object)$detailrecord;
                                    $detailparams = array(
                                        'logid' => $log->id,
                                        'timepoint' => $detailrecord->timepoint
                                    );
                                    if ($detail = $DB->get_record('local_intellidata_trdetails', $detailparams)) {
                                        if (isset($detailrecord->visits)) {
                                            $detail->visits += $detailrecord->visits;
                                        }
                                        $detail->timespend += $detailrecord->timespend;
                                        $detail->timemodified = time();
                                        $DB->update_record('local_intellidata_trdetails', $detail);
                                    } else {
                                        $detail = new \stdClass();
                                        $detail->logid = $log->id;
                                        $detail->visits = $detailrecord->visits;
                                        $detail->timespend = $detailrecord->timespend;
                                        $detail->timepoint = $detailrecord->timepoint;
                                        $detail->timemodified = time();
                                        $detail->id = $DB->insert_record('local_intellidata_trdetails', $detail, true);
                                    }
                                }
                            }
                        }
                    }
                }

                $transaction->allow_commit();
            } catch (\Exception $e) {
                if (!empty($transaction) && !$transaction->is_disposed()) {
                    $transaction->rollback($e);
                }
            }

            $trackingstorage->delete_filepath($tempfilepath);
            mtrace("Successfull imported for user: $userid");
        }

        mtrace('IntelliData Tracking Files Export completed!');
    }
}

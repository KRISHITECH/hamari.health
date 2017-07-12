<?php

/* +-----------------------------------------------------------------------------+
 *    OpenEMR - Open Source Electronic Medical Record
 *    Copyright (C) 2013 Z&H Consultancy Services Private Limited <sam@zhservices.com>
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *    @author  Sanal Jacob <sanalj@zhservices.com>
 * +------------------------------------------------------------------------------+
 */

namespace Lab\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use \Application\Model\ApplicationTable;
use Zend\Validator\File\Size;
use CouchDB;

class ResultTable extends AbstractTableGateway {

    public $tableGateway;
    protected $applicationTable;

    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
        $adapter = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
        $this->adapter = $adapter;
        $this->applicationTable = new ApplicationTable();
    }

    /**
     * listLabOptions()
     * @param type $data
     * @return boolean
     */
    public function listLabOptions($data) {
        if (isset($data['option_id'])) {
            $where = " AND option_id='$data[option_id]'";
        }
        $sql = "SELECT option_id, title FROM list_options 
                                        WHERE list_id='" . $data['optId'] . "' $where 
                                        ORDER BY seq, title";
        $result = $this->applicationTable->zQuery($sql);
        $arr = array();
        $i = 0;
        if ($data['opt'] == 'search') {
            $arr[$i] = array(
                'option_id' => 'all',
                'title' => xlt('All'),
                'selected' => TRUE,
            );
            $i++;
        }

        foreach ($result as $row) {
            $arr[$i] = array(
                'option_id' => htmlspecialchars($row['option_id'], ENT_QUOTES),
                'title' => xlt($row['title']),
            );
            if ($data['optId'] == 'ord_status' && $row['option_id'] == 'pending') {
                $arr[$i]['selected'] = true;
            }
            $i++;
        }
        return $arr;
    }

    /**
     * listResultComment()
     * @param type $data
     * @return boolean
     */
    public function listResultComment($data) {
        $sql = "SELECT result_status, facility, comments FROM procedure_result 
                                        WHERE procedure_report_id='" . $data['procedure_report_id'] . "'";
        $result = $this->applicationTable->zQuery($sql);
        $string = '';
        $arr = array();
        foreach ($result as $row) {
            $result_notes = '';
            $i = strpos($row['comments'], "\n");
            if ($i !== FALSE) {
                $result_notes = trim(substr($row['comments'], $i + 1));
                $result_comments = substr($row['comments'], 0, $i);
            }
            $result_comments = trim($result_comments);
            $string = $row['result_status'] . '|' . $row['facility'] . '|' . $result_comments . '|' . $result_notes;
            $title = $this->listLabOptions(array('option_id' => $row['result_status'], 'optId' => 'proc_res_status'));
            $arr[0]['title'] = $title[0]['title'];
            $arr[0]['result_status'] = trim($row['result_status']);
            $arr[0]['facility'] = $row['facility'];
            $arr[0]['comments'] = $result_comments;
            $arr[0]['notes'] = $result_notes;
            $arr[0]['selected'] = true;
        }
        return $arr;
    }

    /**
     * listLabResult()
     * @global type $pid
     * @param type $data
     * @param type $pageno
     * @param type $orderids
     * @param type $encounterIds
     * @return type
     */
    public function listLabResult($data, $pageno, $orderids = '', $encounterIds = '') {

        global $pid;
        $flagSearch = 0;

        if (isset($data['statusReport']) && $data['statusReport'] != 'all') {
            $statusReport = $data['statusReport'];
            $flagSearch = 1;
        }
        if (isset($data['statusOrder']) && $data['statusOrder'] == 'pending') {
            $statusOrder = $data['statusOrder'];
        } elseif (isset($data['statusOrder'])) {
            if ($data['statusOrder'] != 'all') {
                $statusOrder = $data['statusOrder'];
            }
            $flagSearch = 1;
        }
        if (isset($data['statusResult']) && $data['statusResult'] != 'all') {
            $statsResult = $data['statusResult'];
        }
        if (isset($data['dtFrom'])) {
            $dtFrom = $data['dtFrom'];
        }
        if (isset($data['dtTo'])) {
            $dtTo = $data['dtTo'];
        }
        if (isset($data['dtFrom']) && $data['dtTo'] == '') {
            $dtTo = $data['dtFrom'];
        }
        if (isset($data['encounter'])) {
            $enc = $data['encounter'];
        }
        if (isset($data['testname'])) {
            $test = $data['testname'];
        }
        if (isset($data['labname'])) {
            if ($data['labname'] != "all") {
                $labname = $data['labname'];
                $labnamearr = explode("|", $labname);
                $lab = $labnamearr[0];
            } else {
                $lab = $data['labname'];
            }
        }

        $form_review = 1; // review mode
        $lastpoid = -1;
        $lastpcid = -1;
        $lastprid = -1;
        $encount = 0;
        $lino = 0;
        $extra_html = '';
        $lastrcn = '';
        $facilities = array();
        $prevorder_title = '';

        $selects = "CONCAT(pa.lname, ',', pa.fname) AS patient_name, po.result_file_url, po.patient_id,po.encounter_id, po.lab_id, pp.remote_host, pp.login, pp.password, pp.local_requisition, po.order_status, po.procedure_order_id, po.date_ordered, pc.procedure_order_seq,pc.specimen_details, " .
                "pt1.procedure_type_id AS order_type_id, pc.procedure_name, " .
                "pr.procedure_report_id, pr.date_report, pr.date_collected, pr.specimen_num, " .
                "pr.report_status, pr.review_status,CONCAT_WS('',pc.procedure_code,pc.procedure_suffix) AS proc_code,po.return_comments,pp.name,pp.mirth_lab_id";

        $joins = "JOIN procedure_order_code AS pc ON pc.procedure_order_id = po.procedure_order_id " .
                "LEFT JOIN procedure_type AS pt1 ON pt1.lab_id = po.lab_id AND pt1.procedure_code = pc.procedure_code " .
                "LEFT JOIN procedure_report AS pr ON pr.procedure_order_id = po.procedure_order_id " .
                "AND pr.procedure_order_seq = pc.procedure_order_seq " .
                "LEFT JOIN patient_data AS pa ON pa.pid=po.patient_id LEFT JOIN procedure_providers AS pp ON pp.ppid=po.lab_id";
        $groupby = '';
        if ($flagSearch == 1) {
            
        }
        $orderby = "po.procedure_order_id DESC, pr.procedure_report_id, proc_code, po.date_ordered,  " .
                "pc.procedure_order_seq, pr.procedure_order_seq";

        $where = "1 = 1";
        if ($statusReport) {
            $where .= " AND pr.report_status='$statusReport'";
        }
        if ($statusOrder) {
            $where .= " AND po.order_status='$statusOrder'";
        }
        if ($dtFrom) {
            $where .= " AND po.date_ordered BETWEEN '$dtFrom' AND '$dtTo'";
        }
        if ($enc) {
            $where .= " AND po.encounter_id = '$enc'";
        }
        if ($test) {
            $where .= " AND pc.procedure_name LIKE '%$test%'";
        }
        if ($pid) {
            $where .= " AND po.patient_id = '$pid'";
        }
        if ($lab) {
            if ($lab != "all") {
                $where .= " AND pp.ppid = '$lab'";
            }
        }
        if ($orderids) {
            $where .= " AND po.procedure_order_id IN ( $orderids ) ";
        }
        if ($encounterIds) {
            $where .= " AND po.encounter_id IN ( $encounterIds ) ";
        }
        $start = isset($pageno) ? $pageno : 0;
        $rows = isset($data['rows']) ? $data['rows'] : 40;
        if ($pageno == 1) {
            $start = $pageno - 1;
        } elseif ($pageno > 1) {
            $start = (($pageno - 1) * $rows);
            $rows = $pageno * $rows;
        }

        $sql_cnt = "SELECT $selects " .
                "FROM procedure_order AS po " .
                "$joins " .
                "WHERE $where " .
                "$groupby ORDER BY $orderby ";

        $result_cnt = $this->applicationTable->zQuery($sql_cnt);

        $totrows = $result_cnt->count();


        $sql = "SELECT $selects " .
                "FROM procedure_order AS po " .
                "$joins " .
                "WHERE $where " .
                "$groupby ORDER BY $orderby LIMIT $start,$rows ";

        $result = $this->applicationTable->zQuery($sql);
        $arr1 = array();
        $i = 0;
        $count = 0;
        foreach ($result as $row) {
            $order_type_id = empty($row['order_type_id']) ? 0 : ($row['order_type_id'] + 0);
            $order_id = empty($row['procedure_order_id']) ? 0 : ($row['procedure_order_id'] + 0);
            $order_seq = empty($row['procedure_order_seq']) ? 0 : ($row['procedure_order_seq'] + 0);
            $report_id = empty($row['procedure_report_id']) ? 0 : ($row['procedure_report_id'] + 0);
            $date_report = empty($row['date_report']) ? '' : $row['date_report'];
            $date_collected = empty($row['date_collected']) ? '' : substr($row['date_collected'], 0, 16);
            $specimen_num = empty($row['specimen_num']) ? '' : $row['specimen_num'];
            $report_status = empty($row['report_status']) ? '' : $row['report_status'];
            $review_status = empty($row['review_status']) ? 'received' : $row['review_status'];
            $remoteHost = empty($row['remote_host']) ? '' : $row['remote_host'];
            $local_requisition = empty($row['local_requisition']) ? '' : $row['local_requisition'];
            $local_result_pdf = empty($row['local_result_pdf']) ? '' : $row['local_result_pdf'];
            $remoteUser = empty($row['login']) ? '' : $row['login'];
            $remotePass = empty($row['password']) ? '' : $row['password'];
            $patient_instructions = empty($row['return_comments']) ? '' : $row['return_comments'];
            $lab_name = empty($row['name']) ? '' : $row['name'];
            $spec_detail = empty($row['specimen_details']) ? '' : $row['specimen_details'];
            $mirth_lab_id = empty($row['mirth_lab_id']) ? 0 : $row['mirth_lab_id'];
            $resultFile = empty($row['result_file_url']) ? '' : $row['result_file_url'];
            if ($flagSearch == 0) {
                if ($form_review) {
                    if ($review_status == "reviewed")
                        continue;
                } else {
                    if ($review_status == "received")
                        continue;
                }
            }

            $selects = "pt2.procedure_type, pt2.procedure_code, pt2.units AS pt2_units, " .
                    "pt2.range AS pt2_range, pt2.procedure_type_id AS procedure_type_id, " .
                    "pt2.name AS name, pt2.description, pt2.seq AS seq, " .
                    "ps.procedure_result_id, ps.result_code AS result_code, ps.result_text, ps.abnormal, ps.result, " .
                    "ps.range, ps.result_status as Mresult_status, ps.facility, ps.comments, ps.units, ps.comments as Mcomments,ps.order_title as Morder_title,ps.profile_title as Mprofile_title,ps.code_suffix as Mcode_suffix";
            $selects .= ", psr.procedure_subtest_result_id,
                                psr.subtest_code,
                                psr.subtest_desc AS sub_result_text,
                                psr.result_value AS sub_result,
                                psr.facility as sub_facility,
                                psr.abnormal_flag AS sub_abnormal,
                                psr.units AS sub_units,
                                psr.range AS sub_range,psr.comments as comments,psr.order_title as order_title,psr.profile_title as profile_title,psr.code_suffix as code_suffix,psr.result_status as result_status,psr.providers_id as providers_id";

            // Skip LIKE Cluse for Ext Lab if not set the procedure code or parent
            $pt2cond = '';
            $editor = 0;
            if ($remoteHost != '' && $remoteUser != '' && $remotePass != '') {
                $pt2cond = "pt2.parent = $order_type_id ";
                $editor = 1;
            } else {
                $pt2cond = "pt2.parent = $order_type_id AND " .
                        "(pt2.procedure_type LIKE 'res%' OR pt2.procedure_type LIKE 'rec%')";
            }

            if ($local_requisition == 1) {
                $editor = 2;
            }
            /** If the file already in the field */
            $fileExist = 0;
            if ($resultFile != '') {
                $fileExist = 1;
            }

            //$editor = 0;
            $pscond = "ps.procedure_report_id = $report_id";

            $joincond = "ps.result_code = pt2.procedure_code";
            $joincond .= " LEFT JOIN procedure_subtest_result AS psr ON psr.procedure_report_id=$report_id ";
            if ($statusResult) {
                $where .= " AND (ps.result_status='$statusResult' OR psr.result_status='$statusResult')";
            }

            $query = "(SELECT $selects FROM procedure_type AS pt2 " .
                    "LEFT JOIN procedure_result AS ps ON $pscond AND $joincond " .
                    "WHERE $pt2cond" .
                    ") UNION (" .
                    "SELECT $selects FROM procedure_result AS ps " .
                    "LEFT JOIN procedure_type AS pt2 ON $pt2cond AND $joincond " .
                    "WHERE $pscond) " .
                    "ORDER BY procedure_subtest_result_id, seq, name, procedure_type_id";
            //echo $query."<br><br>";

            $rres = $this->applicationTable->zQuery($query);


            foreach ($rres as $rrow) {
                $restyp_code = empty($rrow['procedure_code']) ? '' : $rrow['procedure_code'];
                $restyp_type = empty($rrow['procedure_type']) ? '' : $rrow['procedure_type'];
                $restyp_name = empty($rrow['name']) ? '' : $rrow['name'];
                $restyp_units = empty($rrow['pt2_units']) ? '' : $rrow['pt2_units'];
                $restyp_range = empty($rrow['pt2_range']) ? '' : $rrow['pt2_range'];

                $result_id = empty($rrow['procedure_result_id']) ? 0 : ($rrow['procedure_result_id'] + 0);
                $result_code = empty($rrow['result_code']) ? $restyp_code : $rrow['result_code'];
                $order_title = empty($rrow['order_title']) ? $rrow['Morder_title'] : $rrow['order_title'];
                $providers_id = empty($rrow['providers_id']) ? '' : $rrow['providers_id'];
                $profile_title = empty($rrow['profile_title']) ? $rrow['Mprofile_title'] : $rrow['profile_title'];
                $code_suffix = empty($rrow['code_suffix']) ? $rrow['code_suffix'] : $rrow['code_suffix'];
                if ($rrow['sub_result_text'] != '') {
                    $result_text = $rrow['sub_result_text'];
                } else {
                    $result_text = empty($rrow['result_text']) ? $restyp_name : $rrow['result_text'];
                }
                if ($rrow['sub_abnormal']) {
                    $result_abnormal = $rrow['sub_abnormal'];
                } else {
                    $result_abnormal = empty($rrow['abnormal']) ? '' : $rrow['abnormal'];
                }
                if ($rrow['sub_result']) {
                    $result_result = $rrow['sub_result'];
                } else {
                    $result_result = empty($rrow['result']) ? '' : $rrow['result'];
                }
                if ($rrow['facility']) {
                    $result_facility = $rrow['facility'];
                } else {
                    $result_facility = empty($rrow['sub_facility']) ? '' : $rrow['sub_facility'];
                }
                if ($rrow['sub_units']) {
                    $result_units = $rrow['sub_units'];
                } else {
                    $result_units = empty($rrow['units']) ? $restyp_units : $rrow['units'];
                }

                $comments = '';
                $comments = $rrow['Mcomments'] . " " . $rrow['comments'];
                $result_comments = empty($comments) ? '' : $comments;
                if ($rrow['sub_range']) {
                    $result_range = $rrow['sub_range'];
                } else {
                    $result_range = empty($rrow['range']) ? $restyp_range : $rrow['range'];
                }


                $result_status = $rrow['Mresult_status'] ? $rrow['Mresult_status'] : $rrow['result_status'];

                if (!empty($rrow['subtest_code'])) {
                    $result_code = $rrow['subtest_code'];
                    $restyp_units = $rrow['units'];
                    $restyp_range = $rrow['range'];
                    if ($rrow['abnormal'] == 'H') {
                        $result_abnormal = 'high';
                    }
                }
                if ($lastpoid != $order_id || $lastpcid != $order_seq) {
                    $lastprid = -1;
                    if ($lastpoid != $order_id) {
                        if ($arr1[$i - 1]['procedure_name'] != $row['procedure_name'] || $arr1[$i - 1]['order_id'] != $row['order_id']) {
                            $arr1[$i]['date_ordered'] = $row['date_ordered'];
                        }
                    }
                }

                if ($lastpoid != $order_id || $lastpcid != $order_seq) {
                    $lastprid = -1;
                    //if ($lastpoid != $order_id) {
                    if ($arr1[$i - 1]['procedure_name'] != $row['procedure_name'] || $arr1[$i - 1]['order_id'] != $row['order_id']) {
                        $arr1[$i]['procedure_name'] = $row['procedure_name'];
                    }
                    //}
                }


                if ($lastpoid != $order_id || $lastpcid != $order_seq) {
                    $lastprid = -1;
                    if ($lastpoid != $order_id) {

                        if ($arr1[$i - 1]['procedure_name'] != $row['procedure_name'] || $arr1[$i - 1]['order_id'] != $row['order_id']) {
                            $arr1[$i]['order_id'] = $order_id;
                            if ($count % 2 == 0) {
                                $arr1[$i]['color'] = "#CCE6FF";
                            } else {
                                $arr1[$i]['color'] = "#fce7b6";
                            }
                            $count++;
                        }
                    }
                }


                if ($arr1[$i - 1]['procedure_name'] != $row['procedure_name'] || $arr1[$i - 1]['order_id'] != $row['order_id']) {
                    $arr1[$i]['date_report'] = $date_report;
                    $arr1[$i]['order_id1'] = $order_id;
                    $arr1[$i]['encounter_id'] = $row['encounter_id'];

                    $title = $this->listLabOptions(array('option_id' => $row['order_status'], 'optId' => 'ord_status'));
                    $arr1[$i]['order_status'] = isset($title) ? xlt($title[0]['title']) : '';
                }
                if ($order_id != $lastpoid || $i == 0) {
                    $arr1[$i]['patient_id'] = $row['patient_id'];
                }
                if ($order_id != $lastpoid || $lastdatecollected != $date_collected) {
                    $arr1[$i]['date_collected'] = $date_collected;
                }


                $arr1[$i]['specimen_num'] = xlt($specimen_num);
                $title = $this->listLabOptions(array('option_id' => $report_status, 'optId' => 'proc_rep_status'));
                $arr1[$i]['report_status'] = xlt($report_status);
                $arr1[$i]['report_title'] = isset($title) ? xlt($title[0]['title']) : '';
                $arr1[$i]['order_type_id'] = $order_type_id;
                $arr1[$i]['procedure_order_id'] = $order_id;
                $arr1[$i]['procedure_order_seq'] = $order_seq;
                $arr1[$i]['procedure_report_id'] = $report_id;
                $arr1[$i]['review_status'] = xlt($review_status);
                $arr1[$i]['procedure_code'] = xlt($restyp_code);
                $arr1[$i]['procedure_type'] = xlt($restyp_type);
                $arr1[$i]['name'] = xlt($restyp_name);
                $arr1[$i]['pt2_units'] = xlt($restyp_units);
                $arr1[$i]['pt2_range'] = xlt($restyp_range);
                $arr1[$i]['procedure_result_id'] = $result_id;
                $arr1[$i]['result_code'] = xlt($result_code);
                $arr1[$i]['result_text'] = xlt($result_text);

                $title = $this->listLabOptions(array('option_id' => $result_abnormal, 'optId' => 'proc_res_abnormal'));

                $arr1[$i]['abnormal_title'] = isset($title) ? xlt($title[0]['title']) : '';
                $arr1[$i]['abnormal'] = xlt($result_abnormal);

                $arr1[$i]['result'] = xlt($result_result);
                $arr1[$i]['units'] = xlt($result_units);
                $arr1[$i]['facility'] = xlt($facility);
                $arr1[$i]['comments'] = xlt($result_comments);
                $arr1[$i]['range'] = xlt($result_range);
                $arr1[$i]['result_status'] = xlt($result_status);
                $arr1[$i]['result_facility'] = $result_facility;
                $arr1[$i]['editor'] = $editor;
                $arr1[$i]['order_title'] = $order_title;
                $arr1[$i]['profile_title'] = $profile_title;
                $arr1[$i]['code_suffix'] = $code_suffix;
                $arr1[$i]['patient_instructions'] = $patient_instructions;
                $arr1[$i]['lab_name'] = $lab_name;
                $arr1[$i]['mirth_lab_id'] = $mirth_lab_id;
                $arr1[$i]['spec_detail'] = $spec_detail;
                $arr1[$i]['file_exist'] = $fileExist;
                $arr1[$i]['providers_id'] = $providers_id;

                $i++;
                $lastpoid = $order_id;
                $lastpcid = $order_seq;
                $lastprid = $report_id;
                $lastdatecollected = $date_collected;
                $prevorder_title = $order_title;
            }
        }
        $arr1[$i]['totalRows'] = $totrows;

        return $arr1;
    }

    /**
     * saveResult()
     * @param type $data
     */
    public function saveResult($data) {
        $report_id = $data['procedure_report_id'];
        $order_id = $data['procedure_order_id'];
        $result_id = $data['procedure_result_id'];
        $specimen_num = $data['specimen_num'];
        $report_status = $data['report_status'];
        $order_seq = $data['procedure_order_seq'];
        $date_report = $data['date_report'];
        $date_collected = $data['date_collected'];

        $result_code = $data['result_code'];
        $procedure_report_id = $data['procedure_report_id'];
        $result_text = $data['result_text'];
        $abnormal = $data['abnormal'];
        $result = $data['result'];
        $range = $data['range'];
        $units = $data['units'];
        $result_status = $data['result_status'];
        $facility = $data['facility'];
        $comments = $data['comments'];

        if (!empty($date_report)) {
            if ($report_id > 0) {
                $arr = array(
                    $order_id,
                    $specimen_num,
                    $report_status,
                    $order_seq,
                    $date_report,
                    $date_collected,
                    'reviewed',
                    $report_id,
                );

                $sql = "UPDATE procedure_report 
                                        SET procedure_order_id= ?, 
                                            specimen_num= ?, 
                                            report_status= ?, 
                                            procedure_order_seq= ?, 
                                            date_report= ?, 
                                            date_collected= ?, 
                                            review_status = ?  								
                                        WHERE procedure_report_id = ?";
                $this->applicationTable->zQuery($sql, $arr);
            } else {
                $arr = array(
                    $order_id,
                    $specimen_num,
                    $report_status,
                    $order_seq,
                    $date_report,
                    $date_collected,
                    'reviewed',
                );

                $sql = "INSERT INTO procedure_report 
                                        SET procedure_order_id= ?, 
                                            specimen_num= ?, 
                                            report_status= ?,
                                            procedure_order_seq= ?, 
                                            date_report= ?,
                                            date_collected= ?, 								
                                            review_status = ?";

                $res = $this->applicationTable->zQuery($sql, $arr);

                $report_id = $res->getGeneratedValue();
            }
        }
        if (!empty($date_report)) {
            if ($result_id > 0) {
                $arr = array(
                    $report_id,
                    $result_code,
                    $result_text,
                    $abnormal,
                    $result,
                    $range,
                    $units,
                    $result_status,
                    $facility,
                    $comments,
                    $result_id,
                );
                $sql = "UPDATE procedure_result 
                                        SET procedure_report_id= ?, 
                                            result_code= ?, 
                                            result_text= ?, 
                                            abnormal= ?, 
                                            result= ?, 
                                            `range`= ?, 
                                            units= ?, 
                                            result_status= ?, 
                                            facility= ?, 
                                            comments= ?							
                                        WHERE procedure_result_id = ?";

                $this->applicationTable->zQuery($sql, $arr);
            } else {
                $arr = array(
                    $report_id,
                    $result_code,
                    $result_text,
                    $abnormal,
                    $result,
                    $range,
                    $units,
                    $result_status,
                    $facility,
                    $comments,
                );
                $sql = "INSERT INTO procedure_result 
                                        SET procedure_report_id= ?, 
                                                result_code= ?, 
                                                result_text= ?, 
                                                abnormal= ?, 
                                                result= ?, 
                                                `range`= ?, 
                                                units= ?, 
                                                result_status= ?, 
                                                facility= ?, 
                                                comments= ?";

                $this->applicationTable->zQuery($sql, $arr);
            }
        }
    }

    /**
     * Result pulling and view
     */
    public function getProcedureOrderSequences($proc_order_id) {
        $sql_order_test = "SELECT procedure_order_id, procedure_order_seq FROM procedure_order_code WHERE procedure_order_id = ? ";
        $value_arr = array();

        $value_arr['procedure_order_id'] = $proc_order_id;

        $result = $this->applicationTable->zQuery($sql_order_test, $value_arr);

        $result_arr = array();

        foreach ($result as $row) {
            $result_arr[] = $row;
        }
        return $result_arr;
    }

    /**
     * getProcedureOrderSequence
     * @param type $proc_order_id
     * @param type $code_suffix
     * @return type
     */
    public function getProcedureOrderSequence($proc_order_id, $code_suffix) {
        $sql_orderseq = "SELECT procedure_order_seq FROM procedure_order_code WHERE
				    procedure_order_id = ? AND procedure_code = ? ";

        $value_arr = array();

        $value_arr['procedure_order_id'] = $proc_order_id;
        $value_arr['code_suffix'] = $code_suffix;

        $result = $this->applicationTable->zQuery($sql_orderseq, $value_arr);
        foreach ($result as $row)
            return ($row['procedure_order_seq'] <> "") ? $row['procedure_order_seq'] : 0;
    }

    /**
     * updateReturnComments
     * @param type $sql
     * @param type $in_array
     */
    public function updateReturnComments($sql, $in_array) {
        $this->applicationTable->zQuery($sql, $in_array);
    }

    /**
     * insertProcedureReport
     * @param type $sql
     * @param type $in_array
     * @return type
     */
    public function insertProcedureReport($sql, $in_array) {
        $res = $this->applicationTable->zQuery($sql, $in_array);
        $procedure_report_id = $res->getGeneratedValue();
        return $procedure_report_id;
    }

    /**
     * insertProcedureResult
     * @param type $sql
     * @param type $in_array
     * @return type
     */
    public function insertProcedureResult($sql, $in_array) {
        $res = $this->applicationTable->zQuery($sql, $in_array);
        $procedure_result_id = $res->getGeneratedValue();
        return $procedure_result_id;
    }

    /**
     * getOrderStatus
     * @param type $proc_order_id
     * @return type
     */
    public function getOrderStatus($proc_order_id) {
        $sql_status = "SELECT order_status FROM procedure_order WHERE procedure_order_id = ? ";
        $status_value_arr = array();

        $status_value_arr['procedure_order_id'] = $proc_order_id;
        $res = $this->applicationTable->zQuery($sql_status, $status_value_arr);
        $res_status = $res->current();
        return $res_status['order_status'];
    }

    /**
     * setOrderStatus
     * @param type $proc_order_id
     * @param type $status
     * @return type
     */
    public function setOrderStatus($proc_order_id, $status) {
        $sql_status = "UPDATE procedure_order SET order_status = ? WHERE procedure_order_id = ? ";
        $status_value_arr = array();

        $status_value_arr['status'] = $status;
        $status_value_arr['procedure_order_id'] = $proc_order_id;

        $res = $this->applicationTable->zQuery($sql_status, $status_value_arr);
        $res_status = $res->current();
        return $res_status;
    }

    /**
     * getOrderResultFile
     * @param type $proc_order_id
     * @return type
     */
    public function getOrderResultFile($proc_order_id) {
        $sql_status = "SELECT result_file_url FROM procedure_order WHERE procedure_order_id = ? ";
        $status_value_arr = array();

        $status_value_arr['procedure_order_id'] = $proc_order_id;
        $res = $this->applicationTable->zQuery($sql_status, $status_value_arr);
        $res_status = $res->current();
        return $res_status['result_file_url'];
    }

    /**
     * getClientCredentials
     * @param type $proc_order_id
     * @return type
     */
    public function getClientCredentials($proc_order_id) {
        $sql_proc = "SELECT lab_id FROM procedure_order WHERE procedure_order_id = ? ";
        $proc_value_arr = array($proc_order_id);
        $res = $this->applicationTable->zQuery($sql_proc, $proc_value_arr);
        $res_proc = $res->current();
        $sql_cred = "SELECT  login, password, remote_host,mirth_lab_name FROM procedure_providers WHERE ppid = ? ";
        $result = $this->applicationTable->zQuery($sql_cred, $res_proc);
        $res_cred = $result->current();
        return $res_cred;
    }

    /**
     * getSingulexCredentials
     * @return type
     */
    public function getSingulexCredentials() {
        $sql = "SELECT login,password,remote_host FROM procedure_providers WHERE name = 'Singulex'";
        $res = $this->applicationTable->zQuery($sql);
        $res_cred = $res->current();
        return $res_cred;
    }

    /**
     * changeOrderResultStatus
     * @param type $proc_order_id
     * @param type $status
     * @param type $file_name
     * @param type $rev_id
     * @param type $storage_method
     * @return type
     */
    public function changeOrderResultStatus($proc_order_id, $status, $file_name, $rev_id, $storage_method) {
        $sql_status = "UPDATE procedure_order SET order_status = ?, result_file_url = ?, couch_rev_id = ? , storage_type = ? WHERE procedure_order_id = ? ";
        $status_value_arr = array($status, $file_name, $rev_id, $storage_method, $proc_order_id);
        $res_status = $this->applicationTable->zQuery($sql_status, $status_value_arr);
        return $res_status;
    }

    /**
     * getOrderRequisitionFile
     * @param type $proc_order_id
     * @return type
     */
    public function getOrderRequisitionFile($proc_order_id) {
        $sql_status = "SELECT requisition_file_url FROM procedure_order WHERE procedure_order_id = ? ";
        $status_value_arr = array($proc_order_id);
        $result = $this->applicationTable->zQuery($sql_status, $status_value_arr);
        $res_status = $result->current();
        return $res_status['requisition_file_url'];
    }

    /**
     * changeOrderRequisitionStatus
     * @param type $proc_order_id
     * @param type $status
     * @param type $file_name
     * @return type
     */
    public function changeOrderRequisitionStatus($proc_order_id, $status, $file_name) {

        $sql_status = "UPDATE procedure_order SET order_status = ?, requisition_file_url = ? WHERE procedure_order_id = ? ";

        $status_value_arr = array($status, $file_name, $proc_order_id);

        $res_status = $this->applicationTable->zQuery($sql_status, $status_value_arr);
        return $res_status;
    }

    /**
     * saveResultComments
     * @param type $result_status
     * @param type $facility
     * @param type $comments
     * @param type $procedure_report_id
     * @return type
     */
    public function saveResultComments($result_status, $facility, $comments, $procedure_report_id) {
        $sql_check = "SELECT  procedure_result_id FROM  procedure_result WHERE procedure_report_id = ?  ";
        $result_id_array = array($procedure_report_id);
        $result = $this->applicationTable->zQuery($sql_check, $result_id_array);
        $res_check = $result->current();
        if ($res_check['procedure_result_id']) {
            $sql_resultcomments = "UPDATE  procedure_result  SET result_status = ?, facility = ?, comments = ? WHERE procedure_report_id = ? ";
        } else {
            $sql_resultcomments = "INSERT INTO  procedure_result  SET result_status = ?, facility = ?, comments = ?,  procedure_report_id = ?";
        }
        $resultcomments_array = array($result_status, $facility, $comments, $procedure_report_id);

        $result = $this->applicationTable->zQuery($sql_resultcomments, $resultcomments_array);
        $res_comments = $result->current();
        return $res_comments;
    }

    /**
     * getOrderResultPulledCount
     * @param type $proc_order_id
     * @return type
     */
    public function getOrderResultPulledCount($proc_order_id) {
        $sql_check = "SELECT COUNT(procedure_order_id) AS cnt FROM procedure_report WHERE procedure_order_id = ? ";
        $status_value_arr = array($proc_order_id);
        $result = $this->applicationTable->zQuery($sql_check, $status_value_arr);
        $res_status = $result->current();
        return $res_status['cnt'];
    }

    /**
     * listResults
     * @param type $pat_id
     * @param type $from_dt
     * @param type $to_dt
     * @return type
     */
    public function listResults($pat_id, $from_dt, $to_dt) {
        $sql = "SELECT po.order_status, po.patient_id, po.date_ordered, poc.procedure_name, pr.specimen_num, pr.date_collected, pr.procedure_report_id AS prid,
        CONCAT(pd.lname, ' ', pd.fname) AS pname, pt2.units AS def_units, pt2.range AS def_range, prs.abnormal, prs.result, prs.result_status,
        prs.facility, prs.comments,prs.units, prs.range,pr.report_status FROM procedure_order po JOIN procedure_order_code poc ON poc.procedure_order_id = po.procedure_order_id
        AND po.order_status = 'pending' AND po.psc_hold = 'onsite' AND po.activity = 1 LEFT JOIN patient_data pd ON pd.pid = po.patient_id
        LEFT JOIN procedure_report pr ON pr.procedure_order_id = poc.procedure_order_id AND pr.procedure_order_seq = poc.procedure_order_seq
        LEFT JOIN procedure_result prs ON prs.procedure_report_id = pr.procedure_report_id LEFT JOIN procedure_type pt1 ON
        pt1.procedure_code = poc.procedure_code LEFT JOIN procedure_type pt2  ON pt2.parent = pt1.procedure_type_id AND pt2.procedure_type = 'res'";
        if ($pat_id || $from_dt || $to_dt) {
            $sql .= " WHERE ";
            $cond = 0;
            $param = array();
            if ($pat_id) {
                $sql .= " po.patient_id = ?";
                array_push($param, $pat_id);
                $cond = 1;
            }
            if ($from_dt && $to_dt) {
                if ($cond) {
                    $sql .= " AND po.date_ordered BETWEEN ? AND ?";
                } else {
                    $sql .= " po.date_ordered BETWEEN ? AND ?";
                    $cond = 1;
                }
                array_push($param, $from_dt, $to_dt);
            } elseif ($from_dt) {
                if ($cond) {
                    $sql .= " AND po.date_ordered > ?";
                } else {
                    $sql .= " po.date_ordered > ?";
                    $cond = 1;
                }
                array_push($param, $from_dt);
            } elseif ($to_dt) {
                if ($cond) {
                    $sql .= " AND po.date_ordered < ?";
                } else {
                    $sql .= " po.date_ordered < ?";
                    $cond = 1;
                }
                array_push($param, $to_dt);
            }
            if ($cond) {
                $sql .= " AND pr.procedure_report_id IS NOT NULL";
            } else {
                $sql .= " pr.procedure_report_id IS NOT NULL";
                $cond = 1;
            }
            $sql .= " ORDER BY po.procedure_order_id DESC";

            $result = $this->applicationTable->zQuery($sql, $param);
        } else {
            $sql .= " WHERE pr.procedure_report_id IS NOT NULL ORDER BY po.procedure_order_id DESC";
            $result = $this->applicationTable->zQuery($sql);
        }
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * getPatientName
     * @param type $pat_id
     * @return type
     */
    public function getPatientName($pat_id) {
        $this->applicationTable = new ApplicationTable();
        $sql = "SELECT CONCAT(lname,' ',fname) AS pname FROM patient_data WHERE pid = ?";
        $param = array($pat_id);
        $result = $this->applicationTable->zQuery($sql, $param);
        $pres = $result->current();
        return $pres['pname'];
    }

    /**
     * saveResultEntryDetails
     * @global \Lab\Model\type $pid
     * @param type $request
     * @param type $files
     */
    public function saveResultEntryDetails($request, $files) {
        global $pid;
        require_once($GLOBALS['srcdir'] . '/classes/CouchDB.class.php');
        $couch = new CouchDB();

        $existing_query = "SELECT * FROM procedure_result WHERE procedure_report_id = ?";
        $sqlins = "INSERT INTO procedure_result SET units = ?, result = ?, `range` = ?, abnormal = ?, facility = ?, comments = ?, result_status = ?, procedure_report_id = ?";
        $sqlupd = "UPDATE procedure_result SET units = ?, result = ?, `range` = ?, abnormal = ?, facility = ?, comments = ?, result_status = ? WHERE procedure_report_id = ?";

        for ($i = 0; $i < count($request->procedure_report_id); $i++) {
            $param = array();
            array_push($param, $request->units[$i]);
            array_push($param, $request->result[$i]);
            array_push($param, $request->range[$i]);
            array_push($param, $request->abnormal[$i]);
            if ($request->facility[$i]) {
                array_push($param, $request->facility[$i]);
            } else {
                array_push($param, '');
            }
            array_push($param, $request->comments[$i]);
            array_push($param, $request->result_status[$i]);
            array_push($param, $request->procedure_report_id[$i]);
            $existing_res = $this->applicationTable->zQuery($existing_query, array($request->procedure_report_id[$i]));

            $result = $existing_res->count();

            if ($result > 0) {
                $result = $this->applicationTable->zQuery($sqlupd, $param);
            } else {
                if ($request->result[$i] != "" || $request->range[$i] != "" || $request->abnormal[$i] != "" || $request->units[$i] != "" || $request->status[$i] != "" || $files->fileupload[$i]['name'] != "") {
                    $result = $this->applicationTable->zQuery($sqlins, $param);
                }
            }

            $queryUpdate = '';
            $sql = "SELECT procedure_order_id FROM procedure_report 
                                WHERE procedure_report_id=?";
            $result = $this->applicationTable->zQuery($sql, array($request->procedure_report_id[$i]));
            foreach ($result as $info)
                $procedureOrderId = $info['procedure_order_id'];
            $values = array(
                $request->status[$i],
                $procedureOrderId
            );
            /** Start File Writing */
            $allowedTypes = array(
                "image/pjpeg",
                "image/jpeg",
                "image/jpg",
                "image/png",
                "image/x-png",
                "image/gif",
                "application/pdf"
            );

            if ($files->fileupload[$i]['error'] == 0 && in_array($files->fileupload[$i]['type'], $allowedTypes)) {
                $time = microtime(true);
                $docname = $_SESSION['authId'] . " " . $pid . $time . "_res";
                if ($GLOBALS['document_storage_method'] == 1) {
                    $labresultfile = $couch->stringToId($docname);
                    $file = $files->fileupload[$i]['tmp_name'];
                    $fp = fopen($file, "r");
                    $content = fread($fp, filesize($file));
                    fclose($fp);
                    $json = json_encode(base64_encode($content));
                    $db = $GLOBALS['couchdb_dbase'];
                    $docdata = array($db, $labresultfile, $pid, '', $files->fileupload[$i]['type'], $json);
                    $resp = $couch->check_saveDOC($docdata);
                    if (!$resp->id || !$resp->rev) {
                        $docdata = array($db, $labresultfile, $pid, '');
                        $resp = $couch->retrieve_doc($docdata);
                        $labresultfile = $resp->_id;
                        $revid = $resp->_rev;
                    } else {
                        $labresultfile = $resp->id;
                        $revid = $resp->rev;
                    }
                    $queryUpdate = ",result_file_url = ?,   
                                    storage_type = ?, 
                                    couch_rev_id = ?";
                    $values = array(
                        $request->status[$i],
                        $labresultfile,
                        $GLOBALS['document_storage_method'],
                        $revid,
                        $procedureOrderId
                    );
                    if (!$labresultfile && !$revid) { //if couchdb save failed
                        $error .= "<font color='red'><b>" . xl("The file could not be saved to CouchDB.") . "</b></font>\n";
                        if ($GLOBALS['couchdb_log'] == 1) {
                            ob_start();
                            var_dump($resp);
                            $couchError = ob_get_clean();
                            $log_content = date('Y-m-d H:i:s') . " ==> Uploading document: " . $fname . "\r\n";
                            $log_content .= date('Y-m-d H:i:s') . " ==> Failed to Store document content to CouchDB.\r\n";
                            $log_content .= date('Y-m-d H:i:s') . " ==> Document ID: " . $labresultfile . "\r\n";
                            $log_content .= date('Y-m-d H:i:s') . " ==> " . print_r($docdata, 1) . "\r\n";
                            $log_content .= $couchError;
                            $this->document_upload_download_log($patient_id, $log_content); //log error if any, for testing phase only
                        }
                    }
                }
                if ($GLOBALS['document_storage_method'] == 0) {
                    $site_dir = $GLOBALS['OE_SITE_DIR'];
                    $result_dir = $site_dir . "/lab/result/";
                    if (!is_dir($result_dir)) {
                        mkdir($result_dir, 0777, true);
                    }
                    $ext = pathinfo($files->fileupload[$i]['name'], PATHINFO_EXTENSION);
                    $labresultfile = "labresult_" . md5($time) . "." . $ext;
                    $file = $files->fileupload[$i]['tmp_name'];
                    $fp = fopen($file, "r");
                    $content = fread($fp, filesize($file));
                    fclose($fp);
                    $fh = fopen($result_dir . $labresultfile, "wb");
                    fwrite($fh, $content);
                    fclose($fh);
                    $queryUpdate = ",result_file_url = ?,   
                                    storage_type = ?";
                    $values = array(
                        $request->status[$i],
                        $labresultfile,
                        $GLOBALS['document_storage_method'],
                        $procedureOrderId
                    );
                }
            }
            /** End File Writing */
            $sql = "UPDATE procedure_order SET order_status = ? 
                                                    $queryUpdate 
                                                WHERE procedure_order_id = ?";
            $this->applicationTable->zQuery($sql, $values);
        }
    }

    /**
     * deleteResults
     * @param type $order_id
     */
    public function deleteResults($order_id) {
        $sql = "DELETE pr.*,prs.*,psr.* FROM procedure_report pr LEFT JOIN procedure_result prs ON prs.procedure_report_id = pr.procedure_report_id
            LEFT JOIN procedure_subtest_result psr ON psr.procedure_report_id = pr.procedure_report_id WHERE pr.procedure_order_id = ?";
        $param = array($order_id);
        $pres = $this->applicationTable->zQuery($sql, $param);
    }

    /**
     * insertQuery
     * @param type $sql
     * @param type $in_array
     * @return type
     */
    public function insertQuery($sql, $in_array) {
        $procedure = $this->applicationTable->zQuery($sql, $in_array);
        $procedure_result_id = $procedure->getGeneratedValue();
        return $procedure_result_id;
    }

    /**
     * cancelOrder
     * @param type $request
     */
    public function cancelOrder($request) {
        $sql1 = "INSERT INTO procedure_report(procedure_order_id,procedure_order_seq) VALUES(?,?)";
        $res1 = $this->insertQuery($sql1, array($request->order, $request->seq));
        $sql2 = "INSERT INTO procedure_result(procedure_report_id,result_status,order_title,comments) VALUES(?,?,?,?)";
        $res2 = $this->insertQuery($sql2, array($res1, 'Cancelled', $request->proc_name, $request->comment));
    }

    /**
     * listEncounters
     * @global \Lab\Model\type $pid
     * @param type $data
     * @return type
     */
    public function listEncounters($data) {
        global $pid;

        $sql = "SELECT encounter,DATE(DATE) as dos FROM form_encounter WHERE pid=?";

        $result = $this->applicationTable->zQuery($sql, array($pid));

        $string = '';
        $arr = array();
        $i = 0;
        foreach ($result as $row) {
            $arr[$i] = array(
                'value' => $row['encounter'], ENT_QUOTES,
                'label' => $row['dos'],
            );
            $i++;
        }
        return $arr;
    }

    /**
     * selectPatientInfo
     * @param type $orderId
     * @return type
     */
    public function selectPatientInfo($orderId) {
        $result = $this->applicationTable->zQuery("SELECT send_fac_id,CONCAT_WS('-',login,procedure_order_id) AS labref,CONCAT_WS(',',lname,fname) AS pname,name,mirth_lab_id FROM procedure_order LEFT OUTER JOIN procedure_providers ON lab_id=ppid LEFT OUTER JOIN
         patient_data ON pid=patient_id WHERE procedure_order_id=?", array($orderId));
        return $result->current();
    }

    /**
     * getList
     * @param type $list_option_id
     * @param type $list_id
     * @return type
     */
    public function getList($list_option_id, $list_id) {
        $obj = new ApplicationTable();
        $sql = "SELECT title FROM list_options WHERE option_id = ? AND list_id = ?";
        $result = $obj->zQuery($sql, array($list_option_id, $list_id));
        $arr = array();
        $i = 0;
        foreach ($result as $row) {
            $arr[$i] = array(
                'list_title' => $row['title'],
            );
            $i++;
        }
        return $arr;
    }

    /**
     * getRequisition
     * @param type $labOrderid
     * @return type
     */
    public function getRequisition($labOrderid) {
        $result = $this->applicationTable->zQuery("SELECT po.*,pd.lname, pd.fname, pd.mname,CONCAT(pd.lname, ' ', pd.fname, ' ', pd.mname) AS pname,pd.street, pd.city, pd.state,pd.pid,"
                . "pd.pubpid, pd.postal_code, pd.DOB, TIMESTAMPDIFF(YEAR, pd.DOB, CURDATE()) AS age, pd.sex,pd.ss,pd.phone_home, "
                . " pd.parent_first,pd.parent_last,pd.parent_mid,pd.parent_add1,pd.parent_add2,pd.parent_city,pd.parent_state,pd.parent_zip,pd.parent_phone, "
                . "pp.name, pp.mirth_lab_name, "
                . "pp.mirth_lab_id,pp.login,pp.send_fac_id, fc.name as fcname, fc.street as fcstreet, fc.city as fccity, fc.state as fcstate, fc.postal_code as fcpost,fc.phone as fcphone, "
                . "us.lname as ulname,us.fname as ufname, CONCAT(us.lname, ' ', us.fname) AS uname, us.npi as unpi, us.upin as uupin, us.id as uid, "
                . "ic.name as insname, ic.freeb_type, ic.cms_id, ad.line1, ad.line2, ad.city as inscity, ad.state as insstate, ad.zip, ad.country,  "
                . "ics.name as secinsname, ics.freeb_type as secfreeb_type, ics.cms_id as seccms_id, ads.line1 as secline1, ads.line2 as secline2, ads.city as secinscity, ads.state as secinsstate, ads.zip as seczip, ads.country as seccountry,  "
                . "id.subscriber_relationship, id.subscriber_ss, id.subscriber_DOB, id.subscriber_sex, id.policy_number, id.group_number,id.subscriber_lname,id.subscriber_fname,id.subscriber_mname,CONCAT(id.subscriber_lname,' ',id.subscriber_fname,' ',id.subscriber_mname) AS subsname,"
                . "id.subscriber_street, id.subscriber_city, id.subscriber_postal_code, id.subscriber_state, id.subscriber_phone,id.subscriber_employer, "
                . "ids.subscriber_relationship AS secsubscriber_relationship, ids.policy_number AS secpolicy_number, ids.group_number AS secgroup_number,CONCAT(ids.subscriber_lname, ' ', ids.subscriber_fname, ' ', ids.subscriber_mname) AS secsubsname, ids.subscriber_street AS secsubscriber_street, ids.subscriber_city AS secsubscriber_city, ids.subscriber_postal_code AS secsubscriber_postal_code, ids.subscriber_state AS secsubscriber_state,ids.subscriber_employer AS secsubscriber_employer, "
                . "idg.subscriber_relationship AS gtrsubscriber_relationship, idg.policy_number AS gtrpolicy_number, idg.group_number AS gtrgroup_number,CONCAT(idg.subscriber_lname, ' ', idg.subscriber_fname, ' ', idg.subscriber_mname) AS gtrsubsname, idg.subscriber_street AS gtrsubscriber_street, idg.subscriber_city AS gtrsubscriber_city, idg.subscriber_postal_code AS gtrsubscriber_postal_code, idg.subscriber_state AS gtrsubscriber_state,idg.subscriber_employer AS gtrsubscriber_employer,"
                . "idg.subscriber_phone AS gtrsubscriber_phone,idg.subscriber_lname AS gtrsubscriber_lname,idg.subscriber_fname AS gtrsubscriber_fname,idg.subscriber_mname AS gtrsubscriber_mname "
                . "FROM procedure_order AS po "
                . "JOIN patient_data AS pd ON pd.pid = po.patient_id "
                . "JOIN procedure_providers AS pp ON pp.ppid = po.lab_id "
                . "JOIN users as us ON us.id=po.provider_id "
                . "JOIN facility as fc ON fc.id=us.facility_id "
                . "LEFT JOIN insurance_data AS id ON (po.patient_id = id.pid AND id.type='primary') AND po.billto<>'C' "
                . "LEFT JOIN insurance_data AS ids ON (po.patient_id = ids.pid AND ids.type='secondary') AND po.billto='T' "
                . "LEFT JOIN insurance_data AS idg ON (po.patient_id = idg.pid AND idg.type='guarantor') AND po.billto<>'C' "
                . "LEFT JOIN insurance_companies AS ic ON (ic.id = id.provider AND id.type='primary') AND po.billto='T' "
                . "LEFT JOIN insurance_companies AS ics ON (ics.id = ids.provider AND ids.type='secondary') AND po.billto='T' "
                . "LEFT JOIN addresses AS ad ON (ad.foreign_id = ic.id AND id.type='primary') AND po.billto='T' "
                . "LEFT JOIN addresses AS ads ON (ads.foreign_id = ics.id AND ids.type='secondary') AND po.billto='T' "
                . "WHERE procedure_order_id=? "
                . "ORDER BY id.date DESC ", array($labOrderid));
        return $result->current();
    }

    /**
     * getRequisitiontests
     * @param type $labOrderid
     * @return type
     */
    public function getRequisitiontests($labOrderid) {
        $result = $this->applicationTable->zQuery("SELECT po.* "
                . "FROM procedure_order_code AS po "
                . "WHERE procedure_order_id=?", array($labOrderid));
        return $result;
    }

    /**
     * getAoeAnswers
     * @param type $labOrderid
     * @return type
     */
    public function getAoeAnswers($labOrderid) {
        $res = $this->applicationTable->zQuery("SELECT
        pq.`question_text`,pq.hl7_segment,
        pa.`answer`,pq.tips,pa.procedure_order_seq,pq.question_code
      FROM
        `procedure_answers` pa
        JOIN `procedure_order_code` poc
          ON poc.`procedure_order_id` = pa.`procedure_order_id`
          AND poc.`procedure_order_seq` = pa.`procedure_order_seq`
         LEFT JOIN `procedure_order` po ON poc.procedure_order_id=po.procedure_order_id
        JOIN `procedure_questions` pq ON FIND_IN_SET(poc.`procedure_code`, pq.`procedure_code`) AND pq.lab_id=po.`lab_id`
         
          AND pq.`question_code` = pa.`question_code`
      WHERE pa.`procedure_order_id` = ?
      ORDER BY pa.procedure_order_seq,SUBSTRING(pq.question_code,1,3), seq,pq.question_code", array($labOrderid));
        return $res;
    }

    /**
     * getPatDetails
     * @param type $labOrderid
     * @return type
     */
    public function getPatDetails($labOrderid) {
        $sql = "SELECT * FROM `procedure_result_only` WHERE procedure_order_id = ?";
        $result = $this->applicationTable->zQuery($sql, array($labOrderid));
        $res = $result->current();
        return $res;
    }

    /**
     * getResDetails
     * @param type $labOrderid
     * @return type
     */
    public function getResDetails($labOrderid) {
        $sql = "SELECT psro.* FROM `procedure_subtest_result_only` AS psro JOIN `procedure_result_only` AS pro ON psro.`lab_result_id` = pro.`lab_result_id` 
             WHERE pro.`procedure_order_id` = ?";
        $result = $this->applicationTable->zQuery($sql, array($labOrderid));
        $arr = array();
        $i = 0;
        foreach ($result as $row) {
            $arr[$i] = $row;
            $i++;
        }
        return $arr;
    }

    /**
     * getVitalDetails
     * @param \Lab\Model\type $pid
     * @return type
     */
    public function getVitalDetails($pid) {
        //Selecting vital details 
        $qry1 = "SELECT bps FROM form_vitals WHERE id= (SELECT MAX(id) FROM form_vitals WHERE pid=? AND bps IS NOT NULL) ";
        $resbps = $this->applicationTable->zQuery($qry1, array($pid));
        $bps = $resbps->current();
        $qry2 = "SELECT bpd FROM form_vitals WHERE id= (SELECT MAX(id) FROM form_vitals WHERE pid=? AND bpd IS NOT NULL) ";
        $resbpd = $this->applicationTable->zQuery($qry2, array($pid));
        $bpd = $resbpd->current();
        $qry3 = "SELECT waist_circ FROM form_vitals WHERE id= (SELECT MAX(id) FROM form_vitals WHERE pid=? AND waist_circ >0) ";
        $reswaist = $this->applicationTable->zQuery($qry3, array($pid));
        $waist = $reswaist->current();
        return array(
            'bps' => $bps['bps'],
            'bpd' => $bpd['bpd'],
            'waist' => $waist['waist_circ']
        );
    }

}

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
use Zend\Config;
use Zend\Config\Writer;
use Zend\Soap\Client;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use \Application\Model\ApplicationTable;

class LabTable extends AbstractTableGateway {

    public $tableGateway;
    protected $applicationTable;

    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
        $adapter = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
        $this->adapter = $adapter;
        $this->applicationTable = new ApplicationTable();
    }

    /**
     * Lab Order Row wise
     */
    public function listPatientLabOrders() {
        global $pid;
        global $encounter;

        $sql = "SELECT po.date_ordered, po.procedure_order_id, po.ord_group  
			FROM procedure_order po 
	 		WHERE po.patient_id=? AND encounter_id=? ORDER BY po.ord_group DESC, po.procedure_order_id DESC";

        $result = $this->applicationTable->zQuery($sql, array($pid, $encounter));
        $arr = array();
        $i = 0;
        foreach ($result as $row) {
            $arr[$i]['date_ordered'] = htmlspecialchars($row['date_ordered'], ENT_QUOTES);
            $arr[$i]['procedure_order_id'] = htmlspecialchars($row['procedure_order_id'], ENT_QUOTES);
            $arr[$i]['ord_group'] = htmlspecialchars($row['ord_group'], ENT_QUOTES);
            $i++;
        }
        return $arr;
    }

    /**
     * listLabOrders()
     * @param type $data
     * @return type
     */
    public function listLabOrders($data) {
        $sql = "SELECT po.*,
			poc.procedure_code,
			poc.procedure_name,
			poc.procedure_suffix,
			poc.diagnoses,
			poc.procedure_order_seq,
			poc.patient_instructions, 
			CONCAT(pd.lname, ',', pd.fname) AS patient_name,
			pp.name AS provider_name 
		    FROM procedure_order po 
		    LEFT JOIN patient_data pd ON po.patient_id=pd.id 
		    LEFT JOIN procedure_providers pp
			ON po.lab_id=pp.ppid
		    LEFT JOIN procedure_order_code poc
			ON poc.procedure_order_id=po.procedure_order_id  
		    WHERE po.ord_group=(SELECT ord_group FROM procedure_order WHERE procedure_order_id='" . $data['ordId'] . "')
		    ORDER BY po.procedure_order_id, poc.procedure_order_seq";

        $result = $this->applicationTable->zQuery($sql);
        $arr = array();
        $i = 0;

        foreach ($result as $row) {
            $arr[$i]['procedure_order_id'] = htmlspecialchars($row['procedure_order_id'], ENT_QUOTES);
            $arr[$i]['provider_id'] = htmlspecialchars($row['provider_id'], ENT_QUOTES);
            $arr[$i]['lab_id'] = htmlspecialchars($row['lab_id'], ENT_QUOTES);
            $arr[$i]['date_ordered'] = htmlspecialchars($row['date_ordered'], ENT_QUOTES);
            $arr[$i]['date_collected'] = htmlspecialchars($row['date_collected'], ENT_QUOTES);
            $arr[$i]['internal_comments'] = htmlspecialchars($row['internal_comments'], ENT_QUOTES);
            $arr[$i]['order_priority'] = htmlspecialchars($row['order_priority'], ENT_QUOTES);
            $arr[$i]['order_status'] = htmlspecialchars($row['order_status'], ENT_QUOTES);
            $arr[$i]['patient_instructions'] = htmlspecialchars($row['patient_instructions'], ENT_QUOTES);
            $arr[$i]['diagnoses'] = htmlspecialchars($row['diagnoses'], ENT_QUOTES);
            $arr[$i]['procedure_name'] = htmlspecialchars($row['procedure_name'], ENT_QUOTES);
            $arr[$i]['procedure_code'] = htmlspecialchars($row['procedure_code'], ENT_QUOTES);
            $arr[$i]['procedure_suffix'] = htmlspecialchars($row['procedure_suffix'], ENT_QUOTES);
            $arr[$i]['procedure_order_seq'] = htmlspecialchars($row['procedure_order_seq'], ENT_QUOTES);
            $arr[$i]['psc_hold'] = htmlspecialchars($row['psc_hold'], ENT_QUOTES);
            $arr[$i]['order_status'] = htmlspecialchars($row['order_status'], ENT_QUOTES);
            $i++;
        }
        return $arr;
    }

    /**
     * listLabOrderAOE()
     * @param type $data
     * @return string
     */
    public function listLabOrderAOE($data) {
        $ordId = $data['ordId'];
        $seq = $data['seq'];
        $sql = "SELECT * FROM procedure_answers pa
	 		LEFT JOIN procedure_order_code poc
			    ON pa.procedure_order_id = poc.procedure_order_id
			    AND pa.procedure_order_seq = poc.procedure_order_seq
			LEFT JOIN procedure_questions pq
			    ON pa.question_code=pq.question_code
			    AND pq.procedure_code = poc.procedure_code 
			WHERE pa.procedure_order_id='$ordId' 
			AND pa.procedure_order_seq='$seq'";
        $result = $this->applicationTable->zQuery($sql);
        $arr = array();
        $i = 0;

        foreach ($result as $tmp) {
            $arr[] = htmlspecialchars($tmp['question_text'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['required'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['question_code'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['tips'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['answer'], ENT_QUOTES);
        }
        return $arr;
    }

    /**
     * removeLabOrders()
     * @param type $data
     */
    public function removeLabOrders($data) {
        $ordId = $data['ordId'];

        $result = $this->applicationTable->zQuery("SELECT procedure_order_id FROM procedure_order WHERE procedure_order_id=?", array($ordId));

        $totrows = $result->count();

        if ($totrows > 0) {
            $this->applicationTable->zQuery("DELETE FROM procedure_order WHERE procedure_order_id=?", array($ordId));
        }
        $result = $this->applicationTable->zQuery("SELECT procedure_order_id FROM procedure_order_code WHERE procedure_order_id=?", array($ordId));
        if ($totrows > 0) {
            $this->applicationTable->zQuery("DELETE FROM procedure_order_code WHERE procedure_order_id=?", array($ordId));
        }
        $result = $this->applicationTable->zQuery("SELECT procedure_order_id FROM procedure_answers WHERE procedure_order_id=?", array($ordId));
        if ($totrows > 0) {
            $this->applicationTable->zQuery("DELETE FROM procedure_answers WHERE procedure_order_id=?", array($ordId));
        }
    }

    // Start Save Lab Data
    public function updateProcedureMaster($post, $ordnum, $orderGroup) {
        $labvalArr = explode("|", $post['lab_id'][$ordnum][0]);
        $labval = $labvalArr[0];
        $this->applicationTable->zQuery("UPDATE procedure_order SET provider_id=?,patient_id=?,encounter_id=?,date_collected=?,date_ordered=?,order_priority=?,order_status=?,lab_id=?,psc_hold=?,billto=?,internal_comments=?,ord_group=? WHERE procedure_order_id=?", array($post['provider'][$ordnum][0], $post['patient_id'], $post['encounter_id'], $post['orderdate'][$ordnum][0], date("Y-m-d", strtotime($post['orderdate'][$ordnum][0])), $post['priority'][$ordnum][0],
            'pending', $labval, $post['specimencollected'][$ordnum][0], $post['billto'][$ordnum][0], $post['internal_comments'][$ordnum][0], $orderGroup, $post['procedure_order_id'][$ordnum][0]));
    }

    /**
     * saveLab
     * @param type $post
     * @param type $aoe
     * @return type
     */
    public function saveLab($post, $aoe) {
        $papArray = array();
        $specimenState = array();
        $procedure_type_id_arr = array();
        $j = 0;
        $prevState = '';
        if ($post['procedure_order_id'][0][0] != '') {
            $result = $this->applicationTable->zQuery("SELECT ord_group FROM procedure_order WHERE procedure_order_id=?", array($post['procedure_order_id'][0][0]));
            $max = $result->current();
            $orderGroup = $max['ord_group'];
        } else {
            $result = $this->applicationTable->zQuery("SELECT (MAX( ord_group ) + 1) AS ord_group FROM procedure_order");
            $max = $result->current();
            $orderGroup = $max['ord_group'];
        }
        for ($ordnum = 0; $ordnum < $post['total_panel']; $ordnum++) {
            $lb_id1 = explode('|', $post['lab_id'][$ordnum][0]);
            $lab_id_local = $lb_id1[0];
            if ($post['procedure_order_id'][$ordnum][0] != '') {
                $this->applicationTable->zQuery("DELETE FROM procedure_order_code WHERE procedure_order_id=?", array($post['procedure_order_id'][$ordnum][0]));
                $this->applicationTable->zQuery("DELETE FROM procedure_answers WHERE procedure_order_id=?", array($post['procedure_order_id'][$ordnum][0]));
            }
            for ($i = 0; $i < sizeof($post['procedures'][$ordnum]); $i++) {
                $result = $this->applicationTable->zQuery("SELECT * FROM procedure_type WHERE procedure_code=? AND (suffix=? or suffix IS NULL ) AND lab_id=? ORDER BY pap_indicator,specimen_state", array($post['procedure_code'][$ordnum][$i], $post['procedure_suffix'][$ordnum][$i], $lab_id_local));
                $PRow = $result->current();
                if (!isset(${$PRow['specimen_state'] . "_" . $ordnum . "_j"}))
                    ${$PRow['specimen_state'] . "_" . $ordnum . "_j"} = 0;
                if ($PRow['pap_indicator'] == "P") {
                    $papArray[$ordnum][$post['procedure_code'][$ordnum][$i] . "|-|" . $post['procedure_suffix'][$ordnum][$i]]['procedure'] = $PRow['name'];
                    $papArray[$ordnum][$post['procedure_code'][$ordnum][$i] . "|-|" . $post['procedure_suffix'][$ordnum][$i]]['diagnoses'] = $post['diagnoses'][$ordnum][$i];
                    $papArray[$ordnum][$post['procedure_code'][$ordnum][$i] . "|-|" . $post['procedure_suffix'][$ordnum][$i]]['patient_instructions'] = $post['patient_instructions'][$ordnum][$i];
                } else {
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['procedure_code'] = $PRow['procedure_code'];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['procedure'] = $PRow['name'];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['procedure_suffix'] = $PRow['suffix'];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['diagnoses'] = $post['diagnoses'][$ordnum][$i];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['patient_instructions'] = $post['patient_instructions'][$ordnum][$i];
                    ${$PRow['specimen_state'] . "_" . $ordnum . "_j"} ++;
                }
            }
            if (sizeof($papArray[$ordnum]) > 0) {
                foreach ($papArray[$ordnum] as $procode_suffix => $pronameArr) {
                    $PSArray = explode("|-|", $procode_suffix);
                    $procode = $PSArray[0];
                    $prosuffix = $PSArray[1];
                    $proname = $pronameArr['procedure'];
                    $diagnoses = $pronameArr['diagnoses'];
                    $patient_instructions = $pronameArr['patient_instructions'];
                    if ($post['procedure_order_id'][$ordnum][0] != '') {
                        $this->updateProcedureMaster($post, $ordnum, $orderGroup);
                        $PAPprocedure_type_id = $post['procedure_order_id'][$ordnum][0];
                    } else {
                        $PAPprocedure_type_id = $this->insertProcedureMaster($post, $ordnum, $orderGroup);
                    }
                    $procedure_type_id_arr[] = $PAPprocedure_type_id;
                    $result = $this->applicationTable->zQuery("INSERT INTO procedure_order_code (procedure_order_id,procedure_code,procedure_name,procedure_suffix,diagnoses,patient_instructions)
			    VALUES (?,?,?,?,?,?)", array($PAPprocedure_type_id, $procode, $proname, $prosuffix, $diagnoses, $patient_instructions));
                    $PAPseq = $result->getGeneratedValue();
                    $this->insertAoe($PAPprocedure_type_id, $PAPseq, $aoe, $procode, $ordnum);
                }
            }
            if ($post['specimencollected'][$ordnum][0] == "onsite") {
                if (sizeof($specimenState[$ordnum]) > 0) {
                    $idx = 0;
                    foreach ($specimenState[$ordnum] as $k => $vArray) {
                        if ($post['procedure_order_id'][$ordnum][0] != '') {
                            $this->updateProcedureMaster($post, $ordnum, $orderGroup);
                            $SPEprocedure_type_id = $post['procedure_order_id'][$ordnum][0];
                        } else {
                            $SPEprocedure_type_id = $this->insertProcedureMaster($post, $ordnum, $orderGroup);
                        }
                        $procedure_type_id_arr[] = $SPEprocedure_type_id;
                        for ($i = 0; $i < sizeof($vArray); $i++) {
                            $procode = $vArray[$i]['procedure_code'];
                            $proname = $vArray[$i]['procedure'];
                            $prosuffix = $vArray[$i]['procedure_suffix'];
                            $diagnoses = $vArray[$i]['diagnoses'];
                            $patient_instructions = $vArray[$i]['patient_instructions'];
                            $specimen_details = "";
                            for ($k = 0; $k < sizeof($post['spec_name'][$ordnum][$i]); $k++) {
                                if ($post['spec_name'][$ordnum][$i][$k] != "" || $post['spselect'][$ordnum][$i][$k] != "@|@" || $post['spselect1'][$ordnum][$i][$k] != '~~' || $post['spselect2'][$ordnum][$i][$k] != '~~' || $post['spselect3'][$ordnum][$i][$k] != '~~' || $post['spec_desc'][$ordnum][$i][$k] != '') {
                                    $specimen_details = $specimen_details . $post['spec_name'][$ordnum][$i][$k] . "@|@" . $post['spselect'][$ordnum][$i][$k] . "@~#~@" . $post['spselect1'][$ordnum][$i][$k] . "#@!#@" . $post['spselect2'][$ordnum][$i][$k] . "#@!#@" . $post['spselect3'][$ordnum][$i][$k] . "@~#~@" . $post['spec_desc'][$ordnum][$i][$k] . "#$@$#";
                                }
                            }
                            $specimen_details = rtrim($specimen_details, "#$@$#");

                            $result = $this->applicationTable->zQuery("INSERT INTO procedure_order_code (procedure_order_id,procedure_code,procedure_name,procedure_suffix,diagnoses,patient_instructions,specimen_details)
					    VALUES (?,?,?,?,?,?,?)", array($SPEprocedure_type_id, $procode, $proname, $prosuffix, $diagnoses, $patient_instructions, $specimen_details));
                            $SPEseq = $result->getGeneratedValue();
                            $this->insertAoe($SPEprocedure_type_id, $SPEseq, $aoe, $procode, $ordnum);
                        }
                        $idx++;
                    }
                }
            } else {
                for ($i = 0; $i < sizeof($post['procedures'][$ordnum]); $i++) {
                    $procedure_code = $post['procedure_code'][$ordnum][$i];
                    $procedure_suffix = $post['procedure_suffix'][$ordnum][$i];
                    if (array_key_exists($procedure_code . "|-|" . $procedure_suffix, $papArray))
                        continue;
                    if ($i == 0) {
                        if ($post['procedure_order_id'][$ordnum][$i] != '') {
                            $this->updateProcedureMaster($post, $ordnum, $orderGroup);
                            $procedure_type_id = $post['procedure_order_id'][$ordnum][$i];
                        } else {
                            $procedure_type_id = $this->insertProcedureMaster($post, $ordnum, $orderGroup);
                        }
                        $procedure_type_id_arr[] = $procedure_type_id;
                    }
                    $specimen_details = "";
                    for ($k = 0; $k < sizeof($post['spec_name'][$ordnum][$i]); $k++) {
                        if ($post['spec_name'][$ordnum][$i][$k] != "" || $post['spselect'][$ordnum][$i][$k] != "@|@" || $post['spselect1'][$ordnum][$i][$k] != '~~' || $post['spselect2'][$ordnum][$i][$k] != '~~' || $post['spselect3'][$ordnum][$i][$k] != '~~' || $post['spec_desc'][$ordnum][$i][$k] != '') {
                            $specimen_details = $specimen_details . $post['spec_name'][$ordnum][$i][$k] . "@|@" . $post['spselect'][$ordnum][$i][$k] . "@~#~@" . $post['spselect1'][$ordnum][$i][$k] . "#@!#@" . $post['spselect2'][$ordnum][$i][$k] . "#@!#@" . $post['spselect3'][$ordnum][$i][$k] . "@~#~@" . $post['spec_desc'][$ordnum][$i][$k] . "#$@$#";
                        }
                    }
                    $specimen_details = rtrim($specimen_details, "#$@$#");
                    $result = $this->applicationTable->zQuery("INSERT INTO procedure_order_code (procedure_order_id,procedure_code,procedure_name,procedure_suffix,diagnoses,patient_instructions,specimen_details)
			VALUES (?,?,?,?,?,?,?)", array($procedure_type_id, $post['procedure_code'][$ordnum][$i], $post['procedures'][$ordnum][$i], $post['procedure_suffix'][$ordnum][$i], $post['diagnoses'][$ordnum][$i], $post['patient_instructions'][$ordnum][$i], $specimen_details));
                    $seq = $result->getGeneratedValue();
                    $this->insertAoe($procedure_type_id, $seq, $aoe, $post['procedure_code'][$ordnum][$i], $post['diagnoses'][$ordnum][$i], $ordnum);
                }
            }
        }
        return $procedure_type_id_arr;
    }

    /**
     * insertAoe()
     * @param type $procedure_type_id
     * @param type $seq
     * @param type $aoe
     * @param type $procedure_code_i
     * @param type $ordnum
     */
    public function insertAoe($procedure_type_id, $seq, $aoe, $procedure_code_i, $ordnum) {
        foreach ($aoe[$ordnum] as $ProcedureOrder => $QuestionArr) {
            if ($ProcedureOrder == $procedure_code_i) {
                $ans_seq = 1;
                foreach ($QuestionArr as $Question => $Answer) {
                    $Question = str_replace('##^', '.', $Question);
                    $len = strlen($Answer);
                    if ($len == 10) {
                        if (substr($Answer, 4, 1) == "/" && substr($Answer, 7, 1) == "/") {
                            $Answer = str_replace("/", "", $Answer);
                        }
                    }
                    if ($len == 8) {
                        if (substr($Answer, 2, 1) == ":" && substr($Answer, 5, 1) == ":") {
                            $Answer = str_replace(":", "", $Answer);
                        }
                    }
                    $this->applicationTable->zQuery("INSERT INTO procedure_answers (procedure_order_id,procedure_order_seq,question_code,answer,answer_seq) VALUES (?,?,?,?,?)", array($procedure_type_id, $seq, $Question, $Answer, $ans_seq));
                    $ans_seq++;
                }
            }
        }
    }

    /**
     * insertProcedureMaster
     * @global \Lab\Model\type $pid
     * @global type $encounter
     * @param type $post
     * @param type $ordnum
     * @param type $orderGroup
     * @return type
     */
    public function insertProcedureMaster($post, $ordnum, $orderGroup) {
        global $pid, $encounter;
        $labvalArr = explode("|", $post['lab_id'][$ordnum][0]);
        $courtesy = "";
        $cor = 'No';
        $abn = 'No';
        $labval = $labvalArr[0];
        for ($i = 0; $i < count($post['accno'][$ordnum]); $i++) {
            if ($post['typeA'][$ordnum][0]) {
                $courtesy .= $post['typeA'][$ordnum][0] . "#@#" . $post['accno'][$ordnum][$i] . "@#@" . $post['accname'][$ordnum][$i] . "**";
            }
        }
        $courtesy = rtrim($courtesy, "**");
        if ($courtesy) {
            $courtesy .= "@@##";
        }
        for ($i = 0; $i < count($post['faxno'][$ordnum]); $i++) {
            if ($post['typeF'][$ordnum][0]) {
                if ($courtesy == '')
                    $courtesy = $courtesy . $post['typeF'][$ordnum][0] . "#@#" . $post['faxno'][$ordnum][$i] . "@#@" . $post['faxname'][$ordnum][$i] . "**";
                else
                    $courtesy = $courtesy . $post['typeF'][$ordnum][0] . "#@#" . $post['faxno'][$ordnum][$i] . "@#@" . $post['faxname'][$ordnum][$i] . "**";
            }
        }
        $courtesy = rtrim($courtesy, "**");

        if ($post['typeP'][$ordnum][0]) {
            if ($courtesy) {
                $courtesy = $courtesy . "@@##";
            }
            $courtesy = $courtesy . $post['typeP'][$ordnum][0];
        }
        if ($post['COR'][$ordnum][0] == 'on') {
            $cor = 'Yes';
        }
        if ($post['ABN'][$ordnum][0] == 'on') {
            $abn = 'Yes';
        }
        $result = $this->applicationTable->zQuery("INSERT INTO procedure_order (provider_id,patient_id,encounter_id,date_collected,date_ordered,order_priority,
				       order_status,lab_id,psc_hold,cor,courtesy_copy,billto,internal_comments, ord_group,abn) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)", array($post['provider'][$ordnum][0], $post['patient_id'], $post['encounter_id'], $post['orderdate'][$ordnum][0], date("Y-m-d", strtotime($post['orderdate'][$ordnum][0])), $post['priority'][$ordnum][0],
            'pending', $labval, $post['specimencollected'][$ordnum][0], $cor, $courtesy,
            $post['billto'][$ordnum][0], $post['internal_comments'][$ordnum][0], $orderGroup, $abn));
        $procedure_type_id = $result->getGeneratedValue();
        addForm($encounter, "Procedure Order", $procedure_type_id, "procedure_order", $pid, $userauthorized);
        return $procedure_type_id;
    }

    // End Save Lab Data

    public function saveLabOLD($post, $aoe) {
        $papArray = array();
        $specimenState = array();
        $procedure_type_id_arr = array();
        $j = 0;
        $prevState = '';
        $result = $this->applicationTable->zQuery("SELECT (MAX( ord_group ) + 1) AS ord_group FROM procedure_order");
        $max = $result->current();
        $orderGroup = $max['ord_group'];
        for ($ordnum = 0; $ordnum < $post['total_panel']; $ordnum++) {
            for ($i = 0; $i < sizeof($post['procedures'][$ordnum]); $i++) {
                $result = $this->applicationTable->zQuery("SELECT * FROM procedure_type WHERE procedure_code=? AND suffix=? ORDER BY pap_indicator,specimen_state", array($post['procedure_code'][$ordnum][$i], $post['procedure_suffix'][$ordnum][$i]));

                $PRow = $result->current();
                if (!isset(${$PRow['specimen_state'] . "_" . $ordnum . "_j"}))
                    ${$PRow['specimen_state'] . "_" . $ordnum . "_j"} = 0;
                if ($PRow['pap_indicator'] == "P") {
                    $papArray[$ordnum][$post['procedure_code'][$ordnum][$i] . "|-|" . $post['procedure_suffix'][$ordnum][$i]]['procedure'] = $PRow['name'];
                    $papArray[$ordnum][$post['procedure_code'][$ordnum][$i] . "|-|" . $post['procedure_suffix'][$ordnum][$i]]['diagnoses'] = $post['diagnoses'][$ordnum][$i];
                    $papArray[$ordnum][$post['procedure_code'][$ordnum][$i] . "|-|" . $post['procedure_suffix'][$ordnum][$i]]['patient_instructions'] = $post['patient_instructions'][$ordnum][$i];
                } else {
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['procedure_code'] = $PRow['procedure_code'];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['procedure'] = $PRow['name'];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['procedure_suffix'] = $PRow['suffix'];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['diagnoses'] = $post['diagnoses'][$ordnum][$i];
                    $specimenState[$ordnum][$PRow['specimen_state']][${$PRow['specimen_state'] . "_" . $ordnum . "_j"}]['patient_instructions'] = $post['patient_instructions'][$ordnum][$i];
                    ${$PRow['specimen_state'] . "_" . $ordnum . "_j"} ++;
                }
            }

            if (sizeof($papArray[$ordnum]) > 0) {
                foreach ($papArray[$ordnum] as $procode_suffix => $pronameArr) {
                    $PSArray = explode("|-|", $procode_suffix);
                    $procode = $PSArray[0];
                    $prosuffix = $PSArray[1];
                    $proname = $pronameArr['procedure'];
                    $diagnoses = $pronameArr['diagnoses'];
                    $patient_instructions = $pronameArr['patient_instructions'];
                    $PAPprocedure_type_id = $this->insertProcedureMaster($post, $ordnum, $orderGroup);
                    $procedure_type_id_arr[] = $PAPprocedure_type_id;
                    $result = $this->applicationTable->zQuery("INSERT INTO procedure_order_code (procedure_order_id,procedure_code,procedure_name,procedure_suffix,diagnoses,patient_instructions)
			 VALUES (?,?,?,?,?,?)", array($PAPprocedure_type_id, $procode, $proname, $prosuffix, $diagnoses, $patient_instructions));
                    $PAPseq = $result->getGeneratedValue();
                    $this->insertAoe($PAPprocedure_type_id, $PAPseq, $aoe, $procode, $ordnum);
                }
            }
            if ($post['specimencollected'][$ordnum][0] == "onsite") {
                if (sizeof($specimenState[$ordnum]) > 0) {
                    foreach ($specimenState[$ordnum] as $k => $vArray) {
                        $SPEprocedure_type_id = $this->insertProcedureMaster($post, $ordnum, $orderGroup);
                        $procedure_type_id_arr[] = $SPEprocedure_type_id;
                        for ($i = 0; $i < sizeof($vArray); $i++) {
                            $procode = $vArray[$i]['procedure_code'];
                            $proname = $vArray[$i]['procedure'];
                            $prosuffix = $vArray[$i]['procedure_suffix'];
                            $diagnoses = $vArray[$i]['diagnoses'];
                            $patient_instructions = $vArray[$i]['patient_instructions'];
                            $sss = "INSERT INTO procedure_order_code (procedure_order_id,procedure_code,procedure_name,procedure_suffix,diagnoses,patient_instructions)
					    VALUES ($SPEprocedure_type_id,$procode,$proname,$prosuffix,$diagnoses,$patient_instructions)";
                            $result = $this->applicationTable->zQuery("INSERT INTO procedure_order_code (procedure_order_id,procedure_code,procedure_name,procedure_suffix,diagnoses,patient_instructions)
					    VALUES (?,?,?,?,?,?)", array($SPEprocedure_type_id, $procode, $proname, $prosuffix, $diagnoses, $patient_instructions));
                            $SPEseq = $result->getGeneratedValue();
                            $this->insertAoe($SPEprocedure_type_id, $SPEseq, $aoe, $procode, $ordnum);
                        }
                    }
                }
            } else {
                for ($i = 0; $i < sizeof($post['procedures'][$ordnum]); $i++) {
                    $procedure_code = $post['procedure_code'][$ordnum][$i];
                    $procedure_suffix = $post['procedure_suffix'][$ordnum][$i];
                    if (array_key_exists($procedure_code . "|-|" . $procedure_suffix, $papArray))
                        continue;
                    if ($i == 0) {
                        $procedure_type_id = $this->insertProcedureMaster($post, $ordnum, $orderGroup);
                        $procedure_type_id_arr[] = $procedure_type_id;
                    }
                    $result = $this->applicationTable->zQuery("INSERT INTO procedure_order_code (procedure_order_id,procedure_code,procedure_name,procedure_suffix,diagnoses,patient_instructions)
			VALUES (?,?,?,?,?,?)", array($procedure_type_id, $post['procedure_code'][$ordnum][$i], $post['procedures'][$ordnum][$i], $post['procedure_suffix'][$ordnum][$i]
                        , $post['patient_instructions'][$ordnum][$i]));
                    $seq = $result->getGeneratedValue();
                    $this->insertAoe($procedure_type_id, $seq, $aoe, $post['procedure_code'][$ordnum][$i], $post['diagnoses'][$ordnum][$i], $ordnum);
                }
            }
        }
        return $procedure_type_id_arr;
    }

    /**
     * List tests
     * @param type $inputString
     * @param type $labId
     * @param type $remLabId
     * @return string
     */
    public function listProcedures($inputString, $labId, $remLabId) {
        if ($remLabId == NULL || $remLabId == 0) {
            $sql = "SELECT * FROM procedure_type AS pt WHERE pt.lab_id=? AND (mirth_lab_id = 0 OR mirth_lab_id IS NULL) AND (pt.name LIKE ? OR pt.procedure_code LIKE ?) AND pt.activity=1 AND pt.procedure_type='ord'";
            $result = $this->applicationTable->zQuery($sql, array($labId, $inputString . "%", $inputString . "%"));
        } else {
            $sql = "SELECT * FROM procedure_type AS pt WHERE pt.lab_id=? AND mirth_lab_id = ? AND (pt.name LIKE ? OR pt.procedure_code LIKE ?) AND pt.activity=1 AND pt.procedure_type='ord'";
            $result = $this->applicationTable->zQuery($sql, array($labId, $remLabId, $inputString . "%", $inputString . "%"));
        }
        $arr = array();
        $i = 0;
        foreach ($result as $tmp) {
            $arr[] = htmlspecialchars($tmp['procedure_type_id'], ENT_QUOTES) . '|-|' . htmlspecialchars($tmp['procedure_code'], ENT_QUOTES) . '|-|' . htmlspecialchars($tmp['suffix'], ENT_QUOTES) . '|-|' . htmlspecialchars($tmp['name'], ENT_QUOTES);
        }

        return $arr;
    }

    /**
     * List AOE 
     * @param type $procedureCode
     * @param type $labId
     * @param type $remLabId
     * @return string
     */
    public function listAOE($procedureCode, $labId, $remLabId) {
        $sql = "SELECT * FROM procedure_questions WHERE lab_id=? AND mirth_lab_id = ? AND FIND_IN_SET(?, procedure_code)   AND activity=1 ORDER BY seq ASC";
        $result = $this->applicationTable->zQuery($sql, array($labId, $remLabId, $procedureCode));
        $arr = array();
        $i = 0;

        foreach ($result as $tmp) {

            $arr[] = htmlspecialchars($tmp['question_text'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['required'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['question_code'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['tips'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['fldtype'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['options'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['hl7_segment'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['options_value'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['specimen_case'], ENT_QUOTES) . "|-|" . htmlspecialchars($tmp['maxsize'], ENT_QUOTES);
        }
        return $arr;
    }

    /**
     * List Diagnosis
     * @param type $inputString
     * @return string
     */
    public function listDiagnoses($inputString) {

        $result = $this->applicationTable->zQuery("SELECT ct_id FROM code_types WHERE ct_key='ICD9'");
        foreach ($result as $codeTypeValue)
            $sql = "SELECT c.code AS code, 
			    c.code_text AS code_text 
        FROM `codes` AS c 
        WHERE c.code_type = ?
          AND (c.code LIKE ? || c.code_text LIKE ?)
          AND (c.active = 1 || c.active IS NULL) 
        ORDER BY CODE";
        $res = $this->applicationTable->zQuery($sql, array($codeTypeValue['ct_id'], "%" . $inputString . "%", "%" . $inputString . "%"));

        $arr = array();
        $i = 0;
        foreach ($res as $tmp) {
            $arr[] = htmlspecialchars($tmp['code'], ENT_QUOTES) . '|-|' . htmlspecialchars($tmp['code_text'], ENT_QUOTES);
        }
        return $arr;
    }

    /**
     * getColumns()
     * */
    public function getColumns($result) {
        $result_columns = array();
        foreach ($result as $res) {
            foreach ($res as $key => $val) {
                if (is_numeric($key))
                    continue;
                $result_columns[] = $key;
            }
            break;
        }
        return $result_columns;
    }

    /**
     * columnMapping()
     * @param type $column_map
     * @param type $result_col
     * @return type
     */
    public function columnMapping($column_map, $result_col) {
        $table_sql = array();

        foreach ($result_col as $col) {
            if (($column_map[$col]['colconfig']['table'] <> "") && ($column_map[$col]['colconfig']['column'] <> "")) {
                $table = $column_map[$col]['colconfig']['table'];
                $column = $column_map[$col]['colconfig']['column'];

                $table_sql[$table][$col] = $column;
            }
        }
        return $table_sql;
    }

    /**
     * Import test ,aoe data
     * @param type $result
     * @param type $column_map
     */
    public function importDataCheck($result, $column_map) {//CHECK DATA IF ALREADY EXISTS
        $result_col = $this->getColumns($result);

        $mapped_tables = $this->columnMapping($column_map, $result_col);

        foreach ($result as $res) {
            foreach ($result_col as $col) {
                ${$col} = $res[$col]; //GETTING IMPORTED VALUES
            }

            foreach ($mapped_tables as $table => $columns) {
                $value_arr = array();
                foreach ($columns as $servercol => $column) {
                    if ($column_map[$servercol]['colconfig']['insert_id'] == "1") {
                        $$servercol = $insert_id;
                    }
                    if ($column_map[$servercol]['colconfig']['value_map'] == "1") {
                        $$servercol = $column_map[$servercol]['valconfig'][$$servercol];
                    }
                    $value_arr[$column] = ${$servercol};
                }

                $fields = implode(",", $columns);
                $col_count = count($columns);
                $field_vars = "$" . implode(",$", $columns);
                $params = rtrim(str_repeat("?,", $col_count), ",");

                $primary_key_arr = $column_map['contraints'][$table]['primary_key'];
                if (count($primary_key_arr) > 0) {
                    $index = 0;
                    $condition = "";
                    $check_value_arr = array();

                    foreach ($primary_key_arr as $pkey) {
                        if ($index > 0) {
                            $condition.=" AND ";
                        }
                        $condition.=" " . $pkey . " = ? ";
                        $index++;
                        $check_value_arr[$pkey] = $value_arr[$pkey];
                    }

                    $update_arr = array();
                    foreach ($value_arr as $key => $val) {
                        if (!in_array($key, $primary_key_arr)) {
                            $update_arr[$key] = $val;
                        }
                    }

                    $update_combined_arr = array_merge($update_arr, $check_value_arr);

                    $index = 0;
                    $update_key_arr = array();

                    foreach ($update_arr as $upkey => $upval) {
                        $update_key_arr[] = $upkey;
                    }

                    $update_expr = implode(" = ? ,", $update_key_arr);
                    $update_expr .= " = ? ";

                    $sql_check = "SELECT COUNT(*) as data_exists FROM " . $table . " WHERE " . $condition;
                    $result = $this->applicationTable->zQuery($sql_check, $check_value_arr);
                    $pat_data_check = $result->current();
                    if ($pat_data_check['data_exists']) {
                        $sqlup = "UPDATE " . $table . " SET " . $update_expr . " WHERE " . $condition;
                        $result = $this->applicationTable->zQuery($sqlup, $update_combined_arr);
                        $pat_data_check = $result->current();
                    } else {
                        $sql = "INSERT INTO " . $table . "(" . $fields . ") VALUES (" . $params . ")";
                        $result = $this->applicationTable->zQuery($sql, $value_arr);
                        $insert_id = $result->getGeneratedValue();
                    }
                }
            }

            $count++;
        }
        $this->applicationTable->zQuery("UPDATE procedure_type SET parent=procedure_type_id");
        $this->applicationTable->zQuery("UPDATE procedure_type SET name=description");
    }

    /**
     * getWebserviceOptions()
     * @return string
     */
    public function getWebserviceOptions() {
        $options = array('location' => "http://192.168.1.60/webserver/lab_server.php",
            'uri' => "urn://zhhealthcare/lab"
        );
        return $options;
    }

    /**
     * pullcompendiumTestConfig
     * @return array
     */
    public function pullcompendiumTestConfig() {
        $column_map['test_lab_id'] = array('colconfig' => array(
                'table' => "procedure_type",
                'column' => "mirth_lab_id",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_lab_entity'] = array('colconfig' => array(
                'table' => "",
                'column' => "seccol",
                'value_map' => "0",
                'insert_id' => "1"));
        $column_map['test_code'] = array('colconfig' => array(
                'table' => "procedure_type",
                'column' => "procedure_code",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_specimen_state'] = array('colconfig' => array(
                'table' => "procedure_type",
                'column' => "specimen_state",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_unit_code'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_status_indicator'] = array('colconfig' => array(
                'table' => "procedure_type",
                'column' => "activity",
                'value_map' => "1",
                'insert_id' => "0"),
            'valconfig' => array(
                'A' => "1",
                'I' => "0"));

        $column_map['test_insert_datetime'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_description'] = array('colconfig' => array(
                'table' => "procedure_type",
                'column' => "description",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_specimen_type'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_service_code'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_lab_site'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_update_datetime'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_update_user'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_code_suffix'] = array('colconfig' => array(
                'table' => "procedure_type",
                'column' => "suffix",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_is_profile'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_is_select'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_performing_site'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_flag'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_is_not_billed'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_is_billed_only'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_reflex_count'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_conforming_indicator'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_alternate_temp'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_pap_indicator'] = array('colconfig' => array(
                'table' => "procedure_type",
                'column' => "pap_indicator",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['test_last_updatetime'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));


        $column_map['contraints'] = array('procedure_type' => array(
                'primary_key' => array(
                    '0' => "mirth_lab_id",
                    '1' => "procedure_code",
                    '2' => "suffix")));

        return $column_map;
    }

    /**
     * pullcompendiumAoeConfig()
     * @return array
     */
    public function pullcompendiumAoeConfig() {
        $column_map['aoe_lab_id'] = array('colconfig' => array(
                'table' => "procedure_questions",
                'column' => "mirth_lab_id",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_lab_entity'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_performing_site'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_unit_code'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_test_code'] = array('colconfig' => array(
                'table' => "procedure_questions",
                'column' => "procedure_code",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_analyte_code'] = array('colconfig' => array(
                'table' => "procedure_questions",
                'column' => "question_code",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_question'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));
        $column_map['aoe_status_indicator'] = array('colconfig' => array(
                'table' => "procedure_questions",
                'column' => "activity",
                'value_map' => "1",
                'insert_id' => "0"),
            'valconfig' => array(
                'A' => "1",
                'I' => "0"));

        $column_map['aoe_profile_component'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_insert_datetime'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_question_description'] = array('colconfig' => array(
                'table' => "procedure_questions",
                'column' => "question_text",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_suffix'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_result_filter'] = array('colconfig' => array(
                'table' => "procedure_questions",
                'column' => "tips",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_test_code_mneumonic'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_test_flag'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_upadate_datetime'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_update_user'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_component_name'] = array('colconfig' => array(
                'table' => "procedure_questions",
                'column' => "question_component",
                'value_map' => "0",
                'insert_id' => "0"));

        $column_map['aoe_last_updatetime'] = array('colconfig' => array(
                'table' => "",
                'column' => "",
                'value_map' => "0",
                'insert_id' => "0"));


        $column_map['contraints'] = array('procedure_questions' => array(
                'primary_key' => array(
                    '0' => "mirth_lab_id",
                    '1' => "procedure_code",
                    '2' => "question_code")));

        return $column_map;
    }

    /**
     * mapcolumntoxml
     * @return array
     */
    public function mapcolumntoxml() {
        $xmlconfig['patient_data'] = array(
            'column_map' => array(
                'pid' => 'pid',
                'pubpid' => 'pubpid',
                'fname' => 'patient_fname',
                'DOB' => 'patient_dob',
                'sex' => 'patient_sex',
                'lname' => 'patient_lname',
                'mname' => 'patient_mname',
                'street' => 'patient_street_address',
                'city' => 'patient_city',
                'state' => 'patient_state',
                'postal_code' => 'patient_postal_code',
                'country_code' => 'patient_country',
                'phone_home' => 'patient_phone_no',
                'ss' => 'patient_ss_no',
                'parent_first' => 'parent_first',
                'parent_last' => 'parent_last',
                'parent_mid' => 'parent_mid',
                'parent_add1' => 'parent_add1',
                'parent_add2' => 'parent_add2',
                'parent_city' => 'parent_city',
                'parent_state' => 'parent_state',
                'parent_zip' => 'parent_zip',
                'parent_phone' => 'parent_phone'
            ),
            'primary_key' => array('pid'),
            'match_value' => array('pid'));


        $xmlconfig['insurance_data'] = array(
            'column_map' => array(
                'type' => '#type',
                'provider' => '#provider',
                'subscriber_street' => '$type_insurance_person_address,guarantor_address',
                'subscriber_city' => '$type_insurance_person_city,guarantor_city',
                'subscriber_state' => '$type_insurance_person_state,guarantor_state',
                'subscriber_postal_code' => '$type_insurance_person_postal_code,guarantor_postal_code',
                'subscriber_lname' => '$type_insurance_person_lname,guarantor_lname',
                'subscriber_fname' => '$type_insurance_person_fname,guarantor_fname',
                'subscriber_relationship' => '$type_insurance_person_relationship,guarantor_relationship',
                'subscriber_phone' => '$type_insurance_person_phone,guarantor_phone_no',
                'policy_number' => '$type_insurance_policy_no',
                'group_number' => '$type_insurance_group_no',
                'subscriber_mname' => '$type_insurance_person_mname,guarantor_mname',
                'subscriber_employer' => '$type_subscriber_employer',
            ),
            'primary_key' => array('pid'),
            'match_value' => array('pid'),
            'child_table' => 'insurance_companies,addresses',
            'tag_value_condition' => array(
                'guarantor_fname' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_mname' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_lname' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_address' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_city' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_state' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_postal_code' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_phone_no' => array(
                    'variable' => "type",
                    'value' => "guarantor"),
                'guarantor_relationship' => array(
                    'variable' => "type",
                    'value' => "guarantor")
        ));

        $xmlconfig['insurance_companies'] = array(
            'column_map' => array(
                'name' => '$type_insurance_name',
                'freeb_type' => '$type_freeb_type',
                'cms_id' => '$type_cms_id'
            ),
            'primary_key' => array('id'),
            'match_value' => array('provider'),
            'parent_table' => 'insurance_data'
        );

        /* -------- NEW CONFIGURATION FOR ADDRESSES --------- */
        //line1,    city,  state,  zip

        $xmlconfig['addresses'] = array(
            'column_map' => array(
                'line1' => '$type_insurance_address',
                'line2' => '$type_insurance_address2',
                'city' => '$type_insurance_city',
                'state' => '$type_insurance_state',
                'zip' => '$type_insurance_postal_code',
            ),
            'primary_key' => array('foreign_id'),
            'match_value' => array('provider'),
            'parent_table' => 'insurance_data'
        );
        /* -------------------------------------------------- */

        $xmlconfig['procedure_providers'] = array(
            'column_map' => array(
                'send_app_id' => 'send_app_id',
                'recv_app_id' => 'recv_app_id',
                'send_fac_id' => 'send_fac_id',
                'recv_fac_id' => 'recv_fac_id',
                'DorP' => 'DorP'
            ),
            'primary_key' => array('ppid'),
            'match_value' => array('lab_id'));


        $xmlconfig['procedure_order'] = array(
            'column_map' => array(
                'provider_id' => '#provider_id',
                'psc_hold' => 'recv_app_id',
                'date_collected' => 'collection_date',
                'billto' => 'bill_to',
                'internal_comments' => 'patient_internal_comments',
                'cor' => 'cor',
                'courtesy_copy' => 'courtesy_copy',
                'abn' => 'abn'
            ),
            'value_map' => array(
                'psc_hold' => array(
                    'onsite' => '',
                    'labsite' => 'PSC'
                )
            ),
            'primary_key' => array('procedure_order_id'),
            'match_value' => array('order_id'));

        $xmlconfig['users'] = array(
            'column_map' => array(
                'fname' => 'ordering_provider_fname',
                'lname' => 'ordering_provider_lname',
                'npi' => 'ordering_provider_id',
                'id' => 'physician_id',
                'upin' => "physician_upin"
            ),
            'primary_key' => array('id'),
            'match_value' => array('provider_id'));


        return $xmlconfig;
    }

    /**
     * mapResultXmlToColumn()
     * @return string
     */
    public function mapResultXmlToColumn() {
        $xmlconfig['procedure_report'] = array(
            'xmltag_map' => array(
                '$procedure_order_id' => 'procedure_order_id',
                '$procedure_order_seq' => 'procedure_order_seq',
                'date_report' => 'patient_dob',
                'date_collected' => 'patient_sex',
                'specimen_num' => 'patient_lname',
                'report_status' => 'report_status',
                '$review_status' => 'review_status'
            ),
            'primary_key' => array('procedure_report_id'),
            'match_value' => array('procedure_report_id'),
            'child_table' => 'procedure_result');


        $xmlconfig['procedure_result'] = array(
            'xmltag_map' => array(
                '$procedure_report_id' => 'procedure_report_id',
                'abnormal' => 'abnormal',
                'result' => 'result',
                'range' => 'range',
                'units' => 'units',
                'facility' => 'facility',
                'comments' => 'comments',
                '$result_status' => 'result_status'
            ),
            'primary_key' => array('procedure_result_id'),
            'match_value' => array('procedure_result_id'),
            'parent_table' => 'procedure_report');

        return $xmlconfig;
    }

    /**
     * generateSQLSelect()
     * @global type $type
     * @global type $provider
     * @global type $provider_id
     * @param \Lab\Model\type $pid
     * @param type $lab_id
     * @param type $order_id
     * @param type $cofig_arr
     * @param type $table
     * @return type
     */
    public function generateSQLSelect($pid, $lab_id, $order_id, $cofig_arr, $table) {
        global $type;
        global $provider;
        global $provider_id;


        $table_name = $table;

        $col_map_arr = $cofig_arr[$table]['column_map'];
        $primary_key_arr = $cofig_arr[$table]['primary_key'];
        $match_value_arr = $cofig_arr[$table]['match_value'];

        $index = 0;
        $condition = "";
        foreach ($primary_key_arr as $pkey) {
            if ($index > 0) {
                $condition .= " AND ";
            }
            $condition .= " " . $pkey . " = ? ";
            $index++;
        }

        $index = 0;
        foreach ($match_value_arr as $param) {
            $match_value_arr[$index] = ${$match_value_arr[$index]};
            $index++;
        }

        $col_arr = array();
        foreach ($col_map_arr as $col => $tag) {
            $col_arr[] = $col;
        }
        $cols = implode(",", $col_arr);

        $sql = "SELECT " . $cols . " FROM " . $table_name . " WHERE " . $condition;
        $result = $this->applicationTable->zQuery($sql, $match_value_arr);
        return $result;
    }

    /**
     * generateOrderXml()
     * @global \Lab\Model\type $type
     * @global \Lab\Model\type $provider
     * @global \Lab\Model\type $provider_id
     * @param \Lab\Model\type $pid
     * @param type $lab_id
     * @param type $xmlfile
     * @return type
     */
    public function generateOrderXml($pid, $lab_id, $xmlfile) {
        global $type;
        global $provider;
        global $provider_id;

        //XML TAGS NOT CONFIGURED YET
        $primary_insurance_coverage_type = "";
        $secondary_insurance_coverage_type = "";
        $primary_insurance_person_address = "";
        $primary_insurance_person_city = "";
        $primary_insurance_person_state = "";
        $primary_insurance_person_postal_code = "";

        $guarantor_phone_no = "";

        $observation_request_comments = "";

        $xmltag_arr = array("pid", "pubpid", "patient_fname", "patient_dob", "patient_sex", "patient_lname", "patient_mname", "patient_street_address", "patient_city",
            "patient_state", "patient_postal_code", "patient_country", "patient_phone_no", "patient_ss_no", "patient_internal_comments", "cor", "courtesy_copy", "abn",
            "primary_insurance_name", "primary_insurance_address", "primary_insurance_address2", "primary_insurance_city", "primary_insurance_state", "primary_insurance_group_no",
            "primary_insurance_postal_code", "primary_insurance_person_lname", "primary_insurance_person_fname", "primary_insurance_person_mname", "primary_insurance_person_phone", "primary_subscriber_employer",
            "primary_insurance_person_relationship", "primary_insurance_policy_no", "primary_insurance_coverage_type", "primary_freeb_type", "primary_cms_id",
            "secondary_insurance_name", "secondary_insurance_address", "secondary_insurance_address2", "secondary_insurance_city", "secondary_insurance_state", "secondary_subscriber_employer",
            "secondary_insurance_postal_code", "secondary_insurance_group_no", "secondary_insurance_person_lname",
            "secondary_insurance_person_fname", "secondary_insurance_person_relationship", "secondary_insurance_policy_no",
            "secondary_insurance_coverage_type", "primary_insurance_person_address", "primary_insurance_person_city",
            "primary_insurance_person_state", "primary_insurance_person_postal_code", "secondary_insurance_person_address",
            "secondary_insurance_person_city", "secondary_insurance_person_state", "secondary_insurance_person_postal_code",
            "guarantor_lname", "guarantor_fname",
            "guarantor_address", "guarantor_city", "guarantor_state", "guarantor_postal_code", "guarantor_phone_no", "guarantor_relationship",
            "guarantor_subscriber_employer", "secondary_insurance_person_mname", "guarantor_mname", "ordering_provider_id", "ordering_provider_lname",
            "ordering_provider_fname", "send_app_id", "recv_app_id", "send_fac_id", "recv_fac_id", "DorP", "bill_to", "collection_date", "physician_id", "physician_upin",
            "parent_first", "parent_last", "parent_mid", "parent_add1", "parent_add2", "parent_city", "parent_state", "parent_zip", "parent_phone");


        $cofig_arr = $this->mapcolumntoxml();


        //GETTING VALUES OF ADDITIONAL XML TAGS
        $sql_order = "SELECT procedure_order_id FROM procedure_order WHERE patient_id = ? AND order_status = ? AND lab_id = ? ";

        $misc_value_arr = array();

        $order_value_arr = array($pid, "pending", $lab_id);
        $res_order = $this->applicationTable->zQuery($sql_order, $order_value_arr);

        $test_count = 0;
        $diag_count = 0;

        $return_arr = array();
        $i = 0;

        foreach ($res_order as $data_order) {
            $diagnosis = "";
            $diagnosis_icd10 = "";
            $test_id = "";
            $test_aoe = "";
            $diagnosis_1 = "";
            $diagnosis_icd10_1 = "";
            $test_id_1 = "";
            $test_aoe_1 = "";
            $result_xml = "";

            /* ------------------------------------GENERATING XML FROM CONFIGURATION FOR EACH ORDER---------------------------------------- */
            $sl = 0;
            foreach ($cofig_arr as $table => $config) {

                //SKIP THE DATA FETCHING OF CHILD TABLE ROWS , WHICH WILL AUTOMATICALLY FETCH IF IT IS CONFIGURED, IT HAS PARENT TABLE
                if ($config['parent_table'] <> "") {
                    continue;
                }
                $col_map_arr = $cofig_arr[$table]['column_map'];
                $res = $this->generateSQLSelect($pid, $lab_id, $data_order['procedure_order_id'], $cofig_arr, $table);

                foreach ($res as $data) {
                    $global_arr = array();
                    $check_arr = array();

                    foreach ($col_map_arr as $col => $tagstr) {
                        //CHECKING FOR MAULTIPLE TAG MAPPING
                        $tag_arr = explode(",", $tagstr);

                        foreach ($tag_arr as $tag) {
                            if (trim($tag) <> "") {
                                if (substr($tag, 0, 1) == "#") {
                                    $tag = substr($tag, 1, strlen($tag));
                                    $check_arr[] = "$" . $tag;
                                }
                                foreach ($check_arr as $check) {
                                    if (strstr($tag, $check)) {
                                        $tag = str_replace($check, ${ltrim($check, "$")}, $tag);
                                    }
                                }
                                if ($cofig_arr[$table]['value_map'][$col] <> "") {
                                    $$tag = $cofig_arr[$table]['value_map'][$col][$data[$col]];
                                } else {
                                    if ($cofig_arr[$table]['tag_value_condition'][$tag]['variable'] <> "") {
                                        if (${$cofig_arr[$table]['tag_value_condition'][$tag]['variable']} == $cofig_arr[$table]['tag_value_condition'][$tag]['value']) {
                                            $$tag = $data[$col];
                                        }
                                    } else {
                                        $$tag = $data[$col];
                                    }
                                }
                            }
                        }
                    }

                    if ($config['child_table'] <> "") {
                        $child_table_arr = explode(",", $config['child_table']);

                        foreach ($child_table_arr as $child_table) {
                            if (trim($child_table) <> "") {
                                $res2 = $this->generateSQLSelect($pid, $lab_id, $data_order['procedure_order_id'], $cofig_arr, $child_table);
                                $fetch2_count = 0;
                                foreach ($res2 as $data1) {
                                    $col_map_arr2 = $cofig_arr[$child_table]['column_map'];

                                    foreach ($col_map_arr2 as $col => $tagstr) {
                                        //CHECKING FOR MAULTIPLE TAG MAPPING
                                        $tag_arr = explode(",", $tagstr);

                                        foreach ($tag_arr as $tag) {
                                            if (trim($tag) <> "") {
                                                foreach ($check_arr as $check) {
                                                    if (strstr($tag, $check)) {
                                                        $tag = str_replace($check, ${ltrim($check, "$")}, $tag);
                                                    }
                                                }

                                                if (substr($tag, 0, 1) == "#") {
                                                    $tag = substr($tag, 1, strlen($tag));
                                                }
                                                if ($cofig_arr[$table]['value_map'][$col] <> "") {
                                                    $$tag = $cofig_arr[$table]['value_map'][$col][$data1[$col]];
                                                } else {
                                                    $$tag = $data1[$col];
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            /* ----------------------------------------------------------------------------------------------------------------------------- */

            $xmlfile = ($xmlfile <> "") ? $xmlfile : "order_new_" . gmdate('YmdHis') . "_" . $data_order['procedure_order_id'] . ".xml";
            $result_xml = '<?xml version="1.0" encoding="UTF-8"?><Order>';

            foreach ($xmltag_arr as $tag) {
                $tag_val = (trim(${$tag}) <> "") ? ${$tag} : "";
                $config->Order->$tag = $tag_val;
                $result_xml.= '<' . $tag . '>' . $tag_val . '</' . $tag . '>';
            }

            /* ------------------ GETTING TEST DETAILS ------------------------ */

            $aoe_count = 0;
            $newReqCheck = 1;
            $dupCheck = 0;
            $cntTest = 1;
            $sql_test = "SELECT procedure_code, procedure_suffix, procedure_order_seq, diagnoses, patient_instructions FROM procedure_order_code
                                WHERE procedure_order_id = ?   ";

            $test_value_arr1 = array($data_order['procedure_order_id']);

            $tests = $this->applicationTable->zQuery($sql_test, $test_value_arr1);
            $orderProceduresArr = array();
            foreach ($tests as $order) {
                if ($cntTest > 40) {
                    $orderProceduresArr[] = $order['procedure_order_seq'];
                    $newReqCheck = 2;
                }
                $cntTest = $cntTest + 1;
            }
            $splitRule = 0;
            $splitRule2 = 0;
            if (empty($orderProceduresArr)) {
                $sql_test = "SELECT procedure_code, procedure_suffix, procedure_order_seq, diagnoses, patient_instructions FROM procedure_order_code
                                WHERE procedure_order_id = ?   ";

                $test_value_arr1 = array($data_order['procedure_order_id']);

                $tests = $this->applicationTable->zQuery($sql_test, $test_value_arr1);
                foreach ($tests as $order) {
                    if ($order['procedure_code'] == '488162' || $order['procedure_code'] == '500918' || $order['procedure_code'] == '980004') {
                        $orderProceduresArr[] = $order['procedure_order_seq'];
                        $splitRule = 1;
                        $newReqCheck = $newReqCheck + 1;
                    } else {
                        $splitRule2 = 1;
                    }
                }
            }
            if (!$splitRule2 && $splitRule) {
                $orderProceduresArr = array();
                $newReqCheck = 1;
            }


            if (empty($orderProceduresArr)) {

                $sql_aoe = "SELECT
        pq.hl7_segment,
        pa.`answer`,pq.tips,pa.procedure_order_seq
      FROM
        `procedure_answers` pa
        JOIN `procedure_order_code` poc
          ON poc.`procedure_order_id` = pa.`procedure_order_id`
          AND poc.`procedure_order_seq` = pa.`procedure_order_seq`
        JOIN `procedure_questions` pq ON FIND_IN_SET(poc.`procedure_code`, pq.`procedure_code`)
         
          AND pq.`question_code` = pa.`question_code`
      WHERE pa.`procedure_order_id` = ?
      ORDER BY pa.procedure_order_seq ";
                $aoe_value_arr1 = array($data_order['procedure_order_id']);

                $res_aoe = $this->applicationTable->zQuery($sql_aoe, $aoe_value_arr1);


                foreach ($res_aoe as $aoe) {
                    if (substr($aoe['hl7_segment'], 0, 3) == 'ZSA' || substr($aoe['hl7_segment'], 0, 3) == 'ZCY' || substr($aoe['hl7_segment'], 0, 3) == 'ZBL') {
                        if (($dupCheck > 0) && ($lastAoe != substr($aoe['hl7_segment'], 0, 3))) {
                            if (!in_array($aoe['procedure_order_seq'], $orderProceduresArr)) {
                                $orderProceduresArr[] = $aoe['procedure_order_seq'];
                                $newReqCheck = $newReqCheck + 1;
                            }
                        }
                        $dupCheck = $dupCheck + 1;
                        $lastAoe = substr($aoe['hl7_segment'], 0, 3);
                    }
                }
            }

            $testList = implode(",", $orderProceduresArr);
            for ($chki = 1; $chki <= $newReqCheck; $chki++) {
                $cndQry = "";
                if ($newReqCheck > 1) {
                    if (!$testList) {
                        $testList = 0;
                    }
                    if ($chki > 1) {
                        $cndQry = " AND procedure_order_seq IN (" . $testList . ") ";
                    } else {
                        $cndQry = " AND procedure_order_seq NOT IN (" . $testList . ") ";
                    }
                }
                $sql_test = "SELECT procedure_code, procedure_suffix, procedure_order_seq, diagnoses, patient_instructions,specimen_details FROM procedure_order_code
                                WHERE procedure_order_id = ?  $cndQry ";

                $test_value_arr = array($data_order['procedure_order_id']);

                $res_test = $this->applicationTable->zQuery($sql_test, $test_value_arr);
                $specimen_details1 = "";
                $specimen_details2 = "";
                $patient_instructions = "";
                foreach ($res_test as $data_test) {
                    /* -------------------- 	GETTING PATIENT INSTRUCTIONS --------------- */
                    $patient_instructions .= $data_test['patient_instructions'] . "*-@-#";

                    if ($chki == 1) {
                        if ($data_test['procedure_code'] <> "") {
                            $test_id .= $data_test['procedure_code'] . "#!#" . $data_test['procedure_suffix'] . "#--#";
                        }
                        if ($data_test['specimen_details'] <> '') {
                            $specimen_details1 .= $data_test['specimen_details'] . "|-@-#";
                        }

                        /* ------------------- GETTING DIAGNOSES DETAILS ------------------- */
                        if ($data_test['diagnoses'] <> "") {
                            $diag_arr = explode(";", $data_test['diagnoses']);

                            foreach ($diag_arr as $diag) {
                                if (strpos($diag, ":")) {
                                    $diag_array = explode(":", $diag, 2);
                                    if ($diag_array[0] == "ICD10") {
                                        $diag_str = $diag_array[1];
                                        $diagnosis_icd10 .= $diag_str;
                                        $diagnosis_icd10 .= "@#@";
                                    } else {
                                        $diag_str = $diag_array[1];
                                        $diagnosis .= $diag_str;
                                        $diagnosis .= "#@#";
                                    }
                                } else {
                                    $diag_str = $diag;
                                    $diagnosis .= $diag_str;
                                    $diagnosis .= "#@#";
                                }
                            }
                            $diagnosis = rtrim($diagnosis, "#@#");
                            $diagnosis_icd10 = rtrim($diagnosis_icd10, "@#@");
                            $diag_count++;
                        }
                        if ($diagnosis)
                            $diagnosis.="#~@~#";
                        if ($diagnosis_icd10)
                            $diagnosis_icd10.="@~#~@";

                        /* ------------------- GETTING AOE DETAILS ----------------- */
                        $sql_aoe = "SELECT question_code,answer FROM procedure_answers
				    WHERE procedure_order_id = ? AND procedure_order_seq = ? ORDER BY answer_seq";
                        $aoe_value_arr = array();

                        $aoe_value_arr = array($data_order['procedure_order_id'], $data_test['procedure_order_seq']);

                        $res_aoe = $this->applicationTable->zQuery($sql_aoe, $aoe_value_arr);
                        $aoe_count = 0;
                        foreach ($res_aoe as $data_aoe) {
                            if ($data_aoe['question_code'] <> "") {
                                $aoe_count++;
                                if ($aoe_count > 1) {
                                    $test_aoe .= "!#@#!";
                                }
                                $test_aoe .= $data_aoe['question_code'] . "!@!" . $data_aoe['answer'];
                            }
                        }
                        $test_aoe .= "!-#@#-!";
                    } else {
                        /* -------------------- 	GETTING PATIENT INSTRUCTIONS --------------- */

                        if ($data_test['procedure_code'] <> "") {
                            $test_id_1 .= $data_test['procedure_code'] . "#!#" . $data_test['procedure_suffix'] . "#--#";
                        }
                        if ($data_test['specimen_details'] <> '') {
                            $specimen_details2 .= $data_test['specimen_details'] . "|-@-#";
                        }
                        /* ------------------- GETTING DIAGNOSES DETAILS ------------------- */
                        if ($data_test['diagnoses'] <> "") {
                            $diag_arr = explode(";", $data_test['diagnoses']);

                            foreach ($diag_arr as $diag) {
                                if (strpos($diag, ":")) {
                                    $diag_array = explode(":", $diag, 2);
                                    if ($diag_array[0] == "ICD10") {
                                        $diag_str = $diag_array[1];
                                        $diagnosis_icd10_1 .= $diag_str;
                                        $diagnosis_icd10_1 .= "@#@";
                                    } else {
                                        $diag_str = $diag_array[1];
                                        $diagnosis_1 .= $diag_str;
                                        $diagnosis_1 .= "#@#";
                                    }
                                } else {
                                    $diag_str = $diag;
                                    $diagnosis_1 .= $diag_str;
                                    $diagnosis_1 .= "#@#";
                                }
                            }
                            $diagnosis_1 = rtrim($diagnosis_1, "#@#");
                            $diagnosis_icd10_1 = rtrim($diagnosis_icd10_1, "@#@");
                            $diag_count++;
                        }
                        if ($diagnosis_1)
                            $diagnosis_1.="#~@~#";
                        if ($diagnosis_icd10_1)
                            $diagnosis_icd10_1.="@~#~@";

                        /* ------------------- GETTING AOE DETAILS ----------------- */
                        $sql_aoe = "SELECT question_code,answer FROM procedure_answers
				    WHERE procedure_order_id = ? AND procedure_order_seq = ? ORDER BY answer_seq";
                        $aoe_value_arr = array();

                        $aoe_value_arr = array($data_order['procedure_order_id'], $data_test['procedure_order_seq']);

                        $res_aoe = $this->applicationTable->zQuery($sql_aoe, $aoe_value_arr);
                        $aoe_count = 0;
                        foreach ($res_aoe as $data_aoe) {
                            if ($data_aoe['question_code'] <> "") {
                                $aoe_count++;
                                if ($aoe_count > 1) {
                                    $test_aoe_1 .= "!#@#!";
                                }
                                $test_aoe_1 .= $data_aoe['question_code'] . "!@!" . $data_aoe['answer'];
                            }
                        }
                        $test_aoe_1 .= "!-#@#-!";
                    }
                }
            }
            $newReqFlag = 'N';
            if ($newReqCheck > 1) {
                $newReqFlag = 'Y';
            }
            //Selecting vital details 
            $qry1 = "SELECT bps FROM form_vitals WHERE id= (SELECT MAX(id) FROM form_vitals WHERE pid=? AND bps IS NOT NULL) ";
            $resbps = $this->applicationTable->zQuery($qry1, array($pid));
            $bps = $resbps->current();
            $qry2 = "SELECT bpd FROM form_vitals WHERE id= (SELECT MAX(id) FROM form_vitals WHERE pid=? AND bpd IS NOT NULL) ";
            $resbpd = $this->applicationTable->zQuery($qry2, array($pid));
            $bpd = $resbpd->current();
            $qry3 = "SELECT waist_circ FROM form_vitals WHERE id= (SELECT MAX(id) FROM form_vitals WHERE pid=? AND waist_circ>0) ";
            $reswaist = $this->applicationTable->zQuery($qry3, array($pid));
            $waist = $reswaist->current();

            $diagnosis = rtrim($diagnosis, "#~@~#");
            $diagnosis_icd10 = rtrim($diagnosis_icd10, "@~#~@");
            $diagnosis_1 = rtrim($diagnosis_1, "#~@~#");
            $diagnosis_icd10_1 = rtrim($diagnosis_icd10_1, "@~#~@");
            $specimen_details = rtrim($specimen_details, "|-@-#");
            $patient_instructions = rtrim($patient_instructions, "*-@-#");
            /* ----------------------------  ASSIGNING ADDITIONAL TAG ELEMENTS FOR ORDER---------------------------------- */
            $result_xml .= '<observation_request_comments>' . $patient_instructions . '</observation_request_comments>';
            $result_xml .= '<test_id>' . $test_id . '</test_id>';
            $result_xml .= '<test_diagnosis>' . $diagnosis . '</test_diagnosis>';
            $result_xml .= '<test_diagnosis_ten>' . $diagnosis_icd10 . '</test_diagnosis_ten>';
            $result_xml .= '<test_aoe>' . $test_aoe . '</test_aoe>';
            $result_xml .= '<test_id_1>' . $test_id_1 . '</test_id_1>';
            $result_xml .= '<test_diagnosis_1>' . $diagnosis_1 . '</test_diagnosis_1>';
            $result_xml .= '<test_diagnosis_ten_1>' . $diagnosis_icd10_1 . '</test_diagnosis_ten_1>';
            $result_xml .= '<test_aoe_1>' . $test_aoe_1 . '</test_aoe_1>';
            $result_xml .= '<specimen_details1>' . $specimen_details1 . '</specimen_details1>';
            $result_xml .= '<specimen_details2>' . $specimen_details2 . '</specimen_details2>';
            $result_xml .= '<bps>' . $bps['bps'] . '</bps>';
            $result_xml .= '<bpd>' . $bpd['bpd'] . '</bpd>';
            $result_xml .= '<waist>' . $waist['waist_circ'] . '</waist>';
            $result_xml .= '</Order>';

            $return_arr[] = array(
                'order_id' => $data_order['procedure_order_id'],
                'test_req_flag' => $newReqFlag,
                'xmlstring' => $result_xml
            );
        }
        return $return_arr;
    }

    /**
     * getClientCredentials()
     * @param type $proc_order_id
     * @return type
     */
    public function getClientCredentials($proc_order_id) {
        $sql_proc = "SELECT lab_id FROM procedure_order WHERE procedure_order_id = ? ";
        $proc_value_arr = array();
        $proc_value_arr['procedure_order_id'] = $proc_order_id;
        $result = $this->applicationTable->zQuery($sql_proc, array($proc_order_id));
        $res_proc = $result->current();
        $sql_cred = "SELECT  login, password, remote_host FROM procedure_providers WHERE ppid = ? ";
        $result = $this->applicationTable->zQuery($sql_cred, array($res_proc['lab_id']));
        $res_cred = $result->current();
        return $res_cred;
    }

    /**
     * setOrderStatus()
     * @param type $proc_order_id
     * @param type $status
     * @return type
     */
    public function setOrderStatus($proc_order_id, $status) {
        $sql_status = "UPDATE procedure_order SET order_status = ? WHERE procedure_order_id = ? ";
        $status_value_arr = array();

        $status_value_arr = array($status, $proc_order_id);
        $res = $this->applicationTable->zQuery($sql_status, $status_value_arr);
        return $res;
    }

    /**
     * saveProcedureProvider()
     * @param type $post
     * @return type
     */
    public function saveProcedureProvider($post) {
        $return = array();
        if ($post['ppid'] && $post['ppid'] > 0) {
            $sql = "UPDATE procedure_providers SET name = ?, 
                                                    npi = ?,           
                                                    send_app_id = ?, 
                                                    send_fac_id = ?, 
                                                    recv_app_id = ?, 
                                                    recv_fac_id = ?, 
                                                    DorP = ?, 
                                                    protocol = ?, 
                                                    remote_host = ?, 
                                                    login = ?, 
                                                    password = ?, 
                                                    orders_path = ?, 
                                                    results_path = ?, 
                                                    notes = ?   
                                                    WHERE ppid = ?";
            $return = $this->applicationTable->zQuery($sql, array($post['name'],
                $post['npi'],
                $post['send_app_id'],
                $post['send_fac_id'],
                $post['recv_app_id'],
                $post['recv_fac_id'],
                $post['DorP'],
                $post['protocol'],
                $post['remote_host'],
                $post['login'],
                $post['password'],
                $post['orders_path'],
                $post['results_path'],
                $post['notes'],
                $post['ppid'])
            );
        } else {
            $sql = "INSERT INTO procedure_providers SET name = ?, 
                                                        npi = ?,           
                                                        send_app_id = ?, 
                                                        send_fac_id = ?, 
                                                        recv_app_id = ?, 
                                                        recv_fac_id = ?, 
                                                        DorP = ?, 
                                                        protocol = ?, 
                                                        remote_host = ?, 
                                                        login = ?, 
                                                        password = ?, 
                                                        orders_path = ?, 
                                                        results_path = ?, 
                                                        notes = ?";
            $return = $this->applicationTable->zQuery($sql, array($post['name'],
                $post['npi'],
                $post['send_app_id'],
                $post['send_fac_id'],
                $post['recv_app_id'],
                $post['recv_fac_id'],
                $post['DorP'],
                $post['protocol'],
                $post['remote_host'],
                $post['login'],
                $post['password'],
                $post['orders_path'],
                $post['results_path'],
                $post['notes'])
            );
        }
        return $return;
    }

    // Delete Procedure Provider
    public function deleteProcedureProvider($post) {
        if ($post['ppid'] && $post['ppid'] > 0) {
            $sql = "DELETE FROM procedure_providers
														WHERE ppid=?";
            $return = $this->applicationTable->zQuery($sql, array($post['ppid']));
        }
    }

    /**
     * Function to get the ICD9 and ICD10 from the code types
     * @global array $code_types
     * @return array $diagArr
     */
    public function getDiagArr() {
        global $code_types;
        $diagArr = array();
        foreach ($code_types as $key => $value) {
            if ($value['diag'] == "1") {
                if ($key != 'ICD10')
                    continue;
                $diagArr[$key] = $value;
            }
        }
        return $diagArr;
    }

    /**
     * Function to get the User selected code Types
     * @return array $arr
     */
    public function getUserSelectedCodeTypes() {
        $codetype_res = $this->applicationTable->zQuery("SELECT * FROM user_settings WHERE setting_user = ? AND setting_label = ?", array($_SESSION['authUserID'], "code_type"));
        $arr = array();
        foreach ($codetype_res as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * Function to get the specimen source details
     * @return array $arr
     */
    public function getSpecimenSource() {
        $sql = "SELECT `specimen_descriptor`,`specimen_term` FROM `procedure_specimen` WHERE specimen_type= 'S'";
        $spec_source = $this->applicationTable->zQuery($sql);
        $arr = array();
        foreach ($spec_source as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * Function to get the specimen source details
     * @return array $arr
     */
    public function getSpecimenSourceDesc() {
        $sql = "SELECT `specimen_descriptor`,`specimen_term` FROM `procedure_specimen` WHERE specimen_type= 'D'";
        $spec_source = $this->applicationTable->zQuery($sql);
        $arr = array();
        foreach ($spec_source as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * Function to find whether the primary insurance of the patient id Medicare Or Not
     * @global type $pid
     * return array $IsMedicare
     */
    public function getPrimaryMedicare() {
        global $pid;
        $sql = "SELECT 
                  ic.`name` 
                FROM
                  patient_data pd 
                  JOIN `insurance_data` AS id 
                    ON id.`pid` = pd.`pid` 
                  JOIN `insurance_companies` AS ic 
                    ON (
                      ic.`id` = id.`provider` 
                      AND ic.`freeb_type` = 2
                    ) 
                WHERE pd.`pid` = ? 
                  AND id.`type` = 'primary' 
                  AND id.date = 
                  (SELECT 
                    MAX(DATE) 
                  FROM
                    insurance_data 
                  WHERE pid = pd.pid 
                    AND TYPE = 'primary')";
        $result = $this->applicationTable->zQuery($sql, array($pid));
        $IsMedicare = $result->current();
        return $IsMedicare;
    }

    /**
     * Function to get the last order ids
     * @param string $orderid
     * @return array $arr
     */
    public function getLastOrderId($orderid) {
        $sql = "SELECT procedure_order_id FROM `procedure_order` ORDER BY procedure_order_id DESC LIMIT $orderid";
        $result = $this->applicationTable->zQuery($sql);
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row['procedure_order_id'];
        }
        return $arr;
    }

}
?>


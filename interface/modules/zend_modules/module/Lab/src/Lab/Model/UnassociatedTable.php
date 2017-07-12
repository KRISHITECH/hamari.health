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

class UnassociatedTable extends AbstractTableGateway {

    public $tableGateway;
    protected $applicationTable;

    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
        $adapter = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
        $this->adapter = $adapter;
        $this->applicationTable = new ApplicationTable();
    }

    /**
     * listPdf
     * @return type
     */
    public function listPdf() {

        $sql = "SELECT * FROM procedure_result_unassociated WHERE attached = 0";
        $result = $this->applicationTable->zQuery($sql);
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * listResolvedPdf
     * @return type
     */
    public function listResolvedPdf() {
        $sql = "SELECT * FROM procedure_result_unassociated WHERE attached = 1";
        $result = $this->applicationTable->zQuery($sql);
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * attachUnassociatedDetails
     * @param type $request
     */
    public function attachUnassociatedDetails($request) {
        if ($request->type == 'attachToCurrentPatient') {
            $sqlupd = "UPDATE procedure_result_unassociated SET attached = 1, comment = ? WHERE id = ?";
            $param = array();
            array_push($param, '');
            array_push($param, $request->file_id);
            $result = $this->applicationTable->zQuery($sqlupd, $param);
        }
    }

    /**
     * listResultOnlyWithnamesearch
     * @param type $start
     * @param type $limit
     * @param type $nameTosearch
     * @param type $dobTosearch
     * @return type
     */
    public function listResultOnlyWithnamesearch($start, $limit, $nameTosearch, $dobTosearch) {
        $searchArray = array();
        if ($nameTosearch != '' || $dobTosearch != '') {
            if ($nameTosearch == '') {
                $WHERE = "p.patient_dob LIKE ? AND p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0 ";
                $searchArray = array("%" . $dobTosearch . "%");
            }
            if ($dobTosearch == '') {
                $WHERE = "p.patient_fname LIKE ? OR p.patient_lname LIKE ? OR CONCAT(patient_lname,' ',patient_fname)  LIKE ? AND p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0 ";
                $searchArray = array("%" . $nameTosearch . "%", "%" . $nameTosearch . "%", "%" . $nameTosearch . "%");
            }
        }
        if ($nameTosearch != '' && $dobTosearch != '') {
            $WHERE = "(p.patient_fname LIKE ? OR p.patient_lname LIKE ? OR CONCAT(patient_lname,' ',patient_fname)  LIKE ? ) AND (p.patient_dob LIKE ?) AND p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0";
            $searchArray = array("%" . $nameTosearch . "%", "%" . $nameTosearch . "%", "%" . $dobTosearch . "%", "%" . $nameTosearch . "%");
        }
        $sql = "SELECT * FROM procedure_result_only AS p JOIN procedure_subtest_result_only AS ps ON ps.lab_result_id = p.lab_result_id WHERE " . $WHERE . " LIMIT $start, $limit ";
        $result = $this->applicationTable->zQuery($sql, $searchArray);
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * listResultOnly
     * @param type $start
     * @param type $limit
     * @return type
     */
    public function listResultOnly($start, $limit) {
        $sql = "SELECT * FROM procedure_result_only AS p JOIN procedure_subtest_result_only AS ps ON ps.lab_result_id = p.lab_result_id WHERE p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0 LIMIT $start, $limit";
        $result = $this->applicationTable->zQuery($sql);
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row;
        }
        return $arr;
    }

    /**
     * listResultOnlyCount
     * @return type
     */
    public function listResultOnlyCount() {
        $sql = "SELECT * FROM procedure_result_only AS p JOIN procedure_subtest_result_only AS ps ON ps.lab_result_id = p.lab_result_id WHERE p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0";
        $result = $this->applicationTable->zQuery($sql);
        return $result->count();
    }

    /**
     * listResultOnlyCountwithnamesearch
     * @param type $nameTosearch
     * @param type $dobTosearch
     * @return type
     */
    public function listResultOnlyCountwithnamesearch($nameTosearch, $dobTosearch) {
        $searchArray = array();
        if ($nameTosearch != '' || $dobTosearch != '') {
            if ($nameTosearch == '') {
                $WHERE = "p.patient_dob LIKE ? AND p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0 ";
                $searchArray = array("%" . $dobTosearch . "%");
            }
            if ($dobTosearch == '') {
                $WHERE = "p.patient_fname LIKE ? OR p.patient_lname LIKE ? OR CONCAT(patient_lname,' ',patient_fname)  LIKE ? AND p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0 ";
                $searchArray = array("%" . $nameTosearch . "%", "%" . $nameTosearch . "%", "%" . $nameTosearch . "%");
            }
        }
        if ($nameTosearch != '' && $dobTosearch != '') {
            $WHERE = "(p.patient_fname LIKE ? OR p.patient_lname LIKE ? OR CONCAT(patient_lname,' ',patient_fname)  LIKE ? ) AND (p.patient_dob LIKE ?) AND p.`procedure_order_id` IS NULL OR p.`procedure_order_id` = 0 ";
            $searchArray = array("%" . $nameTosearch . "%", "%" . $nameTosearch . "%", "%" . $dobTosearch . "%", "%" . $nameTosearch . "%");
        }
        $sql = "SELECT * FROM procedure_result_only AS p JOIN procedure_subtest_result_only AS ps ON ps.lab_result_id = p.lab_result_id WHERE " . $WHERE;
        $result = $this->applicationTable->zQuery($sql, $searchArray);
        return $result->count();
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

}
?>


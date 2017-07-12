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

namespace Lab\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Lab\Model\Lab;
use Lab\Form\LabForm;
use Zend\View\Model\JsonModel;
use Zend\Soap\Client;
use Zend\Config;
use Zend\Config\Reader;
use Zend\ZendPdf;

class LabController extends AbstractActionController {

    protected $labTable;

    /**
     * Lab Order Row wise
     */
    public function orderAction() {

        global $pid;
        $msg = '';
        if ($pid == '' || $_SESSION['encounter'] == '') {
            $msg = 'N';
        }
        $form = new LabForm();
        $providers = $this->CommonPlugin()->getProviders();

        $form->get('provider[0][]')->setValueOptions($providers);

        $labs = $this->CommonPlugin()->getLabs('y');

        $primary_medicare = $this->getLabTable()->getPrimaryMedicare();
        $primary_medicare_data = $primary_medicare['name'];
        if (count($labs) == 0) {
            return $this->redirect()->toRoute('provider', array('action' => 'index'));
        }
        $form->get('lab_id[0][]')->setValueOptions($labs);

        $specimen_source = $this->getLabTable()->getSpecimenSource(); // to get the specimen source details 
        $specimen_source_des = $this->getLabTable()->getSpecimenSourceDesc(); // to get the specimen source details
        array_unshift($specimen_source, "");
        array_unshift($specimen_source_des, "");
        $priority = $this->CommonPlugin()->getList("ord_priority", 'normal');
        $form->get('priority[0][]')->setValueOptions($priority);

        $status = $this->CommonPlugin()->getList("ord_status", 'pending');
        $form->get('status[0][]')->setValueOptions($status);
        $diagArr = $this->getLabTable()->getDiagArr();
        $userCodeTypesArr = $this->getLabTable()->getUserSelectedCodeTypes();
        $userSelectedCodeTypes = explode("|", $userCodeTypesArr[0]['setting_value']);
        $result = new ViewModel(array('form' => $form, 'message' => $msg, 'diagArr' => $diagArr, 'userSelectedCodeTypes' => $userSelectedCodeTypes, 'specimen_source' => $specimen_source, 'specimen_source_des' => $specimen_source_des, 'primary_medicare_data' => $primary_medicare_data));
        return $result;
    }

    /**
     * Lab order edit action
     * @global type $pid
     * @return type
     */
    public function ordereditAction() {
        global $pid;
        $msg = '';
        if ($pid == '') {
            $msg = 'N';
        }
        $form = new LabForm();
        $providers = $this->CommonPlugin()->getProviders();
        $form->get('provider[0][]')->setValueOptions($providers);


        $labs = $this->CommonPlugin()->getLabs('y');
        $form->get('lab_id[0][]')->setValueOptions($labs);

        $priority = $this->CommonPlugin()->getList("ord_priority");
        $form->get('priority[0][]')->setValueOptions($priority);

        $status = $this->CommonPlugin()->getList("ord_status", 'pending');
        $form->get('status[0][]')->setValueOptions($status);

        return array('form' => $form, 'message' => $msg,);
    }

    /**
     * Get all orders of a patient
     * @return \Zend\View\Model\JsonModel
     */
    public function getPatientLabOrdersAction() {
        $labOrders = $this->getLabTable()->listPatientLabOrders();
        $data = new JsonModel($labOrders);
        return $data;
    }

    /**
     * Function to get details of a particular order id
     * @return \Zend\View\Model\JsonModel
     */
    public function getOrderListAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array(
                'ordId' => $request->getPost('id'),
            );
        }
        $labOrders = $this->getLabTable()->listLabOrders($data);
        $data = new JsonModel($labOrders);
        return $data;
    }

    /**
     * Get order AOE details
     * @return type
     */
    public function getLabOrderAOEAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $data = array(
            'ordId' => $request->getPost('inputValue'),
            'seq' => $request->getPost('seq'),
        );
        $aoe = $this->getLabTable()->listLabOrderAOE($data);
        $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'aoeArray' => $aoe)));
        return $response;
    }

    /**
     * Remove Lab order
     * @return boolean
     */
    public function removeLabOrderAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array(
                'ordId' => $request->getPost('orderID'),
            );
        }
        $result = $this->getLabTable()->removeLabOrders($data);
        return true;
    }

    /**
     * Save Lab data
     * @return type
     */
    public function saveDataAction() {
        $request = $this->getRequest();
        $data = array();
        $form = new LabForm();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $Lab = new Lab();
            $aoeArr = array();
            foreach ($request->getPost() as $key => $val) {
                if (substr($key, 0, 4) === 'AOE_') {
                    $key = str_replace("#^#", ".", $key);
                    $NewArr = explode("_", $key);
                    if (is_array($val)) {
                        for ($cnt = 0; $cnt < count($val); $cnt++) {
                            $aoeArr[$NewArr[1] - 1][$NewArr[2]][$NewArr[3]] .= $val[$cnt] . '~:~';
                        }
                        $aoeArr[$NewArr[1] - 1][$NewArr[2]][$NewArr[3]] = rtrim($aoeArr[$NewArr[1] - 1][$NewArr[2]][$NewArr[3]], '~:~');
                    } else {
                        $aoeArr[$NewArr[1] - 1][$NewArr[2]][$NewArr[3]] = $val;
                    }
                }
            }

            $clientorder_id = $this->getLabTable()->saveLab($request->getPost(), $aoeArr);

            //------------- STARTING PROCEDURE ORDER XML IMPORT -------------
            for ($i = 0; $i < sizeof($clientorder_id); $i++) {
                //GET CLIENT CREDENTIALS OF INITIATING ORDER
                $cred = $this->getLabTable()->getClientCredentials($clientorder_id[$i]);
                $username = $cred['login'];
                $password = $cred['password'];
                $remote_host = trim($cred['remote_host']);
                $site_dir = $GLOBALS['OE_SITE_DIR'];

                if (($username <> "") && ($password <> "") && ($remote_host <> "")) {//GENERATE ORDER XML OF EXTERNAL LAB ONLY, NOT FOR LOCAL LAB
                    $post = $request->getPost();
                    $labPost = $post['lab_id'][$i][0];
                    $labArr = explode("|", $labPost);
                    //RETURNS AN ARRAY OF ALL PENDING ORDERS OF THE PATIENT
                    $xmlresult_arr = $this->getLabTable()->generateOrderXml($request->getPost('patient_id'), $labArr[0], "");
                    ini_set("soap.wsdl_cache_enabled", "0");
                    ini_set('memory_limit', '-1');

                    $options = array('location' => $remote_host,
                        'uri' => "urn://zhhealthcare/lab"
                    );
                    $client = new Client(null, $options);

                    $lab_id = $labArr[2];

                    foreach ($xmlresult_arr as $xmlresult) {
                        $order_id = $xmlresult['order_id'];
                        $xmlstring = $xmlresult['xmlstring'];
                        $test_req_flag = $xmlresult['test_req_flag'];
                        //GET CLIENT CREDENTIALS OF EACH PENDING ORDER OF A PARTICULAR PATIENT   
                        $cred = $this->getLabTable()->getClientCredentials($order_id);
                        $username = $cred['login'];
                        $password = $cred['password'];
                        $remote_host = trim($cred['remote_host']);
                        $site_dir = $GLOBALS['OE_SITE_DIR'];

                        if (($username <> "") && ($password <> "") && ($remote_host <> "")) {//GENERATE ORDER XML OF EXTERNAL LAB ONLY, NOT FOR LOCAL LAB
                            $result = $client->importOrder($username, $password, $site_dir, $order_id, $lab_id, $xmlstring, $test_req_flag);
                            if (is_numeric($result)) {// CHECKS IF ORDER IS SUCCESSFULLY IMPORTED
                                $this->getLabTable()->setOrderStatus($order_id, "routed");
                            }
                        }
                    }
                }
                //------------- END PROCEDURE ORDER XML IMPORT -------------
            }
            $order_id = '';
            if ($request->getpost('print_check') == "print") {
                $laborderid = $this->getLabTable()->getLastOrderId($request->getpost('tot_order'));
                foreach ($laborderid as $res) {
                    $order_id = $order_id . $res . ',';
                }
                $order_id = rtrim($order_id, ',');
                return $this->redirect()->toRoute('result', array(
                            'action' => 'index',
                                ), array('query' => array('orderid' => $order_id)));
            } else {
                return $this->redirect()->toRoute('result');
            }
        }
        return array('form' => $form);
    }

    /**
     * Index action
     * @return type
     */
    public function indexAction() {
        return $this->redirect()->toRoute('lab', array('action' => 'order'));
    }

    /**
     * Set Lab location
     * @return \Zend\View\Model\JsonModel
     */
    public function labLocationAction() {
        $request = $this->getRequest();
        $inputString = $request->getPost('inputValue');
        if ($request->isPost()) {
            $location = $this->getLabLocation($inputString);
            $data = new JsonModel($location);
            return $data;
        }
    }

    /**
     * Get lab location
     * @param type $inputString
     * @return type
     */
    public function getLabLocation($inputString) {
        $patents = $this->getLabTable()->listLabLocation($inputString);
        return $patents;
    }

    /**
     * Search Action
     * @return type
     */
    public function searchAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $inputString = $request->getPost('inputValue');
        $dependentId = $request->getPost('dependentId');
        $remoteLabId = $request->getPost('remoteLabId');
        if ($request->isPost()) {
            if ($request->getPost('type') == 'getProcedures') {
                $procedures = $this->getProcedures($inputString, $dependentId, $remoteLabId);
                $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'procedureArray' => $procedures)));
                return $response;
            }
            if ($request->getPost('type') == 'loadAOE') {
                $AOE = $this->getAOE($inputString, $dependentId, $remoteLabId);
                $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'aoeArray' => $AOE)));
                return $response;
            }
            if ($request->getPost('type') == 'getDiagnoses') {
                $diagnoses = $this->getDiagnoses($inputString);
                $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'diagnosesArray' => $diagnoses)));
                return $response;
            }
        }
    }

    /**
     * Get procedures of lab
     * @param type $inputString
     * @param type $labId
     * @param type $remoteLabId
     * @return type
     */
    public function getProcedures($inputString, $labId, $remoteLabId) {
        $procedures = $this->getLabTable()->listProcedures($inputString, $labId, $remoteLabId);
        return $procedures;
    }

    /**
     * Get AOE for a test
     * @param type $procedureCode
     * @param type $labId
     * @param type $remoteLabId
     * @return type
     */
    public function getAOE($procedureCode, $labId, $remoteLabId) {
        $AOE = $this->getLabTable()->listAOE($procedureCode, $labId, $remoteLabId);
        return $AOE;
    }

    /**
     * List diagnosis
     * @param type $inputString
     * @return type
     */
    public function getDiagnoses($inputString) {
        $diagnoses = $this->getLabTable()->listDiagnoses($inputString);
        return $diagnoses;
    }

    /**
     * getLabTable
     * @return type
     */
    public function getLabTable() {
        if (!$this->labTable) {
            $sm = $this->getServiceLocator();
            $this->labTable = $sm->get('Lab\Model\LabTable');
        }
        return $this->labTable;
    }

    // List all providers
    public function getProcedureProvidersAction() {
        $procProviders = $this->CommonPlugin()->getProcedureProviders();
        $data = new JsonModel($procProviders);
        return $data;
    }

    // Save Procedure Provider (New Or Update)
    public function saveProcedureProviderAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $return = $this->getLabTable()->saveProcedureProvider($request->getPost());
            if ($return) {
                $return = array('errorMsg' => 'Error while processing .... !');
            }
            $response->setContent(\Zend\Json\Json::encode($return));
        }
        return $response;
    }

    // Dlete Procedur Provider
    public function deleteProcedureProviderAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $return = $this->getLabTable()->deleteProcedureProvider($request->getPost());
            $response->setContent(\Zend\Json\Json::encode(array('success' => 'Recored Successfully Deleteed .... !')));
        }
        return $response;
    }

}

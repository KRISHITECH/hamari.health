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
use Lab\Model\Pull;
use Lab\Form\PullForm; //EDITED
use Zend\View\Model\JsonModel;
use Zend\Soap\Client;
use Zend\Config;
use Zend\Config\Reader;

class PullController extends AbstractActionController {

    protected $pullTable;

    /**
     * Index action
     * @return type
     */
    public function indexAction() {
        $form = new PullForm();
        $helper = $this->getServiceLocator()->get('viewhelpermanager')->get('emr_helper');

        $labs = $helper->getLabs();
        $form->get('lab_id')->setValueOptions($labs);

        return array('form' => $form);
    }

    /**
     * getPullTable()
     * @return type
     */
    public function getPullTable() {
        if (!$this->pullTable) {
            $sm = $this->getServiceLocator();
            $this->pullTable = $sm->get('Lab/Model/PullTable');
        }
        return $this->pullTable;
    }

    /**
     * pull tests
     * @return type
     */
    public function pullcompendiumtestAction() {
        $retmsg = "";

        $request = $this->getRequest();
        $response = $this->getResponse();

        $lab_id_val = $request->getPost('lab_id');

        $lab_ids = explode("|", $lab_id_val);

        $cred = $this->getPullTable()->getLabCredentials($lab_ids[0]);

        $username = trim($cred['login']);
        $password = trim($cred['password']);
        $remote_host = trim($cred['remote_host']);

        if (($username == "") || ($password == "")) {
            $retmsg = "Lab Credentials not found";
        } else if ($remote_host == "") {
            $retmsg = "Remote Host not found";
        } else {
            ini_set("soap.wsdl_cache_enabled", "0");
            ini_set('memory_limit', '-1');

            $options = array('location' => $remote_host,
                'uri' => "urn://zhhealthcare/lab"
            );

            $client = new Client(null, $options);
            $count = 0;
            $limit = 1000;
            $total_test_count = 0;
            do {
                $result_test_limit = $client->check_for_tests($username, $password, $lab_ids[1], $count);
                $total_test_count = count($result_test_limit);
                for ($cnt = 0; $cnt < $total_test_count; $cnt++) {
                    $result[($count * $limit) + $cnt] = array_shift($result_test_limit);
                }
                $count++;
            } while ($total_test_count);

            if ($result <> "-3") {
                $testconfig_arr = $this->getPullTable()->pullcompendiumTestConfig();
                $retstr = $this->getPullTable()->importDataCheck($result, $testconfig_arr, $lab_ids[0]);
                $retmsg = "Test Pulled";
            } else {
                $retmsg = "Unauthorised Lab Access";
            }
        }
        //return $this->redirect()->toRoute('result');
        $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'result' => $retmsg)));
        return $response;
    }

    /**
     * Pull AOE 
     * @return type
     */
    public function pullcompendiumaoeAction() {
        $retmsg = "";

        $request = $this->getRequest();
        $response = $this->getResponse();

        $lab_id_val = $request->getPost('lab_id');

        $lab_ids = explode("|", $lab_id_val);

        $cred = $this->getPullTable()->getLabCredentials($lab_ids[0]);

        $username = trim($cred['login']);
        $password = trim($cred['password']);
        $remote_host = trim($cred['remote_host']);

        if (($username == "") || ($password == "")) {
            $retmsg = "Lab Credentials not found";
        } else if ($remote_host == "") {
            $retmsg = "Remote Host not found";
        } else {
            ini_set("soap.wsdl_cache_enabled", "0");
            ini_set('memory_limit', '-1');

            $options = array('location' => $remote_host,
                'uri' => "urn://zhhealthcare/lab"
            );
            $client = new Client(null, $options);
            $count_aoe = 0;
            $limit_aoe = 1000;
            $total_aoe_count = 0;
            do {
                $result_aoe_limit = $client->check_for_aoe($username, $password, $lab_ids[1], $count_aoe);
                $total_aoe_count = count($result_aoe_limit);
                for ($cnt_aoe = 0; $cnt_aoe < $total_aoe_count; $cnt_aoe++) {
                    $result[($count_aoe * $limit_aoe) + $cnt_aoe] = array_shift($result_aoe_limit);
                }
                $count_aoe++;
            } while ($total_aoe_count);

            if ($result <> "-3") {
                $aoeconfig_arr = $this->getPullTable()->pullcompendiumAoeConfig();
                $retstr = $this->getPullTable()->importDataCheck($result, $aoeconfig_arr, $lab_ids[0]);
                $retmsg = "AOE Pulled";
            } else {
                $retmsg = "Unauthorised Lab Access";
            }
        }

        $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'result' => $retmsg)));
        return $response;
    }

    /**
     * Pull test and AOE
     * @return type
     */
    public function pullcompendiumtestaoeAction() {
        $retmsg = "";
        $retmsg_test = "";
        $retmsg_aoe = "";

        $request = $this->getRequest();
        $response = $this->getResponse();

        $lab_id_val = $request->getPost('lab_id');

        $lab_ids = explode("|", $lab_id_val);

        $cred = $this->getPullTable()->getLabCredentials($lab_ids[0]);

        $username = trim($cred['login']);
        $password = trim($cred['password']);
        $remote_host = trim($cred['remote_host']);

        if (($username == "") || ($password == "")) {
            $retmsg = "Lab Credentials not found";
        } else if ($remote_host == "") {
            $retmsg = "Remote Host not found";
        } else {
            ini_set('default_socket_timeout', 300);
            ini_set("soap.wsdl_cache_enabled", "0");
            ini_set('memory_limit', '-1');

            $options = array('location' => $remote_host,
                'uri' => "urn://zhhealthcare/lab"
            );

            $client = new Client(null, $options);
            $count = 0;
            $limit = 1000;
            $total_test_count = 0;
            do {
                $result_test_limit = $client->check_for_tests($username, $password, $lab_ids[1], $count);
                $total_test_count = count($result_test_limit);
                for ($cnt = 0; $cnt < $total_test_count; $cnt++) {
                    $result_test[($count * $limit) + $cnt] = array_shift($result_test_limit);
                }
                $count++;
            } while ($total_test_count);

            if ($result_test <> "-3") {
                $testconfig_arr = $this->getPullTable()->pullcompendiumTestConfig();
                $retstr = $this->getPullTable()->importDataCheck($result_test, $testconfig_arr, $lab_ids[0]);
                $retmsg_test = "Test Pulled";
            } else {
                $retmsg = "Unauthorised Lab Access";
            }

            $count_aoe = 0;
            $limit_aoe = 1000;
            $total_aoe_count = 0;
            do {
                $result_aoe_limit = $client->check_for_aoe($username, $password, $lab_ids[1], $count_aoe);
                $total_aoe_count = count($result_aoe_limit);
                for ($cnt_aoe = 0; $cnt_aoe < $total_aoe_count; $cnt_aoe++) {
                    $result_aoe[($count_aoe * $limit_aoe) + $cnt_aoe] = array_shift($result_aoe_limit);
                }
                $count_aoe++;
            } while ($total_aoe_count);

            if ($result <> "-3") {
                $aoeconfig_arr = $this->getPullTable()->pullcompendiumAoeConfig();
                $retstr = $this->getPullTable()->importDataCheck($result_aoe, $aoeconfig_arr, $lab_ids[0]);
                $retmsg_aoe = "AOE Pulled";
            } else {
                $retmsg = "Unauthorised Lab Access";
            }
        }
        //return $this->redirect()->toRoute('result');
        $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'result' => $retmsg . " " . $retmsg_test . ", " . $retmsg_aoe)));
        return $response;
    }

}

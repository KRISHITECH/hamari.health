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
use Lab\Model\Configuration; //vip
use Lab\Form\ConfigurationForm; //EDITED
use Zend\View\Model\JsonModel;
use Zend\Soap\Client;
use Zend\Config;
use Zend\Config\Reader;

class ConfigurationController extends AbstractActionController {

    protected $configurationTable;

    /**
     * Function Index Action
     * @return type
     */
    public function indexAction() {
        $form = new ConfigurationForm();
        $labs = $this->CommonPlugin()->getLabs();
        $form->get('order_from')->setValueOptions($labs);

        $body_sites = $this->CommonPlugin()->getList("proc_body_site");
        $form->get('order_bodysite')->setValueOptions($body_sites);

        $specimen_type = $this->CommonPlugin()->getList("proc_specimen");
        $form->get('order_specimentype')->setValueOptions($specimen_type);

        $admin_via = $this->CommonPlugin()->getList("proc_route");
        $form->get('order_administervia')->setValueOptions($admin_via);

        $laterality = $this->CommonPlugin()->getList("proc_lat");
        $form->get('order_laterality')->setValueOptions($laterality);

        $dafault_units = $this->CommonPlugin()->getList("proc_unit");
        $form->get('result_defaultunits')->setValueOptions($dafault_units);
        $form->get('reccomendation_defaultunits')->setValueOptions($dafault_units);

        return array('form' => $form);
    }

    /**
     * Function to get configuration details
     * @return type
     */
    public function getConfigurationTable() {
        if (!$this->configurationTable) {
            $sm = $this->getServiceLocator();
            $this->configurationTable = $sm->get('Lab/Model/ConfigurationTable');
        }
        return $this->configurationTable;
    }

    /**
     * Function to get configuration details for edit
     * @return type
     */
    public function getConfigEditDeatilsAction() {
        $request = $this->getRequest();
        $data = array('type_id' => $request->getQuery('type_id'));
        $typeID = $data['type_id'];
        $ret_arr = $this->getConfigurationTable()->getConfigDetails($typeID);
        return $ret_arr;
    }

    /**
     * Function to delete configuration details
     * @return type
     */
    public function deleteConfigDetailsAction() {
        $request = $this->getRequest();
        $data = array('type_id' => $request->getQuery('type_id'));
        $typeID = $data['type_id'];
        $ret_arr = $this->getConfigurationTable()->deleteConfigDetails($typeID);
        return $ret_arr;
    }

    /**
     * Function to get all configuration details
     * @return type
     */
    public function getAllConfigDeatilsAction() {
        $ret_arr = $this->getConfigurationTable()->getAllConfigDetails();
        return $ret_arr;
    }

    /**
     * Function to save all configuration details
     * @return type
     */
    public function saveConfigDetailsAction() {
        $request = $this->getRequest();
        $up_res = $this->getConfigurationTable()->updateConfigDetails($request);
        return $up_res;
    }

    /**
     * Function to get configuration page details
     * @return type
     */
    public function getConfigAddPageDeatilsAction() {
        $body_sites = $this->CommonPlugin()->getList("proc_body_site");
        $specimen_type = $this->CommonPlugin()->getList("proc_specimen");
        $admin_via = $this->CommonPlugin()->getList("proc_route");
        $laterality = $this->CommonPlugin()->getList("proc_lat");
        $dafault_units = $this->CommonPlugin()->getList("proc_unit");

        $list_arr = array();

        $list_arr[] = $body_sites;
        $list_arr[] = $specimen_type;
        $list_arr[] = $admin_via;
        $list_arr[] = $laterality;
        $list_arr[] = $dafault_units;

        $ret_arr = $this->getConfigurationTable()->getAddConfigDetails($list_arr);
        return $ret_arr;
    }

    /**
     * Function to add configuration
     * @return type
     */
    public function addConfigDetailsAction() {
        $request = $this->getRequest();
        $up_res = $this->getConfigurationTable()->addConfigDetails($request);
        return $up_res;
    }

    /**
     * Check Procedure Code
     * Avoid Duplicaye code
     */
    public function checkProcedureCodeAction() {
        $request = $this->getRequest();
        $data = array();
        if ($request->isPost()) {
            $data = array(
                'code' => $request->getPost('code'),
                'lab' => $request->getPost('lab')
            );
            $result = $this->getConfigurationTable()->checkProcedureCodeExist($data);
            $data = new JsonModel($result);
            return $data;
        }
    }

    /**
     * SstandardCode Auto suggest
     * ICD9, CPT, HCPCS, CVX and Product 
     */
    public function getStandardCodeAction() {
        $response = $this->getResponse();
        $request = $this->getRequest();
        $data = array();
        if ($request->isPost()) {
            $data = array(
                'inputString' => $request->getPost('queryString'),
                'codeType' => $request->getPost('codeType'),
            );
            $result = $this->getConfigurationTable()->listStandardCode($data);
            $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'resultArray' => $result)));
            return $response;
        }
    }

}

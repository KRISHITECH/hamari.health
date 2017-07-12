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
use Lab\Model\Provider;
use Lab\Form\ProviderForm;
use Zend\View\Model\JsonModel;
use Zend\Soap\Client;

class ProviderController extends AbstractActionController {

    protected $providerTable;

    // Table Gateway
    public function getProviderTable() {
        if (!$this->providerTable) {
            $sm = $this->getServiceLocator();
            $this->providerTable = $sm->get('Lab\Model\ProviderTable');
        }
        return $this->providerTable;
    }

    // Index page
    public function indexAction() {
        $form = new ProviderForm();
        $index = new ViewModel(array(
            'form' => $form,
        ));
        return $index;
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
            $return = $this->getProviderTable()->saveProcedureProvider($request->getPost());
            if ($return) {
                $return = array('errorMsg' => 'Error while processing .... !');
            }
            $response->setContent(\Zend\Json\Json::encode($return));
        }
        return $response;
    }

    // Delete Procedur Provider
    public function deleteProcedureProviderAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $return = $this->getProviderTable()->deleteProcedureProvider($request->getPost());
            $response->setContent(\Zend\Json\Json::encode(array('success' => 'Recored Successfully Deleteed .... !')));
        }
        return $response;
    }

    /**
     * Mirth provider id action
     * @return \Zend\View\Model\JsonModel
     */
    public function getMirthProviderIdAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $username = $request->getPost('username');
            $password = $request->getPost('password');
            $host = $request->getPost('host');
            $site_dir = $_SESSION['site_id'];
            $account_no = $request->getPost('send_fac_id');
            ini_set("soap.wsdl_cache_enabled", "0");
            ini_set('memory_limit', '-1');
            $options = array('location' => $host,
                'uri' => "urn://zhhealthcare/lab"
            );
            try {
                $client = new Client(null, $options);
                $stresult = $client->getMirthProviderId($username, $password, $site_dir, $account_no);
                $arr = new JsonModel($stresult);
                return $arr;
            } catch (\Exception $e) {
                $return[0] = array('return' => 1, 'msg' => xlt("Could not connect to the web service"));
                $arr = new JsonModel($return);
                return $arr;
            }
        }
    }

}

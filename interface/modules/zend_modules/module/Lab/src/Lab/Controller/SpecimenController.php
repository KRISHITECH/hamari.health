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
use Lab\Model\Specimen;
use Lab\Form\SpecimenForm;
use Zend\View\Model\JsonModel;
use Zend\Soap\Client;
use Zend\Config;
use Zend\Config\Reader;
use Zend\ZendPdf;

class SpecimenController extends AbstractActionController {

    protected $specimenTable;

    /**
     * getSpecimenTable()
     * @return type
     */
    public function getSpecimenTable() {
        if (!$this->specimenTable) {
            $sm = $this->getServiceLocator();
            $this->specimenTable = $sm->get('Lab\Model\SpecimenTable');
        }
        return $this->specimenTable;
    }

    /**
     * Index Action
     * @global type $pid
     * @return type
     */
    public function indexAction() {
        global $pid;
        $form = new SpecimenForm();
        $statuses = $this->CommonPlugin()->getList("proc_rep_status");
        $form->get('specimen_status[]')->setValueOptions($statuses);
        array_unshift($statuses, array('value' => 'all', 'label' => 'All'));
        $form->get('specimen_search_status')->setValueOptions($statuses);
        $this->layout()->saved = $this->params('saved');
        if ($pid) {
            $form->get('patient_id')->setValue($pid);
            $search_pid = $pid;
        }
        $form->get('search_patient')->setValue($this->getSpecimenTable()->getPatientName($pid));
        $request = $this->getRequest();
        $from_dt = null;
        $to_dt = null;
        $search_status = 'all';
        if ($request->isPost()) {
            $search_pid = $request->getPost()->patient_id;
            $form->get('search_patient')->setValue($this->getSpecimenTable()->getPatientName($search_pid));
            $from_dt = $request->getPost()->search_from_date;
            $to_dt = $request->getPost()->search_to_date;
            $form->get('patient_id')->setValue($search_pid);
            $form->get('search_from_date')->setValue($from_dt);
            $form->get('search_to_date')->setValue($to_dt);
            $search_status = $request->getPost()->specimen_search_status;
            $form->get('specimen_search_status')->setValue($search_status);
        }
        $this->layout()->res = $this->getSpecimenTable()->listOrders($search_pid, $from_dt, $to_dt, $search_status);
        return array('form' => $form);
    }

    /**
     * Save Data
     * @return type
     */
    public function saveAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->getSpecimenTable()->saveSpecimenDetails($request->getPost());
            return $this->redirect()->toRoute('specimen', array('action' => 'index', 'saved' => 'yes'));
        }
    }

    /**
     * Search patient action
     * @return type
     */
    public function searchPatientAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $inputString = $request->getPost('inputValue');

        if ($request->isPost()) {
            if ($request->getPost('type') == 'getPatient') {
                $patients = $this->getPatients($inputString);
                $response->setContent(\Zend\Json\Json::encode(array('response' => true, 'patientArray' => $patients)));
                return $response;
            }
        }
    }

    /**
     * Get patients action
     * @param type $inputString
     * @return type
     */
    public function getPatients($inputString) {
        $patients = $this->getSpecimenTable()->listPatients($inputString);
        return $patients;
    }

}

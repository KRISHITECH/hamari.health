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
 *    @author  Jacob T.Paul  <jacob@zhservices.com>
 *    @author  Vipin Kumar   <vipink@zhservices.com>
 *    @author  Remesh Babu S <remesh@zhservices.com>
 * +------------------------------------------------------------------------------+
 */

namespace Lab\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Tester\Form\ModuleconfigForm;

class ModuleconfigController extends AbstractActionController {

    /**
     * Function getHookConfig
     * 
     * @return array
     */
    public function getHookConfig() {
        //SHOULD SPECIFY THE CONTROLLER AND ITS ACTION IN THE PATH, INCLUDING INDEX ACTION
        $hooks = array(
            '0' => array(
                'name' => "procedure_order",
                'title' => "Procedure Order",
                'path' => "public/lab/index",
            ),
            '1' => array(
                'name' => "procedure_result",
                'title' => "Lab Result",
                'path' => "public/lab/result/index",
            ),
        );
        return $hooks;
    }

    /**
     * Function getAclConfig
     * 
     * @return array
     */
    public function getAclConfig() {
        $acl = array(
            array(
                'section_id' => 'procedure_order',
                'section_name' => 'Procedure Order',
                'parent_section' => 'lab',
            ),
            array(
                'section_id' => 'procedure_result',
                'section_name' => 'Lab Result',
                'parent_section' => 'lab',
            ),
        );
        return $acl;
    }

    /**
     * Function getDependedModulesConfig
     * 
     * @return type
     */
    public function getDependedModulesConfig() {
        //SPECIFY LIST OF DEPENDED MODULES OF A MODULE
        $dependedModules = array();
        return $dependedModules;
    }

}

?>

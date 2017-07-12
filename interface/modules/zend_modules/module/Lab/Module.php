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
// module/Lab/Module.php

namespace Lab;

use Lab\Model\Lab;
use Lab\Model\LabTable;
use Lab\Model\Result;
use Lab\Model\ResultTable;
use Lab\Model\ResultnewTable; // Added Basil
use Lab\Model\Pull; //ADDED VIPIN
use Lab\Model\PullTable; //ADDED VIPIN
use Lab\Model\Provider;
use Lab\Model\ProviderTable;
use Lab\Model\Configuration; //ADDED VIPIN
use Lab\Model\ConfigurationTable; //ADDED VIPIN
use Lab\Model\Specimen;
use Lab\Model\SpecimenTable;
use Lab\Model\Unassociated;
use Lab\Model\UnassociatedTable;
use Lab\Model\Diagnosis;
use Lab\Model\DiagnosisTable;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\ModuleManager\ModuleManager;
use Zend\View\Helper\Openemr\Emr;
use Zend\View\Helper\Openemr\Menu;

class Module {

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function init(ModuleManager $moduleManager) {
        $sharedEvents = $moduleManager->getEventManager()->getSharedManager();
        $sharedEvents->attach(__NAMESPACE__, 'dispatch', function($e) {
            $controller = $e->getTarget();
            $controller->layout('lab/layout/layout');
            $route = $controller->getEvent()->getRouteMatch();
            $controller->getEvent()->getViewModel()->setVariables(array(
                'current_controller' => $route->getParam('controller'),
                'current_action' => $route->getParam('action'),
            ));
        }, 100);
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                'Lab\Model\LabTable' => function($sm) {
            $tableGateway = $sm->get('LabTableGateway');
            $table = new LabTable($tableGateway);
            return $table;
        },
                'Lab\Model\PullTable' => function($sm) {
            $tableGateway = $sm->get('LabTableGateway');
            $table = new PullTable($tableGateway);
            return $table;
        },
                'Lab\Model\ConfigurationTable' => function($sm) {
            $tableGateway = $sm->get('LabTableGateway');
            $table = new ConfigurationTable($tableGateway);
            return $table;
        },
                'LabTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Lab());
            return new TableGateway('procedure_order', $dbAdapter, null, $resultSetPrototype);
        },
                'Lab\Model\ResultTable' => function($sm) {
            $tableGateway = $sm->get('ResultTableGateway');
            $table = new ResultTable($tableGateway);
            return $table;
        },
                'ResultTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Result());
            return new TableGateway('procedure_result', $dbAdapter, null, $resultSetPrototype);
        },
                'SpecimenTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Specimen());
            return new TableGateway('procedure_order', $dbAdapter, null, $resultSetPrototype);
        },
                'Lab\Model\SpecimenTable' => function($sm) {
            $tableGateway = $sm->get('SpecimenTableGateway');
            $table = new SpecimenTable($tableGateway);
            return $table;
        },
                'UnassociatedTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Unassociated());
            return new TableGateway('procedure_result_unassociated', $dbAdapter, null, $resultSetPrototype);
        },
                'Lab\Model\UnassociatedTable' => function($sm) {
            $tableGateway = $sm->get('UnassociatedTableGateway');
            $table = new UnassociatedTable($tableGateway);
            return $table;
        },
                'Lab\Model\ResultnewTable' => function($sm) {
            $tableGateway = $sm->get('ResultTableGateway');
            $table = new ResultnewTable($tableGateway);
            return $table;
        },
                'ProviderTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Provider());
            return new TableGateway('procedure_providers', $dbAdapter, null, $resultSetPrototype);
        },
                'Lab\Model\ProviderTable' => function($sm) {
            $tableGateway = $sm->get('ProviderTableGateway');
            $table = new ProviderTable($tableGateway);
            return $table;
        },
                'Lab\Model\DiagnosisTable' => function($sm) {
            $tableGateway = $sm->get('DiagnosisTableGateway');
            $table = new DiagnosisTable($tableGateway);
            return $table;
        },
                'DiagnosisTableGateway' => function ($sm) {
            $dbAdapter = $sm->get('Zend\Db\Adapter\Adapter');
            $resultSetPrototype = new ResultSet();
            $resultSetPrototype->setArrayObjectPrototype(new Diagnosis());
            return new TableGateway('billing', $dbAdapter, null, $resultSetPrototype);
        },
            ),
        );
    }

    public function getViewHelperConfig() {
        return array(
            'factories' => array(
                // the array key here is the name you will call the view helper by in your view scripts
                'emr_helper' => function($sm) {
            $locator = $sm->getServiceLocator(); // $sm is the view helper manager, so we need to fetch the main service manager
            return new Emr($locator->get('Request'));
        },
                'menu' => function($sm) {
            $locator = $sm->getServiceLocator();
            return new Menu();
        },
            ),
        );
    }

}

?>

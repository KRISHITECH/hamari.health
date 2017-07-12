<?php

/* vim: set expandtab tabstop=4 shiftwidth=4: */
//
// +----------------------------------------------------------------------+
// | PHP version 4                                                        |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2013 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at                              |
// | http://www.php.net/license/2_02.txt.                                 |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Authors: Antonio Jozzolino <info@sgd.com.br>                         |
// +----------------------------------------------------------------------+
//
// $Id: module.config.php, v 0.00 Mon Aug 19 2013 12:40:07 GMT+0530 (India Standard Time) Antonio Jozzolino $
//

/**
 * Short desc
 *
 * Long description first sentence starts here
 * and continues on this line for a while
 * finally concluding here at the end of
 * this paragraph
 *
 * @package    ABHO | SSCF | SGD
 * @subpackage
 * @author     Antonio Jozzolino <info@sgd.com.br>
 * @version    $Id: module.config.php, v 0.00 Mon Aug 19 2013 12:40:07 GMT+0530 (India Standard Time) Antonio Jozzolino $
 * @since      Mon Aug 19 2013 12:40:07 GMT+0530 (India Standard Time)
 * @access     public
 * @see        http://www.sgd.com.br
 * @uses       file.ext|elementname|class::methodname()|class::$variablename|functionname()|function functionname  description of how the element is used
 * @example    relativepath/to/example.php  description
 */
return array(
    'controllers' => array(
        'invokables' => array(
            'Lab' => 'Lab\Controller\LabController',
            'Result' => 'Lab\Controller\ResultController',
            'Pull' => 'Lab\Controller\PullController',
            'Provider' => 'Lab\Controller\ProviderController',
            'Configuration' => 'Lab\Controller\ConfigurationController',
            'Specimen' => 'Lab\Controller\SpecimenController',
            'Unassociated' => 'Lab\Controller\UnassociatedController',
            'Resultnew' => 'Lab\Controller\ResultnewController',
            'Diagnosis' => 'Lab\Controller\DiagnosisController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'lab' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/lab[/:action][/:id][/:page]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                        'page' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'lab',
                        'action' => 'index',
                    ),
                ),
            ),
            'result' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/lab/result[/:action][/:id][/:saved]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Result',
                        'action' => 'index',
                    ),
                ),
            ),
            'resultnew' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/lab/resultnew[/:action][/:id][/:saved]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Resultnew',
                        'action' => 'index',
                    ),
                ),
            ),
            'pull' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/lab/pull[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Pull',
                        'action' => 'index',
                    ),
                ),
            ),
            'provider' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/provider[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Provider',
                        'action' => 'index',
                    ),
                ),
            ),
            'configuration' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/lab/configuration[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Configuration',
                        'action' => 'index',
                    ),
                ),
            ),
            'specimen' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/specimen[/:action][/:id][/:saved]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Specimen',
                    ),
                ),
            ),
            'unassociated' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/unassociated[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Unassociated',
                        'action' => 'index',
                    ),
                ),
            ),
            'diagnosis' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/diagnosis[/:action][/:id][/:saved]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Diagnosis',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'lab' => __DIR__ . '/../view/',
            'pull' => __DIR__ . '/../view/',
            'specimen' => __DIR__ . '/../view/',
            'unassociated' => __DIR__ . '/../view/',
            'resultnew' => __DIR__ . '/../view/',
            'provider' => __DIR__ . '/../view/',
            'diagnosis' => __DIR__ . '/../view/',
        ),
        'template_map' => array(
            'lab/layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
            'ViewFeedStrategy',
        ),
    ),
);
?>


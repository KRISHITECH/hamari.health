<?php

// +-----------------------------------------------------------------------------+ 
// Copyright (C) 2013 Z&H Consultancy Services Private Limited <sam@zhservices.com>
//
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
//
// A copy of the GNU General Public License is included along with this program:
// openemr/interface/login/GnuGPL.html
// For more information write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
// 
// Author:   Remesh Babu  <remesh@zhservices.com>
//           Jacob T.Paul <jacob@zhservices.com>
//           Eldho Chacko <eldho@zhservices.com>
//
// +------------------------------------------------------------------------------+

namespace Lab\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Configuration implements InputFilterAwareInterface {

    protected $inputFilter;

    /**
     * exchangeArray()
     * @param type $data
     */
    public function exchangeArray($data) {
        
    }

    /**
     * getArrayCopy()
     * @return type
     */
    public function getArrayCopy() {
        return get_object_vars($this);
    }

    /**
     * setInputFilter()
     * @param \Zend\InputFilter\InputFilterInterface $inputFilter
     * @throws \Exception
     */
    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    /**
     * getInputFilter()
     * @return type
     */
    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();


            $this->inputFilter = $inputFilter;
        }
        return $this->inputFilter;
    }

    /**
     * getHookConfig()
     * @return array
     */
    public function getHookConfig() {
        //SHOULD SPECIFY THE CONTROLLER AND ITS ACTION IN THE PATH, INCLUDING INDEX ACTION
        /*
          //SAMPLE CONFIGURATION
          $hooks	=  array(
          '0' => array(
          'name' 	=> "HookName1",
          'title' 	=> "HookTitle1",
          'path' 	=> "path/to/Hook1",
          ),
          '1' => array(
          'name' 	=> "HookName2",
          'title' 	=> "HookTitle2",
          'path' 	=> "path/to/Hook2",
          ),
          ); */
        $hooks = array();
        return $hooks;
    }

    /**
     * getAclConfig()
     * @return array
     */
    public function getAclConfig() {
        /*
          //SAMPLE CONFIGURATION
          $acl = array(
          array(
          'section_id' 				=> 'SectionID1',
          'section_name' 			=> 'SectionDisplayName1',
          'parent_section' 		=> 'ParentSectionID1',
          ),
          array(
          'section_id' 				=> 'SectionID2',
          'section_name' 			=> 'SectionDisplayName2',
          'parent_section' 		=> 'ParentSectionID2',
          ),
          );
         */
        $acl = array();
        return $acl;
    }

    /**
     * configSettings()
     * @return array
     */
    public function configSettings() {
        /*
          //SAMPLE CONFIGURATION
          $settings = array(
          array(
          'display'   => 'Display1',
          'field'     => 'Filed1',
          'type'      => 'FieldType1',
          ),
          array(
          'display'   => 'Display2',
          'field'     => 'Filed2',
          'type'      => 'FieldType2',
          ),
          ); */
        $settings = array();
        return $settings;
    }

    /**
     * getDependedModulesConfig()
     * @return array
     */
    public function getDependedModulesConfig() {
        //SPECIFY LIST OF MODULES NEEDED FOR THE WORKING OF THE CURRENT MODULE
        $dependedModules = array();
        return $dependedModules;
    }

}

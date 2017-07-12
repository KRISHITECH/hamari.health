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
// Author:   Remesh Babu S <remesh@zhservices.com>
//           Jacob T.Paul <jacob@zhservices.com>
//           Eldho Chacko <eldho@zhservices.com>
//
// +------------------------------------------------------------------------------+

namespace Lab\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Pull implements InputFilterAwareInterface {

    protected $inputFilter;

    /**
     * exchangeArray()
     * @param type $data
     */
    public function exchangeArray($data) {
        $fh = fopen("d:/POST.txt", "w");
        fwrite($fh, print_r($data, 1));
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->pid = (isset($data['patient_id'])) ? $data['patient_id'] : null;
        $this->encounter = (isset($data['encounter_id'])) ? $data['encounter_id'] : null;
        $this->lab_id = (isset($data['lab_id'])) ? $data['lab_id'] : null;
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

            $inputFilter->add($factory->createInput(array(
                        'name' => 'lab_id',
                        'required' => false,
                        'filters' => array(
                            array('name' => 'Int'),
                        ),
            )));


            $this->inputFilter = $inputFilter;
        }
        return $this->inputFilter;
    }

}

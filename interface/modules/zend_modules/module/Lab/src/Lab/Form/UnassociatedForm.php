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

namespace Lab\Form;

use Zend\Form\Form;

class UnassociatedForm extends Form {

    public function __construct($name = null) {
        parent::__construct('unassociated');
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id[]',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));

        $this->add(array(
            'name' => 'comments[]',
            'attributes' => array(
                'type' => 'textarea',
                'cols' => '42',
                'rows' => '1',
                'id' => 'comments',
                'class' => 'easyui-validatebox',
            ),
        ));

        $this->add(array(
            'name' => 'check',
            'type' => 'Zend\Form\Element\MultiCheckbox',
            'options' => array(
                'value_options' => array(
                    '1' => '',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'comments_readonly',
            'attributes' => array(
                'type' => 'textarea',
                'cols' => '52',
                'rows' => '1',
                'id' => 'comments_readonly',
                'class' => 'easyui-validatebox',
                'readonly' => true
            ),
        ));
        $this->add(array(
            'name' => 'perPage',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'tftextinput2',
                'data-options' => 'required:true',
                'editable' => 'false',
                'required' => 'required',
                'id' => 'perPage',
                'onChange' => 'javascript:submitPage(this)'
            ),
            'options' => array(
                'value_options' => array(
                    '25' => xlt('25'),
                    '10' => xlt('10'),
                    '50' => xlt('50'),
                    '100' => xlt('100'),
                    '250' => xlt('250'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'selectProvider',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'tftextinput2',
                'data-options' => 'required:true',
                'editable' => 'false',
                'id' => 'selectProvider',
                'onChange' => 'javascript:submitPage(this)'
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('-- Select --'),
                    'Bioreference' => xlt('Bioreference'),
                    'Singulex' => xlt('Singulex'),
                    'Labcorp' => xlt('Labcorp'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'txtSearch',
            'attributes' => array(
                'type' => 'text',
                'class' => 'tftextinput2',
                'size' => '21',
                'maxlength' => '120',
                'placeholder' => 'Enter Name to search',
                'id' => 'txtSearch'
            ),
            'options' => array(
                'label' => '',
            ),
        ));
        $this->add(array(
            'name' => 'txtDob',
            'attributes' => array(
                'type' => 'text',
                'class' => 'tftextinput2',
                'size' => '21',
                'maxlength' => '120',
                'id' => 'txtDob',
                'placeholder' => 'Enter DOB to search',
                'title' => 'Use yyyy-mm-dd OR mm-dd-yyyy Format to search date of birth'
            ),
            'options' => array(
                'label' => '',
            ),
        ));
        $this->add(array(
            'name' => 'btnSearch',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Submit',
                'id' => 'btnSearch',
                'class' => 'tfbutton2',
                'onclick' => 'javascript:submitPage(this)'
            ),
        ));
    }

}

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

class ResultnewForm extends Form {

    public function __construct($name = null) {
        parent::__construct('resultentry');
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'patient_id',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'patient_id',
            ),
        ));
        $this->add(array(
            'name' => 'procedure_report_id[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'procedure_report_id',
            ),
        ));
        $this->add(array(
            'name' => 'abnormal[]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'combo smalltb',
                'editable' => 'true',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'result[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'result',
                'class' => 'combo smalltb',
            ),
        ));
        $this->add(array(
            'name' => 'units[]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'combo smalltb',
                'editable' => 'true',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'result_status[]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'combo mediumtb',
                'editable' => 'true',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'facility[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'facility',
                'class' => 'combo mediumtb',
            ),
        ));
        $this->add(array(
            'name' => 'comments[]',
            'attributes' => array(
                'type' => 'textarea',
                'id' => 'comments',
                'class' => 'combo mediumtb',
                'style' => 'height:100px;width:260px !important;',
            ),
        ));
        $this->add(array(
            'name' => 'notes[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'notes',
                'class' => 'combo mediumtb',
            ),
        ));
        $this->add(array(
            'name' => 'range[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'range',
                'class' => 'combo smalltb',
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Save',
                'id' => 'submitbutton',
            ),
        ));
        $this->add(array(
            'name' => 'search_from_date',
            'attributes' => array(
                'type' => 'text',
                'id' => 'search_from_date',
                'class' => 'dateclass',
            ),
        ));
        $this->add(array(
            'name' => 'search_to_date',
            'attributes' => array(
                'type' => 'text',
                'id' => 'search_to_date',
                'class' => 'dateclass',
            ),
        ));
        $this->add(array(
            'name' => 'search_patient',
            'attributes' => array(
                'type' => 'text',
                'id' => 'search_patient',
                'class' => 'combo',
                'onKeyup' => 'getPatient(this.value, this.id,"../../specimen/searchPatient")',
                'autocomplete' => 'off'
            ),
        ));
        $this->add(array(
            'name' => 'lab_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ easyui-combobox ',
                'data-options' => 'required:true',
                'id' => 'lab_id',
                'required' => 'required',
            ),
            'options' => array(
                'value_options' => array(
                    'all' => xlt('All'),
                ),
            ),
        ));
    }

}

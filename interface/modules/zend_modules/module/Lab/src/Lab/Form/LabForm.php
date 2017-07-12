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

class LabForm extends Form {

    public function __construct($name = null) {
        global $pid, $encounter;
        parent::__construct('lab');
        $this->setAttribute('method', 'post');
        $this->add(array(
            'name' => 'id[0][]',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));
        $this->add(array(
            'name' => 'hiddensubmit',
            'attributes' => array(
                'type' => 'submit',
                'id' => 'hiddensubmit',
                'style' => 'display:none'
            ),
        ));
        $this->add(array(
            'name' => 'patient_id',
            'attributes' => array(
                'type' => 'hidden',
                'value' => $pid,
            ),
        ));
        $this->add(array(
            'name' => 'encounter_id',
            'attributes' => array(
                'type' => 'hidden',
                'value' => $encounter,
            ),
        ));
        $this->add(array(
            'name' => 'procedurecount[0][]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'procedurecount_1_1',
                'value' => 2,
            ),
        ));
        $this->add(array(
            'name' => 'provider[0][]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'editable' => 'false',
                'style' => 'width:116px',
                'required' => 'required',
                'id' => 'provider_1_1'
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'lab_id[0][]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'lab_id_1_1',
                'style' => 'width:116px',
                'required' => 'required',
                'onchange' => 'checkLab(this.value, this.id)',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'orderdate[0][]',
            'type' => 'Zend\Form\Element\Date',
            'attributes' => array(
                'type' => 'text',
                'class' => 'datetimeclass',
                'style' => 'width:116px',
                'data-options' => 'required:true',
                'value' => date("Y-m-d H:i:s"),
                'required' => 'required',
                'id' => 'orderdate_1_1'
            ),
        ));
        $this->add(array(
            'name' => 'timecollected[0][]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'timecollected_1_1',
                'class' => 'datetimeclass',
                'data-options' => 'required:true',
                'value' => date("Y-m-d H:i:s"),
                'required' => 'required'
            ),
        ));
        $this->add(array(
            'name' => 'priority[0][]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'style' => 'width:90px',
                'data-options' => 'required:true',
                'required' => 'required',
                'id' => 'priority_1_1'
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'status[0][]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'style' => 'width:90px',
                'data-options' => 'required:true',
                'required' => 'required',
                'id' => 'status_1_1'
            ),
        ));
        $this->add(array(
            'name' => 'billto[0][]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'style' => 'width:90px',
                'data-options' => 'required:true',
                'required' => 'required',
                'id' => 'billto_1_1'
            ),
            'options' => array(
                'value_options' => array(
                    array(
                        'value' => "P",
                        'label' => xlt('Patient'),
                        'selected' => TRUE
                    ),
                    array(
                        'value' => "T",
                        'label' => xlt('Third Party')
                    ),
                    array(
                        'value' => "C",
                        'label' => xlt('Facility')
                    ),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'diagnoses[0][]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'diagnoses_1_1',
                'autocomplete' => 'off',
                'onKeyup' => 'setDiagnoses(this.value,this.id)',
                'onfocus' => 'readDiagnoses(this.value, this.id)',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:100%',
                //'required' => 'required',
                'placeholder' => 'Seperated by semicolon(;)'
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Textarea',
            'name' => 'patient_instructions[0][]',
            'attributes' => array(
                'class' => 'easyui-validatebox combo',
                'style' => 'height:20px; width:100%',
                'id' => 'patient_instructions_1_1',
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Textarea',
            'name' => 'internal_comments[0][]',
            'attributes' => array(
                'class' => 'easyui-validatebox combo',
                'style' => 'height:20px;width:110px',
                'id' => 'internal_comments_1_1',
            ),
        ));
        $this->add(array(
            'name' => 'specimencollected[0][]',
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => array(
                'required' => 'required',
                'id' => 'specimencollected_1_1',
            ),
            'options' => array(
                'value_options' => array(
                    'onsite' => xlt('On Site'),
                    'labsite' => xlt('Lab Site'),
                ),
            ),
        ));
        $this->add(array(
            'name' => 'procedures[0][]',
            'attributes' => array(
                'id' => 'procedures_1_1',
                'autocomplete' => 'off',
                'type' => 'text',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:100%',
                'onKeyup' => 'getProcedures(this.value, this.id, event)',
                'required' => 'required',
                'placeholder' => xlt('Enter Valid Procedure')
            ),
        ));
        $this->add(array(
            'name' => 'procedure_code[0][]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'procedure_code_1_1'
            ),
        ));
        $this->add(array(
            'name' => 'procedure_suffix[0][]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'procedure_suffix_1_1'
            ),
        ));
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Go',
                'id' => 'submitbutton',
            ),
        ));
        $this->add(array(
            'name' => 'addprocedure',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Add Procedure',
                'id' => 'addprocedure',
                'onclick' => 'cloneRow()',
            ),
        ));
    }

}

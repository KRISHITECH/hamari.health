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

class ConfigurationForm extends Form {

    public function __construct($name = null) {
        global $pid, $encounter;
        parent::__construct('configuration');
        $this->setAttribute('method', 'post');


        $this->add(array(
            'name' => 'group_type_id[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'group_type_id'
            ),
        ));

        $this->add(array(
            'name' => 'group_indicator_value[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'group_indicator_value',
                'value' => '+'
            ),
        ));

        $this->add(array(
            'name' => 'group_name[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'group_name',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'group_description[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'group_description',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));


        $this->add(array(
            'name' => 'order_type_id[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'order_type_id'
            ),
        ));

        $this->add(array(
            'name' => 'order_indicator_value[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'order_indicator_value',
                'value' => '+'
            ),
        ));

        $this->add(array(
            'name' => 'order_name[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'order_name',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'order_description[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'order_description',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'order_sequence[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'order_sequence',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'order_from',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'order_from',
                'required' => 'required',
                'onchange' => '',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'order_bodysite',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'order_bodysite',
                'required' => 'required',
                'onchange' => '',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'order_specimentype',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'order_specimentype',
                'required' => 'required',
                'onchange' => '',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'order_administervia',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'order_administervia',
                'required' => 'required',
                'onchange' => '',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'order_laterality',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'order_laterality',
                'required' => 'required',
                'onchange' => '',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'order_procedurecode[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'order_procedurecode',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => '',
                'onblur' => 'checkCode(this.value,$("#order_from").val())',
            ),
        ));

        $this->add(array(
            'name' => 'order_standardcode[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'order_standardcode',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => '',
                'onkeyup' => 'lookup(this.value, this.id)',
            ),
        ));

        $this->add(array(
            'name' => 'result_type_id[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'result_type_id'
            ),
        ));

        $this->add(array(
            'name' => 'result_indicator_value[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'result_indicator_value',
                'value' => '+'
            ),
        ));

        $this->add(array(
            'name' => 'result_name[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'result_name',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'result_description[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'result_description',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'result_sequence[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'result_sequence',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'result_defaultunits',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'result_defaultunits',
                'required' => 'required',
                'onchange' => '',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'result_defaultrange[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'result_defaultrange',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'result_followupservices[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'result_followupservices',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));


        $this->add(array(
            'name' => 'reccomendation_type_id[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'reccomendation_type_id'
            ),
        ));

        $this->add(array(
            'name' => 'reccomendation_indicator_value[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'reccomendation_indicator_value',
                'value' => '+'
            ),
        ));

        $this->add(array(
            'name' => 'reccomendation_name[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'reccomendation_name',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'reccomendation_description[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'reccomendation_description',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'reccomendation_sequence[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'reccomendation_sequence',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'reccomendation_defaultunits',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'reccomendation_defaultunits',
                'required' => 'required',
                'onchange' => '',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));


        $this->add(array(
            'name' => 'reccomendation_defaultrange[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'reccomendation_defaultrange',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'reccomendation_followupservices[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'reccomendation_followupservices',
                'class' => 'easyui-validatebox combo',
                'style' => 'width:300px',
                'required' => 'required',
                'placeholder' => ''
            ),
        ));

        $this->add(array(
            'name' => 'standard_code',
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => array(
                //'required' => 'required',
                'id' => 'standard_code',
            ),
            'options' => array(
                'value_options' => array(
                    'ICD9' => xlt(' ICD9 '),
                    'CPT4' => xlt(' CPT4 '),
                    'HCPCS' => xlt(' HCPCS '),
                    'CVX' => xlt(' CVX '),
                    'PROD' => xlt(' Product '),
                ),
            ),
        ));
    }

}

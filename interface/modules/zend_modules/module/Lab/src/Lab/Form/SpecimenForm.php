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

class SpecimenForm extends Form {

    public function __construct($name = null) {
        parent::__construct('specimen');
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
            'name' => 'procedure_order_id[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'procedure_order_id',
            ),
        ));
        $this->add(array(
            'name' => 'procedure_order_seq[]',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'procedure_order_seq',
            ),
        ));
        $this->add(array(
            'name' => 'specimen[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'specimen',
                'class' => 'easyui-validatebox combo',
            ),
        ));
        $this->add(array(
            'name' => 'specimen_collected_time[]',
            'attributes' => array(
                'type' => 'text',
                'id' => 'specimen_collected_time',
                'class' => 'datetimeclass',
            ),
        ));
        $this->add(array(
            'name' => 'specimen_search_status',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'combo',
                'editable' => 'true',
            ),
        ));
        $this->add(array(
            'name' => 'specimen_status[]',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => 'combo',
                'editable' => 'true',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
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
                'onKeyup' => 'getPatient(this.value, this.id,"./searchPatient")',
                'autocomplete' => 'off'
            ),
        ));
    }

}

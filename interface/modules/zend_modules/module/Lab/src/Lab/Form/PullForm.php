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

class PullForm extends Form {

    public function __construct($name = null) {
        global $pid, $encounter;
        parent::__construct('pull');
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
            'name' => 'procedurecount',
            'attributes' => array(
                'type' => 'hidden',
                'id' => 'procedurecount',
                'value' => 2,
            ),
        ));

        $this->add(array(
            'name' => 'lab_id',
            'type' => 'Zend\Form\Element\Select',
            'attributes' => array(
                'class' => '/*easyui-combobox*/ combo',
                'data-options' => 'required:true',
                'id' => 'lab_id',
                'required' => 'required',
                'onchange' => '/*checkLab(this.value)*/',
            ),
            'options' => array(
                'value_options' => array(
                    '' => xlt('Unassigned'),
                ),
            ),
        ));

        $this->add(array(
            'name' => 'pulltest',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Pull Test',
                'id' => 'pulltest',
                'onclick' => 'pulldata("lab_id",1)',
            ),
        ));

        $this->add(array(
            'name' => 'pullaoe',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Pull AOE',
                'id' => 'pullAOE',
                'onclick' => 'pulldata(lab_id,2)',
            ),
        ));

        $this->add(array(
            'name' => 'pulltestaoe',
            'attributes' => array(
                'type' => 'button',
                'value' => 'Pull Test and AOE',
                'id' => 'pulltestaoe',
                'onclick' => 'pulldata(lab_id,3)',
            ),
        ));
    }

}

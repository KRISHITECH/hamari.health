<?php

namespace Lab\Model;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class Specimen implements InputFilterAwareInterface {

    protected $inputFilter;

    /**
     * exchangeArray
     * @param type $data
     */
    public function exchangeArray($data) {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->pid = (isset($data['patient_id'])) ? $data['patient_id'] : null;
        $this->encounter = (isset($data['encounter_id'])) ? $data['encounter_id'] : null;
        $this->provider = (isset($data['provider'])) ? $data['provider'] : null;
        $this->lab_id = (isset($data['lab_id'])) ? $data['lab_id'] : null;
        $this->location = (isset($data['location'])) ? $data['locationName'] : null;
        $this->orderdate = (isset($data['orderdate'])) ? $data['orderdate'] : null;
        $this->timecollected = (isset($data['timecollected'])) ? $data['timecollected'] : null;
        $this->diagnoses = (isset($data['diagnoses'])) ? $data['diagnoses'] : null;
        $this->priority = (isset($data['priority'])) ? $data['priority'] : null;
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->patient_instructions = (isset($data['patient_instructions'])) ? $data['patient_instructions'] : null;
        $this->procedures = (isset($data['procedures'])) ? $data['procedures'] : null;
        $this->procedurecode = (isset($data['procedure_code'])) ? $data['procedure_code'] : null;
        $this->proceduresuffix = (isset($data['procedure_suffix'])) ? $data['procedure_suffix'] : null;
        $lab->specimencollected = (isset($data['specimencollected'])) ? $data['specimencollected'] : null;
        $lab->billto = (isset($data['billto'])) ? $data['billto'] : null;
        $lab->internal_comments = (isset($data['internal_comments'])) ? $data['internal_comments'] : null;
    }

    /**
     * getArrayCopy
     * @return type
     */
    public function getArrayCopy() {
        return get_object_vars($this);
    }

    /**
     * setInputFilter
     * @param \Zend\InputFilter\InputFilterInterface $inputFilter
     * @throws \Exception
     */
    public function setInputFilter(InputFilterInterface $inputFilter) {
        throw new \Exception("Not used");
    }

    /**
     * getInputFilter
     * @return type
     */
    public function getInputFilter() {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();
            $factory = new InputFactory();

//            $inputFilter->add($factory->createInput(array(
//                'name'     => 'id',
//                'required' => false,
//                'filters'  => array(
//                    array('name' => 'Int'),
//                ),
//            )));
//	    $inputFilter->add($factory->createInput(array(
//                'name'     => 'patient_id',
//                'required' => false,
//                'filters'  => array(
//                    array('name' => 'Int'),
//                ),
//            )));
//	    $inputFilter->add($factory->createInput(array(
//                'name'     => 'encounter_id',
//                'required' => false,
//                'filters'  => array(
//                    array('name' => 'Int'),
//                ),
//            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'provider',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'Int'),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'lab_id',
                        'required' => false,
                        'filters' => array(
                            array('name' => 'Int'),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'patient_instructios',
                        'required' => false,
                        'allow_empty' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 0,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'procedures',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'procedure_code',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'procedure_suffix',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'diagnoses',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'status',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'priority',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));
            $inputFilter->add($factory->createInput(array(
                        'name' => 'orderdate',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));

            $inputFilter->add($factory->createInput(array(
                        'name' => 'timecollected',
                        'required' => true,
                        'filters' => array(
                            array('name' => 'StripTags'),
                            array('name' => 'StringTrim'),
                        ),
                        'validators' => array(
                            array(
                                'name' => 'StringLength',
                                'options' => array(
                                    'encoding' => 'UTF-8',
                                    'min' => 1,
                                    'max' => 100,
                                ),
                            ),
                        ),
            )));

            $this->inputFilter = $inputFilter;
        }
        return $this->inputFilter;
    }

}

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

namespace Lab\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Config;
use Zend\Config\Writer;
use Zend\Soap\Client;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;
use Application\Listener\Listener;
use Zend\View\Model\JsonModel;
use \Application\Model\ApplicationTable;

class ConfigurationTable extends AbstractTableGateway {

    public $tableGateway;
    public $parent_result;
    public $child_result;
    public $initiating_typeid;
    public $result;
    public $child_ids;
    protected $applicationTable;

    public function __construct(TableGateway $tableGateway) {
        $this->tableGateway = $tableGateway;
        $this->parent_result = array();
        $this->child_result = array();
        $this->initiating_typeid = 0;
        $this->result = array();
        $this->child_ids = array();
        $adapter = \Zend\Db\TableGateway\Feature\GlobalAdapterFeature::getStaticAdapter();
        $this->adapter = $adapter;
        $this->applicationTable = new ApplicationTable();
    }

    /**
     * getConfigDetails()
     * @param type $type_id
     * @return \Zend\View\Model\JsonModel
     */
    public function getConfigDetails($type_id) {

        $comb_arr = array();
        $resparent_arr[] = $this->getConfigParentDetails($type_id);

        $is_group = 0;

        foreach ($resparent_arr as $resparent) {
            foreach ($resparent as $type_arr) {
                foreach ($type_arr as $type) {
                    $comb_arr[] = $type;
                    if ($type['group'] == 'grp') {
                        $is_group = 1;
                    }
                }
            }
        }

        if ($is_group == 0) {
            $reschild_arr[] = $this->getConfigChildDetails($type_id);
        }

        foreach ($reschild_arr as $reschild) {
            foreach ($reschild as $type_arr) {
                foreach ($type_arr as $type) {
                    $comb_arr[] = $type;
                }
            }
        }

        $ret_arr = new JsonModel($comb_arr);

        return $ret_arr;
    }

    //NEW FUNCTION TO SHOW TEST DETAILS IN A TREE VIEW
    public function getConfigParentDetails($type_id) {
        $init_tag = 1;
        $json_arr = array();
        $res_arr = array();

        $res = $this->getTypeDetails($type_id);

        foreach ($res as $row)
            if ($row['procedure_type_id'] <> "") {
                $res_arr = $this->saveDetailsArray($row, $init_tag);
                array_push($this->parent_result, $res_arr);
            }
        return $this->parent_result;
    }

    /**
     * getConfigChildDetails()
     * @param type $type_id
     * @return type
     */
    public function getConfigChildDetails($type_id) {

        $child_res = $this->getChildDetails($type_id);

        foreach ($child_res as $child) {
            if (($child['procedure_type_id'] <> "") && ($child['procedure_type_id'] <> $type_id)) {
                $res_arr = $this->saveDetailsArray($child, $init_tag);
                array_push($this->child_result, $res_arr);

                $this->getConfigChildDetails($child['procedure_type_id']);
            }
        }
        return $this->child_result;
    }

    /**
     * getConfigChildIds()
     * @param type $type_id
     * @return type
     */
    public function getConfigChildIds($type_id) {

        $child_res = $this->getChildDetails($type_id);

        foreach ($child_res as $child) {
            if (($child['procedure_type_id'] <> "") && ($child['procedure_type_id'] <> $type_id)) {
                array_push($this->child_ids, $child['procedure_type_id']);

                $this->getConfigChildIds($child['procedure_type_id']);
            }
        }
        return $this->child_ids;
    }

    /**
     * getTypeDetails()
     * @param type $type_id
     * @return type
     */
    public function getTypeDetails($type_id) {
        $sel_col = "";
        foreach ($upcols_array as $col) {
            if ($data[$col] <> "") {
                $sel_col .= $col . " = ? ,";
                $input_arr[] = $data[$col];
            }
        }

        $sql = "SELECT procedure_type_id, parent, name, lab_id, procedure_code, procedure_type, description, seq, units,`range`, related_code,
				route_admin, laterality, standard_code, body_site, specimen
			    FROM procedure_type 
				WHERE procedure_type_id = ? ";

        $value_arr = array($type_id);

        $res = $this->applicationTable->zQuery($sql, $value_arr);

        return $res;
    }

    /**
     * getChildDetails()
     * @param type $type_id
     * @return type
     */
    public function getChildDetails($type_id) {
        $sql = "SELECT procedure_type_id, parent, name, lab_id, procedure_code, procedure_type, description, seq, units,`range`, related_code
				FROM procedure_type 
				    WHERE parent = ? ";
        $value_arr = array($type_id);

        $res = $this->applicationTable->zQuery($sql, $value_arr);
        return $res;
    }

    /**
     * saveDetailsArray()
     * @param type $row
     * @param type $open_flag
     * @return array
     */
    public function saveDetailsArray($row, $open_flag = 0) {
        $arr = array();
        $ret_arr = array();
        $result_array = array();
        $orderfrom_arr = array();

        $grp_array = array('name' => 'group_name',
            'description' => 'group_description');

        $ord_array = array('name' => 'order_name',
            'description' => 'order_description',
            'seq' => 'order_sequence',
            'lab_id' => 'order_from',
            'procedure_code' => 'order_procedurecode',
            'standard_code' => 'order_standardcode',
            'body_site' => 'order_bodysite',
            'specimen' => 'order_specimentype',
            'route_admin' => 'order_administervia',
            'laterality' => 'order_laterality');

        $res_array = array('name' => 'result_name',
            'description' => 'result_description',
            'seq' => 'result_sequence',
            'units' => 'result_defaultunits',
            'range' => 'result_defaultrange',
            'related_code' => 'result_followupservices');

        $rec_array = array('name' => 'reccomendation_name',
            'description' => 'reccomendation_description',
            'seq' => 'reccomendation_sequence',
            'units' => 'reccomendation_defaultunits',
            'range' => 'reccomendation_defaultrange',
            'related_code' => 'reccomendation_followupservices');

        if ($row['procedure_type'] == 'grp') {
            $arr['group_type_id'] = $row['procedure_type_id'];
            $arr['group'] = $row['procedure_type'];
            foreach ($grp_array as $column => $grp) {
                $arr[$grp] = $row[$column];
            }
            array_push($result_array, $arr);
        } else if ($row['procedure_type'] == 'ord') {
            $arr['order_type_id'] = $row['procedure_type_id'];
            $arr['group'] = $row['procedure_type'];
            foreach ($ord_array as $column => $ord) {
                $arr[$ord] = $row[$column];
            }
            array_push($result_array, $arr);
        } else if ($row['procedure_type'] == 'res') {
            $arr['result_type_id'] = $row['procedure_type_id'];
            $arr['group'] = $row['procedure_type'];
            foreach ($res_array as $column => $res) {
                $arr[$res] = $row[$column];
            }
            array_push($result_array, $arr);
        } else if ($row['procedure_type'] == 'rec') {
            $arr['reccomendation_type_id'] = $row['procedure_type_id'];
            $arr['group'] = $row['procedure_type'];
            foreach ($rec_array as $column => $rec) {
                $arr[$rec] = $row[$column];
            }
            array_push($result_array, $arr);
        }

        return $result_array;
    }

    /**
     * getAllConfigDetails()
     * @return \Zend\View\Model\JsonModel
     */
    public function getAllConfigDetails() {
        $reult_arr = array();
        $json_arr = array();

        $start = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $rows = isset($_POST['rows']) ? intval($_POST['rows']) : 10;

        if ($_POST['page'] == 1) {
            $start = $_POST['page'] - 1;
        } elseif ($_POST['page'] > 1) {
            $start = (($_POST['page'] - 1) * $rows);
        }

        $sql = "SELECT procedure_type_id, parent, p.name, procedure_code, procedure_type, `range`, description,
	    remote_host, login, password FROM procedure_type p LEFT JOIN procedure_providers pp ON pp.ppid = p.lab_id LIMIT $start, $rows ";
        $res = $this->applicationTable->zQuery($sql);

        $sql_count = "SELECT procedure_type_id FROM procedure_type";

        $res_count = $this->applicationTable->zQuery($sql_count);

        $numrows = $res_count->count();

        $json_arr['total'] = $numrows;
        $no_rows = 0;

        foreach ($res as $row) {
            $exists = 0;
            foreach ($this->result as $result) {

                if ($result['id'] == $row['procedure_type_id']) {
                    $exists = 1;
                    break;
                }
            }

            if ($exists == 1) {
                continue;
            }

            $no_rows++;

            $res_arr = array();

            $res_arr['id'] = $row['procedure_type_id'];
            $res_arr['pk'] = $row['procedure_type_id'];
            $res_arr['name'] = $row['name'];

            $res_arr['procedure_code'] = $row['procedure_code'];
            $res_arr['procedure_type'] = $row['procedure_type'];
            $res_arr['range'] = $row['range'];
            $res_arr['discription'] = $row['description'];
            $cnt = '';
            if ($row['remote_host'] || $row['login'] || $row['password']) {
                $res_arr['action'] = "<div class=\"icon_disadd\" \">&nbsp;</div>";
                $res_arr['action'] .= "<div class=\"icon_disedit\">&nbsp;</div>";
                $res_arr['action'] .= "<div class=\"icon_disdelete\">&nbsp;</div>";
            } else {
                if ($row['procedure_code']) {
                    $res = $this->applicationTable->zQuery("SELECT count(*) AS cnt FROM procedure_order_code WHERE procedure_code=?", array($row['procedure_code']));
                    $ro = $res->current();
                    $cnt = $ro['cnt'];
                }
                $res_arr['action'] = "<div class=\"icon_add\" onclick=\"addExist(" . $row['procedure_type_id'] . ")\">&nbsp;</div>";
                if (!$cnt) {
                    $res_arr['action'] .= "<div class=\"icon_edit\" onclick=\"editItem(" . $row['procedure_type_id'] . ")\">&nbsp;</div>";
                    $res_arr['action'] .= "<div class=\"icon_delete\" onclick=\"deleteItem(" . $row['procedure_type_id'] . ")\">&nbsp;</div>";
                } else {
                    $res_arr['action'] .= "<div class=\"icon_disedit\">&nbsp;</div>";
                    $res_arr['action'] .= "<div class=\"icon_disdelete\">&nbsp;</div>";
                }
            }
            if ($res_arr['procedure_type'] == "grp") {
                $res_arr['iconCls'] = "icon-lab-group";
                $res_arr['order'] = "Group";
            } else if ($res_arr['procedure_type'] == "ord") {
                $res_arr['iconCls'] = "icon-lab-order";
                $res_arr['order'] = "Order";
            } else if ($res_arr['procedure_type'] == "res") {
                $res_arr['iconCls'] = "icon-lab-result";
                $res_arr['order'] = "Result";
            } else if ($res_arr['procedure_type'] == "rec") {
                $res_arr['iconCls'] = "icon-lab-reccomendation";
                $res_arr['order'] = "Recommendation";
            }

            $sql_child = "SELECT procedure_type_id FROM procedure_type WHERE parent = '" . $row['procedure_type_id'] . "' AND
				procedure_type_id <> '" . $row['procedure_type_id'] . "' ";
            $res_child = $this->applicationTable->zQuery($sql_child);

            $numchilds = $res_child->count();

            if ($row['procedure_type_id'] <> $row['parent']) {

                $row['parent'] = ($row['parent'] == 0) ? "" : $row['parent'];
                $res_arr['_parentId'] = $row['parent'];
            }
            if ($numchilds > 0) {
                $res_arr['state'] = "closed";
            }

            $this->result[] = $res_arr;

            if ($numchilds > 0) {
                //$this->saveAllChildConfigArray($row['procedure_type_id']);
            }
        }

        $json_arr['rows'] = $this->result;
        $result_arr = new JsonModel($json_arr);

        return $result_arr;
    }

    /**
     * saveAllChildConfigArray()
     * @param type $type_id
     */
    public function saveAllChildConfigArray($type_id) {
        $sql = "SELECT `procedure_type_id`, parent, name, procedure_code, procedure_type, `range`, description
			FROM procedure_type WHERE parent = '" . $type_id . "' AND
				    procedure_type_id <> '" . $type_id . "' ";

        $res = $this->applicationTable->zQuery($sql);

        foreach ($res as $row) {
            $res_arr = array();

            $res_arr['id'] = $row['procedure_type_id'];
            $res_arr['pk'] = $row['procedure_type_id'];
            $res_arr['name'] = $row['name'];

            $res_arr['procedure_code'] = $row['procedure_code'];
            $res_arr['procedure_type'] = $row['procedure_type'];
            $res_arr['range'] = $row['range'];
            $res_arr['discription'] = $row['description'];

            $cnt = '';
            if ($row['procedure_code']) {
                $res = $this->applicationTable->zQuery("SELECT count(*) AS cnt FROM procedure_order_code WHERE procedure_code=?", array($row['procedure_code']));
                $ro = $res->current();
                $cnt = $ro['cnt'];
            }
            $res_arr['action'] = "<div class=\"icon_add\" onclick=\"addExist(" . $row['procedure_type_id'] . ")\">&nbsp;</div>";
            if (!$cnt) {
                $res_arr['action'] .= "<div class=\"icon_edit\" onclick=\"editItem(" . $row['procedure_type_id'] . ")\">&nbsp;</div>";
                $res_arr['action'] .= "<div class=\"icon_delete\" onclick=\"deleteItem(" . $row['procedure_type_id'] . ")\">&nbsp;</div>";
            } else {
                $res_arr['action'] .= "<div class=\"icon_disedit\">&nbsp;</div>";
                $res_arr['action'] .= "<div class=\"icon_disdelete\">&nbsp;</div>";
            }

            if ($res_arr['procedure_type'] == "grp") {
                $res_arr['iconCls'] = "icon-lab-group";
                $res_arr['order'] = "Group";
            } else if ($res_arr['procedure_type'] == "ord") {
                $res_arr['iconCls'] = "icon-lab-order";
                $res_arr['order'] = "Order";
            } else if ($res_arr['procedure_type'] == "res") {
                $res_arr['iconCls'] = "icon-lab-result";
                $res_arr['order'] = "Result";
            } else if ($res_arr['procedure_type'] == "rec") {
                $res_arr['iconCls'] = "icon-lab-reccomendation";
                $res_arr['order'] = "Recommendation";
            }

            $sql_child = "SELECT procedure_type_id FROM procedure_type WHERE parent = '" . $row['procedure_type_id'] . "' AND
				    procedure_type_id <> '" . $row['procedure_type_id'] . "' ";
            $res_child = $this->applicationTable->zQuery($sql_child);

            $numchilds = $res_child->count();

            if ($row['procedure_type_id'] <> $row['parent']) {
                if ($numchilds <> 0) {
                    $res_arr['state'] = "closed";
                }
                $row['parent'] = ($row['parent'] == 0) ? "" : $row['parent'];
                $res_arr['_parentId'] = $row['parent'];
            }

            $this->result[] = $res_arr;
            if ($numchilds > 0) {
                $this->saveAllChildConfigArray($row['procedure_type_id']);
            }
        }
    }

    /**
     * updateConfigDetails()
     * @param type $request
     * @return \Zend\View\Model\JsonModel
     */
    public function updateConfigDetails($request) {
        $upcols_array = array('name', 'procedure_code', 'lab_id', 'body_site', 'specimen',
            'route_admin', 'laterality', 'description', 'standard_code', 'related_code', 'units', 'range', 'seq');

        $data = array(
            'type_id' => $request->getQuery('type_id'),
            'name' => $request->getQuery('name'),
            'description' => $request->getQuery('description'),
            'seq' => $request->getQuery('seq'),
            'lab_id' => $request->getQuery('lab_id'),
            'procedure_code' => $request->getQuery('procedure_code'),
            'standard_code' => $request->getQuery('standard_code'),
            'body_site' => $request->getQuery('body_site'),
            'specimen' => $request->getQuery('specimen'),
            'route_admin' => $request->getQuery('route_admin'),
            'laterality' => $request->getQuery('laterality'),
            'units' => $request->getQuery('units'),
            'range' => $request->getQuery('range'),
            'related_code' => $request->getQuery('related_code'));

        $sel_col = "";
        $input_arr = array();

        foreach ($upcols_array as $col) {

            $sel_col .= "`" . $col . "` = ? ,";
            $input_arr[] = $data[$col];
        }
        $input_arr[] = $data['type_id'];
        $sel_col = rtrim($sel_col, ",");

        $sql = "UPDATE procedure_type SET $sel_col WHERE procedure_type_id = ? ";
        $res = $this->applicationTable->zQuery($sql, $input_arr);
        $return = array();

        $return[0] = array('return' => 0, 'type_id' => $data['type_id']);
        $arr = new JsonModel($return);

        return $arr;
    }

    /**
     * getAddConfigDetails()
     * @param type $list_array
     * @return \Zend\View\Model\JsonModel
     */
    public function getAddConfigDetails($list_array) {
        $arr = array();
        $ret_arr = array();
        $result_array = array();
        $orderfrom_arr = array();

        $bodysite = $list_array[0];
        $specimen = $list_array[1];
        $administervia = $list_array[2];
        $laterality = $list_array[3];
        $defaultunits = $list_array[4];

        foreach ($bodysite as $bodysite_array) {
            $bodysite_arr[] = array('id' => $bodysite_array['value'],
                'value' => $bodysite_array['label'],
                'text' => $bodysite_array['label']);
        }

        foreach ($specimen as $specimen_array) {
            $specimen_arr[] = array('id' => $specimen_array['value'],
                'value' => $specimen_array['label'],
                'text' => $specimen_array['label']);
        }

        foreach ($administervia as $administervia_array) {
            $administervia_arr[] = array('id' => $administervia_array['value'],
                'value' => $administervia_array['label'],
                'text' => $administervia_array['label']);
        }

        foreach ($laterality as $laterality_array) {
            $laterality_arr[] = array('id' => $laterality_array['value'],
                'value' => $laterality_array['label'],
                'text' => $laterality_array['label']);
        }

        foreach ($defaultunits as $defaultunits_array) {
            $defaultunits_arr[] = array('id' => $defaultunits_array['value'],
                'value' => $defaultunits_array['label'],
                'text' => $defaultunits_array['label']);
        }

        $ppres = $this->applicationTable->zQuery("SELECT ppid, name FROM procedure_providers ORDER BY name, ppid");

        foreach ($ppres as $pprow) {
            $orderfrom_arr[] = array('id' => $pprow['ppid'],
                'value' => $pprow['name'],
                'text' => $pprow['name']);
        }

        $grp_array = array('name' => array('title' => "Name",
                'editor' => "text"),
            'description' => array('title' => "Description",
                'editor' => "text"));

        $ord_array = array('name' => array('title' => "Name",
                'editor' => "text"),
            'description' => array('title' => "Description",
                'editor' => "text"),
            'seq' => array('title' => "Sequence",
                'editor' => "text"),
            'order_from' => array('title' => "Order From",
                'editor' => "combobox",
                'options' => 'orderfrom_arr'),
            'procedure_code' => array('title' => "Procedure Code",
                'editor' => "text"),
            'standard_code' => array('title' => "Standard Code",
                'editor' => "text"),
            'body_site' => array('title' => "Body Site",
                'editor' => "combobox",
                'options' => 'bodysite_arr'),
            'specimen' => array('title' => "Specimen Type",
                'editor' => "combobox",
                'options' => 'specimen_arr'),
            'route_admin' => array('title' => "Administer Via",
                'editor' => "combobox",
                'options' => 'administervia_arr'),
            'laterality' => array('title' => "Laterality",
                'editor' => "combobox",
                'options' => 'laterality_arr'));

        $res_array = array('name' => array('title' => "Name",
                'editor' => "text"),
            'description' => array('title' => "Description",
                'editor' => "text"),
            'seq' => array('title' => "Sequence",
                'editor' => "text"),
            'units' => array('title' => "Default Units",
                'editor' => "combobox",
                'options' => 'defaultunits_arr'),
            'range' => array('title' => "Default Range",
                'editor' => "text"),
            'related_code' => array('title' => "Followup Services",
                'editor' => "text"));

        $rec_array = array('name' => array('title' => "Name",
                'editor' => "text"),
            'description' => array('title' => "Description",
                'editor' => "text"),
            'seq' => array('title' => "Sequence",
                'editor' => "text"),
            'units' => array('title' => "Default Units",
                'editor' => "combobox",
                'options' => 'defaultunits_arr'),
            'range' => array('title' => "Default Range",
                'editor' => "text"),
            'related_code' => array('title' => "Followup Services",
                'editor' => "text"));



        foreach ($grp_array as $column => $grp) {
            $arr['name'] = $grp['title'];
            $arr['value'] = "";
            $arr['group'] = 'Group';

            if ($grp['editor'] == "text") {
                $arr['editor'] = $grp['editor'];
            } else if ($grp['editor'] == "combobox") {
                $arr['editor'] = array('type' => $grp['editor'],
                    'options' => array('data' => ${$grp['options']})
                );
            }
            array_push($result_array, $arr);
        }

        foreach ($ord_array as $column => $ord) {
            $arr['name'] = $ord['title'];
            $arr['value'] = "";
            $arr['group'] = 'Order';

            if ($ord['editor'] == "text") {
                $arr['editor'] = $ord['editor'];
            } else if ($ord['editor'] == "combobox") {
                $arr['editor'] = array('type' => $ord['editor'],
                    'options' => array('data' => ${$ord['options']})
                );
            }
            array_push($result_array, $arr);
        }

        foreach ($res_array as $column => $res) {
            $arr['name'] = $res['title'];
            $arr['value'] = "";
            $arr['group'] = 'Result';

            if ($res['editor'] == "text") {
                $arr['editor'] = $res['editor'];
            } else if ($res['editor'] == "combobox") {
                $arr['editor'] = array('type' => $res['editor'],
                    'options' => array('data' => ${$res['options']})
                );
            }
            array_push($result_array, $arr);
        }

        foreach ($rec_array as $column => $rec) {
            $arr['name'] = $rec['title'];
            $arr['value'] = "";
            $arr['group'] = 'Recommendation';
            if ($rec['editor'] == "text") {
                $arr['editor'] = $rec['editor'];
            } else if ($rec['editor'] == "combobox") {
                $arr['editor'] = array('type' => $rec['editor'],
                    'options' => array('data' => ${$rec['options']}
                    )
                );
            }
            array_push($result_array, $arr);
        }

        $ret_arr = new JsonModel($result_array);
        return $ret_arr;
    }

    /**
     * addConfigDetails()
     * @param type $request
     * @return \Zend\View\Model\JsonModel
     */
    public function addConfigDetails($request) {
        $upcols_array = array('procedure_type', 'parent', 'name', 'lab_id', 'procedure_code', 'body_site', 'specimen',
            'route_admin', 'laterality', 'description', 'standard_code', 'related_code', 'units', 'range', 'seq');

        $data = array(
            'procedure_type' => $request->getQuery('procedure_type'),
            'parent' => $request->getQuery('parent'),
            'name' => $request->getQuery('name'),
            'description' => $request->getQuery('description'),
            'seq' => $request->getQuery('seq'),
            'lab_id' => $request->getQuery('lab_id'),
            'procedure_code' => $request->getQuery('procedure_code'),
            'standard_code' => $request->getQuery('standard_code'),
            'body_site' => $request->getQuery('body_site'),
            'specimen' => $request->getQuery('specimen'),
            'route_admin' => $request->getQuery('route_admin'),
            'laterality' => $request->getQuery('laterality'),
            'units' => $request->getQuery('units'),
            'range' => $request->getQuery('range'),
            'related_code' => $request->getQuery('related_code'));

        foreach ($data as $key => $val) {
            $data[$key] = (($data[$key] <> null) || (isset($data[$key]))) ? $data[$key] : "";
        }



        $sel_col = "";
        $input_arr = array();

        $param_count = 0;
        foreach ($upcols_array as $col) {
            $sel_col .= "`" . $col . "`,";
            $input_arr[] = $data[$col];
            $param_count++;
        }
        $param_str = str_repeat("?,", ($param_count));
        $params = rtrim($param_str, ",");
        $sel_col = rtrim($sel_col, ",");

        $sql = "INSERT INTO procedure_type ($sel_col) VALUES ($params)";
        $result = $this->applicationTable->zQuery($sql, $input_arr);
        $res = $result->getGeneratedValue();

        $return = array();

        $return[0] = array('return' => 0, 'type_id' => $res);
        $arr = new JsonModel($return);

        return $arr;
    }

    /**
     * deleteConfigDetails()
     * @param type $type_id
     * @return \Zend\View\Model\JsonModel
     */
    public function deleteConfigDetails($type_id) {
        array_push($this->child_ids, $type_id);
        $ret_arr = $this->getConfigChildIds($type_id);
        $ret_arr = array_reverse($ret_arr);

        $sql = "DELETE FROM procedure_type WHERE procedure_type_id = ? ";

        foreach ($ret_arr as $typeid) {
            $in_arr = array($typeid);
            $this->applicationTable->zQuery($sql, $in_arr);
        }

        $return = array();

        $return[0] = array('return' => 0, 'type_id' => $type_id);
        $arr = new JsonModel($return);

        return $arr;
    }

    /**
     * Check Procedure Code Exist
     *  Avoid Duplicates
     */
    public function checkProcedureCodeExist($data) {
        $sql = "SELECT procedure_code FROM procedure_type WHERE procedure_code=? AND lab_id = ?";
        $res = $this->applicationTable->zQuery($sql, array($data['code'], $data['lab']));
        $result = $res->count();
        if ($result > 0) {
            return array(1);
        } else {
            return array(0);
        }
    }

    /**
     * SstandardCode Auto suggest
     * ICD9, CPT, HCPCS, CVX and Product 
     */
    public function listStandardCode($data) {
        $inputString = $data['inputString'];
        $codeType = $data['codeType'];
        $codeTypeValue = $this->applicationTable->zQuery("SELECT ct_id FROM code_types WHERE ct_key=?", array($codeType));

        // Search code type ICD9
        if ($codeType == 'ICD9') {
            $sql = "SELECT ref.formatted_dx_code as code, 
			    ref.long_desc as code_text 
			FROM `icd9_dx_code` as ref 
			LEFT OUTER JOIN `codes` as c 
				ON ref.formatted_dx_code = c.code 
				AND c.code_type = ? 
				WHERE (ref.long_desc LIKE ? OR ref.formatted_dx_code LIKE ?) 
				AND ref.active = '1'
				AND (c.active = 1 || c.active IS NULL) 
				ORDER BY ref.formatted_dx_code+0, ref.formatted_dx_code";
            $result = $this->applicationTable->zQuery($sql, array($codeTypeValue['ct_id'], "%" . $inputString . "%", "%" . $inputString . "%"));
        }

        // Search code type CPT4
        if ($codeType == 'CPT4') {
            $sql = "SELECT c.id,
				c.code, 
				c.code_text  
			FROM `codes` as c 
			WHERE (c.code_text LIKE ? OR c.code LIKE ?) 
			AND c.code_type = ? 
			AND c.active = 1 
			ORDER BY c.code+0,c.code";
            $result = $this->applicationTable->zQuery($sql, array("%" . $inputString . "%", "%" . $inputString . "%", $codeTypeValue['ct_id']));
        }

        // Seach code type HCPCS
        if ($codeType == 'HCPCS') {
            $sql = "SELECT c.id,
			c.code, 
			c.code_text 
		FROM `codes` as c 
		WHERE (c.code_text LIKE ? OR c.code LIKE ?) 
		AND c.code_type = ?
		AND c.active = 1 
		ORDER BY c.code+0,c.code";
            $result = $this->applicationTable->zQuery($sql, array("%" . $inputString . "%", "%" . $inputString . "%", $codeTypeValue['ct_id']));
        }

        // Seach code type CVX
        if ($codeType == 'CVX') {
            $sql = "SELECT c.id, 
			c.code_text, 
			c.code  
		FROM `codes` as c 
		WHERE (c.code_text LIKE ? OR c.code LIKE ?) 
		AND c.code_type = ?
		AND c.active = 1 
		ORDER BY c.code+0,c.code";
            $result = $this->applicationTable->zQuery($sql, array("%" . $inputString . "%", "%" . $inputString . "%", $codeTypeValue['ct_id']));
        }

        // Seach code type Product
        if ($codeType == 'PROD') {
            $sql = "SELECT dt.drug_id, 
			dt.selector, 
			d.name 
		FROM drug_templates AS dt, drugs AS d 
		WHERE ( d.name LIKE ? OR dt.selector LIKE ? ) 
		AND d.drug_id = dt.drug_id 
		ORDER BY d.name, dt.selector, dt.drug_id";
            $result = $this->applicationTable->zQuery($sql, array("%" . $inputString . "%", "%" . $inputString . "%"));
        }

        $arr = array();
        $i = 0;
        if ($codeType == 'PROD') {
            foreach ($result as $tmp) {
                $arr[] = htmlspecialchars($tmp['drug_id'], ENT_QUOTES) . '|-|' . htmlspecialchars($tmp['selector'], ENT_QUOTES) . '|-|' . htmlspecialchars($tmp['name'], ENT_QUOTES);
            }
        } else {
            foreach ($result as $tmp) {
                $arr[] = htmlspecialchars($tmp['code'], ENT_QUOTES) . '|-|' . htmlspecialchars($tmp['code_text'], ENT_QUOTES);
            }
        }
        return $arr;
    }

}
?>


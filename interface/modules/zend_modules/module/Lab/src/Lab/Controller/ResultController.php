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

namespace Lab\Controller;
require_once($GLOBALS['fileroot']. "/library/html2pdf/vendor/autoload.php");

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Lab\Form\ResultForm;
use Zend\Json\Json;
use Zend\Soap\Client;
use Zend\Config;
use Zend\Config\Reader;
use CouchDB;
use DOMPDFModule\View\Model\PdfModel;
use Zend\Barcode\Barcode;
use \DOMPDF;
use \HTML2PDF;
use TCPDF2DBarcode;
use Zend\Filter\Compress\Zip;
use Lab\Model\WsseAuthHeader;

class ResultController extends AbstractActionController {

    protected $labTable;

    /**
     * getResultTable()
     * @return type
     */
    public function getResultTable() {
        if (!$this->labTable) {
            $sm = $this->getServiceLocator();
            $this->labTable = $sm->get('Lab\Model\ResultTable');
        }
        return $this->labTable;
    }

    /**
     * Index Action
     * @global type $pid
     * @return \Zend\View\Model\ViewModel
     */
    public function indexAction() {
        $form = new ResultForm();
        global $pid;
        $msg = '';
        if ($pid == '') {
            $msg = 'N';
        }
        $request = $this->getRequest();
        $pageno = 1;
        $labs = $this->CommonPlugin()->getLabs('y', 'search');

        //*****singulex lab**********

        $cred = $this->getResultTable()->getSingulexCredentials();
        if (!empty($cred)) {
            $username = $cred['login'];
            $password = $cred['password'];
            $remote_host = trim($cred['remote_host']);
            $site_dir = $GLOBALS['site_id'];

            ini_set("soap.wsdl_cache_enabled", "0");
            ini_set('memory_limit', '-1');

            $options = array('location' => $remote_host,
                'uri' => "urn://zhhealthcare/lab"
            );
            $client = new Client(null, $options);
            $stresultonly = $client->getResultOnlyDetails($username, $password);  //USERNAME, PASSWORD     
            if ($stresultonly) {
                $labresultonlydetailsfile = "labresultonlydetails_" . gmdate('YmdHis') . substr((string) microtime(), 1, 8) . ".xml";
                $resultonlydetails_dir = $GLOBALS['OE_SITE_DIR'] . "/lab/resultonlydetails/";
                if (!is_dir($resultonlydetails_dir)) {
                    mkdir($resultonlydetails_dir, 0777, true);
                }
                $fp = fopen($resultonlydetails_dir . $labresultonlydetailsfile, "wb");
                fwrite($fp, $stresultonly);
                $reader = new Config\Reader\Xml();
                $xmldata_all = $reader->fromFile($resultonlydetails_dir . $labresultonlydetailsfile);
                $inserted_arr = array();
                foreach ($xmldata_all['ResultOnly'] as $xmldata) {
                    if (!is_array($xmldata)) {
                        $xmldata = $xmldata_all['ResultOnly'];
                        $break = 1;
                    }
                    $sql_result_only = "INSERT INTO procedure_result_only(lab_result_id,patient_lname,patient_fname,patient_dob,patient_gender,patient_home_phone,patient_work_phone,patient_ss_no,date,result_status, order_level_comment
		      ,                    performing_lab_name,performing_lab_addr1,performing_lab_addr2,performing_lab_city,performing_lab_state,performing_lab_zip,performing_lab_phone,performing_lab_provider,procedure_order_id)
		      VALUES(?,?,?,?,?,?,?,?,NOW(),?,?,?,?,?,?,?,?,?,?,?)";
                    $vals = array($xmldata['result_only_id'], $xmldata['patient_lname'], $xmldata['patient_fname'], $xmldata['dob'], $xmldata['gender'], $xmldata['home_phone'], $xmldata['work_phone'], $xmldata['ss_number'], $xmldata['order_status'], $xmldata['pat_report_comments'],
                        $xmldata['performing_lab'],
                        isset($xmldata['performing_lab_addr1']) ? $xmldata['performing_lab_addr1'] : "",
                        isset($xmldata['performing_lab_addr2']) ? $xmldata['performing_lab_addr2'] : "",
                        isset($xmldata['performing_lab_city']) ? $xmldata['performing_lab_city'] : "",
                        isset($xmldata['performing_lab_state']) ? $xmldata['performing_lab_state'] : "",
                        isset($xmldata['performing_lab_zip']) ? $xmldata['performing_lab_zip'] : "",
                        isset($xmldata['performing_lab_phone']) ? $xmldata['performing_lab_phone'] : "",
                        isset($xmldata['performing_lab_provider']) ? $xmldata['performing_lab_provider'] : "", $xmldata['procedure_order_id']);
                    $this->getResultTable()->insertProcedureResult($sql_result_only, $vals);
                    $test_arr = explode("#--#", $xmldata['test_ids']);
                    $result_test_arr = explode("!-#@#-!", $xmldata['result_values']);
                    $resultcomments_test_arr = explode("#-!!-#", $xmldata['res_report_comments']);
                    $test_count = count($test_arr) - 1;
                    $prev_seq = 0;
                    for ($index = 0; $index < $test_count; $index++) {
                        $has_subtest = 0;
                        $testdetails = $test_arr[$index];
                        if (trim($testdetails) <> "") {
                            $testdetails_arr = explode("#!#", $testdetails);
                            list($test_code, $profile_title, $code_suffix, $order_title, $spec_collected_time, $spec_received_time, $res_reported_time) = $testdetails_arr;
                            $prev_seq++;
                            $result_test_comments = $resultcomments_test_arr[$index];
                            $resultcomments_arr = explode("#!!#", $result_test_comments);
                            $resultdetails_test = $result_test_arr[$index];
                            $resultdetails_subtest_arr = explode("!#@#!", $resultdetails_test);
                            $no_of_subtests = substr_count($resultdetails_test, "!#@#!");
                            if (trim($resultdetails_test) <> "") {
                                for ($j = 0; $j < $no_of_subtests; $j++) {
                                    $subtest_comments = $resultcomments_arr[$j];
                                    $subtest_comments = str_replace("\n", "\\r\\n", $subtest_comments);
                                    $subtest_resultdetails_arr = explode("!@!", $resultdetails_subtest_arr[$j]);
                                    list($subtest_code, $subtest_name, $result_value, $units, $range, $abn_flag, $result_status, $result_time, $providers_id) = $subtest_resultdetails_arr;
                                    $sql_subtest_result_only = "INSERT INTO procedure_subtest_result_only(lab_result_id,subtest_code,subtest_desc,
				result_value,units,`range`,abnormal_flag,result_status,result_time,provider_name,comments,order_title
				)
				VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                    $result_only_inarray = array($xmldata['result_only_id'], $subtest_code, $subtest_name, $result_value, $units, $range,
                                        $abn_flag, $result_status, $result_time, $xmldata['provider_name'], $subtest_comments, $order_title
                                    );
                                    $this->getResultTable()->insertProcedureResult($sql_subtest_result_only, $result_only_inarray);
                                }
                            }
                        }
                    }
                    $status_res = $this->getResultTable()->changeOrderResultStatus($xmldata['procedure_order_id'], 'completed', '', '', 0);
                    array_push($inserted_arr, $xmldata['result_only_id']);
                    if ($break == 1) {
                        break;
                    }
                }
                $stresultonly = $client->updateResultOnlyStatus($username, $password, $site_dir, $inserted_arr);
            }
        }
        //*************end**************  

        $lastlaborderid = $this->params()->fromQuery('orderid') ? $this->params()->fromQuery('orderid') : '';

        if ($request->isGet()) {
            $pageno = ($request->getQuery('pageno') <> "") ? $request->getQuery('pageno') : 1;
            $lab = $request->getQuery('lab');
            $form->get('lab_id')->setValueOptions($labs)->setValue($lab);

            $labresult1 = $this->resultShowAction($pageno);
            $viewModel = new ViewModel(array(
                "labresults" => $labresult1,
                "message" => $msg,
                "order_status" => $request->getQuery('searchStatusOrder'),
                "report_status" => $request->getQuery('searchStatusReport'),
                "result_status" => $request->getQuery('searchStatusResult'),
                "dtFrom" => $request->getQuery('dtFrom'),
                "dtTo" => $request->getQuery('dtTo'),
                "searchtest" => $request->getQuery('searchtest'),
                "enc_select" => $request->getQuery('searchenc'),
                "form" => $form,
                "lastlaborderid" => $lastlaborderid
            ));
            return $viewModel;
        } else {
            $form->get('lab_id')->setValueOptions($labs)->setValue($this->getRequest()->getPost('lab_id'));
            $labresult1 = $this->resultShowAction($pageno);
            $viewModel = new ViewModel(array(
                "labresults" => $labresult1,
                "message" => $msg,
                "order_status" => $this->getRequest()->getPost('searchStatusOrder'),
                "report_status" => $this->getRequest()->getPost('searchStatusReport'),
                "result_status" => $this->getRequest()->getPost('searchStatusResult'),
                "dtFrom" => $this->getRequest()->getPost('dtFrom'),
                "dtTo" => $this->getRequest()->getPost('dtTo'),
                "searchtest" => $this->getRequest()->getPost('searchtest'),
                "enc_select" => $this->getRequest()->getPost('searchenc'),
                "form" => $form
            ));
            return $viewModel;
        }
    }

    /**
     * Label download
     * @param type $orderId
     */
    public function getLabelDownloadAction($orderId) {
        $site_id = $_SESSION['site_id'];
        if (!$orderId)
            $orderId = $_GET['order_id'];
        // Set font size
        $font_size = 2;
        $row = $this->getResultTable()->selectPatientInfo($orderId);
        if ($row['mirth_lab_id'] != 0) {
            $client = "Client: " . $row['send_fac_id'];
            $labref = "\nLab Ref: " . $row['labref'];
        } else {
            $client = "Client: " . $site_id;
            $labref = "\nLab Ref: " . $row['name'] . '-' . $orderId;
        }
        $text = $client . $labref . "\nPat Name: " . $row['pname'];
        $ts = explode("\n", $text);
        $total_lines = count($ts);
        $width = 0;
        foreach ($ts as $k => $string) { //compute width
            $width = max($width, strlen($string));
        }

        // Create image width dependant on width of the string
        //$width  = imagefontwidth($font_size)*$width;
        $width = 168;
        // Set height to that of the font
        //$height = imagefontheight($font_size)*count($ts);
        $height = 72;
        $el = imagefontheight($font_size);
        $em = imagefontwidth($font_size);
        // Create the image pallette
        $img = imagecreatetruecolor($width, $height);
        // Dark red background
        $bg = imagecolorallocate($img, 255, 255, 255);
        imagefilledrectangle($img, 0, 0, $width, $height, $bg);
        // White font color
        $color = imagecolorallocate($img, 0, 0, 0);

        foreach ($ts as $k => $string) {
            // Length of the string
            $len = strlen($string);
            // Y-coordinate of character, X changes, Y is static
            $ypos = 0;
            // Loop through the string
            for ($i = 0; $i < $len; $i++) {
                // Position of the character horizontally
                $xpos = $i * $em;
                $ypos = $k * $el;

                $center_x = ceil(( ( imagesx($img) - ( $em * $len ) ) / 2 ) + ( $i * $em ));
                $center_y = ceil(( ( imagesy($img) - ( $el * $total_lines ) ) / 2) + ( $k * $el ));

                //error_log("aa:$xpos, $ypos---$center_x, $center_y");
                // Draw character
                imagechar($img, $font_size, $center_x, $center_y, $string, $color);
                // Remove character from string
                $string = substr($string, 1);
            }
        }
        // Return the image       
        ob_end_clean();
        ob_start();
        $this->createImageBorder($img);
        $IMGING = ob_get_contents();
        header("Content-Type: image/jpg");
        header('Content-Disposition: attachment; filename=SpecimenLabel_' . $orderId . '.jpg');
        header("Content-Type: application/octet-stream");
        header("Content-Length: " . filesize($IMGING));
        // Remove image
        imagedestroy($img);
        exit;
    }

    /**
     * function createImageBorder
     * @param type $imgName
     */
    function createImageBorder($imgName) {

        //$img     =  substr($imgName, 0, -4); // remove fileExtension
        //$ext     = ".jpg";
        //$quality = 95;
        $borderColor = 0;  // 255 = white

        /*
          a                         b
          +-------------------------+
          |
          |          IMAGE
          |
          +-------------------------+
          c                         d
         */

        //$scr_img = imagecreatefromjpeg($img.$ext);
        $scr_img = $imgName;
        $width = imagesx($scr_img);
        $height = imagesy($scr_img);

        // line a - b
        $abX = 0;
        $abY = 0;
        $abX1 = $width;
        $abY1 = 0;

        // line a - c
        $acX = 0;
        $acY = 0;
        $acX1 = 0;
        $acY1 = $height;

        // line b - d
        $bdX = $width - 1;
        $bdY = 0;
        $bdX1 = $width - 1;
        $bdY1 = $height;

        // line c - d
        $cdX = 0;
        $cdY = $height - 1;
        $cdX1 = $width;
        $cdY1 = $height - 1;

        $w = imagecolorallocate($scr_img, 255, 255, 255);
        $b = imagecolorallocate($scr_img, 0, 0, 0);

        $style = array_merge(array_fill(0, 5, $b), array_fill(0, 5, $w));
        imagesetstyle($scr_img, $style);

        // DRAW LINES   
        imageline($scr_img, $abX, $abY, $abX1, $abY1, IMG_COLOR_STYLED);
        imageline($scr_img, $acX, $acY, $acX1, $acY1, IMG_COLOR_STYLED);
        imageline($scr_img, $bdX, $bdY, $bdX1, $bdY1, IMG_COLOR_STYLED);
        imageline($scr_img, $cdX, $cdY, $cdX1, $cdY1, IMG_COLOR_STYLED);

        // create copy from image   
        imagejpeg($scr_img);
        //imagedestroy($scr_img);
    }

    /**
     * Paginate Action
     * @return \Zend\View\Model\ViewModel
     */
    public function paginationAction() {

        $request = $this->getRequest();
        $pageno = 1;
        if ($request->isGet()) {

            $pageno = ($request->getQuery('pageno') <> "") ? $request->getQuery('pageno') : 1;
        }

        $labresult1 = $this->resultShowAction($pageno);
        $viewModel = new ViewModel(array(
            "labresults" => $labresult1
        ));
        return $viewModel;
    }

    /**
     * Result show Action
     * @param type $pageno
     * @return type
     */
    public function resultShowAction($pageno) {
        $request = $this->getRequest();
        $data = array();
        if ($request->isPost()) {
            $data = array(
                'statusReport' => $request->getPost('searchStatusReport'),
                'statusOrder' => $request->getPost('searchStatusOrder'),
                'statusResult' => $request->getPost('searchStatusResult'),
                'dtFrom' => $request->getPost('dtFrom'),
                'dtTo' => $request->getPost('dtTo'),
                'page' => $request->getPost('page'),
                'rows' => $request->getPost('rows'),
                'encounter' => $request->getPost('searchenc'),
                'testname' => $request->getPost('searchtest'),
                'labname' => $request->getPost('lab_id'),
            );
        } else {
            $data = array(
                'statusReport' => $request->getQuery('searchStatusReport'),
                'statusOrder' => $request->getQuery('searchStatusOrder'),
                'statusResult' => $request->getQuery('searchStatusResult'),
                'labname' => $request->getQuery('lab'),
                'testname' => $request->getQuery('searchtest'),
                'encounter' => $request->getQuery('searchenc'),
                'dtFrom' => $request->getQuery('dtFrom'),
                'dtTo' => $request->getQuery('dtTo'),
            );
        }

        $data = $this->getLabResult($data, $pageno);
        return $data;
    }

    /**
     * Get lab result
     * @param type $data
     * @param type $pageno
     * @return type
     */
    public function getLabResult($data, $pageno) {
        $labResult = $this->getResultTable()->listLabResult($data, $pageno);
        return $labResult;
    }

    /**
     * Get lab options
     * @return \Zend\View\Model\JsonModel
     */
    public function getLabOptionsAction() {
        $request = $this->getRequest();
        $data = array();
        if ($request->getQuery('opt')) {
            switch ($request->getQuery('opt')) {
                case 'search':
                    $data['opt'] = 'search';
                    break;
                case 'status':
                    $data['opt'] = 'status';
                    break;
                case 'abnormal':
                    $data['opt'] = 'abnormal';
                    break;
            }
        }
        if ($request->getQuery('optId')) {
            switch ($request->getQuery('optId')) {
                case 'order':
                    $data['optId'] = 'ord_status';
                    $data['select'] = $this->params()->fromQuery('order_select') ? $this->params()->fromQuery('order_select') : '';
                    break;
                case 'report':
                    $data['optId'] = 'proc_rep_status';
                    $data['select'] = $this->params()->fromQuery('report_select') ? $this->params()->fromQuery('report_select') : '';
                    break;
                case 'result':
                    $data['optId'] = 'proc_res_status';
                    $data['select'] = $this->params()->fromQuery('result_select', '') ? $this->params()->fromQuery('result_select', '') : '';
                    break;
                case 'abnormal':
                    $data['optId'] = 'proc_res_abnormal';
                    $data['select'] = '';
                    break;
            }
        }
        $labOptions = $this->CommonPlugin()->getList($data['optId'], $data['select'], $data['opt']);
        $data = new JsonModel($labOptions);
        return $data;
    }

    /**
     * Get result Comments
     * @return \Zend\View\Model\JsonModel
     */
    public function getResultCommentsAction() {
        $request = $this->getRequest();
        $data = array();
        if ($request->getPost('prid')) {
            $data['procedure_report_id'] = $request->getPost('prid');
        }
        $comments = $this->getResultTable()->listResultComment($data);
        $data = new JsonModel($comments);
        return $data;
    }

    /**
     * Insert lab comments
     * @return \Zend\View\Model\JsonModel
     */
    public function insertLabCommentsAction() {
        $request = $this->getRequest();
        $data = array();
        if ($request->isPost()) {
            $data = array(
                'procedure_report_id' => $request->getPost('procedure_report_id'),
                'result_status' => $request->getPost('result_status'),
                'facility' => $request->getPost('facility'),
                'comments' => $request->getPost('comments'),
                'notes' => $request->getPost('notes'),
            );
            $this->getResultTable()->saveResultComments($data['result_status'], $data['facility'], $data['comments'], $data['procedure_report_id']);
            $return = array();
            $return[0] = array('return' => 0, 'report_id' => $data['procedure_report_id']);
            $arr = new JsonModel($return);
            return $arr;
        }
    }

    /**
     * result update action
     * @return type
     */
    public function resultUpdateAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $arr = explode('|', $request->getPost('comments'));
            $comments = '';
            $comments = $arr[2];
            if ($arr[3] != '') {
                $comments .= "\n" . $arr[3];
            }

            $data = array(
                'procedure_report_id' => $request->getPost('procedure_report_id'),
                'procedure_result_id' => $request->getPost('procedure_result_id'),
                'procedure_order_id' => $request->getPost('procedure_order_id'),
                'specimen_num' => $request->getPost('specimen_num'),
                'report_status' => $request->getPost('report_status'),
                'procedure_order_seq' => $request->getPost('procedure_order_seq'),
                'date_report' => $request->getPost('date_report'),
                'date_collected' => $request->getPost('date_collected'),
                'result_code' => $request->getPost('result_code'),
                'result_text' => $request->getPost('result_text'),
                'abnormal' => $request->getPost('abnormal'),
                'result' => $request->getPost('result'),
                'range' => $request->getPost('range'),
                'units' => $request->getPost('units'),
                'result_status' => $arr[0],
                'facility' => $arr[1],
                'comments' => $comments,
            );
            $this->getResultTable()->saveResult($data);
            return $this->redirect()->toRoute('result');
        }
        return $this->redirect()->toRoute('result');
    }

    /**
     * Result pulling and view
     */
    public function getLabResultPDFAction() {
        global $pid;
        $site_dir = $GLOBALS['OE_SITE_DIR'];
        $result_dir = $site_dir . "/lab/result/";
        $unassociated_result_dir = $site_dir . "/lab/unassociated_result/";
        $result = array();
        if (!class_exists('CouchDB')) {
            require(dirname(__FILE__) . "/../../../../../../../../library/classes/CouchDB.class.php");
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array('procedure_order_id' => $request->getPost('order_id'));
        } elseif ($request->isGet()) {
            $data = array('procedure_order_id' => $request->getQuery('order_id'));
        }
        $curr_status = $this->getResultTable()->getOrderStatus($data['procedure_order_id']);

        if ($request->isPost()) {
            $cred = $this->getResultTable()->getClientCredentials($data['procedure_order_id']);
            $username = $cred['login'];
            $password = $cred['password'];
            $site_dir = $_SESSION['site_id'];
            $remote_host = trim($cred['remote_host']);
            /** Check Result PDF Local */
            if(($username == "") || ($password == "") || ($remote_host == "")) {
                $return[0]  = array('return' => 0, 'order_id' => $data['procedure_order_id']);
                $arr        = new JsonModel($return);
                return $arr;
            }else if (($username == "") || ($password == "")) {
                $return[0] = array('return' => 1, 'msg' => xlt("Lab Credentials not found"));
                $arr = new JsonModel($return);
                return $arr;
            } else if ($remote_host == "") {
                $return[0] = array('return' => 1, 'msg' => xlt("Remote Host not found"));
                $arr = new JsonModel($return);
                return $arr;
            } else {
                ini_set("soap.wsdl_cache_enabled", "0");
                ini_set('memory_limit', '-1');
                $options = array('location' => $remote_host,
                    'uri' => "urn://zhhealthcare/lab"
                );
                try {
                    $client = new Client(null, $options);
                    $stresult = $client->getLabResultStatus($username, $password, $site_dir, $data['procedure_order_id']);  //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID
                } catch (\Exception $e) {
                    $return[0] = array('return' => 1, 'msg' => xlt("Could not connect to the web service"));
                    $arr = new JsonModel($return);
                    return $arr;
                }
                try {
                    $result = $client->getLabResult($username, $password, $site_dir, $data['procedure_order_id']);  //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID
                    $unassociated_result = $client->getLabUnassociatedResult($username, $password, $site_dir);
                } catch (\Exception $e) {
                    $return[0] = array('return' => 1, 'msg' => xlt("Could not connect to the web service"));
                    $arr = new JsonModel($return);
                    return $arr;
                }
                // Ajax Handling (Result success or failed)
                if ($result['status'] == 'failed') {
                    $unassociated_arr = array();
                    if (!is_dir($unassociated_result_dir)) {
                        mkdir($unassociated_result_dir, 0777, true);
                    }
                    foreach ($unassociated_result as $ur) {
                        $labresultunassocfile = "labresult_unassociated_" . gmdate('YmdHis') . ".pdf";
                        $cnt = 1;
                        while (file_exists($unassociated_result_dir . $labresultunassocfile)) {
                            $labresultunassocfile = "labresult_unassociated_" . gmdate('YmdHis') . "_" . $cnt . ".pdf";
                            $cnt++;
                        }
                        $fp = fopen($unassociated_result_dir . $labresultunassocfile, "wb");
                        fwrite($fp, base64_decode($ur['content']));
                        $sql_unassoc = "INSERT INTO procedure_result_unassociated(patient_name,file_order_id,file_location) VALUES (?,?,?)";
                        $sql_unassoc_arr = array($ur['patient_name'], $ur['order_id'], $labresultunassocfile);
                        $this->getResultTable()->insertQuery($sql_unassoc, $sql_unassoc_arr);
                        array_push($unassociated_arr, $ur['id']);
                    }
                    $client->updateUnassociatedResult($username, $password, $site_dir, $unassociated_arr);
                    $return[0] = array('return' => 1, 'msg' => xlt($result['content']));
                    $arr = new JsonModel($return);
                    return $arr;
                } else { //IF THE RESULT RETURNS VALID OUTPUT                    
                    if ($GLOBALS['document_storage_method'] == 1 && $result['content'] != "" && strtolower($cred['mirth_lab_name']) != 'labcorp') {
                        $couch = new CouchDB();
                        $docname = $_SESSION['authId'] . " " . $pid . date("%Y-%m-%d H:i:s") . "_res";
                        $labresultfile = $couch->stringToId($docname);
                        $json = json_encode($result['content']);
                        $db = $GLOBALS['couchdb_dbase'];
                        $docdata = array($db, $labresultfile, $pid, '', 'application/pdf', $json);
                        $resp = $couch->check_saveDOC($docdata);
                        if (!$resp->id || !$resp->rev) {
                            $docdata = array($db, $labresultfile, $pid, '');
                            $resp = $couch->retrieve_doc($docdata);
                            $labresultfile = $resp->_id;
                            $revid = $resp->_rev;
                        } else {
                            $labresultfile = $resp->id;
                            $revid = $resp->rev;
                        }
                        if (!$labresultfile && !$revid) { //if couchdb save failed
                            $error .= "<font color='red'><b>" . xl("The file could not be saved to CouchDB.") . "</b></font>\n";
                            if ($GLOBALS['couchdb_log'] == 1) {
                                ob_start();
                                var_dump($resp);
                                $couchError = ob_get_clean();
                                $log_content = date('Y-m-d H:i:s') . " ==> Uploading document: " . $fname . "\r\n";
                                $log_content .= date('Y-m-d H:i:s') . " ==> Failed to Store document content to CouchDB.\r\n";
                                $log_content .= date('Y-m-d H:i:s') . " ==> Document ID: " . $labresultfile . "\r\n";
                                $log_content .= date('Y-m-d H:i:s') . " ==> " . print_r($docdata, 1) . "\r\n";
                                $log_content .= $couchError;
                            }
                        }
                    }
                    if ($GLOBALS['document_storage_method'] == 0 && $result['content'] != "" && strtolower($cred['mirth_lab_name']) != 'labcorp') {
                        $labresultfile = "labresult_" . gmdate('YmdHis') . ".pdf";
                        $file_cnt = 1;
                        while (file_exists($result_dir . $labresultfile)) {
                            $labresultfile = "labresult_" . gmdate('YmdHis') . "_" . $file_cnt . ".pdf";
                            $file_cnt++;
                        }
                        if (!is_dir($result_dir)) {
                            mkdir($result_dir, 0777, true);
                        }
                        $fp = fopen($result_dir . $labresultfile, "wb");
                        fwrite($fp, base64_decode($result['content']));
                    }

                    //PULING RESULT DETAILS INTO THE OPENEMR TABLES
                    $this->getLabResultDetails($data['procedure_order_id']);
                    $status_res = $this->getResultTable()->changeOrderResultStatus($data['procedure_order_id'], $stresult['status'], $labresultfile, $revid, $GLOBALS['document_storage_method']);
                    $unassociated_arr = array();
                    if (!is_dir($unassociated_result_dir)) {
                        mkdir($unassociated_result_dir, 0777, true);
                    }
                    foreach ($unassociated_result as $ur) {
                        $labresultunassocfile = "labresult_unassociated_" . gmdate('YmdHis') . ".pdf";
                        $cnt = 1;
                        while (file_exists($unassociated_result_dir . $labresultunassocfile)) {
                            $labresultunassocfile = "labresult_unassociated_" . gmdate('YmdHis') . "_" . $cnt . ".pdf";
                            $cnt++;
                        }
                        $fp = fopen($unassociated_result_dir . $labresultunassocfile, "wb");
                        fwrite($fp, base64_decode($ur['content']));
                        $sql_unassoc = "INSERT INTO procedure_result_unassociated(patient_name,file_order_id,file_location) VALUES (?,?,?)";
                        $sql_unassoc_arr = array($ur['patient_name'], $ur['order_id'], $labresultunassocfile);
                        $this->getResultTable()->insertQuery($sql_unassoc, $sql_unassoc_arr);
                        array_push($unassociated_arr, $ur['id']);
                    }
                    $client->updateUnassociatedResult($username, $password, $site_dir, $unassociated_arr);
                    //}else{              
                    $return[0] = array('return' => 0, 'order_id' => $data['procedure_order_id']);
                    $arr = new JsonModel($return);
                    return $arr;
                }
            }
        }

        if ($curr_status == "completed" || $curr_status == "partial" || $data['procedure_order_id'] > 0) {
            $labresultfile = $this->getResultTable()->getOrderResultFile($data['procedure_order_id']);
        }
        if ($labresultfile <> "") {
            while (ob_get_level()) {
                ob_end_clean();
            }
            /** PDF File View */
            if ($GLOBALS['document_storage_method'] == 1) {
                $labresultfilename = "labresult_" . gmdate('YmdHis') . ".pdf";
                $couch = new CouchDB();
                $resdocdata = array($GLOBALS['couchdb_dbase'], $labresultfile);
                $resp = $couch->retrieve_doc($resdocdata);
                $content = $resp->data;
                if ($content == '' && $GLOBALS['couchdb_log'] == 1) {
                    $log_content = date('Y-m-d H:i:s') . " ==> Retrieving document\r\n";
                    $log_content = date('Y-m-d H:i:s') . " ==> URL: " . $url . "\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> CouchDB Document Id: " . $couch_docid . "\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> CouchDB Revision Id: " . $couch_revid . "\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> Failed to fetch document content from CouchDB.\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> Will try to download file from HardDisk if exists.\r\n\r\n";
                    $this->document_upload_download_log($d->get_foreign_id(), $log_content);
                    die(xl("File retrieval from CouchDB failed"));
                }
                if (!is_dir($GLOBALS['OE_SITE_DIR'] . '/documents/temp/')) {
                    mkdir($GLOBALS['OE_SITE_DIR'] . '/documents/temp/', 0777, true);
                }
                $tmpcouchpath = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/couch_' . date("YmdHis") . $labresultfilename;
                $fh = fopen($tmpcouchpath, "w");
                fwrite($fh, base64_decode($content));
                fclose($fh);
                header('Content-Disposition: attachment; filename=' . $labresultfilename);
                header("Content-Type: application/octet-stream");
                header("Content-Length: " . filesize($tmpcouchpath));
                readfile($tmpcouchpath);
                unlink($tmpcouchpath);
            }
            if ($GLOBALS['document_storage_method'] == 0) {
                header('Content-Disposition: attachment; filename=' . $labresultfile);
                header("Content-Type: application/octet-stream");
                header("Content-Length: " . filesize($result_dir . $labresultfile));
                readfile($result_dir . $labresultfile);
            }
            exit;
        } else {
            $this->resultPdfAction($data['procedure_order_id']);
        }
    }

    /**
     * Insert lab result details
     * @param type $order_id
     */
    public function getLabResultDetails($order_id) {
        $site_dir = $GLOBALS['OE_SITE_DIR'];
        $resultdetails_dir = $site_dir . "/lab/resultdetails/";

        $data['procedure_order_id'] = $order_id;
        $cred = $this->getResultTable()->getClientCredentials($data['procedure_order_id']);

        $username = $cred['login'];
        $password = $cred['password'];
        $remote_host = trim($cred['remote_host']);
        $site_dir = $GLOBALS['site_id'];

        ini_set("soap.wsdl_cache_enabled", "0");
        ini_set('memory_limit', '-1');

        $options = array('location' => $remote_host,
            'uri' => "urn://zhhealthcare/lab"
        );
        $client = new Client(null, $options);
        $result = $client->getLabResultDetails($username, $password, $site_dir, $data['procedure_order_id']);  //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID       
        $labresultdetailsfile = "labresultdetails_" . gmdate('YmdHis') . ".xml";

        if (!is_dir($resultdetails_dir)) {
            mkdir($resultdetails_dir, 0777, true);
        }

        $fp = fopen($resultdetails_dir . $labresultdetailsfile, "wb");
        fwrite($fp, $result);

        $reader = new Config\Reader\Xml();
        $xmldata = $reader->fromFile($resultdetails_dir . $labresultdetailsfile);

        //CHECKS IF THE RESULT DETAIL IS ALREADY PULLED
        $pulled_count = $this->getResultTable()->getOrderResultPulledCount($order_id);

        if ($pulled_count > 0) {
            $this->getResultTable()->deleteResults($order_id);
            $pulled_count = 0;
        }

        if ($pulled_count == 0) {
            $patient_comments = $xmldata['pat_report_comments'];
            $sql_return_comments = "UPDATE procedure_order SET return_comments = ? WHERE procedure_order_id = ?";
            $sql_return_comments_array = array($patient_comments, $data['procedure_order_id']);
            $this->getResultTable()->updateReturnComments($sql_return_comments, $sql_return_comments_array);
            //SEPERATES EACH TEST DETAILS
            $test_arr = explode("#--#", $xmldata['test_ids']);
            $result_test_arr = explode("!-#@#-!", $xmldata['result_values']);
            $resultcomments_test_arr = explode("#-!!-#", $xmldata['res_report_comments']);
            $performing_facility = $xmldata['performing_lab'];

            $test_count = count($test_arr) - 1;

            /* HARD CODED */
            $source = "source";
            $report_notes = "report_notes";
            $comments = 'comments';
            /* HARD CODED */

            $prev_seq = "1";


            for ($index = 0; $index < $test_count; $index++) { //ITERATING THROUGH NO OF TESTS IN AN ORDER.
                $has_subtest = 0;    //FLAG FOR INDICATING IF ith TEST HAS SUBTEST OR NOT
                $testdetails = $test_arr[$index]; // i th  test

                if (trim($testdetails) <> "") { //CHECKING IF THE RESULT CONTAINS DATA FOR THE TEST
                    //SEPERATES TEST SPECIFIC DETAILS
                    $testdetails_arr = explode("#!#", $testdetails);
                    list($test_code, $profile_title, $code_suffix, $order_title, $spec_collected_time, $spec_received_time, $res_reported_time) = $testdetails_arr;

                    $order_seq = $this->getResultTable()->getProcedureOrderSequence($data['procedure_order_id'], $test_code);

                    if (empty($order_seq)) {
                        $order_seq = $prev_seq;
                    } else {
                        $prev_seq = $order_seq;
                    }

                    $sql_report = "INSERT INTO procedure_report (procedure_order_id,procedure_order_seq,date_collected,date_report,source,
														specimen_num,report_status,review_status,report_notes) VALUES (?,?,?,?,?,?,?,?,?)";

                    $report_inarray = array($data['procedure_order_id'], $order_seq, $spec_collected_time, $res_reported_time, $source,
                        '', '', 'received', $report_notes);

                    $procedure_report_id = $this->getResultTable()->insertProcedureReport($sql_report, $report_inarray);

                    // RESULT REPORT COMMENTS OF ith TEST	
                    $result_test_comments = $resultcomments_test_arr[$index];

                    //SEPERATES RESULT REPORT COMMENTS OF EACH SUBTEST OF ith TEST
                    $resultcomments_arr = explode("#!!#", $result_test_comments);

                    //RESULT VALUES/DETAILS OF ith TEST
                    $resultdetails_test = $result_test_arr[$index];
                    //SEPERATES RESULT VALUES/DETAILS OF EACH SUBTEST OF ith TEST
                    $resultdetails_subtest_arr = explode("!#@#!", $resultdetails_test);

                    //CHECKING THE NO OF SUBTESTS IN A TEST, IF IT HAS MORE THAN ONE SUBTEST, THE RESULT DETAILS WLL BE ENTERD INTO THE
                    //SUBTEST RESULT DETAILS TABLE, OTHER WISE INSERT DETAILS INTO THE PROCEDURE RESULT TABLE.

                    $no_of_subtests = substr_count($resultdetails_test, "!#@#!"); //IF THERE IS ONE SEPERATOR, THERE WILL BE TWO SUBTESTS, SO ADD ONE TO THE NO OF SEPERATORS
                    if (trim($resultdetails_test) <> "") { //CHECKING IF THE RESULT CONTAINS DATA FOR THE SUBTEST OR TEST DETAILS
                        if ($no_of_subtests < 2) {
                            $subtest_comments = $resultcomments_arr[0];
                            $subtest_comments = str_replace("\n", "\\r\\n", $subtest_comments);
                            $subtest_resultdetails_arr = explode("!@!", $resultdetails_subtest_arr[0]);
                            list($subtest_code, $subtest_name, $result_value, $units, $range, $abn_flag, $result_status, $result_time, $providers_id) = $subtest_resultdetails_arr;

                            $sql_test_result = "INSERT INTO procedure_result(procedure_report_id,result_code,result_text,date,
																		facility,units,result,`range`,abnormal,comments,result_status,order_title,code_suffix,profile_title)
																VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                            $result_inarray = array($procedure_report_id, $subtest_code, $subtest_name, '', $performing_facility, $units, $result_value, $range, $abn_flag,
                                $subtest_comments, $result_status, $order_title, $code_suffix, $profile_title);
                            $this->getResultTable()->insertProcedureResult($sql_test_result, $result_inarray);
                        } else {

                            for ($j = 0; $j < $no_of_subtests; $j++) {
                                $subtest_comments = $resultcomments_arr[$j];
                                $subtest_comments = str_replace("\n", "\\r\\n", $subtest_comments);
                                $subtest_resultdetails_arr = explode("!@!", $resultdetails_subtest_arr[$j]);
                                list($subtest_code, $subtest_name, $result_value, $units, $range, $abn_flag, $result_status, $result_time, $providers_id) = $subtest_resultdetails_arr;

                                $sql_subtest_result = "INSERT INTO procedure_subtest_result(procedure_report_id,subtest_code,subtest_desc,
																				result_value,units,`range`,abnormal_flag,result_status,result_time,providers_id,comments,
																				order_title,code_suffix,profile_title,facility)
																		VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                                $result_inarray = array($procedure_report_id, $subtest_code, $subtest_name, $result_value, $units, $range,
                                    $abn_flag, $result_status, $result_time, $providers_id, $subtest_comments, $order_title, $code_suffix, $profile_title, $performing_facility);
                                $this->getResultTable()->insertProcedureResult($sql_subtest_result, $result_inarray);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * get Lab requisition 
     * @global \Lab\Controller\type $pid
     * @return \Zend\View\Model\JsonModel
     */
    public function getLabRequisitionPDFAction() {
        global $pid;
        $site_dir = $GLOBALS['OE_SITE_DIR'];
        $requisition_dir = $site_dir . "/lab/requisition/";
        if (!class_exists('CouchDB')) {
            require(dirname(__FILE__) . "/../../../../../../../../library/classes/CouchDB.class.php");
        }
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array('procedure_order_id' => $request->getPost('order_id'));
        } elseif ($request->isGet()) {
            $data = array('procedure_order_id' => $request->getQuery('order_id'));
        }

        $curr_status = $this->getResultTable()->getOrderStatus($data['procedure_order_id']);

        if (($curr_status == "requisitionpulled") || ($curr_status == "completed")) {
            $labrequisitionfile = $this->getResultTable()->getOrderRequisitionFile($data['procedure_order_id']);
        } else {
            $cred = $this->getResultTable()->getClientCredentials($data['procedure_order_id']);
            $username = $cred['login'];
            $password = $cred['password'];
            $site_dir = $_SESSION['site_id'];

            $remote_host = trim($cred['remote_host']);

            if (($username == "") || ($password == "")) {
                $return[0] = array('return' => 1, 'msg' => "Lab Credentials not found");
                $arr = new JsonModel($return);
                return $arr;
            } else if ($remote_host == "") {
                $return[0] = array('return' => 1, 'msg' => "Remote Host not found");
                $arr = new JsonModel($return);
                return $arr;
            } else {
                ini_set("soap.wsdl_cache_enabled", "0");
                ini_set('memory_limit', '-1');

                $options = array('location' => $remote_host,
                    'uri' => "urn://zhhealthcare/lab"
                );
                try {
                    $client = new Client(null, $options);
                    $result = $client->getLabRequisition($username, $password, $site_dir, $data['procedure_order_id']); //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID                                                      
                } catch (\Exception $e) {
                    $return[0] = array('return' => 1, 'msg' => "Could not connect to the web service");
                    $arr = new JsonModel($return);
                    return $arr;
                }
            }
        }

        // Ajax Handling (Result success or failed)  
        if ($request->isPost()) {
            if ($result['status'] == 'failed') {
                $return[0] = array('return' => 1, 'msg' => xlt($result['content']));
                $arr = new JsonModel($return);
                return $arr;
            } else { //IF THE REQUISITION RETURNS VALID OUTPUT
                if (($curr_status <> "requisitionpulled") && ($curr_status <> "completed")) { //IF THE REQUISITION/RESULT IS ALREADY DOWNLOADED
                    if ($GLOBALS['document_storage_method'] == 1) {
                        $couch = new CouchDB();
                        $docname = $_SESSION['authId'] . " " . $pid . date("%Y-%m-%d H:i:s") . "_req";
                        $labrequisitionfile = $couch->stringToId($docname);
                        $json = json_encode($result['content']);
                        $db = $GLOBALS['couchdb_dbase'];
                        $docdata = array($db, $labrequisitionfile, $pid, '', 'application/pdf', $json);
                        $resp = $couch->check_saveDOC($docdata);
                        if (!$resp->id || !$resp->rev) {
                            $docdata = array($db, $labrequisitionfile, $pid, '');
                            $resp = $couch->retrieve_doc($docdata);
                            $labrequisitionfile = $resp->_id;
                            $revid = $resp->_rev;
                        } else {
                            $labrequisitionfile = $resp->id;
                            $revid = $resp->rev;
                        }
                        if (!$labrequisitionfile && !$revid) { //if couchdb save failed
                            $error .= "<font color='red'><b>" . xl("The file could not be saved to CouchDB.") . "</b></font>\n";
                            if ($GLOBALS['couchdb_log'] == 1) {
                                ob_start();
                                var_dump($resp);
                                $couchError = ob_get_clean();
                                $log_content = date('Y-m-d H:i:s') . " ==> Uploading document: " . $fname . "\r\n";
                                $log_content .= date('Y-m-d H:i:s') . " ==> Failed to Store document content to CouchDB.\r\n";
                                $log_content .= date('Y-m-d H:i:s') . " ==> Document ID: " . $labrequisitionfile . "\r\n";
                                $log_content .= date('Y-m-d H:i:s') . " ==> " . print_r($docdata, 1) . "\r\n";
                                $log_content .= $couchError;
                                $this->document_upload_download_log($pid, $log_content); //log error if any, for testing phase only
                            }
                        }
                    }
                    if ($GLOBALS['document_storage_method'] == 0) {
                        $labrequisitionfile = "labrequisition_" . gmdate('YmdHis') . ".pdf";
                        if (!is_dir($requisition_dir)) {
                            mkdir($requisition_dir, 0777, true);
                        }
                        $fp = fopen($requisition_dir . $labrequisitionfile, "wb");
                        fwrite($fp, base64_decode($result['content']));
                    }
                    $status_res = $this->getResultTable()->changeOrderRequisitionStatus($data['procedure_order_id'], "requisitionpulled", $labrequisitionfile);
                }
                $return[0] = array('return' => 0, 'order_id' => $data['procedure_order_id']);
                $arr = new JsonModel($return);
                return $arr;
            }
        }
        if ($labrequisitionfile <> "") {
            while (ob_get_level()) {
                ob_get_clean();
            }

            if ($GLOBALS['document_storage_method'] == 1) {
                $labrequisitionfilename = "labrequisition_" . gmdate('YmdHis') . ".pdf";
                $couch = new CouchDB();
                $resdocdata = array($GLOBALS['couchdb_dbase'], $labrequisitionfile);
                $resp = $couch->retrieve_doc($resdocdata);
                $content = $resp->data;
                if ($content == '' && $GLOBALS['couchdb_log'] == 1) {
                    $log_content = date('Y-m-d H:i:s') . " ==> Retrieving document\r\n";
                    $log_content = date('Y-m-d H:i:s') . " ==> URL: " . $url . "\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> CouchDB Document Id: " . $couch_docid . "\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> CouchDB Revision Id: " . $couch_revid . "\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> Failed to fetch document content from CouchDB.\r\n";
                    $log_content .= date('Y-m-d H:i:s') . " ==> Will try to download file from HardDisk if exists.\r\n\r\n";
                    $this->document_upload_download_log($d->get_foreign_id(), $log_content);
                    die(xl("File retrieval from CouchDB failed"));
                }
                if (!is_dir($GLOBALS['OE_SITE_DIR'] . '/documents/temp/')) {
                    mkdir($GLOBALS['OE_SITE_DIR'] . '/documents/temp/', 0777, true);
                }
                $tmpcouchpath = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/couch_' . date("YmdHis") . $labrequisitionfilename;
                $fh = fopen($tmpcouchpath, "w");
                fwrite($fh, base64_decode($content));
                fclose($fh);
                header('Content-Disposition: attachment; filename=' . $labrequisitionfilename);
                header("Content-Type: application/octet-stream");
                header("Content-Length: " . filesize($tmpcouchpath));
                readfile($tmpcouchpath);
                unlink($tmpcouchpath);
            }
            if ($GLOBALS['document_storage_method'] == 0) {
                header('Content-Disposition: attachment; filename=' . $labrequisitionfile);
                header("Content-Type: application/octet-stream");
                header("Content-Length: " . filesize($requisition_dir . $labrequisitionfile));
                readfile($requisition_dir . $labrequisitionfile);
            }
            exit;
        }
    }

    /**
     * Result entry action
     * @global \Lab\Controller\type $pid
     * @return type
     */
    public function resultEntryAction() {
        global $pid;
        $form = new ResultForm();
        $statuses_abn = $this->CommonPlugin()->getList("proc_res_abnormal");
        $form->get('abnormal[]')->setValueOptions($statuses_abn);
        $statuses_units = $this->CommonPlugin()->getList("proc_unit");
        $form->get('units[]')->setValueOptions($statuses_units);
        $statuses_res = $this->CommonPlugin()->getList("ord_status");
        $form->get('status[]')->setValueOptions($statuses_res);
        $this->layout()->saved = $this->params('saved');
        if ($pid) {
            $form->get('patient_id')->setValue($pid);
            $search_pid = $pid;
        }
        $form->get('search_patient')->setValue($this->getResultTable()->getPatientName($pid));
        $request = $this->getRequest();
        $from_dt = null;
        $to_dt = null;
        if ($request->isPost()) {
            $search_pid = $request->getPost()->patient_id;
            $form->get('search_patient')->setValue($this->getResultTable()->getPatientName($search_pid));
            $from_dt = $request->getPost()->search_from_date;
            $to_dt = $request->getPost()->search_to_date;
            $form->get('patient_id')->setValue($search_pid);
            $form->get('search_from_date')->setValue($from_dt);
            $form->get('search_to_date')->setValue($to_dt);
        }
        $this->layout()->res = $this->getResultTable()->listResults($search_pid, $from_dt, $to_dt);
        $result = $this->CommonPlugin();
        return array('form' => $form, "common_plugin" => $result);
    }

    /**
     * save result entry action
     * @return type
     */
    public function saveResultEntryAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->getResultTable()->saveResultEntryDetails($request->getPost(), $request->getFiles() );
            return $this->redirect()->toRoute('result', array('action' => 'resultEntry', 'saved' => 'yes'));
        }
    }

    /**
     * Cancel order
     * @return type
     */
    public function cancelAction() {
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            $this->getResultTable()->cancelOrder($request->getPost());
            return $response->setContent(\Zend\Json\Json::encode(array('response' => true)));
        }
    }

    /**
     * 
     * @return \Zend\View\Model\JsonModellistEncountersAction
     */
    public function listEncountersAction() {
        $encounters = $this->getResultTable()->listEncounters();
        $data = new JsonModel($encounters);
        return $data;
    }

    /*     * Requisition pdf action
     * 
     */

    public function requisitionPdfAction() {
        $labOrderid = $this->params('id');
        global $pid;
        $SITE_PATH = $GLOBALS['OE_SITES_BASE'] . '/' . $_SESSION['site_id'] . '/';
        $account_no = '';
        $orderDetails = $this->getResultTable()->getRequisition($labOrderid);
        $orders = $this->getResultTable()->getRequisitiontests($labOrderid);
        $aoeans = $this->getResultTable()->getAoeAnswers($labOrderid);
        $vitals = $this->getResultTable()->getVitalDetails($orderDetails['patient_id']);
        $tests = array();

        foreach ($orders as $order) {
            $diagnosis = '';
            $diagArr = explode(';', $order['diagnoses']);
            foreach ($diagArr as $dta) {
                $diag = explode(':', $dta);
                $diagnosis .= $diag[1] . ';';
            }
            $order['diagnoses'] = rtrim($diagnosis, ';');

            array_push($tests, $order);
        }
        $aoes = array();
        foreach ($aoeans as $aoe) {
            array_push($aoes, $aoe);
        }
        $procOrderId = $orderDetails['procedure_order_id'];
        $site_id = $_SESSION['site_id'];
        $orderId = $site_id . '-' . $procOrderId;

        $cred = $this->getResultTable()->getClientCredentials($labOrderid);
        $username = $cred['login'];
        $password = $cred['password'];
        $remote_host = trim($cred['remote_host']);
        if (($username == "") || ($password == "")) {
            die("Lab Credentials not found");
        } else if ($remote_host == "") {
            die("Remote Host not found");
        } else {
            ini_set("soap.wsdl_cache_enabled", "0");
            ini_set('memory_limit', '-1');
            $options = array('location' => $remote_host,
                'uri' => "urn://zhhealthcare/lab"
            );
            try {
                $client = new Client(null, $options);
                $result = $client->getInsuranceCode($username, $password, $site_id, $orderDetails['mirth_lab_id'], $orderDetails['insname'], $orderDetails['line1'], $orderDetails['inscity'], $orderDetails['insstate'], $orderDetails['zip']); //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID
            } catch (\Exception $e) {
                die("Could not connect to the web service");
            }
            if ($result['ins_code'] == "Credentials failed") {
                die("Credentials failed");
            } else if ($orderDetails['secinsname']) {
                $resultSec = $client->getInsuranceCode($username, $password, $site_id, $orderDetails['mirth_lab_id'], $orderDetails['secinsname'], $orderDetails['secline1'], $orderDetails['secinscity'], $orderDetails['secinsstate'], $orderDetails['seczip']); //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID
            }
        }

        if (strtolower($orderDetails['mirth_lab_name']) == 'dianon') {
            //$account_no = 'TSAEF10';//'TUAEF01';
            $account_no = $orderDetails['send_fac_id'];
            // $orderId   = "T-AEF00*".$orderId;
            $barcodeOptions = array('text' => $orderId, 'barHeight' => 30);
            $rendererOptions = array();
            $imageResource = Barcode::draw(
                            'code128', 'image', $barcodeOptions, $rendererOptions
            );
            $img_name = date("YmdHis") . substr((string) microtime(), 1, 8) . ".jpg";
            $barcodePath = $SITE_PATH . '/dianon';
            if (!file_exists($barcodePath)) {
                mkdir($barcodePath, 0777);
            }
            if (!imagejpeg($imageResource, $barcodePath . '/' . $img_name)) {
                return;
            }
            $type = pathinfo($barcodePath . '/' . $img_name, PATHINFO_EXTENSION);
            $data = file_get_contents($barcodePath . '/' . $img_name);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            unlink($barcodePath . '/' . $img_name);
            $img_name = $base64;
            $ins_code = $result['ins_code'];
            $ins_codeSec = $resultSec['ins_code'];
            $htmlViewPart = new ViewModel();
            $htmlViewPart->setTerminal(true)
                    ->setTemplate('lab/result/requisition-pdf')
                    ->setVariables(array(
                        'orderId' => $orderId,
                        'labOrderid' => $labOrderid,
                        'account_no' => $account_no,
                        'ins_code' => $ins_code,
                        'ins_codeSec' => $ins_codeSec,
                        'orderDetails' => $orderDetails,
                        'img_name' => $img_name,
                        'extBarcodeIamge' => $extBarcode,
                        'tests' => $tests,
                        'aoes' => $aoes
            ));
            $htmlOutput = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart);
            $html2pdf = new HTML2PDF('P', 'A4', 'en');
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($htmlOutput);
            $html2pdf->Output('requistion' . $labOrderid . '.pdf', 'D');
            exit;
        } elseif (strtolower($orderDetails['mirth_lab_name']) == 'labcorp') {
            $barcodePath = $SITE_PATH . '/labcorp';
            if (!file_exists($barcodePath)) {
                mkdir($barcodePath, 0777);
            }
            //$account_no = '90016315';
            $account_no = $orderDetails['send_fac_id'];
            $clinicalInfoAoe = 0;
            $clinicalInfoComm = 0;
            $clinical_info = "";
            $clinical_info_first = "^SRC:";
            $newReqCheck = 1;
            $dupCheck = 0;
            $splitRule = 0;
            $orderProceduresArr = array();
            $cntTest = 1;
            foreach ($tests as $order) {
                if ($cntTest > 40) {
                    $orderProceduresArr[] = $order['procedure_order_seq'];
                    $newReqCheck = 2;
                }
                $cntTest = $cntTest + 1;
            }

            foreach ($aoes as $aoe) {
                if (empty($orderProceduresArr)) {
                    if (substr($aoe['hl7_segment'], 0, 3) == 'ZSA' || substr($aoe['hl7_segment'], 0, 3) == 'ZCY' || substr($aoe['hl7_segment'], 0, 3) == 'ZBL') {
                        if (($dupCheck > 0) && ($lastAoe != substr($aoe['hl7_segment'], 0, 3))) {
                            if (!in_array($aoe['procedure_order_seq'], $orderProceduresArr)) {
                                $orderProceduresArr[] = $aoe['procedure_order_seq'];
                                $newReqCheck = $newReqCheck + 1;
                            }
                        }
                        $dupCheck = $dupCheck + 1;
                        $lastAoe = substr($aoe['hl7_segment'], 0, 3);
                    }
                }
            }
            if (empty($orderProceduresArr) && count($tests) > 1) {
                foreach ($tests as $order) {
                    if ($order['procedure_code'] == '488162' || $order['procedure_code'] == '500918' || $order['procedure_code'] == '980004') {
                        $orderProceduresArr[] = $order['procedure_order_seq'];
                        $splitRule = 1;
                        $newReqCheck = $newReqCheck + 1;
                    }
                }
            }

            ///creating 2nd pdf file if needed $orderProceduresArr

            $testsArr1 = array();
            $testsArr2 = array();
            $abnArr = array();
            foreach ($tests as $order) {
                if (!in_array($order['procedure_order_seq'], $orderProceduresArr)) {
                    array_push($testsArr1, $order);
                } else {
                    array_push($testsArr2, $order);
                }
                array_push($abnArr, $order);
            }

            if (empty($testsArr1) && $splitRule) {
                $testsArr1 = $testsArr2;
                $newReqCheck = 1;
            }
            $aoesArr1 = array();
            $aoesArr2 = array();
            foreach ($aoes as $aoe) {
                if (!in_array($aoe['procedure_order_seq'], $orderProceduresArr)) {
                    array_push($aoesArr1, $aoe);
                } else {
                    array_push($aoesArr2, $aoe);
                }
            }

            $abnFlag = '';
            ////############################ABN WEBSERVICE#################################################
            if ($orderDetails['freeb_type'] == '2' && $orderDetails['abn'] == 'Yes') { //ABN is needed only for medicare           
                $abnUserName = 'emrt_zhhealthcare';
                $abnPassword = 'jLXptEL31C';
                $vendor = 'ZHHealthcare';
                $patName = $orderDetails['lname'] . " " . $orderDetails['fname'] . " " . $orderDetails['mname'];
                $abnUrl = "https://labcorpemr:labcorp123@emrsvcs-stage.labcorpbeacon.com:7360/emr-service/services/emrService.wsdl";
                $ABNDiagsArr = array();
                $ABNTestsArr = array();
                foreach ($abnArr as $testsAbn) {
                    if ($testsAbn['procedure_code']) {
                        array_push($ABNTestsArr, $testsAbn['procedure_code']);
                        $diagAbnStr = $diagAbnStr . ';' . $testsAbn['diagnoses'];
                    }
                }
                //diag codes
                $diagAbnArr = explode(';', $diagAbnStr);
                foreach ($diagAbnArr as $diagAbn) {
                    if ($diagAbn) {
                        array_push($ABNDiagsArr, $diagAbn);
                    }
                }
                try {
                    $client = new \SoapClient($abnUrl);
                    $wss_header = new WsseAuthHeader($abnUserName, $abnPassword);
                    $client->__setSoapHeaders(Array($wss_header));
                    $req = array(
                        "checkABNRequest" => array(
                            "clientCode" => $vendor,
                            "abnRequest" => array(
                                "accountNumber" => $account_no,
                                "diagCodes" => $ABNDiagsArr,
                                "testCodes" => $ABNTestsArr,
                                "patientName" => $patName),
                            "IncludePdfInResponse" => true
                    ));

                    $result = $client->__soapCall('checkABN', $req);
                    //$result = $client->checkABN($req);   
                } catch (SoapFault $e) {
                    echo 'ABN Request Error..';
                    exit;
                }
                $AbnCcontent = $result->abnContents;
                if ($AbnCcontent) {
                    $abnFlag = 'Yes';
                    $fileFolder = $barcodePath . '/' . $tempFolder . '/' . $labOrderid;
                    if (!file_exists($fileFolder)) {
                        mkdir($fileFolder, 0777);
                    }
                    $tmpcouchpath = $fileFolder . '/ABN-' . $labOrderid . '.pdf';
                    $fh = fopen($tmpcouchpath, "w");
                    fwrite($fh, $AbnCcontent);
                    fclose($fh);
                }
            }
            ///####################END OF CODE Labcorp ABN Call#####################333
            $nameARr = array();
            $testsMaster = array();
            $aoesMaster = array();

            for ($reqCnt = 1; $reqCnt <= $newReqCheck; $reqCnt++) {
                if ($reqCnt == 1) {
                    $testsMaster = $testsArr1;
                    $aoesMaster = $aoesArr1;
                } else {
                    $testsMaster = $testsArr2;
                    $aoesMaster = $aoesArr2;
                }
                if ($newReqCheck > 1) {
                    $orderId = $site_id . '-' . $procOrderId . '-' . $reqCnt;
                } else {
                    $orderId = $site_id . '-' . $procOrderId;
                }
                $testArray = array();
                $testArrayDiag = array();
                $dob = str_replace('-', '', $orderDetails['DOB']);
                $gender = ($orderDetails['sex'] == 'Male' ) ? 'M' :
                        (($orderDetails['sex'] == 'Female' ) ? 'F' :
                                'NI');
                $home_phone = str_replace('-', '', $orderDetails['phone_home']);
                if ($orderDetails['billto'] != 'C') {
                    $subscriber_phone = str_replace('-', '', $orderDetails['gtrsubscriber_phone']);
                }
                $collection_details = explode(' ', $orderDetails['date_collected']);
                $collection_date = str_replace('-', '', $collection_details[0]);
                $collection_time = str_replace(':', '', $collection_details[1]);

                $billType = '';
                $policyNoP19 = '';
                $policyNoP53 = '';
                $policyNo40 = '';
                if ($orderDetails['billto'] == 'T') {
                    $policyNo40 = $orderDetails['policy_number'];
                    // $billType = 'XI';
                    if ($orderDetails['freeb_type'] == '2') {
                        $policyNoP19 = $orderDetails['policy_number'];
                        $policyNo40 = '';
                        //  $billType = '05';
                    } else if ($orderDetails['freeb_type'] == '3') {
                        $policyNoP53 = $orderDetails['policy_number'];
                        $policyNo40 = '';
                        //  $billType = $result['state'];
                    } else if ($orderDetails['freeb_type'] == '19') {
                        // $billType = $result['insurance_code'];
                    }
                } else if ($orderDetails['billto'] == 'P') {
                    $billType = '04';
                } else if ($orderDetails['billto'] == 'C') {
                    $billType = '03';
                }
                if ($orderDetails['billto'] != 'T') {
                    $orderDetails['group_number'] = '';
                    $orderDetails['policy_number'] = '';
                    $orderDetails['secpolicy_number'] = '';
                    $orderDetails['secgroup_number'] = '';
                }
                $cms_d = $orderDetails['cms_id']; // chcek it an change
                $cms_d = "";
                $subscriber_relation = '';
                if ($orderDetails['billto'] == 'T') {
                    $subscriber_relation = ($orderDetails['subscriber_relationship'] == '' ) ? '' :
                            ($orderDetails['subscriber_relationship'] == 'self' ) ? '1' :
                                    (($orderDetails['subscriber_relationship'] == 'spouse' ) ? '2' :
                                            '3');
                }
                $workers_comp = ($orderDetails['freeb_type'] == 25) ? 'Y' : 'N';
                $pServiceCenter = ($orderDetails['cor'] == 'Yes') ? 'PA' : '';

                $cntSpm = 1;
                $spmSeg = '';
                $p79SpmNo = "";
                foreach ($testsMaster as $test) {
                    //  echo '<pre>'; print_r($test); exit;
                    array_push($testArray, $test['procedure_code']);
                    $diagarr = explode(';', rtrim($test['diagnoses'], ';'));
                    array_push($testArrayDiag, implode('^', $diagarr));
                    if ($test['specimen_details']) {
                        $spmArr = explode('#$@$#', $test['specimen_details']);
                        foreach ($spmArr as $spmGroup) {
                            $spm = explode('@~#~@', $spmGroup);
                            $spm1 = explode('@|@', $spm[0]);
                            $spm2 = explode('#@!#@', $spm[1]);
                            $spm3 = explode('~~', $spm2[0]);
                            $spm4 = explode('~~', $spm2[1]);
                            $spm5 = explode('~~', $spm2[2]);
                            if ($spmSeg) {
                                $spmSeg .='~';
                            }
                            $spmSeg .= $test['procedure_code'] . "^" . $spm1[0] . "^" . $spm1[2] . '^' . $spm3[1] . '^' . $spm4[1] . '^' . $spm5[1] . '^' . $spm[2];
                            $cntSpm = $cntSpm + 1;
                        }
                    }
                }
                $coursesyCopy = '';
                $crtCnt = 0;
                //$orderDetails['courtesy_copy']
                if ($orderDetails['courtesy_copy']) {
                    $crtsyCopy = explode('@@##', $orderDetails['courtesy_copy']);
                    foreach ($crtsyCopy as $cort) {
                        if ($crtCnt < 4) {
                            $crtCnt = $crtCnt + 1;
                            $cort1 = explode('#@#', $cort);
                            if ($cort1[0] == 'P') {
                                if ($coursesyCopy) {
                                    $coursesyCopy .= "|";
                                }
                                $coursesyCopy .= "P";
                            } else {
                                if ($coursesyCopy) {
                                    $coursesyCopy .= "|";
                                }
                                $crt2 = explode('@#@', $cort1[1]);
                                $coursesyCopy .= $cort1[0] . "^" . $crt2[0] . "^" . $crt2[1];
                            }
                        }
                    }
                }
                if ($crtCnt < 4) {
                    for ($icnt = $crtCnt; $icnt < 4; $icnt++) {
                        if ($coursesyCopy) {
                            $coursesyCopy .= "|";
                        }
                        $coursesyCopy .= "^^";
                    }
                }
                $coursesyCopy .='|';
                //end 
                foreach ($aoesMaster as $aoe123) {
                    if ($aoe123['question_text'] == 'Height (inches)') {
                        $height = ltrim($aoe123['answer'], '0');
                    } elseif ($aoe123['question_text'] == 'Weight (ounces)') {
                        $wtonc = ltrim($aoe123['answer'], '0');
                    } elseif ($aoe123['question_text'] == 'Collection/Urine Volume (Quantity/Field Value)') {
                        $collection_vol = ltrim($aoe123['answer'], '0');
                    } elseif ($aoe123['question_text'] == 'Source of Specimen') {
                        $clinical_info .= str_pad($aoe123['answer'], 21, " ");
                        $clinicalInfoAoe = 1;
                    } else if ($aoe123['hl7_segment'] == 'ZCI2.1') {
                        if (substr($aoe123['question_code'], 0, 5) == 'HTWTT') {
                            $wtpnd = ltrim($aoe123['answer'], '0');
                        }
                    }
                }

                //internal_comments
                if ($orderDetails['internal_comments']) {
                    $cliInfo = ' ' . substr($orderDetails['internal_comments'], 0, 68);
                    if (!$clinicalInfoAoe) {
                        $clinical_info .= str_pad('', 21, " ");
                    }
                    $clinical_info .= str_pad($cliInfo, 42, " ");
                    $clinicalInfoComm = 1;
                };
                if ($clinicalInfoAoe || $clinicalInfoComm) {
                    if (!$clinicalInfoComm) {
                        $clinical_info .= str_pad('', 42, " ");
                    }
                    $clinical_info = $clinical_info_first . $clinical_info;
                } else {
                    $clinical_info = '^';
                }
                if (!trim($orderDetails['parent_last'])) {
                    $orderDetails['parent_city'] = '';
                    $orderDetails['parent_state'] = '';
                    $orderDetails['parent_zip'] = '';
                    $orderDetails['parent_phone'] = '';
                }
                $barcode2d_1 = "H|ZHEMR4.1|" . date('Ymd') . "|E|^^|" . $pServiceCenter . "|\r";
                $barcode2d_1 .= "P|" . $pid . "||||||" . $account_no . "||" . $orderDetails['lname'] . "^" . $orderDetails['fname'] . "^" . $orderDetails['mname'] . "|" . $dob . "|"; // 0 - 10    
                $barcode2d_1 .= $gender . "|" . $orderDetails['ss'] . "|" . $orderDetails['street'] . "|" . $orderDetails['city'] . "|" . $orderDetails['state'] . "|" . str_replace('-', '', $orderDetails['postal_code']) .
                        "|" . $home_phone . "|" . $billType . "|" . $policyNoP19 . "|" . $orderDetails['gtrsubscriber_lname'] . "^" . $orderDetails['gtrsubscriber_fname'] . "^" . $orderDetails['gtrsubscriber_mname'] . "|"; //11-20            
                $barcode2d_1 .= "|" . $orderDetails['gtrsubscriber_street'] . "|" . $orderDetails['gtrsubscriber_city'] . "|" . $orderDetails['gtrsubscriber_state'] . "|" . str_replace('-', '', $orderDetails['gtrsubscriber_postal_code']) .
                        "|" . $orderDetails['gtrsubscriber_employer'] . "|" . $subscriber_relation . "|" . $orderDetails['uid'] . "|" . $orderDetails['ulname'] . "^" . $orderDetails['ufname'] . "^" . $orderDetails['umname'] . "|" . $cms_d . "|"; // 21 - 30
                $barcode2d_1 .= "|" . $orderDetails['uupin'] . "|||" . $orderDetails['insname'] . "|" . $orderDetails['line1'] . "^" . $orderDetails['line2'] . "|" . $orderDetails['inscity'] . "|" . $orderDetails['insstate'] . "|" . str_replace('-', '', $orderDetails['zip']) . "|" . $policyNo40 . "|"; // 31 - 40 
                $barcode2d_1 .= $orderDetails['group_number'] . "|||" . $orderDetails['secinsname'] . "|" . $orderDetails['secline1'] . "^" . $orderDetails['secline2'] . "|" . $orderDetails['secinscity'] . "|" . $orderDetails['secinsstate'] . "|" . str_replace('-', '', $orderDetails['seczip']) . "|" . $orderDetails['secpolicy_number'] . "|" . $orderDetails['secgroup_number'] . "|"; // 41 - 50   secgroup_number
                $barcode2d_1 .= "|" . $workers_comp . "|" . $policyNoP53 . "|^^^^^^^^^^^^^^|^^^^^^^|" . $subscriber_phone . "|" . $orderId . "|" . $orderDetails['pubpid'] . "|||"; // 51 - 60
                $barcode2d_1 .= "|||||||" . $wtpnd . "|" . $wtonc . "|" . $height . "|"; // 61 - 70
                $barcode2d_1 .= $orderDetails['unpi'] . "|" . $coursesyCopy . "|||^||"; //71 - 80
                if ($orderDetails['billto'] != 'C') {
                    $barcode2d_1 .= "||||" . $orderDetails['parent_last'] . "^" . $orderDetails['parent_first'] . "^" . $orderDetails['parent_mid'] . "|"; // 81 - 85  
                    $barcode2d_1 .= $orderDetails['parent_add1'] . "^" . $orderDetails['parent_add2'] . "^" . $orderDetails['parent_city'] . "^" . $orderDetails['parent_state'] . "^" . str_replace('-', '', $orderDetails['parent_zip']) . "|" . str_replace('-', '', $orderDetails['parent_phone']) . "|"; // 86 - 89           
                } else {
                    $barcode2d_1 .= "||||^^|^^^^||"; // 86 - 89           
                }
                $barcode2d_1 .= $vitals['waist'] . "|" . $vitals['bps'] . "^" . $vitals['bpd'] . "||\r"; // 86 - 89           
                $barcode2d_1 .= $this->getLabcorpSegmentBarcodeC($aoesMaster, $collection_date, $collection_vol, $clinical_info, $collection_time) . "\r"; // C
                $barcode2d_1 .= $this->getLabcorpSegmentBarcodeA($aoesMaster) . "\r"; // A		
                $barcode2d_1 .= $this->getLabcorpSegmentBarcodeM($aoesMaster) . "\r"; // A	   
                $barcode2d_1 .= "B|||||||||||||||||||||\r" .
                        "K|^|||||||||||||||^^^^||||||\r" .
                        "I|^^|^^|^^|^^|^^|^^|^^|^^|\r" .
                        "T|";
                $barcode2d_1 .= implode('|', $testArray);
                for ($i = count($testArray); $i < 40; $i++) {
                    $barcode2d_1 .= "|";
                }
                $barcode2d_1 .= "\r";
                if ($spmSeg) {
                    $barcode2d_1 .= "S|" . $spmSeg . "|\r";
                } else {
                    $barcode2d_1 .= "S|^^^^^^|\r";
                }
                $aoeMasterArray = $this->createLabcorpAoeArray($aoesMaster);
                //echo '<pre>';
                // print_r($aoeMasterArray); exit;
                $barcodeDiag = "D|";
                $barcodeDiag .= implode('^', $testArrayDiag);
                $barcodeDiag .= "||\r";
                $barcodeLength1 = "L|";
                $barcodeLength2 = "|\r";
                $barcodeError = "E|0|";
                $length = strlen($barcode2d_1) + strlen($barcodeDiag) + strlen($barcodeLength1) + strlen($barcodeLength2) + strlen($barcodeError);
                //checking if total length exceeds 1040 2d supports maximum 1040 charectors
                $checkLength = $length + strlen($length);
                $extBarCode = false;
                $extPageCount = 1;
                if ($checkLength > 1040) {
                    $extBarCode = true;
                    $extPageCount = 2;
                    $barLength = strlen($barcode2d_1) + strlen($barcodeLength1) + strlen($barcodeLength2) + strlen($barcodeError);
                    $barcodeError = "E|5|";
                    $fullLength = $barLength + strlen($barLength);
                    $barcode2d = $barcode2d_1 . $barcodeLength1 . $fullLength . $barcodeLength2 . $barcodeError;
                    $barcodeError1 = "E|0|";
                    $diagLength = strlen($barcodeDiag);
                    $expanded2d = $barcodeDiag . $barcodeLength1 . $diagLength . $barcodeLength2 . $barcodeError1;
                } else {
                    $barcode2d = $barcode2d_1 . $barcodeDiag . $barcodeLength1 . $checkLength . $barcodeLength2 . $barcodeError;
                }
                $fh = fopen($barcodePath . '/' . $orderId . '.txt', 'w');
                fwrite($fh, $barcode2d);
                fclose($fh);
                $pdf = new TCPDF2DBarcode($barcode2d, 'PDF417');
                $fileName = 'my2dtemp.png';
                $pdf->getBarcodePNG($barcodePath, $fileName, 4, 2);
                $data = file_get_contents($barcodePath . '/' . $fileName);
                $base64 = 'data:image/PNG;base64,' . base64_encode($data);
                unlink($barcodePath . '/' . $fileName);
                $img_name = $base64;
                $orderDetails['waist'] = $vitals['waist'];
                $orderDetails['bps'] = $vitals['bps'];
                $orderDetails['bpd'] = $vitals['bpd'];
                if ($expanded2d) {
                    $fh = fopen($barcodePath . '/' . $orderId . '_exp.txt', 'w');
                    fwrite($fh, $expanded2d);
                    fclose($fh);
                    $pdf = new TCPDF2DBarcode($expanded2d, 'PDF417');
                    $fileName1 = "my2dtemp_ext.png";
                    $pdf->getBarcodePNG($barcodePath, $fileName1);
                    $dataExt = file_get_contents($barcodePath . '/' . $fileName1);
                    $base64Ext = 'data:image/PNG;base64,' . base64_encode($dataExt);
                    unlink($barcodePath . '/' . $fileName1);
                    $extBarcode = $base64Ext;
                }
                $htmlViewPart = new ViewModel();
                $htmlViewPart->setTerminal(true)
                        ->setTemplate('lab/result/requisition-pdf')
                        ->setVariables(array(
                            'orderId' => $orderId,
                            'labOrderid' => $labOrderid,
                            'account_no' => $account_no,
                            'ins_code' => $ins_code,
                            'orderDetails' => $orderDetails,
                            'img_name' => $img_name,
                            'extBarcodeIamge' => $extBarcode,
                            'tests' => $testsMaster,
                            'aoes' => $aoesMaster,
                            'extBarCodeCheck' => $extBarCode,
                            'extPageCount' => $extPageCount,
                            'newReqCheck' => $newReqCheck,
                            'abnFlag' => $abnFlag,
                            'aoeMasterArray' => $aoeMasterArray
                ));
                $htmlOutput = $this->getServiceLocator()
                        ->get('viewrenderer')
                        ->render($htmlViewPart);
                $tempFolder = $barcodePath; //exit($htmlOutput);
                //$dompdf = new \DOMPDF();
                $html2pdf = new HTML2PDF('P', 'A4', 'en');
                if ($newReqCheck == 1 && !$abnFlag) {
                    $html2pdf->pdf->SetDisplayMode('fullpage');
                    $html2pdf->writeHTML($htmlOutput);
                    $html2pdf->Output('requistion' . $labOrderid . '.pdf', 'D');

                    exit;
                } else {
                    $html2pdf->pdf->SetDisplayMode('fullpage');
                    $html2pdf->writeHTML($htmlOutput);
                    if (!file_exists($tempFolder . '/' . $labOrderid)) {
                        mkdir($tempFolder . '/' . $labOrderid, 0777);
                    }
                    $html2pdf->Output($tempFolder . '/' . $labOrderid . '/requistion' . $labOrderid . '_' . $reqCnt . '.pdf', 'F');
                }
            }
            if ($newReqCheck > 1 || $abnFlag == 'Yes') {
                $dwnFileName = $labOrderid . '.zip';
                $zip = new Zip();
                $zip->setArchive($tempFolder . '/' . $dwnFileName);
                $zip->compress($tempFolder . '/' . $labOrderid);
                if (file_exists($tempFolder . '/' . $dwnFileName)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($dwnFileName));
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($tempFolder . '/' . $dwnFileName));
                    readfile($tempFolder . '/' . $dwnFileName);
                }
            }
        }
        exit;
    }

    /**
     * getLabcorpSegmentBarcodeC()
     */
    public function getLabcorpSegmentBarcodeC($aoes, $collection_date, $collection_vol, $clinical_info, $collection_time) {
        $c1_1 = ' ';
        $c1_2 = ' ';
        $c1_3 = ' ';
        $c1_4 = ' ';
        $c1_5 = ' ';
        $c1_6 = ' ';
        $c1_7 = ' ';
        $c1_8 = ' ';
        $c1_9 = ' ';
        $c1_10 = ' ';
        $c2_1 = ' ';
        $c2_2 = ' ';
        $c2_3 = ' ';
        $c2_4 = ' ';
        $c2_5 = ' ';
        $c2_6 = ' ';
        $c3_1 = '';
        $c4_1 = ' ';
        $c4_2 = ' ';
        $c4_3 = ' ';
        $c4_4 = ' ';
        $c4_5 = ' ';
        $c4_6 = ' ';
        $c4_7 = ' ';
        $c4_8 = ' ';
        $c4_9 = ' ';
        $c5_1 = '';
        $c6_1 = '';
        $c7_1 = '';
        $c8_1 = '';
        $c9_1 = '';
        $c10_1 = '';
        $c11_1 = '';
        $c12_1 = '';
        $c13_1 = '';
        $c14_1 = '';
        $c15_1 = ' ';
        $c15_2 = ' ';
        $c15_3 = ' ';
        $c15_4 = ' ';
        $c15_5 = ' ';
        $c15_6 = ' ';
        $c15_7 = ' ';
        $c15_8 = ' ';
        $c16_1 = '';
        $c17_1 = $collection_date;
        $c18_1 = '';
        $c19_1 = $clinical_info;
        $c20_1 = '';
        $c21_1 = '';
        $c22_1 = 'EREQ';
        $c23_1 = 'EEDI';
        $c24_1 = '';
        $c25_1 = $collection_time;
        $c26_1 = '';
        foreach ($aoes as $aoe) { //echo "<pre>"; print_r($aoe); 
            if ($aoe['answer'] != '') {
                if (substr($aoe['hl7_segment'], 0, 3) == 'ZCY') {
                    $c22_1 = '120';
                }
                if ($aoe['hl7_segment'] == 'ZCY1') {
                    $c1_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY2') {
                    $c1_3 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY5') {
                    $c1_4 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY4') {
                    $c1_6 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY37') {
                    $c1_8 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY38') {
                    $c1_9 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY39') {
                    $c1_10 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY9') {
                    $c2_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY7') {
                    $c2_2 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY10') {
                    $c2_3 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY11') {
                    $c2_4 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY8') {
                    $c2_5 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY6') {
                    $c2_6 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY12') {
                    $c3_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY16') {
                    $c4_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY15') {
                    $c4_2 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY18') {
                    $c4_3 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY17') {
                    $c4_5 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY13') {
                    $c4_6 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY19') {
                    $c4_7 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY40') {
                    $c4_8 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY41') {
                    $c4_9 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY20') {
                    $c5_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY21') {
                    $c6_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY22') {
                    $c7_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY23') {
                    $c8_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY24') {
                    $c9_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY25') {
                    $c10_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY26') {
                    $c11_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY27') {
                    $c12_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY28') {
                    $c13_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY29') {
                    $c14_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY30') {
                    $c15_5 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY31') {
                    $c15_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY32') {
                    $c15_3 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY33') {
                    $c15_2 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY34') {
                    $c15_4 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY35') {
                    $c15_6 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY42') {
                    $c15_7 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCY43') {
                    $c15_8 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'OBR9.1' || $aoe['hl7_segment'] == 'ZCI3.1') {
                    $c18_1 = $aoe['answer'];
                } else if ($aoe['hl7_segment'] == 'ZCI4') {
                    $c24_1 = $aoe['answer'];
                }
            }
        }
        $retValue = "C|$c1_1$c1_2$c1_3$c1_4$c1_5$c1_6$c1_7$c1_8$c1_9$c1_10|" .
                "$c2_1$c2_2$c2_3$c2_4$c2_5$c2_6|" .
                "$c3_1|$c4_1$c4_2$c4_3$c4_4$c4_5$c4_6$c4_7$c4_8$c4_9|" .
                "$c5_1|$c6_1|$c7_1|$c8_1|$c9_1|$c10_1|$c11_1|$c12_1|$c13_1|$c14_1|" .
                "$c15_1$c15_2$c15_3$c15_4$c15_5$c15_6$c15_7$c15_8|" .
                "$c16_1|$c17_1|$c18_1|$c19_1|$c20_1|$c21_1|$c22_1|$c23_1|$c24_1|$c25_1|$c26_1|";
        return $retValue;
    }

    /**
     * getLabcorpSegmentBarcodeA()
     */
    public function getLabcorpSegmentBarcodeA($aoes) {
        //  $barcode2d_1 .= "A|||||||||||||||||||||^^|^|^||||||^^|^^^^^|||\r".
        foreach ($aoes as $aoe) {
            if ($aoe['hl7_segment'] == 'ZSA2.1' || $aoe['hl7_segment'] == 'ZSA2.2' || $aoe['hl7_segment'] == 'ZSA2.3') {
                if ($aoe['answer'] != '') {
                    if ($aoe['hl7_segment'] == 'ZSA2.1') {
                        if ($a1_1 == '') {
                            $a1_1 = $aoe['answer'];
                        } else {
                            $a1_1 = $aoe['answer'] . $a1_1;
                        }
                    } else if ($aoe['hl7_segment'] == 'ZSA2.2') {
                        if ($a1_1 == '') {
                            $a1_1 = ':' . $aoe['answer'];
                        } else {
                            $a1_1 = $a1_1 . ':' . $aoe['answer'];
                        }
                    } else if ($aoe['hl7_segment'] == 'ZSA2.3') {
                        if ($a1_1 == '') {
                            $a1_1 = $aoe['answer'];
                        }
                    }
                }
            } else if ($aoe['hl7_segment'] == 'ZSA3.1') {
                if ($aoe['answer'] == 'Y') {
                    $a2_1 = 'LMP';
                }
            } else if ($aoe['hl7_segment'] == 'ZSA4.1') {
                if ($aoe['answer'] == 'Y') {
                    $a2_1 = 'US';
                }
            } else if ($aoe['hl7_segment'] == 'ZSA5.1') {
                if ($aoe['answer'] == 'Y') {
                    $a2_1 = 'EDD';
                }
            } else if ($aoe['hl7_segment'] == 'ZCI2.1' && ((substr($aoe['question_code'], 0, 5) == 'MSONL') || (substr($aoe['question_code'], 0, 5) == 'MSSNT') || (substr($aoe['question_code'], 0, 5) == 'SERIN'))) {
                $a3_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA6') {
                $a4_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'PID10') {
                $a5_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA1') {
                $a6_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA12') {
                $a11_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA5.2') {
                $a12_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA3.2') {
                $a13_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA13') {
                $a16_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA14') {
                $a17_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA2.4') {
                $a18_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA17.1') {
                $a21_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA17.2') {
                $a21_2 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA17.3') {
                $a21_3 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA18.1') {
                $a22_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA18.2') {
                $a22_2 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA19.1') {
                $a23_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA19.2') {
                $a23_2 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA20') {
                $a24_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA21') {
                $a25_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA22') {
                $a26_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA23') {
                $a27_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA24') {
                $a28_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA25.1') {
                $a29_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA25.2') {
                $a29_2 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA25.3') {
                $a29_3 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA26.1') {
                $a30_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA26.2') {
                $a30_2 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA26.3') {
                $a30_3 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA26.4') {
                $a30_4 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA26.5') {
                $a30_5 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA26.6') {
                $a30_6 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA27') {
                $a31_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZSA28') {
                $a32_1 = $aoe['answer'];
            }
        }
        $retValue = "A|$a1_1|$a2_1|$a3_1|$a4_1|$a5_1|$a6_1|||||"; //0-10
        $retValue .= "$a11_1|$a12_1|$a13_1|||$a16_1|$a17_1|$a18_1|||"; //11-20
        $retValue .= "$a21_1^$a21_2^$a21_3|$a22_1^$a22_2|$a23_1^$a23_2|$a24_1|$a25_1|$a26_1|$a27_1|$a28_1|$a29_1^$a29_2^$a29_3|"; //21-29
        $retValue .= "$a30_1^$a30_2^$a30_3^$a30_4^$a30_5^$a30_6|$a31_1|$a32_1|"; //30-40
        return $retValue;
    }

    /**
     * getLabcorpSegmentBarcodeM()
     */
    public function getLabcorpSegmentBarcodeM($aoes) {

        foreach ($aoes as $aoe) {
            if ($aoe['hl7_segment'] == 'ZBL1' && $aoe['answer'] != '') {
                $m1_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZBL2' && $aoe['answer'] != '') {
                $m2_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZBL3' && $aoe['answer'] != '') {
                $m3_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZBL4' && $aoe['answer'] != '') {
                $m4_1 = $aoe['answer'];
            } else if ($aoe['hl7_segment'] == 'ZBL5' && $aoe['answer'] != '') {
                $m5_1 = $aoe['answer'];
            }
        }
        $retValue = "M|$m1_1|$m2_1|$m3_1|$m4_1|$m5_1|";
        return $retValue;
    }

    /**
     * Result pdf action
     */
    public function resultPdfAction($labOrderid) {

        global $pid;
        $site_dir = $GLOBALS['OE_SITE_DIR'];
        $resultdetails_dir1 = $site_dir . "/lab/resultdetails/";
        $resultdetails_dir = $site_dir . "/lab/resultdetails/" . $labOrderid . '-' . gmdate('YmdHis') . '/';

        $cred = $this->getResultTable()->getClientCredentials($labOrderid);

        $username = $cred['login'];
        $password = $cred['password'];
        $remote_host = trim($cred['remote_host']);
        $site_dir = $GLOBALS['site_id'];

        ini_set("soap.wsdl_cache_enabled", "0");
        ini_set('memory_limit', '-1');

        $options = array('location' => $remote_host,
            'uri' => "urn://zhhealthcare/lab"
        );
        $client = new Client(null, $options);
        $result = $client->getLabResultDetails($username, $password, $site_dir, $labOrderid);  //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID       
        $result_content = $client->getLabResult($username, $password, $site_dir, $labOrderid);  //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID
        $labresultdetailsfile = "labresultdetails_" . gmdate('YmdHis') . ".xml";

        if (!is_dir($resultdetails_dir1)) {
            mkdir($resultdetails_dir1, 0777, true);
        }
        if (!is_dir($resultdetails_dir)) {
            mkdir($resultdetails_dir, 0777, true);
        }

        $fp = fopen($resultdetails_dir1 . $labresultdetailsfile, "wb");
        fwrite($fp, $result);

        $reader = new Config\Reader\Xml();
        $xmldata = $reader->fromFile($resultdetails_dir1 . $labresultdetailsfile);
        if ($xmldata['Order_1'] && $result_content['content']) {
            $html2pdf = new HTML2PDF('P', 'A4', 'en');

            ///first result
            $htmlViewPart = new ViewModel();
            $htmlViewPart->setTerminal(true)
                    ->setTemplate('lab/result/result-pdf')
                    ->setVariables(array(
                        'xmldata' => $xmldata['Order'],
            ));
            $htmlOutput = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart);
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($htmlOutput);
            if (!file_exists($resultdetails_dir)) {
                mkdir($resultdetails_dir, 0777);
            }
            $html2pdf->Output($resultdetails_dir . '/Result-' . $labOrderid . '-1.pdf', 'F');

            //second result
            $html2pdf1 = new HTML2PDF('P', 'A4', 'en');
            $htmlViewPart2 = new ViewModel();
            $htmlViewPart2->setTerminal(true)
                    ->setTemplate('lab/result/result-pdf')
                    ->setVariables(array(
                        'xmldata' => $xmldata['Order_1'],
            ));
            $htmlOutput2 = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart2);
            $html2pdf1->pdf->SetDisplayMode('fullpage');
            $html2pdf1->writeHTML($htmlOutput2);
            $html2pdf1->Output($resultdetails_dir . '/Result-' . $labOrderid . '-2.pdf', 'F');

            // Third pdf
            $fp = fopen($resultdetails_dir . '/Result-' . $labOrderid . '-3.pdf', "wb");
            fwrite($fp, base64_decode($result_content['content']));
            fclose($fp);

            $dwnFileName = 'Result-' . $labOrderid . '.zip';
            $zip = new Zip();
            $zip->setArchive($resultdetails_dir . '/' . $dwnFileName);
            $zip->compress($resultdetails_dir);
            if (file_exists($resultdetails_dir . '/' . $dwnFileName)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($dwnFileName));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($resultdetails_dir . '/' . $dwnFileName));
                readfile($resultdetails_dir . '/' . $dwnFileName);
                unlink($resultdetails_dir . '/' . $dwnFileName);
            }
        } elseif ($xmldata['Order_1']) {
            $html2pdf = new HTML2PDF('P', 'A4', 'en');

            //first result
            $htmlViewPart = new ViewModel();
            $htmlViewPart->setTerminal(true)
                    ->setTemplate('lab/result/result-pdf')
                    ->setVariables(array(
                        'xmldata' => $xmldata['Order'],
            ));
            $htmlOutput = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart);
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($htmlOutput);
            if (!file_exists($resultdetails_dir)) {
                mkdir($resultdetails_dir, 0777);
            }
            $html2pdf->Output($resultdetails_dir . '/Result-' . $labOrderid . '-1.pdf', 'F');

            //second result
            $html2pdf1 = new HTML2PDF('P', 'A4', 'en');
            $htmlViewPart2 = new ViewModel();
            $htmlViewPart2->setTerminal(true)
                    ->setTemplate('lab/result/result-pdf')
                    ->setVariables(array(
                        'xmldata' => $xmldata['Order_1'],
            ));
            $htmlOutput2 = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart2);
            $html2pdf1->pdf->SetDisplayMode('fullpage');
            $html2pdf1->writeHTML($htmlOutput2);
            $html2pdf1->Output($resultdetails_dir . '/Result-' . $labOrderid . '-2.pdf', 'F');
            $dwnFileName = 'Result-' . $labOrderid . '.zip';
            $zip = new Zip();
            $zip->setArchive($resultdetails_dir . '/' . $dwnFileName);
            $zip->compress($resultdetails_dir);
            if (file_exists($resultdetails_dir . '/' . $dwnFileName)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($dwnFileName));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($resultdetails_dir . '/' . $dwnFileName));
                readfile($resultdetails_dir . '/' . $dwnFileName);
                unlink($resultdetails_dir . '/' . $dwnFileName);
            }
        } elseif ($result_content['content']) {
            $html2pdf = new HTML2PDF('P', 'A4', 'en');

            //first result
            $htmlViewPart = new ViewModel();
            $htmlViewPart->setTerminal(true)
                    ->setTemplate('lab/result/result-pdf')
                    ->setVariables(array(
                        'xmldata' => $xmldata,
            ));
            $htmlOutput = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart);
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($htmlOutput);
            if (!file_exists($resultdetails_dir)) {
                mkdir($resultdetails_dir, 0777);
            }
            $html2pdf->Output($resultdetails_dir . '/Result-' . $labOrderid . '-1.pdf', 'F');

            //second result
            $fp = fopen($resultdetails_dir . '/Result-' . $labOrderid . '-2.pdf', "wb");
            fwrite($fp, base64_decode($result_content['content']));
            fclose($fp);

            $dwnFileName = 'Result-' . $labOrderid . '.zip';
            $zip = new Zip();
            $zip->setArchive($resultdetails_dir . '/' . $dwnFileName);
            $zip->compress($resultdetails_dir);
            if (file_exists($resultdetails_dir . '/' . $dwnFileName)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename=' . basename($dwnFileName));
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($resultdetails_dir . '/' . $dwnFileName));
                readfile($resultdetails_dir . '/' . $dwnFileName);
                unlink($resultdetails_dir . '/' . $dwnFileName);
            }
        } else {
            $htmlViewPart = new ViewModel();
            $htmlViewPart->setTerminal(true)
                    ->setTemplate('lab/result/result-pdf')
                    ->setVariables(array(
                        'xmldata' => $xmldata,
            ));

            $htmlOutput = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart);
            $html2pdf = new HTML2PDF('P', 'A4', 'en');
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($htmlOutput);
            $html2pdf->Output('Result-' . $labOrderid . '.pdf', 'D');
        }
        exit;
    }

    /**
     * singulexresultPdfAction()
     */
    public function singulexresultPdfAction() {
        $labOrderid = $this->params('id');
        global $pid;

        $pdf = new PdfModel();
        $pdf->setOption('filename', 'result' . $labOrderid); // Triggers PDF download, automatically appends ".pdf"
        $pdf->setOption('paperSize', 'a4'); // Defaults to "8x11"
        $pdf->setOption('paperOrientation', 'portrait'); // Defaults to "portrait"    
        $patDetails = $this->getResultTable()->getPatDetails($labOrderid);
        $resultDetails = $this->getResultTable()->getResDetails($labOrderid);

        $pdf->setVariables(array(
            'patDetails' => $patDetails,
            'resultDetails' => $resultDetails,
        ));
        return $pdf;
    }

    /**
     * Local requisition
     */
    public function localrequisitionPdfAction() {
        $sitePath = $GLOBALS['OE_SITE_DIR'];
        $labrequisitionPath = $sitePath . '/labrequisition';
        if (!file_exists($labrequisitionPath)) {
            mkdir($labrequisitionPath, 0777);
        }
        $labOrderid = $this->params('id') ? $this->params('id') : $this->params()->fromQuery('orderid');
        $fromorder = $this->params()->fromQuery('save');
        if ($fromorder == "yes") {
            $temp = explode(",", $labOrderid);

            if (count($temp) == 1) {

                $orderDetails = $this->getResultTable()->getRequisition($labOrderid);
                $tests = $this->getResultTable()->getRequisitiontests($labOrderid);
                $htmlViewPart = new ViewModel();
                $htmlViewPart
                        ->setTerminal(true)
                        ->setTemplate('lab/result/localrequisition-pdf')
                        ->setVariables(array(
                            'labOrderid' => $labOrderid,
                            'orderDetails' => $orderDetails,
                            'tests' => $tests,
                ));
                $htmlOutput = $this->getServiceLocator()
                        ->get('viewrenderer')
                        ->render($htmlViewPart);
                $html2pdf = new HTML2PDF('P', 'A4', 'en');
                $html2pdf->pdf->SetDisplayMode('fullpage');
                $html2pdf->writeHTML($htmlOutput);
                $html2pdf->Output('requistion' . $labOrderid . '.pdf', 'D');
                exit;
            } else {
                $labOrderid = str_replace(",", "_", $labOrderid);
                foreach ($temp as $row_item):
                    $orderDetails = $this->getResultTable()->getRequisition($row_item);
                    $tests = $this->getResultTable()->getRequisitiontests($row_item);
                    $htmlViewPart = new ViewModel();
                    $htmlViewPart->setTerminal(true)
                            ->setTemplate('lab/result/localrequisition-pdf.phtml')
                            ->setVariables(array(
                                'labOrderid' => $row_item,
                                'orderDetails' => $orderDetails,
                                'tests' => $tests,
                    ));
                    $htmlOutput = $this->getServiceLocator()
                            ->get('viewrenderer')
                            ->render($htmlViewPart);
                    $html2pdf = new HTML2PDF('P', 'A4', 'en');
                    $html2pdf->pdf->SetDisplayMode('fullpage');
                    $html2pdf->writeHTML($htmlOutput);
                    if (!file_exists($labrequisitionPath . '/' . $labOrderid)) {
                        mkdir($labrequisitionPath . '/' . $labOrderid, 0777);
                    }
                    $html2pdf->Output($labrequisitionPath . '/' . $labOrderid . '/order_' . $row_item . '.pdf', 'F');
                endforeach;
                $dwnFileName = 'order_pdf_' . $labOrderid . '.zip';
                $zip = new Zip();
                $zip->setArchive($labrequisitionPath . '/' . $dwnFileName);
                $zip->compress($labrequisitionPath . '/' . $labOrderid);
                if (file_exists($labrequisitionPath . '/' . $dwnFileName)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($dwnFileName));
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($labrequisitionPath . '/' . $dwnFileName));
                    readfile($labrequisitionPath . '/' . $dwnFileName);
                }
                exit;
            }
        } else {
            $orderDetails = $this->getResultTable()->getRequisition($labOrderid);
            $tests = $this->getResultTable()->getRequisitiontests($labOrderid);
            $htmlViewPart = new ViewModel();
            $htmlViewPart
                    ->setTerminal(true)
                    ->setTemplate('lab/result/localrequisition-pdf')
                    ->setVariables(array(
                        'labOrderid' => $labOrderid,
                        'orderDetails' => $orderDetails,
                        'tests' => $tests,
            ));
            $htmlOutput = $this->getServiceLocator()
                    ->get('viewrenderer')
                    ->render($htmlViewPart);
            $html2pdf = new HTML2PDF('P', 'A4', 'en');
            $html2pdf->pdf->SetDisplayMode('fullpage');
            $html2pdf->writeHTML($htmlOutput);
            $html2pdf->Output('requistion' . $labOrderid . '.pdf', 'D');
            exit;
        }
    }

    /**
     * createLabcorpAoeArray()
     */
    public function createLabcorpAoeArray($aoes) {
        $clincal = array();
        $addInfo = array();
        $micro = array();
        $bloodLead = array();
        $cytology = array();
        $afp = array();
        $aoeArray = array();
        foreach ($aoes as $aoe) {
            $dupeCheck = 0;
            if ($aoe['answer'] != '') {
                if (substr($aoe['hl7_segment'], 0, 3) == 'ZCY') {
                    $aoeText = explode(':', $aoe['question_text']);
                    foreach ($aoeArray as $akey => $aoeAns) {
                        $aoeTextSub = explode(':', $aoeAns['question_text']);
                        if ($aoeText[0] == $aoeTextSub[0]) {
                            $dupeCheck = 1;
                            $aoeArray[$akey]['question_text'] = $aoeArray[$akey]['question_text'] . ', ' . $aoeText[1];
                            break;
                        }
                    }
                    if (!$dupeCheck) {
                        array_push($aoeArray, $aoe);
                    }
                } else if (substr($aoe['hl7_segment'], 0, 5) == 'ZSA25' || substr($aoe['hl7_segment'], 0, 5) == 'ZSA26') {
                    if ($aoe['answer'] != 'N') {
                        array_push($aoeArray, $aoe);
                    }
                } else {
                    array_push($aoeArray, $aoe);
                }
            }
        }
        foreach ($aoeArray as $aoe) {
            $skipValue = false;
            if ($aoe['answer'] != '') {
                //###################################################################################              
                $zcyAns = '';
                $questionText = '';
                $aoeAswer = '';
                if (substr($aoe['hl7_segment'], 0, 3) == 'ZCY' && $aoe['hl7_segment'] != 'ZCY12' && substr($aoe['hl7_segment'], 0, 5) != 'ZSA25' && substr($aoe['hl7_segment'], 0, 5) != 'ZSA26') {
                    $qstnArr = explode(':', $aoe['question_text']);
                    $questionText = $qstnArr[0]; // $aoe['question_text']; 
                    $zcyAns = $qstnArr[1];
                } else if (substr($aoe['hl7_segment'], 0, 5) == 'ZSA25' || substr($aoe['hl7_segment'], 0, 5) == 'ZSA26' || substr($aoe['hl7_segment'], 0, 4) == 'ZSA4' || substr($aoe['hl7_segment'], 0, 4) == 'ZSA5' || substr($aoe['hl7_segment'], 0, 5) == 'ZSA3.') {
                    $qstnArr = explode('-', $aoe['question_text']);
                    $questionText = $qstnArr[0];
                    $zsaAns1 = explode('(', $qstnArr[1]);
                    $zsaAns = $zsaAns1[0];
                } else {
                    $questionText = $aoe['question_text'];
                }


                if (substr($aoe['hl7_segment'], 0, 3) == 'ZBL' || $aoe['hl7_segment'] == 'PID10') {
                    $tipsArr = explode(',', $aoe['tips']);
                    foreach ($tipsArr as $dta) {
                        $tip = explode('-', $dta);
                        if (trim($tip[0]) == trim($aoe['answer'])) {
                            $aoeAswer = $tip[1];
                        } else {
                            $tip1 = explode('=', $dta);
                            if (trim($tip1[0]) == trim($aoe['answer'])) {
                                $aoeAswer = $tip1[1];
                            }
                        }
                    }
                } else if ($aoe['hl7_segment'] == 'ZCY12') {
                    $aoeAswer = substr($aoe['answer'], 0, 4) . '-' . substr($aoe['answer'], 4, 2) . '-' . substr($aoe['answer'], 6, 2);
                } else if (substr($aoe['hl7_segment'], 0, 3) == 'ZCY' || substr($aoe['hl7_segment'], 0, 3) == 'ZSA' || substr($aoe['hl7_segment'], 0, 3) == 'ZCI') {
                    if ($aoe['answer'] == 'Y') {
                        if (substr($aoe['hl7_segment'], 0, 3) != 'ZCY') {
                            if (substr($aoe['hl7_segment'], 0, 5) == 'ZSA25' || substr($aoe['hl7_segment'], 0, 5) == 'ZSA26' || substr($aoe['hl7_segment'], 0, 4) == 'ZSA4' || substr($aoe['hl7_segment'], 0, 4) == 'ZSA5' || substr($aoe['hl7_segment'], 0, 5) == 'ZSA3.') {
                                $aoeAswer = $zsaAns;
                            } else {
                                $aoeAswer = 'Yes';
                            }
                        } else {
                            $aoeAswer = $zcyAns;
                        }
                    } else if ($aoe['answer'] == 'N') {
                        $aoeAswer = 'No';
                    } else {
                        $aoeAswer = str_replace('~:~', ', ', $aoe['answer']);
                    }
                } else {
                    $aoeAswer = str_replace('~:~', ', ', $aoe['answer']);
                }
                if ((strlen($aoeAswer) == 8) && (is_numeric($aoeAswer))) {
                    if (strpos(strtoupper($aoe['question_text']), 'DATE') !== false) {
                        $aoeAswer1 = substr($aoeAswer, 0, 4);
                        $aoeAswer2 = substr($aoeAswer, 4, 2);
                        $aoeAswer3 = substr($aoeAswer, 6, 2);
                        $aoeAswer = $aoeAswer1 . '-' . $aoeAswer2 . '-' . $aoeAswer3;
                    }
                }
                $pushArr = array('question' => $questionText, 'answer' => $aoeAswer);
                //############################################################################################
                if ($aoe['hl7_segment'] == 'OBR13.1') {
                    array_push($clincal, $pushArr);
                }
                if ($aoe['hl7_segment'] == 'ZCI4' || $aoe['hl7_segment'] == 'ZCI1' || $aoe['hl7_segment'] == 'ZCI2.1' || $aoe['hl7_segment'] == 'ZCI3.1' || $aoe['hl7_segment'] == 'OBR9.1') {
                    foreach ($addInfo as $data) {
                        if (trim($data['question']) == trim($questionText)) {
                            $skipValue = true;
                        }
                    }
                    if ($skipValue == true) {
                        continue;
                    }
                    if ($aoe['hl7_segment'] == 'ZCI2.1') {
                        if (substr($aoe['question_code'], 0, 5) == 'HTWTT') {
                            array_push($addInfo, $pushArr);
                        }
                    } else {
                        array_push($addInfo, $pushArr);
                    }
                }
                if ($aoe['hl7_segment'] == 'OBR15') {
                    array_push($micro, $pushArr);
                }
                if ($aoe['hl7_segment'] == 'ZBL1' || $aoe['hl7_segment'] == 'ZBL2' || $aoe['hl7_segment'] == 'ZBL3' || $aoe['hl7_segment'] == 'ZBL4') {
                    array_push($bloodLead, $pushArr);
                }
                if ((substr($aoe['hl7_segment'], 0, 3) == 'ZCY') && $aoe['answer'] != 'N') {

                    array_push($cytology, $pushArr);
                }

                if ((substr($aoe['hl7_segment'], 0, 3) == 'ZSA') || $aoe['hl7_segment'] == 'ZCI2.1' || $aoe['hl7_segment'] == 'PID10') {
                    if ((substr($aoe['hl7_segment'], 0, 5) == 'ZSA25' || substr($aoe['hl7_segment'], 0, 5) == 'ZSA26' || substr($aoe['hl7_segment'], 0, 4) == 'ZSA4' || substr($aoe['hl7_segment'], 0, 4) == 'ZSA5' || substr($aoe['hl7_segment'], 0, 5) == 'ZSA3.')) {
                        if ($aoe['answer'] != 'N') {
                            array_push($afp, $pushArr);
                        }
                    } else {
                        if ($aoe['hl7_segment'] == 'ZCI2.1') {
                            if ((substr($aoe['question_code'], 0, 5) == 'MSONL') || (substr($aoe['question_code'], 0, 5) == 'MSSNT') || (substr($aoe['question_code'], 0, 5) == 'SERIN')) {
                                array_push($afp, $pushArr);
                            }
                        } else {
                            array_push($afp, $pushArr);
                        }
                    }
                }
            }
        }
        $returnArray = array();
        $returnArray['clincal'] = $clincal;
        $returnArray['addInfo'] = $addInfo;
        $returnArray['micro'] = $micro;
        $returnArray['bloodLead'] = $bloodLead;
        $returnArray['cytology'] = $cytology;
        $returnArray['afp'] = $afp;
        return $returnArray;
    }

}

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

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Lab\Form\ResultnewForm;
use Zend\Json\Json;
use Zend\Soap\Client;
use Zend\Config;
use Zend\Config\Reader;
use Zend\Filter\Compress;
use CouchDB;

class ResultnewController extends AbstractActionController {

    protected $labTable;

    /**
     * getResultTable()
     * @return type
     */
    public function getResultTable() {
        if (!$this->labTable) {
            $sm = $this->getServiceLocator();
            $this->labTable = $sm->get('Lab\Model\ResultnewTable');
        }
        return $this->labTable;
    }

    /**
     * Index Action
     * @global type $pid
     * @return \Zend\View\Model\ViewModel
     */
    public function indexAction() {
        global $pid;
        $form = new ResultnewForm();
        $msg = '';
        if ($pid == '') {
            //$msg = 'N';  
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
                        ,performing_lab_name,performing_lab_addr1,performing_lab_addr2,performing_lab_city,performing_lab_state,performing_lab_zip,performing_lab_phone,performing_lab_provider,procedure_order_id)
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


        if ($request->isGet()) {
            $pageno = ($request->getQuery('pageno') <> "") ? $request->getQuery('pageno') : 1;
            $lab = $request->getQuery('lab');
            $form->get('lab_id')->setValueOptions($labs)->setValue($lab);
            $labresult1 = $this->resultShowAction($pageno);
            $viewModel = new ViewModel(array(
                "labresults" => $labresult1,
                "message" => $msg,
                "searchpid_name" => $request->getQuery('searchpid_name'),
                "searchpid_id" => $request->getQuery('searchpid_id'),
                "searchtest" => $request->getQuery('searchtest'),
                "order_status" => $request->getQuery('searchStatusOrder'),
                "report_status" => $request->getQuery('searchStatusReport'),
                "result_status" => $request->getQuery('searchStatusResult'),
                "dtFrom" => $request->getQuery('dtFrom'),
                "dtTo" => $request->getQuery('dtTo'),
                "form" => $form
            ));
            return $viewModel;
        } else {
            $form->get('lab_id')->setValueOptions($labs)->setValue($this->getRequest()->getPost('lab_id'));
            $labresult1 = $this->resultShowAction($pageno);
            $viewModel = new ViewModel(array(
                "labresults" => $labresult1,
                "message" => $msg,
                "searchpid_name" => $this->getRequest()->getPost('searchpid_name'),
                "searchpid_id" => $this->getRequest()->getPost('searchpid_id'),
                "dtFrom" => $this->getRequest()->getPost('dtFrom'),
                "dtTo" => $this->getRequest()->getPost('dtTo'),
                "searchtest" => $this->getRequest()->getPost('searchtest'),
                "order_status" => $this->getRequest()->getPost('searchStatusOrder'),
                "report_status" => $this->getRequest()->getPost('searchStatusReport'),
                "result_status" => $this->getRequest()->getPost('searchStatusResult'),
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
        exit(0);
        if (!$orderId)
            $orderId = $_GET['order_id'];
        // Set font size
        $font_size = 2;
        $row = $this->getResultTable()->selectPatientInfo($orderId);
        $text = "Client: " . $row['send_fac_id'] . "\nLab Ref: " . $row['labref'] . "\nPat Name: " . $row['pname'];
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
        //$IMGING = imagepng($img);
        //header("Content-Type: image/png");
        //header('Content-Disposition: attachment; filename=Specimen Label.png' );
        //  header("Content-Type: application/octet-stream" );
        //  header("Content-Length: " . filesize( $IMGING ) );
        ob_end_clean();
        ob_start();
        ////////imagejpeg($img);
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
     * createImageBorder()
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
     * Pagination
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
     * Get reault 
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
                'pid' => $request->getPost('searchpid_id'),
                'labname' => $request->getPost('lab_id'),
            );
        } else {
            $data = array(
                'statusReport' => $request->getQuery('searchStatusReport'),
                'statusOrder' => $request->getQuery('searchStatusOrder'),
                'statusResult' => $request->getQuery('searchStatusResult'),
                'dtFrom' => $request->getQuery('dtFrom'),
                'dtTo' => $request->getQuery('dtTo'),
                'labname' => $request->getQuery('lab'),
                'testname' => $request->getQuery('searchtest'),
                'pid' => $request->getQuery('searchpid_id'),
            );
        }

        $data = $this->getLabResult($data, $pageno);
        return $data;
    }

    /**
     * getLabResult()
     * @param type $data
     * @param type $pageno
     * @return type
     */
    public function getLabResult($data, $pageno) {
        $labResult = $this->getResultTable()->listLabResult($data, $pageno);
        return $labResult;
    }

    /**
     * getLabOptionsAction()
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
     * getResultCommentsAction()
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
     * insertLabCommentsAction()
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
     * resultUpdateAction()
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
            if (($username == "") || ($password == "")) {
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
                //if ($stresult['status'] != $curr_status) {
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
                    $return[0] = array('return' => 1, 'msg' => xlt($result['content']));
                    $arr = new JsonModel($return);
                    return $arr;
                } else { //IF THE RESULT RETURNS VALID OUTPUT
                    //if($curr_status <> "completed" || $labresultfile == "") { //IF DOESN'T HAVE RESULT FILE
                    if ($GLOBALS['document_storage_method'] == 1) {
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
                                $this->document_upload_download_log($patient_id, $log_content); //log error if any, for testing phase only
                            }
                        }
                    }
                    if ($GLOBALS['document_storage_method'] == 0) {
                        $labresultfile = "labresult_" . gmdate('YmdHis') . ".pdf";
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
                        $fp = fopen($unassociated_result_dir . $labresultunassocfile, "wb");
                        fwrite($fp, base64_decode($ur['content']));
                        $sql_unassoc = "INSERT INTO procedure_result_unassociated(patient_name,file_order_id,file_location) VALUES (?,?,?)";
                        $sql_unassoc_arr = array($ur['patient_name'], $ur['order_id'], $labresultunassocfile);
                        $this->getResultTable()->insertQuery($sql_unassoc, $sql_unassoc_arr);
                        array_push($unassociated_arr, $ur['id']);
                    }
                    $client->updateUnassociatedResult($username, $password, $site_dir, $unassociated_arr);

                    $return[0] = array('return' => 0, 'order_id' => $data['procedure_order_id']);
                    $arr = new JsonModel($return);
                    return $arr;
                }
            }
        }

        if ($curr_status == "completed" || $curr_status == "partial") {
            $labresultfile = $this->getResultTable()->getOrderResultFile($data['procedure_order_id']);
        }
        if ($labresultfile <> "") {
            while (ob_get_level()) {
                ob_end_clean();
            }

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
        }
    }

    /**
     * getLabResultDetails()
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
     * getLabRequisitionPDFAction()
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
            } elseif ($remote_host == "") {
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
                                $this->document_upload_download_log($patient_id, $log_content); //log error if any, for testing phase only
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
     * resultEntryAction()
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
        $statuses_res = $this->CommonPlugin()->getList("proc_res_status");
        $form->get('result_status[]')->setValueOptions($statuses_res);
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
        return array('form' => $form);
    }

    /**
     * saveResultEntryAction()
     * @return type
     */
    public function saveResultEntryAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->getResultTable()->saveResultEntryDetails($request->getPost());
            return $this->redirect()->toRoute('result', array('action' => 'resultEntry', 'saved' => 'yes'));
        }
    }

    /**
     * cancelAction()
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
     * listEncountersAction()
     * @return \Zend\View\Model\JsonModel
     */
    public function listEncountersAction() {
        $encounters = $this->getResultTable()->listEncounters();
        $data = new JsonModel($encounters);
        return $data;
    }

    /**
     * searchpidAction()
     */
    public function searchpidAction() {
        $seatch = $this->params()->fromQuery('q');
        $data = $this->getResultTable()->listPid($seatch);
        echo $data;
        exit(0);
    }

    /**
     * listEncountersByIdAction()
     */
    public function listEncountersByIdAction() {
        $pid = $this->getRequest()->getPost('pid', null);
        $encounters = $this->getResultTable()->list_encounter_by_pid($pid);
        $data = new JsonModel($encounters);
        echo $encounters;
        exit(0);
    }

    /**
     * reviewAction()
     * @return \Zend\View\Model\ViewModel
     */
    public function reviewAction() {
        $order_num = $this->params()->fromQuery('order_num');
        $order_id_list = $this->params()->fromQuery('order_id_list');
        $order_id_arr = explode(",", $order_id_list);
        $key = array_search($order_num, $order_id_arr);
        $len = sizeof($order_id_arr) - 1;

        $data = array(
            'order_id' => $order_num,
        );

        $labResult = $this->getResultTable()->listLabResult($data, 1);
        $pid = $labResult[0]['patient_id'];
        $pdf_url = $this->getLabResultPDFFILEAction($pid, $order_num);

        $this->layout('layout/layout2');
        $viewModel = new ViewModel(array(
            "key" => $key,
            "order_id_list" => $order_id_list,
            "labResult" => $labResult,
            "order_id" => $order_num,
            "len" => $len,
            "report_pdf" => $pdf_url,
        ));
        return $viewModel;
    }

    /**
     * reviewupdateAction()
     */
    public function reviewupdateAction() {
        $order_id = $this->getRequest()->getPost('order_id', null);
        $status = $this->getRequest()->getPost('status', null);
        $comment = $this->getRequest()->getPost('comment', null);
        $data = array(
            "order_id" => $order_id,
            "status" => $status,
            "comment" => $comment
        );
        $this->getResultTable()->updateReviewStatus($data);
        exit(0);
    }

    /**
     * getLabResultPDFFILEAction()
     * @param \Lab\Controller\type $pid
     * @param type $order_id
     * @return \Zend\View\Model\JsonModel|string
     */
    public function getLabResultPDFFILEAction($pid, $order_id) {
        $data = array('procedure_order_id' => $order_id);
        $site_dir = $GLOBALS['OE_SITE_DIR'];
        $result_dir = $site_dir . "/lab/result/";
        $unassociated_result_dir = $site_dir . "/lab/unassociated_result/";
        $result = array();
        if (!class_exists('CouchDB')) {
            require(dirname(__FILE__) . "/../../../../../../../../library/classes/CouchDB.class.php");
        }

        $request = $this->getRequest();

        $curr_status = $this->getResultTable()->getOrderStatus($data['procedure_order_id']);
        if ($request->isPost()) {
            $cred = $this->getResultTable()->getClientCredentials($data['procedure_order_id']);
            $username = $cred['login'];
            $password = $cred['password'];
            $site_dir = $_SESSION['site_id'];
            $remote_host = trim($cred['remote_host']);

            if (($username == "") || ($password == "")) {
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
                    $return[0] = array('return' => 1, 'msg' => xlt($result['content']));
                    $arr = new JsonModel($return);
                    return $arr;
                } else { //IF THE RESULT RETURNS VALID OUTPUT
                    //if($curr_status <> "completed" || $labresultfile == "") { //IF DOESN'T HAVE RESULT FILE
                    if ($GLOBALS['document_storage_method'] == 1) {
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
                                $this->document_upload_download_log($patient_id, $log_content); //log error if any, for testing phase only
                            }
                        }
                    }
                    if ($GLOBALS['document_storage_method'] == 0) {
                        $labresultfile = "labresult_" . gmdate('YmdHis') . ".pdf";
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
                        $fp = fopen($unassociated_result_dir . $labresultunassocfile, "wb");
                        fwrite($fp, base64_decode($ur['content']));
                        $sql_unassoc = "INSERT INTO procedure_result_unassociated(patient_name,file_order_id,file_location) VALUES (?,?,?)";
                        $sql_unassoc_arr = array($ur['patient_name'], $ur['order_id'], $labresultunassocfile);
                        $this->getResultTable()->insertQuery($sql_unassoc, $sql_unassoc_arr);
                        array_push($unassociated_arr, $ur['id']);
                    }
                    $client->updateUnassociatedResult($username, $password, $site_dir, $unassociated_arr);

                    $return[0] = array('return' => 0, 'order_id' => $data['procedure_order_id']);
                    $arr = new JsonModel($return);
                    return $arr;
                }
            }
        }

        if ($curr_status == "completed" || $curr_status == "partial") {
            $labresultfile = $this->getResultTable()->getOrderResultFile($data['procedure_order_id']);
        }
        if ($labresultfile <> "") {
            while (ob_get_level()) {
                ob_end_clean();
            }

            if (!is_dir($GLOBALS['OE_SITE_DIR'] . '/lab/temp/')) {
                mkdir($GLOBALS['OE_SITE_DIR'] . '/lab/temp/', 0777, true);
            }
            $labresultfilename = "labresult" . gmdate('YmdHis') . ".pdf";
            if ($GLOBALS['document_storage_method'] == 1) {
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
                $tmpcouchpath = $GLOBALS['OE_SITE_DIR'] . '/lab/temp/' . $labresultfilename;
                $tmppath_return = $GLOBALS['webroot'] . '/sites/' . $_SESSION['site_id'] . '/lab/temp/' . $labresultfilename;
                $fh = fopen($tmpcouchpath, "w");
                fwrite($fh, base64_decode($content));
                fclose($fh);
                return $tmppath_return;
            }
            if ($GLOBALS['document_storage_method'] == 0) {
                $tmppath = $GLOBALS['OE_SITE_DIR'] . '/lab/temp/' . $labresultfilename;
                $tmppath_return = $GLOBALS['webroot'] . '/sites/' . $_SESSION['site_id'] . '/lab/temp/' . $labresultfilename;
                $content = @file_get_contents($result_dir . $labresultfile);
                $fh = fopen($tmppath, "w");
                fwrite($fh, $content);
                fclose($fh);
                return $tmppath_return;
            }
        }
    }

    /**
     * getMultipleLabRequisitionPDFAction()
     * @global \Lab\Controller\type $pid
     * @return \Zend\View\Model\JsonModel
     */
    public function getMultipleLabRequisitionPDFAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            global $pid;
            $zip_folder = "lab_req_" . date("YmdHis") . substr((string) microtime(), 1, 8);
            $site_dir = $GLOBALS['OE_SITE_DIR'];
            $requisition_dir = $site_dir . "/lab/requisition/";
            if (!class_exists('CouchDB')) {
                require(dirname(__FILE__) . "/../../../../../../../../library/classes/CouchDB.class.php");
            }
            $order_ids = $request->getPost('order_ids');
            foreach ($order_ids as $order_id) {
                $data['procedure_order_id'] = $order_id;
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
                        $return = array('return' => -1, 'msg' => "Lab Credentials not found for order - " . $data['procedure_order_id']);
                        $arr = new JsonModel($return);
                        return $arr;
                    } elseif ($remote_host == "") {
                        $return[0] = array('return' => -1, 'msg' => "Remote Host not found for order - " . $data['procedure_order_id']);
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
                            $return[0] = array('return' => -1, 'msg' => "Could not connect to the web service for for order - " . $data['procedure_order_id']);
                            $arr = new JsonModel($return);
                            return $arr;
                        }
                        if ($result['status'] == 'failed') {
                            $return[0] = array('return' => -1, 'msg' => "Error for order - " . $data['procedure_order_id'] . ":" . xlt($result['content']));
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
                                            $this->document_upload_download_log($patient_id, $log_content); //log error if any, for testing phase only
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
                        }
                    }
                }
                if ($labrequisitionfile <> "") {
                    $temp_folder = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . '/';
                    $labrequisitionfilename = "labrequisition_" . $data['procedure_order_id'] . "_" . gmdate('YmdHis') . ".pdf";
                    if (!is_dir($temp_folder)) {
                        mkdir($temp_folder, 0777, true);
                    }
                    while (ob_get_level()) {
                        ob_get_clean();
                    }
                    if ($GLOBALS['document_storage_method'] == 1) {
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
                        $tmpcouchpath = $temp_folder . $labrequisitionfilename;
                        $fh = fopen($tmpcouchpath, "w");
                        fwrite($fh, base64_decode($content));
                        fclose($fh);
                    }
                    if ($GLOBALS['document_storage_method'] == 0) {
                        $tmpcouchpath = $temp_folder . $labrequisitionfilename;
                        copy($requisition_dir . $labrequisitionfile, $tmpcouchpath);
                    }
                }
            }
            $zipFile = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . ".zip";
            $filter = new Compress(array(
                'adapter' => 'zip',
                'options' => array(
                    'archive' => $zipFile
                ),
            ));
            $compressed = $filter->filter($temp_folder);
            $return[0] = array('return' => 1, 'folder' => $zip_folder);
            $arr = new JsonModel($return);
            return $arr;
        } elseif ($request->isGet()) {
            $zip_folder = $request->getQuery('zip_name');
            $temp_folder = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . '/';
            $zipFile = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . ".zip";
            unlink($temp_folder);
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=$zipFile");
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile("$zipFile");
            unlink($zipFile);
            exit;
        }
    }

    /**
     * getMultipleLabResultPDFAction()
     * @global \Lab\Controller\type $pid
     * @return \Zend\View\Model\JsonModel
     */
    public function getMultipleLabResultPDFAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            global $pid;
            $zip_folder = "lab_res_" . date("YmdHis") . substr((string) microtime(), 1, 8);
            $site_dir = $GLOBALS['OE_SITE_DIR'];
            $result_dir = $site_dir . "/lab/result/";
            if (!class_exists('CouchDB')) {
                require(dirname(__FILE__) . "/../../../../../../../../library/classes/CouchDB.class.php");
            }
            $order_ids = $request->getPost('order_ids');
            foreach ($order_ids as $order_id) {
                $data['procedure_order_id'] = $order_id;
                $curr_status = $this->getResultTable()->getOrderStatus($data['procedure_order_id']);
                $cred = $this->getResultTable()->getClientCredentials($data['procedure_order_id']);
                $username = $cred['login'];
                $password = $cred['password'];
                $site_dir = $_SESSION['site_id'];
                $remote_host = trim($cred['remote_host']);
                if (($username == "") || ($password == "")) {
                    $return = array('return' => -1, 'msg' => "Lab Credentials not found for order - " . $data['procedure_order_id']);
                    $arr = new JsonModel($return);
                    return $arr;
                } elseif ($remote_host == "") {
                    $return[0] = array('return' => -1, 'msg' => "Remote Host not found for order - " . $data['procedure_order_id']);
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
                        $return[0] = array('return' => -1, 'msg' => "Could not connect to the web service for for order - " . $data['procedure_order_id']);
                        $arr = new JsonModel($return);
                        return $arr;
                    }
                    //if ($stresult['status'] != $curr_status) {
                    try {
                        $client = new Client(null, $options);
                        $result = $client->getLabResult($username, $password, $site_dir, $data['procedure_order_id']); //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID
                    } catch (\Exception $e) {
                        $return[0] = array('return' => -1, 'msg' => "Could not connect to the web service for for order - " . $data['procedure_order_id']);
                        $arr = new JsonModel($return);
                        return $arr;
                    }
                    if ($result['status'] == 'failed') {
                        $return[0] = array('return' => -1, 'msg' => "Error for order - " . $data['procedure_order_id'] . ":" . xlt($result['content']));
                        $arr = new JsonModel($return);
                        return $arr;
                    } else { //IF THE REQUISITION RETURNS VALID OUTPUT
                        //if(($curr_status <> "requisitionpulled")&&($curr_status <> "completed")) { //IF THE REQUISITION/RESULT IS ALREADY DOWNLOADED
                        if ($GLOBALS['document_storage_method'] == 1) {
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
                                    $this->document_upload_download_log($patient_id, $log_content); //log error if any, for testing phase only
                                }
                            }
                        }
                        if ($GLOBALS['document_storage_method'] == 0) {
                            $labresultfile = "labresult_" . gmdate('YmdHis') . ".pdf";
                            if (!is_dir($result_dir)) {
                                mkdir($result_dir, 0777, true);
                            }
                            $fp = fopen($result_dir . $labresultfile, "wb");
                            fwrite($fp, base64_decode($result['content']));
                        }
                        $this->getLabResultDetails($data['procedure_order_id']);
                        $status_res = $this->getResultTable()->changeOrderResultStatus($data['procedure_order_id'], $stresult['status'], $labresultfile);
                    }
                }
                if ($curr_status == "completed" || $curr_status == "partial") {
                    $labresultfile = $this->getResultTable()->getOrderResultFile($data['procedure_order_id']);
                }
                if ($labresultfile <> "") {
                    $temp_folder = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . '/';
                    $labresultfilename = "labresult_" . $data['procedure_order_id'] . "_" . gmdate('YmdHis') . ".pdf";
                    if (!is_dir($temp_folder)) {
                        mkdir($temp_folder, 0777, true);
                    }
                    while (ob_get_level()) {
                        ob_get_clean();
                    }
                    if ($GLOBALS['document_storage_method'] == 1) {
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
                        $tmpcouchpath = $temp_folder . $labresultfilename;
                        $fh = fopen($tmpcouchpath, "w");
                        fwrite($fh, base64_decode($content));
                        fclose($fh);
                    }
                    if ($GLOBALS['document_storage_method'] == 0) {
                        $tmpcouchpath = $temp_folder . $labresultfilename;
                        copy($result_dir . $labresultfile, $tmpcouchpath);
                    }
                }
            }
            $zipFile = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . ".zip";
            $filter = new Compress(array(
                'adapter' => 'zip',
                'options' => array(
                    'archive' => $zipFile
                ),
            ));
            $compressed = $filter->filter($temp_folder);
            $return[0] = array('return' => 1, 'folder' => $zip_folder);
            $arr = new JsonModel($return);
            return $arr;
        } elseif ($request->isGet()) {
            $zip_folder = $request->getQuery('zip_name');
            $temp_folder = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . '/';
            $zipFile = $GLOBALS['OE_SITE_DIR'] . '/documents/temp/' . $zip_folder . ".zip";
            unlink($temp_folder);
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=$zipFile");
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile("$zipFile");
            unlink($zipFile);
            exit;
        }
    }

    /**
     * getMultipleLabResultsAction()
     * @return \Zend\View\Model\ViewModel
     */
    public function getMultipleLabResultsAction() {
        $request = $this->getRequest();
        $order_ids = $this->params()->fromQuery('hidResultids');
        $encounterIds = $this->params()->fromQuery('hidEncounter');
        if ($this->params()->fromQuery('pid')) {
            $data['pid'] = $this->params()->fromQuery('pid');
        }
        $labResults = array();
        $labResults1 = array();
        $order_ids = explode(',', $encounterIds);
        if ($encounterIds == 'yes') {
            $labResult = $this->getResultTable()->listLabResult($data, $pageno, $this->params()->fromQuery('hidResultids'));
        } else {
            $labResult = $this->getResultTable()->listLabResult($data, $pageno, $this->params()->fromQuery('hidResultids'), $encounterIds);
        }

        $viewModel = new ViewModel(array(
            'ecncounterIds' => $encounterIds,
            'labresults' => $labResult,
        ));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

}

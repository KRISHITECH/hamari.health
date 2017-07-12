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
use Lab\Model\Unassociated;
use Lab\Form\UnassociatedForm;
use Zend\View\Model\JsonModel;
use Zend\Soap\Client;
use Zend\Config;
use Zend\Config\Reader;
use C_Document;
use \HTML2PDF;
use Zend\Filter\Compress\Zip;

class UnassociatedController extends AbstractActionController {

    protected $unassociatedTable;

    /**
     * getUnassociatedTable()
     * @return type
     */
    public function getUnassociatedTable() {
        if (!$this->unassociatedTable) {
            $sm = $this->getServiceLocator();
            $this->unassociatedTable = $sm->get('Lab\Model\UnassociatedTable');
        }
        return $this->unassociatedTable;
    }

    /**
     * Index ACtion
     * @return type
     */
    public function indexAction() {
        $request = $this->getRequest();
        $type = $request->getQuery('type');
        $this->layout()->type = $type;
        if ($type == 'resolved') {
            $this->layout()->res = $this->getUnassociatedTable()->listResolvedPdf();
            $form = new UnassociatedForm();
            return array('form' => $form);
        } elseif ($type == 'resultsonly') {
            $selectProvider = $request->getPost('selectProvider');
            if (!$selectProvider)
                $selectProvider = $request->getQuery('selectProvider');
            if ($selectProvider == '') {
                $this->layout()->errorMsg = "Select a provider";
                $form = new UnassociatedForm();
                return array('form' => $form);
            } else {
                ini_set("soap.wsdl_cache_enabled", "0");
                ini_set('memory_limit', '-1');
                $cred = $this->CommonPlugin()->getClientCredentialsLab($selectProvider);
                $username = $cred['login'];
                $password = $cred['password'];
                $mirth_lab_id = $cred['mirth_lab_id'];
                $site_dir = $_SESSION['site_id'];
                $remote_host = trim($cred['remote_host']);
                $options = array(
                    'location' => $remote_host,
                    'uri' => "urn://zhhealthcare/lab"
                );
                try {
                    $client = new Client(null, $options);
                    $stresultonly = $client->getResultOnlyDetails($username, $password, $mirth_lab_id);
                } catch (\Exception $e) {
                    $this->layout()->errorMsg = "Could not connect to the web service";
                    $form = new UnassociatedForm();
                    return array('form' => $form);
                }
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
                        // cheecking whether result_only_id is present or not
                        if (isset($xmldata['result_only_id'])) {
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
                                isset($xmldata['performing_lab_provider']) ? $xmldata['performing_lab_provider'] : "",
                                isset($xmldata['procedure_order_id']) ? $xmldata['procedure_order_id'] : "",
                            );
                            $this->getUnassociatedTable()->insertQuery($sql_result_only, $vals);
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
                                            )VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
                                            $result_only_inarray = array($xmldata['result_only_id'], $subtest_code, $subtest_name, $result_value, $units, $range,
                                                $abn_flag, $result_status, $result_time, $xmldata['provider_name'], $subtest_comments, $order_title
                                            );
                                            $this->getUnassociatedTable()->insertQuery($sql_subtest_result_only, $result_only_inarray);
                                        }
                                    }
                                }
                            }
                            array_push($inserted_arr, $xmldata['result_only_id']);
                            if ($break == 1) {
                                break;
                            }
                        }
                    }
                    try {
                        $stresultonly = $client->updateResultOnlyStatus($username, $password, $site_dir, $inserted_arr);
                    } catch (\Exception $e) {
                        $this->layout()->errorMsg = "Could not update result only status";
                        $form = new UnassociatedForm();
                        return array('form' => $form);
                    }
                }
                $per_page = $request->getPost('perPage'); // number of results to show per page
                $nameTosearch = $request->getPost('txtSearch');
                $dobTosearch = $request->getPost('txtDob');
                $selectProvider = $request->getPost('selectProvider');

                if (!$per_page)
                    $per_page = (int) $request->getQuery('per');
                if (!$nameTosearch)
                    $nameTosearch = $request->getQuery('searchName');
                if (!$dobTosearch)
                    $dobTosearch = $request->getQuery('searchDob');
                if (!$selectProvider)
                    $selectProvider = $request->getQuery('selectProvider');

                $ymd = (bool) preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/", $dobTosearch);
                $mdy = (bool) preg_match("/^(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])-[0-9]{4}$/", $dobTosearch);

                if ($mdy == true) {
                    $dobTosearch = preg_replace("!([01][0-9])-([0-9]{2})-([0-9]{4})!", "$3-$1-$2", $dobTosearch);
                }

                if ($nameTosearch != '' || $dobTosearch != '')
                    $total_results = $this->getUnassociatedTable()->listResultOnlyCountwithnamesearch($nameTosearch, $dobTosearch);
                else
                    $total_results = $this->getUnassociatedTable()->listResultOnlyCount();


                $total_pages = ceil($total_results / $per_page);
                $show_page = (int) $request->getQuery('page');
                $reload = "?type=resultsonly" . $tpages . "&per=" . $per_page . "&searchName=" . $nameTosearch . "&searchDob=" . $dobTosearch . "&selectProvider=" . $selectProvider;
                if ($show_page) {
                    if ($show_page > 0 && $show_page <= $total_pages) {
                        $start = ($show_page - 1) * $per_page;
                        $end = $start + $per_page;
                    } else {
                        // error - show first set of results
                        $start = 0;
                        $end = $per_page;
                    }
                } else {
                    // if page isn't set, show first set of results
                    $start = 0;
                    $end = $per_page;
                }
                // display pagination
                $page = (int) $this->params()->fromRoute('page');

                $tpages = $total_pages;
                if ($page <= 0)
                    $page = 1;
                if ($show_page == 0)
                    $show_page = 1;
                $pagination = '<div class="pagination">';
                if ($total_pages > 1) {
                    $pagination.= $this->paginateAction($reload, $show_page, $total_pages);
                }
                $pagination.= "</div>";


                if ($nameTosearch != '' || $dobTosearch != '')
                    $this->layout()->res_only = $this->getUnassociatedTable()->listResultOnlyWithnamesearch($start, $per_page, $nameTosearch, $dobTosearch);
                else
                    $this->layout()->res_only = $this->getUnassociatedTable()->listResultOnly($start, $per_page);
                if (($request->getPost('txtSearch') || $request->getPost('txtDob')) && !($this->layout()->res_only)) {
                    $errorMsg = "No matching records  found !!!! ";
                } else {
                    $errorMsg = "";
                }

                $this->layout()->pagination = $pagination;
                $this->layout()->per_page = $per_page;
                $this->layout()->selectProvider = $selectProvider;
                $this->layout()->nameTosearch = $nameTosearch;
                $this->layout()->dobTosearch = $dobTosearch;
                $this->layout()->errorMsg = $errorMsg;
                $form = new UnassociatedForm();
                $form->setValue($request->getPost('selectProvider'));
                return array('form' => $form);
            }
        } else {
            $this->layout()->res = $this->getUnassociatedTable()->listPdf();
            $form = new UnassociatedForm();
            return array('form' => $form);
        }
    }

    /**
     * View action
     */
    public function viewAction() {
        $request = $this->getRequest();
        $filename = $request->getQuery('filename');
        $unassociated_result_dir = $GLOBALS['OE_SITE_DIR'] . "/lab/unassociated_result/";
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Disposition: attachment; filename=' . $filename);
        header("Content-Type: application/octet-stream");
        header("Content-Length: " . filesize($unassociated_result_dir . $filename));
        readfile($unassociated_result_dir . $filename);
        exit;
    }

    /**
     * Attach Action
     * @global type $pid
     * @return type
     */
    public function attachAction() {
        global $pid;
        $request = $this->getRequest();
        $response = $this->getResponse();
        if ($request->isPost()) {
            if ($request->getPost()->type == 'attachToCurrentPatient') {
                if (!$pid) {
                    return $response->setContent(\Zend\Json\Json::encode(array('error' => 'You must select a patient')));
                }
                if (!class_exists('CouchDB')) {
                    require(dirname(__FILE__) . "/../../../../../../../../controllers/C_Document.class.php");
                }
                $_POST['process'] = true;
                $file_path = $GLOBALS['OE_SITE_DIR'] . "/lab/unassociated_result/" . $request->getPost()->file_name;
                $mime_types = array(
                    "pdf" => "application/pdf"
                    , "exe" => "application/octet-stream"
                    , "zip" => "application/zip"
                    , "docx" => "application/msword"
                    , "doc" => "application/msword"
                    , "xls" => "application/vnd.ms-excel"
                    , "ppt" => "application/vnd.ms-powerpoint"
                    , "gif" => "image/gif"
                    , "png" => "image/png"
                    , "jpeg" => "image/jpg"
                    , "jpg" => "image/jpg"
                    , "mp3" => "audio/mpeg"
                    , "wav" => "audio/x-wav"
                    , "mpeg" => "video/mpeg"
                    , "mpg" => "video/mpeg"
                    , "mpe" => "video/mpeg"
                    , "mov" => "video/quicktime"
                    , "avi" => "video/x-msvideo"
                    , "3gp" => "video/3gpp"
                    , "css" => "text/css"
                    , "jsc" => "application/javascript"
                    , "js" => "application/javascript"
                    , "php" => "text/html"
                    , "htm" => "text/html"
                    , "html" => "text/html"
                );

                $extension = strtolower(end(explode('.', $file_path)));
                $mime_type = $mime_types[$extension];
                $_FILES['file']['name'][0] = $request->getPost()->file_name;
                $_FILES['file']['type'][0] = $mime_type;
                $_FILES['file']['tmp_name'][0] = $file_path;
                $_FILES['file']['error'][0] = 0;
                $_FILES['file']['size'][0] = filesize($file_path);
                $_POST['category_id'] = '2';
                $_POST['patient_id'] = $pid;
                $_GET['patient_id'] = $pid;

                $cdoc = new C_Document();
                if (!file_exists($cdoc->file_path . $request->getPost()->file_name)) {
                    $cdoc->upload_action_process();
                    copy($file_path, $cdoc->file_path . $request->getPost()->file_name);
                    $this->getUnassociatedTable()->attachUnassociatedDetails($request->getPost());
                    return $response->setContent(\Zend\Json\Json::encode(array('response' => true)));
                } else {
                    return $response->setContent(\Zend\Json\Json::encode(array('error' => 'This file is already attached to current patient')));
                }
            } elseif ($request->getPost()->type == 'attachToOrder') {
                $this->getLabResultDetails($request->getPost()->file_order_id);
                return $response->setContent(\Zend\Json\Json::encode(array('response' => true)));
            }
        }
    }

    /**
     * Pagination
     * @param type $reload
     * @param type $page
     * @param type $tpages
     * @return string
     */
    function paginateAction($reload, $page, $tpages) {
        $adjacents = 3;
        $prevlabel = "&lsaquo; Prev";
        $nextlabel = "Next &rsaquo;";
        $out = "";
        // previous
        if ($page == 1) {
            $out.= "<span class=\"page gradient\">" . $prevlabel . "</span>";
        } elseif ($page == 2) {
            $out.="<a class=\"page gradient\" href=\"" . $reload . "\">" . $prevlabel . "</a>";
        } else {
            $out.="<a class=\"page gradient\" href=\"" . $reload . "\"> First </a>";
            $out.="<a class=\"page gradient\" href=\"" . $reload . "&amp;page=" . ($page - 1) . "\">" . $prevlabel . "</a>";
        }
        $pmin = ($page > $adjacents) ? ($page - $adjacents) : 1;
        $pmax = ($page < ($tpages - $adjacents)) ? ($page + $adjacents) : $tpages;
        for ($i = $pmin; $i <= $pmax; $i++) {
            if ($i == $page) {
                $out.= "<a class=\"page active\" href=''>" . $i . "</a>";
            } elseif ($i == 1) {
                $out.= "<a class=\"page gradient\" href=\"" . $reload . "\">" . $i . "</a>";
            } else {
                $out.= "<a class=\"page gradient\" href=\"" . $reload . "&amp;page=" . $i . "\">" . $i . "</a>";
            }
        }
        if ($page < ($tpages - $adjacents)) {
            $out.= " ..... <a class=\"page gradient\" style='font-size:11px' href=\"" . $reload . "&amp;page=" . $tpages . "\">" . $tpages . "</a>";
        }
        // next
        if ($page < $tpages) {
            $out.= "<a class=\"page gradient\" href=\"" . $reload . "&amp;page=" . ($page + 1) . "\">" . $nextlabel . "</a>";
        } else {

            $out.= "<span class=\"page gradientqq\" style='font-size:11px'>" . $nextlabel . "</span>";
        }
        $out.= "<span>Showing Page <font color=\"red\"><strong>" . $page . "</strong></font> of <font color=\"blue\"><strong>" . $tpages . "</strong></font></font></span>";
        $out.= "";

        return $out;
    }

    // labcorp result printout from unaaaociated 
    public function unassociatedresultpdfAction() {
        $result_only_id = $this->params('id');
        $site_dir = $GLOBALS['OE_SITE_DIR'];
        $resultdetails_dir1 = $site_dir . "/lab/unassociated_result_only/";
        $resultdetails_dir2 = $site_dir . "/lab/unassociated_result_only/result_only_id/" . $result_only_id . '-' . gmdate('YmdHis') . '/';
        ini_set("soap.wsdl_cache_enabled", "0");
        ini_set('memory_limit', '-1');
        $cred = $this->CommonPlugin()->getClientCredentialsLab('Labcorp');
        $username = $cred['login'];
        $password = $cred['password'];
        $mirth_lab_id = $cred['mirth_lab_id'];
        $remote_host = trim($cred['remote_host']);
        $options = array(
            'location' => $remote_host,
            'uri' => "urn://zhhealthcare/lab"
        );
        try {
            $client = new Client(null, $options);
            $result = $client->getResultOnlyForLabcorp($username, $password, $result_only_id, $mirth_lab_id);  //USERNAME, PASSWORD, SITE DIRECTORY, CLIENT PROCEDURE ORDER ID               
            $labresultdetailsfile = "labresultdetails_" . gmdate('YmdHis') . ".xml";

            if (!is_dir($resultdetails_dir1)) {
                mkdir($resultdetails_dir1, 0777, true);
            }
            if (!is_dir($resultdetails_dir2)) {
                mkdir($resultdetails_dir2, 0777, true);
            }


            $fp = fopen($resultdetails_dir1 . $labresultdetailsfile, "wb");
            fwrite($fp, $result);
            fclose($fp);
            $reader = new Config\Reader\Xml();
            $xmldata = $reader->fromFile($resultdetails_dir1 . $labresultdetailsfile);
            if ($xmldata['content']) {
                $labresultfile = 'Result-' . $result_only_id;

                // save the generated 1st  pdf to the specified location
                $fp = fopen($resultdetails_dir2 . '/' . $labresultfile . '-1.pdf', "wb");
                fwrite($fp, base64_decode($xmldata['content']));
                fclose($fp);

                // generate 2nd pdf and save to the specified location 
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
                $html2pdf->Output($resultdetails_dir2 . '/' . $labresultfile . '-2.pdf', 'F');
                $dwnFileName = 'Result-' . $result_only_id . '.zip';
                $zip = new Zip();
                $zip->setArchive($resultdetails_dir2 . '/' . $dwnFileName);
                $zip->compress($resultdetails_dir2);
                if (file_exists($resultdetails_dir2 . '/' . $dwnFileName)) {
                    header('Content-Description: File Transfer');
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename=' . basename($dwnFileName));
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . filesize($resultdetails_dir2 . '/' . $dwnFileName));
                    readfile($resultdetails_dir2 . '/' . $dwnFileName);
                    unlink($resultdetails_dir2 . '/' . $dwnFileName);
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
                $html2pdf->Output('Result-' . $result_only_id . '.pdf', 'D');
            }
            exit;
        } catch (\Exception $e) {
            $this->layout()->errorMsg = "Could not connect to the web service";
            $form = new UnassociatedForm();
            return array('form' => $form);
        }
    }

}

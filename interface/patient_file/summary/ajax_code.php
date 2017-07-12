<?php

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//===============================================================================
//Ajax Code for Insurance drop down
//===============================================================================
//function AjaxDropDownCode()
// {
require_once("../../globals.php");
require_once("../../../library/acl.inc");
require_once($GLOBALS['srcdir'] . '/csv_like_join.php');
require_once($GLOBALS['fileroot'] . '/custom/code_types.inc.php');

if ($_REQUEST["ajax_mode"] == "addissue") {
    $CountIndex = 1;
    $code_ajax = trim($_REQUEST['code_ajax']);
    $form_code_type = explode(",", $_REQUEST['code_types']);
    if ($form_code_type[0] == 'map_9to10') {
        $limit = 100;
        $limit_start = trim($_REQUEST['limit_start']);
        $CountIndex = $limit_start + 1;
        $externalCodeSearch = trim($_REQUEST['external_code_search']);
        $queryString = mysql_real_escape_string($_REQUEST['queryString']);
        $res = getMapIcd9To10(array("", $code_ajax, $externalCodeSearch, $limit_start, $limit));
        $limit_previous = $limit_start - $limit;
        $limit_start = $limit_start + $limit;

        if ($res) {
            $rows = $res->RecordCount();
        } else {
            $rows = 0;
        }
        if ($rows == 0) {
            $pages = 0;
        } else {
            $pages = intval($rows / $limit) + 1;
        }
        $msg = ($rows == 0) ? "No records found.." : '';


        $StringForAjax = "<div id='AjaxContainer'><table width='552' border='1' cellspacing='0' cellpadding='0'>";
        if ($msg != '') {
            $StringForAjax.= "<tr class='text' height='20'  bgcolor='$bgcolor' id=\"tr_code_$CountIndex\" 
                            onkeydown=\"ProcessKeyForColoring(event,$CountIndex);PlaceValues(event,'&nbsp;','','','','addissue')\"   onclick=\"PutTheValuesClick('&nbsp;','','','','')\">
                           <td colspan='5' ><a id='anchor_code_$CountIndex' href='#'></a>$msg</td>";
        }
        $StringForAjax .= "</tr> <tr class='text'><td colspan='5'>";
        //PAGINATION FOR MULTIPLE CODE TYPE DIAGNOSIS
        if ($limit_previous >= 0) {
            $StringForAjax .= "<span><a href='#inputString1' style='color: #red;' onClick=\"nextPageCodeMapping('$limit_previous','map_9to10');\" id='previous'>Previous</a></span>";
        }
        if ($pages > 1) {
            $StringForAjax .= "<span style='float: right;'><a href='#inputString1' style='color: #red;' onClick=\"nextPageCodeMapping('$limit_start','map_9to10');\" id='next'>Next</a></span>";
        }
        $StringForAjax .= "</td></tr>";
        $StringForAjax .= "<tr class='text'><td colspan='5'><span><input type='checkbox' id='code_10' name='code_type_10' value='1' onclick=\"searchCodeString('ICD10')\"/>
                                                ICD10
                        <input type='checkbox' checked='checked' id='code_map' name='code_type_map' value='1' onclick=\"searchCodeString('map_9to10')\" />
                                                ICD9 - ICD10      </span></td></tr>


                        <tr class='text' bgcolor='#dddddd'  style='font-size:11px'>
                        <td width='50' style='text-align:center'>Sl.No</td>
                         <td width='50' style='text-align:center'>CODE</td>
                         <td width='400' style='text-align:center'>ICD9 DESCRIPTION</td>
                         <td width='50' style='text-align:center'>CODE</td>
                         <td width='400' style='text-align:center'>ICD10 DESCRIPTION</td>
			                  <input type='hidden' name='totalPages' id='totalPages' value= $pages>	
                        </tr> ";
        $prevCodeType = "";
        while ($row = sqlFetchArray($res)) {
            if ($CountIndex % 2 == 1) {
                $bgcolor = '#ddddff';
            } else {
                $bgcolor = '#ffdddd';
            }
            $i9code = $row['i9code'];
            $i9code_text = $row['i9desc'];
            $i10code = $row['CODE'];
            $i10code_text = $row['code_text'];
            $code_textj = addslashes($row['code_text']);
            $code_type = 'ICD10';
            $code_textj = addslashes($row['code_text']);

            $StringForAjax.="<tr class='text'  bgcolor='$bgcolor' id=\"tr_code_$CountIndex\" style='font-size:11px' onkeydown=\"ProcessKeyForColoring(event,$CountIndex);PlaceValues(event,'$i10code','" . $code_textj . "','','','addissue','$code_type')\"
                              onclick=\"PutTheValuesClick('$i10code','" . $code_textj . "','','','addissue','$code_type','$externalCodeSearch','icd9t010','$i9code');\">
                                <td>$CountIndex.</td>
                           <td><a id='anchor_code1_$CountIndex' href='#'>$i9code</a></td>
                           <td><a href='#'>$i9code_text</a></td>
                               <td><a id='anchor_code_$CountIndex' href='#'>$i10code</a></td>
                             <td><a href='#'>$i10code_text</a></td>
                           </tr>";
            $prevCodeType = $code_type;
            $CountIndex++;
        }
        $StringForAjax.="</table></div>";
        echo $StringForAjax;
        die;
    } else {
        $externalCodeSearch = trim($_REQUEST['external_code_search']);
        if ($_REQUEST["datatype"] == "type_1226") {
            $limit_start = trim($_REQUEST['limit_start']);
            $limit = 100;
        } else {
            $limit_start = NULL;
            $limit = NULL;
        }
        /* $form_code_type,$search_term,$limit=NULL,$category=NULL,$active=true,$modes=NULL,$count=false,$start=NULL,$number=NULL,$filter_elements=array(),$search_external_db = 0 */
        $res = main_code_set_search($form_code_type, $code_ajax, NULL, NULL, true, NULL, false, $limit_start, $limit, array(), $externalCodeSearch);
        $limit_previous = $limit_start - $limit;
        $limit_start = $limit_start + $limit;
        if ($res) {
            $rows = $res->RecordCount();
        } else {
            $rows = 0;
        }
        if ($rows == 0) {
            $pages = 0;
        } else {
            $pages = intval($rows / $limit) + 1;
        }

        $msg = ($rows == 0) ? "No records found.." : '';

        $StringForAjax = "<div id='AjaxContainer'><table width='552' border='1' cellspacing='0' cellpadding='0'> ";
        if ($_REQUEST["datatype"] == "type_1226") {
            $StringForAjax.="  <tr class='text'><td colspan='4'><span><input type='checkbox' id='code_10' checked='checked' name='code_type_10' value='1' onclick=\"searchCodeString('ICD10')\"/>
                                             ICD10
                    <input type='checkbox' id='code_map' name='code_type_map' value='1' onclick=\"searchCodeString('map_9to10')\"/>
                        ICD9 - ICD10      </span></td></tr> ";
            $StringForAjax .= "</tr> <tr class='text'><td colspan='5'>";
            //PAGINATION FOR MULTIPLE CODE TYPE DIAGNOSIS
            if ($limit_previous >= 0) {
                $StringForAjax .= "<span><a href='#inputString1' style='color: #red;' onClick=\"nextPageCodeMapping('$limit_previous','ICD10');\" id='previous'>Previous</a></span>";
            }
            if ($pages > 1) {
                $StringForAjax .= "<span style='float: right;'><a href='#inputString1' style='color: #red;' onClick=\"nextPageCodeMapping('$limit_start','ICD10');\" id='next'>Next</a></span>";
            }
            $StringForAjax .= "</td></tr>";
        }
        $StringForAjax.= "<tr class='text' bgcolor='#dddddd'  style='font-size:11px'>
											<td width='50' style='text-align:center'>CODE</td>
											<td width='300' style='text-align:center'>NAME</td>
										 </tr>";
        if ($msg != '') {
            $StringForAjax.="<tr class='text' height='20'  bgcolor='$bgcolor' id=\"tr_code_$CountIndex\" 
												onkeydown=\"ProcessKeyForColoring(event,$CountIndex);PlaceValues(event,'&nbsp;','','','','addissue')\"   onclick=\"PutTheValuesClick('&nbsp;','','','','')\">
											<td colspan='4' ><a id='anchor_code_$CountIndex' href='#'></a>$msg</td>
          </tr>";
        }

        $prevCodeType = "";
        while ($row = sqlFetchArray($res)) {
            if ($CountIndex % 2 == 1) {
                $bgcolor = '#ddddff';
            } else {
                $bgcolor = '#ffdddd';
            }
            $CountIndex++;
            $code = $row['code'];
            $code_text = $row['code_text'];
            $code_textj = addslashes($row['code_text']);
            $code_type = $row['code_type_name'];
            $price = '';
            $unit = '';

            $newCodeType = 0;
            if (($code_type <> $prevCodeType) || ($prevCodeType == "")) {
                $newCodeType = 1;
            }

            if ($newCodeType == "1") {
                $StringForAjax.= "<tr class='text body_title' height='20' style='font-weight:bold;color:#000000' >
													 <td colspan='4' align='center'>$code_type</td>
													</tr>";
            }

            $StringForAjax.="<tr class='text'  bgcolor='$bgcolor' id=\"tr_code_$CountIndex\" style='font-size:11px' onkeydown=\"ProcessKeyForColoring(event,$CountIndex);PlaceValues(event,'$code','" . $code_textj . "','','','addissue','$code_type')\"
													 onclick=\"PutTheValuesClick('$code','" . $code_textj . "','','','addissue','$code_type','$externalCodeSearch');\"><td>
												<a id='anchor_code_$CountIndex' href='#'>$code</a></td>
												<td>
													<a href='#'>$code_text</a></td>
												</tr>";
            $prevCodeType = $code_type;
        }
        $StringForAjax.="</table></div>";
        echo $StringForAjax;
        die;
    }
} else if ($_REQUEST["ajax_mode"] == "addcvx") {

    $ele_id = $_REQUEST['cvx_ele'];
    $CountIndex = 1;
    $StringForAjax = "<div id='AjaxContainer'><table width='552' border='1' cellspacing='0' cellpadding='0'>
       <tr class='text' bgcolor='#dddddd'  style='font-size:11px'>
	      <td width='50' style='text-align:center'>CODE</td>
	      <td width='300' style='text-align:center'>NAME</td>
       </tr>
       <tr class='text' height='20'  bgcolor='$bgcolor' id=\"tr_code_$CountIndex\" 
	  onkeydown=\"ProcessKeyForColoring(event,$CountIndex);PlaceValues(event,'&nbsp;','','','','$ele_id')\"   onclick=\"PutTheValuesClick('&nbsp;','','','','$ele_id')\">
			<td colspan='4' align='center'><a id='anchor_code_$CountIndex' href='#'></a></td>
       </tr>";
    $code_ajax = trim($_REQUEST['code_ajax']);
    $res = sqlStatement("SELECT * FROM codes WHERE code_type='100' AND ( code like '$code_ajax%' or  code_text like '$code_ajax%') ORDER BY code");
    while ($row = sqlFetchArray($res)) {
        if ($CountIndex % 2 == 1) {
            $bgcolor = '#ddddff';
        } else {
            $bgcolor = '#ffdddd';
        }
        $CountIndex++;
        $code = $row['code'];
        $code_text = $row['code_text'];
        $price = '';
        $unit = '';
        $StringForAjax.="<tr class='text'  bgcolor='$bgcolor' id=\"tr_code_$CountIndex\" style='font-size:11px' onkeydown=\"ProcessKeyForColoring(event,$CountIndex);PlaceValues(event,'$code','$code_text','','','$ele_id')\"
			   onclick=\"PutTheValuesClick('$code','$code_text','','','$ele_id');\"><td>
			<a id='anchor_code_$CountIndex' href='#'>$code</a></td>
			<td>
			  <a href='#'>$code_text</a></td>
			</tr>";
    }
    $StringForAjax.="</table></div>";
    echo $StringForAjax;
    die;
} else if ($_REQUEST["ajax_mode"] == "saveExternalCodes") {

    $code = stripslashes($_REQUEST['code']);
    $codeType = $_REQUEST['codeType'];

    // We are looking up the external table id here.  An "unset" value gets treated as 0(zero) without this test.
    //This way we can differentiate between "unset" and explicitly zero.
    $table_id = isset($code_types[$codeType]['external']) ? intval(($code_types[$codeType]['external'])) : -9999;

    // Get/set the basic metadata information
    $table_info = $code_external_tables[$table_id];

    $table = $table_info[EXT_TABLE_NAME];
    $extTable = $exDbase . "." . $table_info[EXT_TABLE_NAME];

    $code_col = $table_info[EXT_COL_CODE];
    $code_text_col = $table_info[EXT_COL_DESCRIPTION];


    //CHECK IF THE SELECTED CODE FROM EXTERNAL DB EXISTS IN THE CORRESPONDING TABLE IN LOCAL EMR DB
    $res = sqlStatement("SELECT * FROM " . $table . " WHERE " . $code_col . "='" . $code . "'");
    $row = sqlFetchArray($res);

    //IF THE SELECTED CODE FROM ADVANCED SEARCH IS ALREADY EXISTS IN THE CORRESPONDING CODE TABLE,
    //UPDATE THE EXISTING CODE'S ACTIVE FLAG TO ZERO, AND INSERT A NEW ENTRY WITH ACTIVE FLAG AS ONE			
    if ($row[$code_col] <> "") { // UPDATE EXISTING CODE'S ACTIVE TO ZERO
        if (($codeType == 'ICD9') || ($codeType == 'ICD10')) {
            sqlStatement("UPDATE " . $table . " SET active = '0' WHERE " . $code_col . " = '" . $code . "'");
        }
    }

    $selectedColumns = "";
    if ($codeType == 'ICD9') {
        $selectedColumns = " dx_code,formatted_dx_code,short_desc,long_desc,active,revision ";
    } elseif ($codeType == 'ICD10') {
        $selectedColumns = " dx_code, formatted_dx_code, valid_for_coding, short_desc, long_desc, active, revision ";
    }

    $sqlInsCode = "INSERT INTO " . $table;

    if ($selectedColumns <> "") {
        $sqlInsCode .= " (" . $selectedColumns . ") ";
    }

    $sqlInsCode .= " SELECT ";

    if ($selectedColumns <> "") {
        $sqlInsCode .= $selectedColumns;
    } else {
        $sqlInsCode .= " * ";
    }

    $sqlInsCode .= " FROM " . $extTable . " WHERE " . $extTable . "." . $code_col . "='" . $code . "'";

    sqlStatement($sqlInsCode);

    //CHECK IF THE SELECTED CODE FROM EXTERNAL DB EXISTS IN THE CODES TABLE IN LOCAL EMR DB
    $resCode = sqlStatement("SELECT * FROM codes WHERE code_type = '" . $code_types[$codeType]['id'] . "' AND code = '" . $code . "' AND active <> '0' ");
    $isUpdated = 0;
    $rowCode = sqlFetchArray($resCode);

    if ($rowCode['code'] <> "") {
        $isUpdated = 1;
        sqlStatement("UPDATE codes SET active = '0' WHERE code = '" . $code . "'");
    }

    if ($isUpdated == 0) {
        //INSERT INTO CODES WITH ACTIVE AS ONE				
        $sqlIns = "INSERT INTO codes(code_text,code_text_short,code,code_type,active) " .
                "SELECT $extTable.$code_text_col,$extTable.$code_text_col, $extTable.$code_col, '" .
                $code_types[$codeType]['id'] . "' AS code_type, '1' AS active " .
                "FROM $extTable  WHERE " . $extTable . "." . $code_col . "='" . $code . "'";
        sqlStatement($sqlIns);
    }

    if (count($table_info[EXT_JOINS]) > 0) {
        foreach ($table_info[EXT_JOINS] as $join_info) {
            $join_table = $join_info[JOIN_TABLE];
            $exjoin_table = $exDbase . "." . $join_info[JOIN_TABLE];
            $joinCol = "";
            if (count($join_info[JOIN_FIELDS]) > 0) {
                foreach ($join_info[JOIN_FIELDS] as $field) {
                    //PREFIX EXTERNAL CODE DATABASE NAME BEFORE JOINING FIELDS OF JOINING TABLE
                    //EXAMPLE OF JOIN FILED ENTRY => sct_descriptions.ConceptId=sct_concepts.ConceptId
                    // FIND OUT JOINING COLUMN IN JOINING TABLE FOR USING IN WHERE CLAUSE
                    if (strpos($field, "=") == true) {
                        list($joiningLeftSection, $joiningRightSection) = explode("=", $field);
                        if ($joiningLeftSection <> "") {
                            $field = $exDbase . "." . $joiningLeftSection;
                            if (strpos($field, $join_table . ".") == true) {
                                $joinCol = $joiningLeftSection;
                            }
                        }
                        if (($joiningLeftSection <> "") && ($joinCol == "")) {
                            $field .= "=" . $exDbase . "." . $joiningRightSection;
                            if (strpos($field, $join_table . ".") == true) {
                                $joinCol = $joiningRightSection;
                            }
                        }
                    }
                }
            }
            $res = sqlStatement("SELECT * FROM " . $join_table . " WHERE " . $joinCol . "='" . $code . "'");
            $row = sqlFetchArray($res);
            if ($row[$code_col] == "") {
                sqlStatement("INSERT INTO " . $join_table . " SELECT * FROM " . $exjoin_table . " WHERE " . $exDbase . "." . $joinCol . "='" . $code . "'");
            }
        }
    } else {
        //echo "SELECT * FROM ".$extTable." INTO ".$table;
    }
} else if ($_REQUEST["ajax_mode"] == "saveExternalCodes9t010") {
    $code = $_REQUEST['code'];
    $codeType = $_REQUEST['codeType'];
    $icd9code_map = $_REQUEST['icd9code_map'];
    $res = checkExternalIcdCodes9to10(array("", $code, $codeType, $icd9code_map));
}
?>
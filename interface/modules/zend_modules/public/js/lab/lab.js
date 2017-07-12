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
$('body').on('focus', ".dateclass", function() {
    $(this).datepicker({
        dateFormat: "yy/mm/dd"
    });
});
$('body').on('focus', ".datetimeclass", function() {
    $(this).timepicker({
        timeFormat: "hh:mm:ss",
    });
});
/**
 * getLocation
 * @param {type} selectedLab
 * @returns {undefined}
 */
function getLocation(selectedLab) {
    if (selectedLab != '') {
        if ($('#locationrow').css('display') == 'none') {
            $('#locationrow').css('display', 'block');
        }
        $.ajax({
            url: "./labLocation",
            type: "POST",
            dataType: 'json',
            data: {
                inputValue: selectedLab,
                type: 'lab_location'
            },
            success: function(data) {
                $("#locationName").val(data);
            },
            error: function() {
                alert('ajax error');
            }
        });

    } else {
        $('#locationrow').css('display', 'none');

    }
}
document.onclick = HideTheAjaxDivs;
function HideTheAjaxDivs() {
    $(".autocomplete-suggestions").css('display', 'none');
}
/**
 * loadAoeQuest
 * @param {type} labval
 * @param {type} ProcedureCode
 * @param {type} procedure
 * @param {type} count
 * @param {type} suffix
 * @param {type} ordercnt
 * @param {type} remote_labval
 * @returns {undefined}
 */
function loadAoeQuest(labval, ProcedureCode, procedure, count, suffix, ordercnt, remote_labval) {
    //alert(ProcedureCode+"-"+procedure+"-"+count);

    var listprocode = $('#procedure_list').val();
    arrprocode = listprocode.split("|");
    cnt = 0;
    for (var i = 0; i < arrprocode.length; i++) {
        if (arrprocode[i] == ProcedureCode)
            cnt++;
    }
    if (cnt > 0)
    {
        alert("Already used this procedure");
        $('#procedure_code_' + count).val("");
    }
    else {
        $('#procedures_' + count).focus();
        $('#procedures_' + count).val(procedure);
        $('#procedure_code_' + count).val(ProcedureCode);
        $('#procedure_list').val($('#procedure_list').val() + "|" + ProcedureCode);
        $('#procedure_suffix_' + count).val(suffix);
        $.post("./search", {
            type: "loadAOE",
            inputValue: ProcedureCode,
            dependentId: labval,
            remoteLabId: remote_labval
        },
        function(data) {
            if (data.response == true) {
                //alert(data.procedureArray);
                aoeArray = data.aoeArray;
                j = '<table>';
                i = 0;

                for (var questioncode in aoeArray) {
                    var skip = 0;
                    i++;
                    splitArr = aoeArray[questioncode].split("|-|");
                    specimen_case = splitArr[8];
                    Width = 'style = "width:160px;"';
                    if (specimen_case == 'C') {
                        order = count.split("_");
                        $("#dgord_" + order[0]).find(".aoeCheck").each(function() {
                            aoe_name = $(this).attr('name');
                            aoe_nameArr = aoe_name.split("_");
                            if (splitArr[2] == aoe_nameArr[3] || splitArr[2] + '[]' == aoe_nameArr[3]) {
                                skip = 1;
                            }
                        });
                    }
                    if (skip == 1) {
                        i = 0;
                        continue;
                    }
                    tips = splitArr[3];
                    if (tips)
                        cls = "personPopupTrigger aoeCheck";
                    else
                        cls = "aoeCheck";
                    if (splitArr[4] == 'M') {
                        var options = splitArr[5].split('~');
                        var options_value = splitArr[7].split('~');
                        var optionsdisplay = '';
                        for (oc = 0; oc < options.length; oc++) {
                            optionsdisplay += '<option value="' + options[oc] + '" >' + options[oc] + '</option>';
                        }
                        j += '<tr><td>' + i + '</td><td>' + splitArr[0] + '</td><td><select multiple="multiple" rel="' + tips + '" class="combo ' + cls + '" ' + Width + ' name="AOE_' + ordercnt + "_" + ProcedureCode.replace(".", "#^#") + "_" + splitArr[2].replace(".", "#^#") + '[]" >' + optionsdisplay + '</select></td></tr>';
                    } else if (splitArr[4] == 'S') {
                        if (parseInt(remote_labval) == 10) {
                            if (tips != '') {
                                options_value = tips.split(',');
                                var optionsdisplay = '';
                                optionsdisplay += '<option value="" ></option>';
                                for (oc1 = 0; oc1 < options_value.length; oc1++) {
                                    optval1 = options_value[oc1].split('=');
                                    optionsdisplay += '<option value="' + optval1[0].trim() + '" >' + optval1[1] + '</option>';
                                }
                            } else {
                                options = splitArr[5].split('~');
                                options_value = splitArr[7].split('~');
                                var optionsdisplay = '';
                                optionsdisplay += '<option value="" ></option>';
                                for (oc = 0; oc < options.length; oc++) {
                                    if (options[oc] == 'Y') {
                                        txt = 'Yes';
                                    } else if (options[oc] == 'N') {
                                        txt = 'No';
                                    } else {
                                        txt = options[oc];
                                    }
                                    optionsdisplay += '<option value="' + options[oc] + '" >' + txt + '</option>';
                                }
                            }
                        } else {
                            options = splitArr[5].split('~');
                            options_value = splitArr[7].split('~');
                            var optionsdisplay = '';
                            optionsdisplay += '<option value="" ></option>';
                            for (oc = 0; oc < options.length; oc++) {
                                optionsdisplay += '<option value="' + options[oc] + '" >' + options[oc] + '</option>';
                            }
                            splitArr[5];
                        }
                        j += '<tr><td>' + i + '</td><td>' + splitArr[0] + '</td><td><select rel="' + tips + '" class="combo ' + cls + '" ' + Width + ' name="AOE_' + ordercnt + "_" + ProcedureCode.replace(".", "#^#") + "_" + splitArr[2].replace(".", "#^#") + '" >' + optionsdisplay + '</select></td></tr>';
                    } else if (splitArr[4] == 'D') {
                        j += '<tr><td>' + i + '</td><td>' + splitArr[0] + '</td><td><input rel="' + tips + '" class="combo ' + cls + ' dateclass" ' + Width + ' type="text" name="AOE_' + ordercnt + "_" + ProcedureCode + "_" + splitArr[2].replace(".", "##^") + '"></td></tr>';
                    } else if (splitArr[4] == 'E') {
                        j += '<tr><td>' + i + '</td><td>' + splitArr[0] + '</td><td><input rel="' + tips + '" class="combo ' + cls + ' datetimeclass" ' + Width + ' type="text" name="AOE_' + ordercnt + "_" + ProcedureCode + "_" + splitArr[2].replace(".", "##^") + '"></td></tr>';
                    }
                    else {
                        maxsize = splitArr[9];
                        maxlen = '';
                        if (maxsize != 0) {
                            maxlen = 'maxlength=' + maxsize + '';
                        }
                        isDigit = '';
                        if (splitArr[4] == 'N') {
                            isDigit = 'onkeypress="return isNumber(event)"';
                        }
                        if (remote_labval == 9) {
                            j += '<tr><td>' + i + '</td><td>' + splitArr[0] + '</td><td><input rel="' + tips + '" class="combo ' + cls + '" ' + Width + ' type="text" ' + isDigit + ' ' + maxlen + ' name="AOE_' + ordercnt + "_" + ProcedureCode.replace(".", "#^#") + "_" + splitArr[2].replace(".", "#^#") + '"></td></tr>';
                        } else {
                            j += '<tr><td>' + i + '</td><td>' + splitArr[0] + '</td><td><input rel="' + tips + '" class="combo ' + cls + '" ' + Width + ' type="text" ' + isDigit + ' ' + maxlen + ' name="AOE_' + ordercnt + "_" + ProcedureCode + "_" + splitArr[2].replace(".", "##^") + '"></td></tr>';
                        }
                    }
                }
                j += "</table>";
                //alert(j);
                contents = "<fieldset><legend>" + procedure + "</legend>";
                if (j === '<table></table>') {
                    $("#AOEtemplate_" + count).css('display', 'none');
                    $("#AOE_" + count).html("");
                }
                else {
                    $("#AOEtemplate_" + count).css('display', '');
                    $("#AOE_" + count).html(contents + j + "</fieldset>");
                }
                // print success message
            } else {
                alert("Failed");
                // print error message
                console.log('could not add');
            }
        }, 'json');
    }
}
/**
 * Function to check whether the pressed key is digit or not 
 * @param {type} evt
 * @returns {Boolean}
 */
function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode != 46) {
        alert("Digits Only");
        return false;
    }
    return true;
}
/**
 * checkLab
 * @param {type} labval
 * @param {type} id
 * @returns {undefined}
 */
function checkLab(labval, id) {

    var arrId = id.split('lab_id_');
    var arr = labval.split("|");
    var labvalue = arr[0];
    var type = arr[1];

    var check = arrId[1].split("_");
    var cnt = document.getElementsByName("procedures[" + (check[0] - 1) + "][]").length;
    if (check[0] - 1 == 0) {
        cnt = cnt - 1;
    }
    var cur_order = check[0];
    var p = 1;
    var k = 1;
    while (p <= cnt) {
        if ($('#procedures_' + cur_order + '_' + k).length > 0) {
            var chk = "";
            var listprocode = $('#procedure_list').val();
            var arrprocode = listprocode.split("|");
            var pcheck = $('#procedure_code_' + cur_order + '_' + k).val();
            for (var i = 0; i < arrprocode.length; i++) {
                if (pcheck == arrprocode[i]) {
                    chk = chk + "";
                }
                else {
                    chk = chk + "|" + arrprocode[i];
                }
            }
            $('#procedure_list').val(chk);
            p++;
        }
        k++;
    }
    var p = 1;
    var k = 2;
    while (p < cnt) {
        if ($('#procedures_' + cur_order + '_' + k).length > 0) {
            cancelItem('deleteButton_' + cur_order + '_' + k);
            p++;
        }
        k++;
    }
    $('#procedures_' + cur_order + '_1').val("");
    $('#diagnoses_' + cur_order + '_1').val("");
    $('#procedure_code_' + cur_order + '_1').val("");
    $('#patient_instructions_' + cur_order + '_1').val("");
    if (labvalue > 0) {
        $("#internaltimecaption_" + arrId[1]).css('display', 'none');
        $("#internaltime_" + arrId[1]).css('display', 'none');
    } else {
        $("#internaltimecation_" + arrId[1]).css('display', 'block');
        $("#internaltime_" + arrId[1]).css('display', 'none');
    }
    if (type == 1 && arr[2] != "11") {

        if ($("#" + id + " option:selected").text() == 'Labcorp') {
            $("#CORcaption_" + arrId[1]).css('display', 'table-cell');
            $("#CORtd_" + arrId[1]).css('display', 'table-cell');
            $("#CourtesyCopycaption_" + arrId[1]).css('display', 'table-cell');
            $("#CourtesyCopytd_" + arrId[1]).css('display', 'table-cell');
            $(".addSpecimen_" + check[0]).show();
            $("#addSpecimen").css('display', 'inline-table');
            if ($("#primary_medicare_data").val() != '' && $("#primary_medicare_data").val() != 'undefined') {
                $("#ABNcaption_" + arrId[1]).css('display', 'table-cell');
                $("#ABNtd_" + arrId[1]).css('display', 'table-cell');
            }
        }
        else {
            $("#CORcaption_" + arrId[1]).css('display', 'none');
            $("#CORtd_" + arrId[1]).css('display', 'none');
            $("#CourtesyCopycaption_" + arrId[1]).css('display', 'none');
            $("#CourtesyCopytd_" + arrId[1]).css('display', 'none');
            $(".addSpecimen_" + check[0]).hide();
            $("#addSpecimen").css('display', 'none');
            $("#ABNcaption_" + arrId[1]).css('display', 'none');
            $("#ABNtd_" + arrId[1]).css('display', 'none');
        }
        $("#specimencollectedcaption_" + arrId[1]).css('display', 'block');
        $("#specimencollectedtd_" + arrId[1]).css('display', 'block');
        $("#billtocaption_" + arrId[1]).css('display', 'block');
        $("#billtotd_" + arrId[1]).css('display', 'block');

    } else {
        $("#specimencollectedcaption_" + arrId[1]).css('display', 'none');
        $("#specimencollectedtd_" + arrId[1]).css('display', 'none');
        $("#billtocaption_" + arrId[1]).css('display', 'none');
        $("#billtotd_" + arrId[1]).css('display', 'none');
        $("#CORcaption_" + arrId[1]).css('display', 'none');
        $("#CORtd_" + arrId[1]).css('display', 'none');
        $("#CourtesyCopycaption_" + arrId[1]).css('display', 'none');
        $("#CourtesyCopytd_" + arrId[1]).css('display', 'none');
        $(".addSpecimen_" + check[0]).hide();
        $("#addSpecimen").css('display', 'none');
        $("#ABNcaption_" + arrId[1]).css('display', 'none');
        $("#ABNtd_" + arrId[1]).css('display', 'none');
    }

}
/**
 * cloneRow
 * @returns {undefined}
 */
function cloneRow()
{
    var rowcount = document.getElementById('procedurecount').value;
    var row = document.getElementById("proceduretemplate_1"); // find row to copy
    var AOErow = document.getElementById("AOEtemplate_1"); // find row to copy
    var Diagrow = document.getElementById("diagnosestemplate_1"); // find row to copy
    var table = document.getElementById("ordertable"); // find table to append to
    var Diagclone = Diagrow.cloneNode(true); // copy children too
    //clone.id = newrowid+""+rowcount; // change id or other attributes/contents
    Diagclone.id = "diagnosestemplate_" + rowcount;//
    table.appendChild(Diagclone); // add new row to end of table
    $('#diagnosestemplate_' + rowcount + " > td:last input[type=text]").removeAttr("required");
    $('#diagnosestemplate_' + rowcount + " > td:last input[type=text]").removeAttr("class");
    $('#diagnosestemplate_' + rowcount + " > td:last input[type=text]").attr("class", "combo");
    var clone = row.cloneNode(true); // copy children too
    clone.id = "proceduretemplate" + rowcount;
    table.appendChild(clone); // add new row to end of table
    //alert($('#proceduretemplate'+rowcount+" > td:last input[type=text]").attr("id"));
    $('#proceduretemplate' + rowcount + " > td:last input[type=text]").attr("id", "procedures_" + rowcount);
    $('#proceduretemplate' + rowcount + " > td:last input[type=text]").removeAttr("required");
    $('#proceduretemplate' + rowcount + " > td:last input[type=text]").removeAttr("class");
    $('#proceduretemplate' + rowcount + " > td:last input[type=text]").attr("class", "combo");
    $('#proceduretemplate' + rowcount + " > td:last input[type=text]").val("");
    $('#proceduretemplate' + rowcount + " > td:last div").attr("id", "prodiv_" + rowcount);
    $('#proceduretemplate' + rowcount + " > td:last div").html("");
    $('#proceduretemplate' + rowcount + " > td:last input[type=hidden]").attr("id", "procedure_code_" + rowcount);
    $('#proceduretemplate' + rowcount + " > td:last input[type=hidden]").val("");
    $('#proceduretemplate' + rowcount + " > td:last input[type=hidden]:last").attr("id", "procedure_suffix_" + rowcount);
    $('#proceduretemplate' + rowcount + " > td:last input[type=hidden]:last").val("");
    //alert($('#proceduretemplate'+rowcount+" td >input[type=text]").id);
    //$("#"+tableid+" tr:last select").val("");
    var AOEclone = AOErow.cloneNode(true); // copy children too
    //clone.id = newrowid+""+rowcount; // change id or other attributes/contents
    AOEclone.id = "AOEtemplate_" + rowcount
    table.appendChild(AOEclone); // add new row to end of table
    $('#AOEtemplate_' + rowcount).css("display", "none");
    $('#AOEtemplate_' + rowcount + " > td:last").attr("id", "AOE_" + rowcount);
    $('#AOEtemplate_' + rowcount + " > td:last").html("");
    $('#proceduretemplate' + rowcount + " > td:last input[type=text]").focus();
    document.getElementById('procedurecount').value = parseInt(rowcount) + 1;
}
/**
 * getProcedures
 * @param {type} inputString
 * @param {type} thisID
 * @param {type} event
 * @returns {undefined}
 */
function getProcedures(inputString, thisID, event) {
    arr = thisID.split("procedures_");
    ordercntArr = arr[1].split("_");
    labID = "lab_id_" + ordercntArr[0] + "_1"; //alert(inputString + '|' + thisID + '|' + labID);
    count = arr[1];
    var labval1 = document.getElementById(labID).value;
    arrLab = labval1.split("|");
    labval = arrLab[0];
    remote_labval = arrLab[2];
    $.post("./search", {
        type: "getProcedures",
        inputValue: inputString,
        dependentId: labval,
        remoteLabId: remote_labval
    },
    function(data) {
        if (data.response == true) {
            if ($('#procedure_code_' + count).val()) {
                if (event.which == 8 || event.which == 46) {
                    var new_val = $('#procedure_list').val().replace("|" + $('#procedure_code_' + count).val(), '');
                    $('#procedure_list').val(new_val);
                    $('#procedure_code_' + count).val('');
                    $('#procedure_suffix_' + count).val('');
                }
            }
            //alert(data.procedureArray);
            if (data.procedureArray.length > 0) {
                procedureArray = data.procedureArray;
                j = '<ul class="suggestion">';
                for (var procedure in procedureArray) {
                    splitArr = procedureArray[procedure].split("|-|");
                    //alert('"'+splitArr[3]+'"');
                    j += "<li onclick=loadAoeQuest('" + labval + "','" + splitArr[1].replace(/\s+/gi, "&#160;") + "','" + splitArr[3].replace(/\s+/gi, "&#160;") + "','" + count + "','" + splitArr[2].replace(/\s+/gi, "&#160;") + "','" + ordercntArr[0] + "','" + remote_labval + "')><a href='#'>" + splitArr[1] + "-" + splitArr[3] + "</a></li>";
                }
                j += "</ul>";
                //alert(j);
                //$("#prodiv_"+count).css('display','block');
                //$("#"+thisID).focus();
                $("#prodiv_" + arr[1]).css('display', 'block');
                $("#prodiv_" + arr[1]).html(j);
            }
            else {
                //$("#"+thisID).val("");
                //$("#prodiv_"+count).html("");
                //$("#prodiv_"+count).css('display','none');
                //$('#' + thisID).val('');
                if ($('#procedure_code_' + count).val()) {
                    if (event.which == 8 || event.which == 46) {
                        var new_val = $('#procedure_list').val().replace("|" + $('#procedure_code_' + count).val(), '');
                        $('#procedure_list').val(new_val);
                        $('#procedure_code_' + count).val('');
                        $('#procedure_suffix_' + count).val('');
                    }
                }
                $("#prodiv_" + arr[1]).html("");
                $("#prodiv_" + arr[1]).css('display', 'none');
            }
            // print success message
        } else {
            alert("Failed");
            // print error message
            console.log('could not add');
        }
    }, 'json');
}
/**
 * getDiagnoses
 * @param {type} inputString
 * @param {type} thisID
 * @returns {Boolean}
 */
function getDiagnoses(inputString, thisID) {
    arr = thisID.split("diagnoses_");
    inputString = $.trim(inputString.substring(inputString.lastIndexOf(';') + 1));
    if (inputString == '')
        return false;
    $.post("./search", {
        type: "getDiagnoses",
        inputValue: inputString
    },
    function(data) {
        if (data.response == true) {
            if (data.diagnosesArray.length > 0) {
                diagnosesArray = data.diagnosesArray;
                j = '<ul class="suggestion">';
                for (var diagnoses in diagnosesArray) {
                    splitArr = diagnosesArray[diagnoses].split("|-|");
                    j += "<li onclick=loadDiagnoses('" + splitArr[0] + "','" + arr[1] + "')><a href='#'>" + splitArr[0] + "-" + splitArr[1] + "</a></li>";
                }
                j += "</ul>";
                $("#diagnodiv_" + arr[1]).css('display', 'block');
                $("#diagnodiv_" + arr[1]).html(j);
            }
            else {
                $("#diagnodiv_" + arr[1]).html("");
                $("#diagnodiv_" + arr[1]).css('display', 'none');
            }
        } else {
            alert("Failed");
            console.log('could not add');
        }
    }, 'json');

}
/**
 * setDiagnoses
 * @param {type} value
 * @param {type} id
 * @returns {undefined}
 */
function setDiagnoses(value, id) {
    $("#form_diagnosis").val(value);
    searchCodeString();
}
/**
 * loadDiagnoses
 * @param {type} data
 * @param {type} id
 * @returns {undefined}
 */
function loadDiagnoses(data, id) {
    var oldval = '';
    var lastpos = $('#diagnoses_' + id).val().lastIndexOf(';');
    if (lastpos != -1) {
        oldval = $('#diagnoses_' + id).val().substring(0, lastpos + 1);
    }
    $('#diagnoses_' + id).val(oldval + data + ";");
}
/**
 * readDiagnoses
 * @param {type} thisValue
 * @param {type} thisId
 * @returns {undefined}
 */
function readDiagnoses(thisValue, thisId) {
    keyWord = thisValue;
    $("#diagid").val(thisId);
}
/**
 * pulldata
 * @param {type} lab_id
 * @param {type} type
 * @returns {undefined}
 */
function pulldata(lab_id, type) {
    //alert("hi pull :"+lab_id.value+" type :"+type );
    var labVal = document.getElementById("lab_id").value;
    var actionvar = '';
    if (type == 1)
    {
        actionvar = "pullcompendiumtest";
    }
    else if (type == 2)
    {
        actionvar = "pullcompendiumaoe";
    }
    else if (type == 3)
    {
        actionvar = "pullcompendiumtestaoe";
    }

    document.getElementById("ajaxload").innerHTML = "<img src = '../images/pulling.gif' >";


    $.post("pull/" + actionvar, {
        lab_id: labVal
    },
    function(data) {
        if (data.response == true) {
            //alert("hi resp :"+data.response);
            //alert("hi resp :"+data.result);
            document.getElementById("ajaxload").innerHTML = data.result;
//		    /*if(data.procedureArray.length>0){
//		    
//		    //alert(j);
//		    //$("#prodiv_"+count).css('display','block');
//		    //$("#prodiv_"+count).html(j);
//		    }
//		    else{
//			//$("#prodiv_"+count).html("");
//			//$("#prodiv_"+count).css('display','none');
//		    }*/
//	    // print success message
        } else {
            document.getElementById("ajaxload").innerHTML = "";
            alert("Failed");
            // print error message
            console.log('could not add');
        }
    }, 'json');
}

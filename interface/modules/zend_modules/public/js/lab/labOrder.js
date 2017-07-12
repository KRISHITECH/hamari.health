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
/**
 * Lab Multiple Procedure Order Screen
 * New Procedure Order and Procedure Order Edit
 */

// Hidden Value Settings
$(function() {
    //Hidden value setting for first panel and row count
    if ($('#accord_panel_0').length < 1) {
        $('#lab').append('<input type="hidden" id="accord_panel_0" name="accord_panel_0" value="1" />');
    }
    // Total panels
    if ($('#total_panel').length < 1) {
        $('#lab').append('<input type="hidden" id="total_panel" name="total_panel" value="1" />');
    }
    var $radios = $('#specimencollected_1_1');
    if ($radios.is(':checked') === false) {
        $radios.filter('[value=onsite]').attr('checked', true);
    }
});

// Save the data
var url = './savedata'
function saveFrm(print) {
    Code = '';
    tot_order = 0;
    for (var i = 0; i < $("#total_panel").val(); i++) {
        if (document.getElementsByName('procedure_code[' + (i + 1) + '][]'))
            SubLen = document.getElementsByName('procedure_code[' + i + '][]').length;
        else
            continue;
        if (SubLen > 0) {
            tot_order++;
        }
        for (j = 0; j < SubLen; j++) {
            if ($("#procedure_code_" + (i + 1) + "_" + (j + 1))) {
                if ($("#procedure_code_" + (i + 1) + "_" + (j + 1)).val() == "")
                    Code += $("#procedures_" + (i + 1) + "_" + (j + 1)).val() + "\r\n";
            }
        }
    }
    if (Code == '') {
        if (print == "print" && print != undefined) {
            $("#print_check").val("print");
            $("#tot_order").val(tot_order);
        }
        $("#hiddensubmit").click();
    }
    else
        alert("Invalid Procedures \r\n" + Code);
}

// Remove the dynamically added rows
function cancelItem(id) {
    var arr = id.split('_');
    var chk = "";
    var listprocode = $('#procedure_list').val();
    var arrprocode = listprocode.split("|");
    var pcheck = $('#procedure_code_' + arr[1] + '_' + arr[2]).val();
    for (var i = 0; i < arrprocode.length; i++) {
        if (pcheck == arrprocode[i]) {
            chk = chk + "";
        }
        else {
            chk = chk + "|" + arrprocode[i];
        }
    }
    $('#procedure_list').val(chk);
    $('#cloneID_' + arr[1] + '_' + arr[2]).remove();
}

//Remove the selected Panell (New Order)
function removeAccord(id) {
    var main = id.split("_");
    var pp = $('#accord').accordion('getSelected');
    if (pp) {
        var cnt = document.getElementsByName("procedures[" + (main[1] - 1) + "][]").length;
        var p = 1;
        var k = 1;
        while (p <= cnt) {
            if ($('#procedures_' + main[1] + '_' + k).length > 0) {
                var chk = "";
                var listprocode = $('#procedure_list').val();
                var arrprocode = listprocode.split("|");
                var pcheck = $('#procedure_code_' + main[1] + '_' + k).val();
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
        var index = $('#accord').accordion('getPanelIndex', pp);
        $('#accord').accordion('remove', index);
    }
}
// Dynamically add new rows in the Procedure order
var inc = 1;
function addRow(thisID) {
    $("#form_diagnosis").val("");
    inc++;
    var arrId = thisID.split("_");
    var pp = $('#accord').accordion('getSelected');
    if (pp) {
        var index = $('#accord').accordion('getPanelIndex', pp);
        //var acc_cnt = index + 1;
        var acc_cnt = arrId[1];
        // Setting row count for each panell
        var key = parseInt(arrId[1]) - 1; //index;
        var rowCount = 1;
        rowCount = parseInt($('#accord_panel_' + key).val());
        inc = rowCount + 1;
        // Show or hide the add Specimen button
        if ($("#lab_id_" + acc_cnt + "_1 option:selected").text().toLowerCase() == 'labcorp') {
            $("#addSpecimen" + acc_cnt + "_1").css('display', 'inline-table');
            $("#addSpecimen").css('display', 'inline-table');
        } else {
            $("#addSpecimen_" + acc_cnt + "_1").css('display', 'none');
            $("#addSpecimen").css('display', 'none');
        }
        $("#accord_panel_" + key).val(rowCount + 1);
        var clone = $("#cloneID_1").clone(false).appendTo("#insTempl_" + acc_cnt + "_1");
        $("#insTempl_" + acc_cnt + "_1 table:last").attr('id', 'cloneID_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#diagnosestemplate').attr('id', 'diagnosestemplate_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#proceduretemplate').attr('id', 'proceduretemplate_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#diagnodiv').attr('id', 'diagnodiv_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#prodiv').attr('id', 'prodiv_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#patient_instructions_1_1').attr('id', 'patient_instructions_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#diagnoses_1_1').attr('id', 'diagnoses_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#procedures_1_1').attr('id', 'procedures_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#procedure_code_1_1').attr('id', 'procedure_code_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#procedure_suffix_1_1').attr('id', 'procedure_suffix_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find('#AOEtemplate').attr('id', 'AOEtemplate_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#procedures").attr('id', 'procedures_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#AOE").attr('id', 'AOE_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#deleteButton").attr('id', 'deleteButton_' + acc_cnt + '_' + inc);


        $('#cloneID_' + acc_cnt + '_' + inc).find("#addSpecimen").attr('id', 'addSpecimen_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#hid_spec").attr('id', 'hid_spec_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#addSpecimenCollect").attr('id', 'addSpecimenCollect_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#spec_name").attr('id', 'spec_name_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#spselect").attr('id', 'spselect_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#spselect1").attr('id', 'spselect1_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#spselect2").attr('id', 'spselect2_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#spselect3").attr('id', 'spselect3_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#spec_desc").attr('id', 'spec_desc_' + acc_cnt + '_' + inc);


        $('#cloneID_' + acc_cnt + '_' + inc).find("#viewButton").attr('id', 'viewButton_' + acc_cnt + '_' + inc);
        $('#cloneID_' + acc_cnt + '_' + inc).find("#addButton").attr('id', 'addButton_' + acc_cnt + '_' + inc);

        // Field Name Change
        $('#cloneID_' + acc_cnt + '_' + inc).find('#patient_instructions_' + acc_cnt + '_' + inc).attr('name', 'patient_instructions[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#diagnoses_' + acc_cnt + '_' + inc).attr('name', 'diagnoses[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#procedures_' + acc_cnt + '_' + inc).attr('name', 'procedures[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#procedure_code_' + acc_cnt + '_' + inc).attr('name', 'procedure_code[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#procedure_suffix_' + acc_cnt + '_' + inc).attr('name', 'procedure_suffix[' + (acc_cnt - 1) + '][]');


        $('#cloneID_' + acc_cnt + '_' + inc).find('#hid_spec_' + acc_cnt + '_' + inc).attr('name', 'hid_spec[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#spec_name_' + acc_cnt + '_' + inc).attr('name', 'spec_name[' + (acc_cnt - 1) + '][' + (inc - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#spselect_' + acc_cnt + '_' + inc).attr('name', 'spselect[' + (acc_cnt - 1) + '][' + (inc - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#spselect1_' + acc_cnt + '_' + inc).attr('name', 'spselect1[' + (acc_cnt - 1) + '][' + (inc - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#spselect2_' + acc_cnt + '_' + inc).attr('name', 'spselect2[' + (acc_cnt - 1) + '][' + (inc - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#spselect3_' + acc_cnt + '_' + inc).attr('name', 'spselect3[' + (acc_cnt - 1) + '][' + (inc - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_' + inc).find('#spec_desc_' + acc_cnt + '_' + inc).attr('name', 'spec_desc[' + (acc_cnt - 1) + '][' + (inc - 1) + '][]');

        // add class to the add specimen section
        $('#cloneID_' + acc_cnt + '_' + inc).find('#addSpecimen_' + acc_cnt + '_' + inc).addClass('addSpecimen_' + acc_cnt);


    }
}

// Create a new Panell (New Order)
var idx = 1;
var j = 1;
var nOrd = 2;
function newOrder() {
    $("#form_diagnosis").val("");
    // Create a hidden field for each panell for row count
    $('#lab').append('<input type="hidden" id="accord_panel_' + idx + '" name="accord_panel_' + idx + '" value="1" />');
    // Set total panels
    $("#total_panel").val(nOrd);
    var acc_cnt = nOrd;
    var newAccord = $('#mainTemplate>*').clone(false).appendTo("#accord");
    newAccord.find('#main').closest("#panel_" + (nOrd - 1)).attr('id', 'main_' + nOrd);
    newAccord.find('#toolbar').attr('id', 'toolbar_' + nOrd);
    newAccord.find('#addButton').attr('id', 'addButton_' + nOrd);
    newAccord.find('#removeOrder').attr('id', 'removeOrder_' + nOrd);
    newAccord.find('#editor').attr('id', 'editor_' + nOrd);
    newAccord.find('#tt').attr('id', 'tt_' + nOrd);
    newAccord.find('#dgord').attr('id', 'dgord_' + nOrd);

    newAccord.find('#provider_1_1').attr('id', 'provider_' + acc_cnt + '_1');
    newAccord.find('#lab_id_1_1').attr('id', 'lab_id_' + acc_cnt + '_1');
    newAccord.find('#orderdate_1_1').attr('id', 'orderdate_' + acc_cnt + '_1');
    newAccord.find('#internaltimecaption').attr('id', 'internaltimecaption_' + acc_cnt + '_1');
    newAccord.find('#internaltime').attr('id', 'internaltime_' + acc_cnt + '_1');
    newAccord.find('#specimencollectedcaption').attr('id', 'specimencollectedcaption_' + acc_cnt + '_1');
    newAccord.find('#oderingdate > img').remove();
    newAccord.find('#CORcaption').attr('id', 'CORcaption_' + acc_cnt + '_1');
    newAccord.find('#CourtesyCopycaption').attr('id', 'CourtesyCopycaption_' + acc_cnt + '_1');
    newAccord.find('#ABNcaption').attr('id', 'ABNcaption_' + acc_cnt + '_1');
    newAccord.find('#oderingdate').attr('id', 'oderingdate_' + acc_cnt + '_1');
    newAccord.find('#timecollected_1_1').attr('id', 'timecollected_' + acc_cnt + '_1');
    newAccord.find('#internal_comments_1_1').attr('id', 'internal_comments_' + acc_cnt + '_1');
    newAccord.find('#specimencollected_1_1').attr('id', 'specimencollected_' + acc_cnt + '_1');
    newAccord.find('#specimencollectedtd').attr('id', 'specimencollectedtd_' + acc_cnt + '_1');
    newAccord.find('#COR_1_1').attr('id', 'COR_' + acc_cnt + '_1');
    newAccord.find('#CORtd').attr('id', 'CORtd_' + acc_cnt + '_1');
    newAccord.find('#CourtesyCopy_1_1').attr('id', 'CourtesyCopy_' + acc_cnt + '_1');
    newAccord.find('#ccselect_1_1').attr('id', 'ccselect_' + acc_cnt + '_1');
    newAccord.find('#CourtesyCopy_' + acc_cnt + '_1').attr('onclick', 'dispPopUp(this.id);');
    newAccord.find('#ccselect_' + acc_cnt + '_1').attr('onchange', 'addFields(this.id);');
    newAccord.find('#viewButton_' + acc_cnt + '_1').attr('onclick', 'disPopUp(this.id);');
    newAccord.find('#tab_1_1').attr('id', 'tab_' + acc_cnt + '_1');
    newAccord.find('#CourtesyCopytd').attr('id', 'CourtesyCopytd_' + acc_cnt + '_1');
    newAccord.find('#viewButton').attr('id', 'viewButton_' + acc_cnt + '_1');
    newAccord.find('#ABN_1_1').attr('id', 'ABN_' + acc_cnt + '_1');
    newAccord.find('#ABNtd').attr('id', 'ABNtd_' + acc_cnt + '_1');
    newAccord.find('#hid_div_1_1').attr('id', 'hid_div_' + acc_cnt + '_1');
    newAccord.find('#accno_1_1').attr('id', 'accno' + acc_cnt + '_1');
    newAccord.find('#accname_1_1').attr('id', 'accname' + acc_cnt + '_1');
    newAccord.find('#faxno_1_1').attr('id', 'faxno' + acc_cnt + '_1');
    newAccord.find('#faxname_1_1').attr('id', 'faxname' + acc_cnt + '_1');
    newAccord.find('#priority_1_1').attr('id', 'priority_' + acc_cnt + '_1');
    newAccord.find('#status_1_1').attr('id', 'status_' + acc_cnt + '_1');
    newAccord.find('#billtocaption').attr('id', 'billtocaption_' + acc_cnt + '_1');
    newAccord.find('#billtotd').attr('id', 'billtotd_' + acc_cnt + '_1');
    newAccord.find('#billto_1_1').attr('id', 'billto_' + acc_cnt + '_1');
    newAccord.find('#insTempl').attr('id', "insTempl_" + acc_cnt + "_1");

    // Remove duplicate Order Date and Internal Time Collected
    $('#oderingdate_' + acc_cnt + '_1 span').remove();
    $('#internaltime_' + acc_cnt + '_1 span').remove();

    var clone = $("#cloneID_1").clone(false).appendTo("#insTempl_" + acc_cnt + "_1");
    $("#insTempl_" + acc_cnt + "_1 table:last").attr('id', 'cloneID_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#diagnosestemplate').attr('id', 'diagnosestemplate_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#proceduretemplate').attr('id', 'proceduretemplate_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#diagnodiv').attr('id', 'diagnodiv_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#prodiv').attr('id', 'prodiv_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#patient_instructions_1_1').attr('id', 'patient_instructions_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#diagnoses_1_1').attr('id', 'diagnoses_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#procedures_1_1').attr('id', 'procedures_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#procedure_code_1_1').attr('id', 'procedure_code_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#procedure_suffix_1_1').attr('id', 'procedure_suffix_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#AOEtemplate').attr('id', 'AOEtemplate_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#procedures').attr('id', 'procedures_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#AOE').attr('id', 'AOE_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#deleteButton').attr('id', 'deleteButton_' + acc_cnt + '_1');


    $('#cloneID_' + acc_cnt + '_1').find('#addSpecimen').attr('id', 'addSpecimen_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#hid_spec').attr('id', 'hid_spec_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#addSpecimenCollect').attr('id', 'addSpecimenCollect_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#spec_name').attr('id', 'spec_name_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#spselect').attr('id', 'spselect_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#spselect1').attr('id', 'spselect1_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#spselect2').attr('id', 'spselect2_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#spselect3').attr('id', 'spselect3_' + acc_cnt + '_1');
    $('#cloneID_' + acc_cnt + '_1').find('#spec_desc').attr('id', 'spec_desc_' + acc_cnt + '_1');

    $('#cloneID_' + acc_cnt + '_1').find('#viewButton').attr('id', 'viewButton_' + acc_cnt + '_' + '_1');
    //$('#cloneID_' + acc_cnt + '_1').find("#viewButton").css('display', 'none');
    $('#cloneID_' + acc_cnt + '_1').find('#addButton').attr('id', 'addButton_' + acc_cnt + '_1');

    // Field name settings
    newAccord.find('#provider_' + acc_cnt + '_1').attr('name', 'provider[' + (acc_cnt - 1) + '][]');
    newAccord.find('#lab_id_' + acc_cnt + '_1').attr('name', 'lab_id[' + (acc_cnt - 1) + '][]');
    newAccord.find('#orderdate_' + acc_cnt + '_1').attr('name', 'orderdate[' + (acc_cnt - 1) + '][]');
    newAccord.find('#orderdate_' + acc_cnt + '_1').attr('class', 'datetimeclass');
    newAccord.find('#timecollected_' + acc_cnt + '_1').attr('name', 'timecollected[' + (acc_cnt - 1) + '][]');
    newAccord.find('#internal_comments_' + acc_cnt + '_1').attr('name', 'internal_comments[' + (acc_cnt - 1) + '][]');
    newAccord.find('#specimencollected_' + acc_cnt + '_1').attr('name', 'specimencollected[' + (acc_cnt - 1) + '][]');
    newAccord.find('#COR_' + acc_cnt + '_1').attr('name', 'COR[' + (acc_cnt - 1) + '][]');
    newAccord.find('#CourtesyCopy_' + acc_cnt + '_1').attr('name', 'CourtesyCopy[' + (acc_cnt - 1) + '][]');
    newAccord.find('#CourtesyCopy_' + acc_cnt + '_1').attr('onclick', 'dispPopUp(this.id);');
    newAccord.find('#hid_div_' + acc_cnt + '_1').attr('name', 'hid_div[' + (acc_cnt - 1) + '][]');
    newAccord.find('#ABN_' + acc_cnt + '_1').attr('name', 'ABN[' + (acc_cnt - 1) + '][]');

    newAccord.find('#hid_spec_' + acc_cnt + '_1').attr('name', 'hid_spec[' + (acc_cnt - 1) + '][]');
    newAccord.find('#spec_name_' + acc_cnt + '_1').attr('name', 'spec_name[' + (acc_cnt - 1) + '][0][]');
    newAccord.find('#spselect_' + acc_cnt + '_1').attr('name', 'spselect[' + (acc_cnt - 1) + '][0][]');
    newAccord.find('#spselect1_' + acc_cnt + '_1').attr('name', 'spselect1[' + (acc_cnt - 1) + '][0][]');
    newAccord.find('#spselect2_' + acc_cnt + '_1').attr('name', 'spselect2[' + (acc_cnt - 1) + '][0][]');
    newAccord.find('#spselect3_' + acc_cnt + '_1').attr('name', 'spselect3[' + (acc_cnt - 1) + '][0][]');
    newAccord.find('#spec_desc_' + acc_cnt + '_1').attr('name', 'spec_desc[' + (acc_cnt - 1) + '][0][]');

    newAccord.find('#ccselect_' + acc_cnt + '_1').attr('name', 'ccselect[' + (acc_cnt - 1) + '][]');
    newAccord.find('#viewButton_' + acc_cnt + '_1').attr('name', 'viewButton[' + (acc_cnt - 1) + '][]');
    newAccord.find('#accno_' + acc_cnt + '_1').attr('name', 'accno[' + (acc_cnt - 1) + '][]');
    newAccord.find('#accname_' + acc_cnt + '_1').attr('name', 'accname[' + (acc_cnt - 1) + '][]');
    newAccord.find('#faxno_' + acc_cnt + '_1').attr('name', 'faxno[' + (acc_cnt - 1) + '][]');
    newAccord.find('#faxname_' + acc_cnt + '_1').attr('name', 'faxname[' + (acc_cnt - 1) + '][]');
    newAccord.find('#priority_' + acc_cnt + '_1').attr('name', 'priority[' + (acc_cnt - 1) + '][]');
    newAccord.find('#status_' + acc_cnt + '_1').attr('name', 'status[' + (acc_cnt - 1) + '][]');
    newAccord.find('#billto_' + acc_cnt + '_1').attr('name', 'billto[' + (acc_cnt - 1) + '][]');

    // add class to addSpecimen
    $('#cloneID_' + acc_cnt + '_1').find('#addSpecimen_' + acc_cnt + '_1').addClass('addSpecimen_' + acc_cnt);

    $('#cloneID_' + acc_cnt + '_1').find('#patient_instructions_' + acc_cnt + '_1').attr('name', 'patient_instructions[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_1').find('#diagnoses_' + acc_cnt + '_1').attr('name', 'diagnoses[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_1').find('#procedures_' + acc_cnt + '_1').attr('name', 'procedures[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_1').find('#procedure_code_' + acc_cnt + '_1').attr('name', 'procedure_code[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_1').find('#procedure_suffix_' + acc_cnt + '_1').attr('name', 'procedure_suffix[' + (acc_cnt - 1) + '][]');

    var $radios = $('#specimencollected_' + acc_cnt + '_1');
    if ($radios.is(':checked') === false) {
        $radios.filter('[value=onsite]').attr('checked', true);
    }
    // Show or hide labcorp specific fields
    if ($("#lab_id_" + acc_cnt + "_1 option:selected").text().toLowerCase() == 'labcorp') {
        $("#addSpecimen_" + acc_cnt + "_1").css('display', 'inline-table');
        $("#addSpecimen").css('display', 'inline-table');
        $("#CORcaption_" + acc_cnt + "_1").css('display', 'table-cell');
        $("#CORtd_" + acc_cnt + "_1").css('display', 'table-cell');
        $("#CourtesyCopycaption_" + acc_cnt + "_1").css('display', 'table-cell');
        $("#CourtesyCopytd_" + acc_cnt + "_1").css('display', 'table-cell');
        $("#ABNcaption_" + acc_cnt + "_1").css('display', 'table-cell');
        $("#ABNtd_" + acc_cnt + "_1").css('display', 'table-cell');
        $("#specimencollectedcaption_" + acc_cnt + "_1").css('display', 'block');
        $("#specimencollectedtd_" + acc_cnt + "_1").css('display', 'block');
        $("#billtocaption_" + acc_cnt + "_1").css('display', 'block');
        $("#billtotd_" + acc_cnt + "_1").css('display', 'block');
    } else {
        $("#addSpecimen_" + acc_cnt + "_1").css('display', 'none');
        $("#addSpecimen").css('display', 'none');
    }

    $('#accord').accordion('add', {
        title: '<img  class="easyui-linkbutton" iconCls="icon-save" src="../css/icons/multiple.png"  border="0" />  New Procedure Order ' + idx,
        content: newAccord
    });

    idx++;
    j++;
    nOrd++;
    newAccord.find(".datetimeclass").datetimepicker({
        showSecond: true,
        dateFormat: "yy-mm-dd",
        timeFormat: "hh:mm:ss",
        showOn: 'button',
        buttonImage: '../css/icons/calendar.gif',
        buttonImageOnly: true,
    });
    $(".datetimeclass").on('change', function() {
        var x = $(this).val();
        if (!(x.match(/\d{4}\-\d{2}\-\d{2}\s\d{2}:\d{2}:\d{2}/))) {
            alert("Invalid Date Format");
            $(this).val("");
            $(this).attr('required', 'required');
        }
    });
}

/**
 * Procedure Order Edit
 */

var n = 0;
var order = '';
var status = '';
var orderNo = '';
$(function() {
    // Select Lab Order from List
    $('#dglist').datagrid({
        onSelect: function(rowIndex, rowData) {
            // Reset global variables
            idxVal = 1;
            ordVal = 1;
            orderID = '';
            incVal = 1;
            id = rowData.procedure_order_id;
            order = id;
            // Remove the existing Procedure Orders
            if (n > 0)
                remove();

            $.ajax({
                type: "POST",
                cache: false,
                dataType: "json",
                url: "./getOrderList",
                data: {
                    id: id
                },
                success: function(data) {
                    for (var i = 0; i < data.length; i++) {
                        //alert(data[i].procedure_order_id);
                        // List Lab Order
                        procedureOrder(data, i);
                        status = data[i].order_status;
                    }

                    //Setting specimencollected radio options
                    for (var i = 0; i < data.length; i++) {
                        var radios = $('#specimencollected_' + i + '_1');
                        //var radios = $('input[name^="specimencollected[' + (acc_cnt - 1) + '][]"]');  

                        if (data[i].psc_hold == 'onsite') {
                            radios.filter('[value=onsite]').attr('checked', true);
                        } else if (data[i].psc_hold == 'labsite') {
                            radios.filter('[value=labsite]').attr('checked', true);
                        }
                        // Default settings
                        if (radios.is(':checked') === false) {
                            radios.filter('[value=onsite]').attr('checked', true);
                        }
                    }
                    // Set if Status not pending disable editing
                    if (status != 'pending') {
                        //setTimeout('enableButton()', 500);
                        setTimeout(disableElements, 500);
                    }
                },
                error: function(data) {
                    alert("Ajax Fail");
                }
            });
            n++;
        }
    });

    $('#accord').accordion({
        onSelect: function(title, index) {
            arr = title.split(' ');
            orderNo = arr[11];
        }
    });
});



/**
 * Remove the existing Procedure Orders from the panell
 * (for listing the selected procedure order from the left side list)
 */
function remove() {
    var p = $('#accord').accordion('panels');
    var len = p.length;
    var p = $('#accord').accordion('select', 1);
    if (p) {
        var index = $('#accord').accordion('getPanelIndex', p);
        for (var i = len - 1; i >= 0; --i) {
            index = i;
            $('#accord').accordion('remove', index);
        }
    }
}

/**
 * List Procedure Order
 */
var idxVal = 1;
var ordVal = 1;
var orderID = '';
function procedureOrder(result, i) {
    if (orderID != result[i].procedure_order_id) {
        // Create a hidden field for each panell for row count
        $('#lab').append('<input type="hidden" id="accord_panel_' + idxVal + '" name="accord_panel_' + idxVal + '" value="1" />');
        // Set total panels
        $("#total_panel").val(ordVal);

        // Variables for New Procedure Order
        idx = idxVal;
        nOrd = ordVal + 1;

        orderID = result[i].procedure_order_id
        var acc_cnt = ordVal;

        // Create a hidden field for each procedure order id
        //$('#lab').append('<input type="hidden" id="procedure_order_id_'  + acc_cnt + '_1" name="procedure_order_id_[' + (acc_cnt - 1) + '][]" value="'+  result[i].procedure_order_id +'" />');

        var newAccord = $('#mainTemplate>*').clone(false).appendTo("#accord");
        newAccord.find('#main').closest("#panel_" + (ordVal - 1)).attr('id', 'main_' + ordVal);
        newAccord.find('#toolbar').attr('id', 'toolbar_' + ordVal);
        newAccord.find('#addButton').attr('id', 'addButton_' + ordVal);
        newAccord.find('#editor').attr('id', 'editor_' + ordVal);
        newAccord.find('#tt').attr('id', 'tt_' + ordVal);
        newAccord.find('#dgord').attr('id', 'dgord_' + ordVal);

        newAccord.find('#procedure_order_id').attr('id', 'procedure_order_id_' + acc_cnt + '_1');
        newAccord.find('#provider_1_1').attr('id', 'provider_' + acc_cnt + '_1');
        newAccord.find('#lab_id_1_1').attr('id', 'lab_id_' + acc_cnt + '_1');
        newAccord.find('#orderdate_1_1').attr('id', 'orderdate_' + acc_cnt + '_1');
        newAccord.find('#internaltimecaption').attr('id', 'internaltimecaption_' + acc_cnt + '_1');
        newAccord.find('#internaltime').attr('id', 'internaltime_' + acc_cnt + '_1');
        newAccord.find('#specimencollectedcaption').attr('id', 'specimencollectedcaption_' + acc_cnt + '_1');
        newAccord.find('#CORcaption').attr('id', 'CORcaption_' + acc_cnt + '_1');
        newAccord.find('#CourtesyCopycaption').attr('id', 'CourtesyCopycaption_' + acc_cnt + '_1');
        newAccord.find('#ABNcaption').attr('id', 'ABNcaption_' + acc_cnt + '_1');
        newAccord.find('#oderingdate').attr('id', 'oderingdate_' + acc_cnt + '_1');
        newAccord.find('#timecollected_1_1').attr('id', 'timecollected_' + acc_cnt + '_1');
        newAccord.find('#internal_comments_1_1').attr('id', 'internal_comments_' + acc_cnt + '_1');
        newAccord.find('#specimencollected_1_1').attr('id', 'specimencollected_' + acc_cnt + '_1');
        newAccord.find('#specimencollectedtd').attr('id', 'specimencollectedtd_' + acc_cnt + '_1');
        newAccord.find('#COR_1_1').attr('id', 'COR_' + acc_cnt + '_1');
        newAccord.find('#CORtd').attr('id', 'CORtd_' + acc_cnt + '_1');
        newAccord.find('#CourtesyCopy_1_1').attr('id', 'CourtesyCopy_' + acc_cnt + '_1');
        newAccord.find('#CourtesyCopytd').attr('id', 'CourtesyCopytd_' + acc_cnt + '_1');
        newAccord.find('#priority_1_1').attr('id', 'priority_' + acc_cnt + '_1');
        newAccord.find('#status_1_1').attr('id', 'status_' + acc_cnt + '_1');
        newAccord.find('#billtocaption').attr('id', 'billtocaption_' + acc_cnt + '_1');
        newAccord.find('#billtotd').attr('id', 'billtotd_' + acc_cnt + '_1');
        newAccord.find('#billto_1_1').attr('id', 'billto_' + acc_cnt + '_1');
        newAccord.find('#insTempl').attr('id', "insTempl_" + acc_cnt + "_1");
        newAccord.find('#ABN_1_1').attr('id', 'ABN_' + acc_cnt + '_1');
        newAccord.find('#ABNtd').attr('id', 'ABNtd_' + acc_cnt + '_1');

        // Remove duplicate Order Date and Internal Time Collected
        $('#oderingdate_' + acc_cnt + '_1 span').remove();
        $('#internaltime_' + acc_cnt + '_1 span').remove();

        var clone = $("#cloneID_1").clone(false).appendTo("#insTempl_" + acc_cnt + "_1");
        $("#insTempl_" + acc_cnt + "_1 table:last").attr('id', 'cloneID_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#diagnosestemplate').attr('id', 'diagnosestemplate_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#proceduretemplate').attr('id', 'proceduretemplate_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#diagnodiv').attr('id', 'diagnodiv_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#prodiv').attr('id', 'prodiv_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#procedure_order_seq_1_1').attr('id', 'procedure_order_seq_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#patient_instructions_1_1').attr('id', 'patient_instructions_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#diagnoses_1_1').attr('id', 'diagnoses_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#procedures_1_1').attr('id', 'procedures_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#procedure_code_1_1').attr('id', 'procedure_code_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#procedure_suffix_1_1').attr('id', 'procedure_suffix_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#AOEtemplate').attr('id', 'AOEtemplate_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#procedures').attr('id', 'procedures_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#AOE').attr('id', 'AOE_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#deleteButton').attr('id', 'deleteButton_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#addSpecimen').attr('id', 'addSpecimen_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#viewButton').attr('id', 'viewButton_' + acc_cnt + '_1');
        $('#cloneID_' + acc_cnt + '_1').find('#addButton').attr('id', 'addButton_' + acc_cnt + '_1');

        // Field name settings
        newAccord.find('#procedure_order_id_' + acc_cnt + '_1').attr('name', 'procedure_order_id[' + (acc_cnt - 1) + '][]');
        newAccord.find('#provider_' + acc_cnt + '_1').attr('name', 'provider[' + (acc_cnt - 1) + '][]');
        newAccord.find('#lab_id_' + acc_cnt + '_1').attr('name', 'lab_id[' + (acc_cnt - 1) + '][]');
        newAccord.find('#orderdate_' + acc_cnt + '_1').attr('name', 'orderdate[' + (acc_cnt - 1) + '][]');
        newAccord.find('#timecollected_' + acc_cnt + '_1').attr('name', 'timecollected[' + (acc_cnt - 1) + '][]');
        newAccord.find('#internal_comments_' + acc_cnt + '_1').attr('name', 'internal_comments[' + (acc_cnt - 1) + '][]');
        newAccord.find('#specimencollected_' + acc_cnt + '_1').attr('name', 'specimencollected[' + (acc_cnt - 1) + '][]');
        newAccord.find('#COR_' + acc_cnt + '_1').attr('name', 'COR[' + (acc_cnt - 1) + '][]');
        newAccord.find('#CourtesyCopy_' + acc_cnt + '_1').attr('name', 'CourtesyCopy[' + (acc_cnt - 1) + '][]');
        newAccord.find('#priority_' + acc_cnt + '_1').attr('name', 'priority[' + (acc_cnt - 1) + '][]');
        newAccord.find('#status_' + acc_cnt + '_1').attr('name', 'status[' + (acc_cnt - 1) + '][]');
        newAccord.find('#billto_' + acc_cnt + '_1').attr('name', 'billto[' + (acc_cnt - 1) + '][]');
        newAccord.find('#ABN_' + acc_cnt + '_1').attr('name', 'ABN[' + (acc_cnt - 1) + '][]');

        $('#cloneID_' + acc_cnt + '_1').find('#procedure_order_seq_' + acc_cnt + '_1').attr('name', 'procedure_order_seq[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_1').find('#patient_instructions_' + acc_cnt + '_1').attr('name', 'patient_instructions[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_1').find('#diagnoses_' + acc_cnt + '_1').attr('name', 'diagnoses[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_1').find('#procedures_' + acc_cnt + '_1').attr('name', 'procedures[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_1').find('#procedure_code_' + acc_cnt + '_1').attr('name', 'procedure_code[' + (acc_cnt - 1) + '][]');
        $('#cloneID_' + acc_cnt + '_1').find('#procedure_suffix_' + acc_cnt + '_1').attr('name', 'procedure_suffix[' + (acc_cnt - 1) + '][]');

        // Values assign to fields
        $('#lab_id_' + acc_cnt + '_1 option').each(function() {
            var arr = $(this).val().split('|');
            if (arr[0] == result[i].lab_id) {
                $('#lab_id_' + acc_cnt + '_1').val($(this).val());
                var id = 'lab_id_' + acc_cnt + '_1';
                checkLab($(this).val(), id);
            }
        });
        $('#procedure_order_id_' + acc_cnt + '_1').val(result[i].procedure_order_id);
        $('#provider_' + acc_cnt + '_1').val(result[i].provider_id);
        $('#orderdate_' + acc_cnt + '_1').val(result[i].date_ordered);
        $('#timecollected_' + acc_cnt + '_1').val(result[i].date_collected);
        $('#internal_comments_' + acc_cnt + '_1').val(result[i].internal_comments);
        $('#priority_' + acc_cnt + '_1').val(result[i].order_priority);
        $('#status_' + acc_cnt + '_1').val(result[i].order_status);

        // First row of the panell
        $('#procedure_order_seq_' + acc_cnt + '_1').val(result[i].procedure_order_seq);
        $('#patient_instructions_' + acc_cnt + '_1').val(result[i].patient_instructions);
        $('#diagnoses_' + acc_cnt + '_1').val(result[i].diagnoses);
        $('#procedures_' + acc_cnt + '_1').val(result[i].procedure_name);
        $('#procedure_code_' + acc_cnt + '_1').val(result[i].procedure_code);
        $('#procedure_suffix_' + acc_cnt + '_1').val(result[i].procedure_suffix);

        // AOE Question and Answer
        count = acc_cnt + '_1';
        ordercnt = acc_cnt - 1;
        AOE(count, result[i].procedure_order_id, result[i].procedure_code, result[i].procedure_name, result[i].procedure_order_seq, acc_cnt);

        /*var radios = $('#specimencollected_' + acc_cnt + '_1');
         //var radios = $('input[name^="specimencollected[' + (acc_cnt - 1) + '][]"]');  
         
         if (result[i].psc_hold == 'onsite') {
         radios.filter('[value=onsite]').attr('checked', true);
         } else if (result[i].psc_hold == 'labsite') {
         radios.filter('[value=labsite]').attr('checked', true);
         }
         // Default settings
         if(radios.is(':checked') === false) {
         radios.filter('[value=onsite]').attr('checked', true);
         }*/

        $('#accord').accordion('add', {
            title: '<img  class="easyui-linkbutton" iconCls="icon-save" src="../css/icons/multiple.png"  border="0" />  Procedure Order ' + result[i].procedure_order_id,
            content: newAccord
        });
        idxVal++;
        ordVal++;
    } else {
        // Clone Second and remaining rows
        var panellId = '_' + (ordVal - 1);
        if (i > 0) {
            procedureOrderRow(panellId, result[i]);
        }
    }
}

/**
 * List All row wise data
 */
var incVal = 1;
function procedureOrderRow(thisID, result) {
    incVal++;
    var arrId = thisID.split("_");
    var acc_cnt = arrId[1];
    // Setting row count for each panell
    var key = parseInt(arrId[1]) - 1;
    var rowCount = 1;
    rowCount = parseInt($('#accord_panel_' + key).val());
    incVal = rowCount + 1;

    $("#accord_panel_" + key).val(rowCount + 1);
    var clone = $("#cloneID_1").clone(false).appendTo("#insTempl_" + acc_cnt + "_1");
    $("#insTempl_" + acc_cnt + "_1 table:last").attr('id', 'cloneID_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#diagnosestemplate').attr('id', 'diagnosestemplate_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#proceduretemplate').attr('id', 'proceduretemplate_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#diagnodiv').attr('id', 'diagnodiv_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#prodiv').attr('id', 'prodiv_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedure_order_seq_1_1').attr('id', 'procedure_order_seq_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#patient_instructions_1_1').attr('id', 'patient_instructions_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#diagnoses_1_1').attr('id', 'diagnoses_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedures_1_1').attr('id', 'procedures_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedure_code_1_1').attr('id', 'procedure_code_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedure_suffix_1_1').attr('id', 'procedure_suffix_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#AOEtemplate').attr('id', 'AOEtemplate_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find("#procedures").attr('id', 'procedures_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find("#AOE").attr('id', 'AOE_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find("#deleteButton").attr('id', 'deleteButton_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find("#addSpecimen").attr('id', 'addSpecimen_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find("#viewButton").attr('id', 'viewButton_' + acc_cnt + '_' + incVal);
    $('#cloneID_' + acc_cnt + '_' + incVal).find("#addButton").attr('id', 'addButton_' + acc_cnt + '_' + incVal);

    // Field Name Change
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedure_order_seq_' + acc_cnt + '_' + incVal).attr('name', 'procedure_order_seq[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#patient_instructions_' + acc_cnt + '_' + incVal).attr('name', 'patient_instructions[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#diagnoses_' + acc_cnt + '_' + incVal).attr('name', 'diagnoses[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedures_' + acc_cnt + '_' + incVal).attr('name', 'procedures[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedure_code_' + acc_cnt + '_' + incVal).attr('name', 'procedure_code[' + (acc_cnt - 1) + '][]');
    $('#cloneID_' + acc_cnt + '_' + incVal).find('#procedure_suffix_' + acc_cnt + '_' + incVal).attr('name', 'procedure_suffix[' + (acc_cnt - 1) + '][]');

    // Value settings
    $('#procedure_order_seq_' + acc_cnt + '_' + incVal).val(result.procedure_order_seq);
    $('#patient_instructions_' + acc_cnt + '_' + incVal).val(result.patient_instructions);
    $('#diagnoses_' + acc_cnt + '_' + incVal).val(result.diagnoses);
    $('#procedures_' + acc_cnt + '_' + incVal).val(result.procedure_name);
    $('#procedure_code_' + acc_cnt + '_' + incVal).val(result.procedure_code);
    $('#procedure_suffix_' + acc_cnt + '_' + incVal).val(result.procedure_suffix);

    // AOE Question and Answer
    count = acc_cnt + '_' + incVal;
    ordercnt = acc_cnt - 1;
    AOE(count, result.procedure_order_id, result.procedure_code, result.procedure_name, result.procedure_order_seq, ordercnt);
}

/**
 * List AOE Question and Answer
 */
function AOE(count, orderId, ProcedureCode, procedure, seq, ordercnt) {
    $.post("./getLabOrderAOE", {
        inputValue: orderId,
        seq: seq,
    },
            function(data) {
                if (data.response == true) {
                    aoeArray = data.aoeArray;
                    j = '<table>';
                    i = 0;
                    //ordercnt = 'procedures_';
                    for (var questioncode in aoeArray) {
                        i++;
                        splitArr = aoeArray[questioncode].split("|-|");
                        tips = splitArr[3];
                        if (tips)
                            cls = "personPopupTrigger";
                        else
                            cls = "";
                        j += '<tr><td>' + i + '</td><td>' + splitArr[0] + '</td><td><input rel="' + tips + '" class="combo ' + cls + '" type="text" name="AOE_' + ordercnt + "_" + ProcedureCode + "_" + splitArr[2] + '" value="' + splitArr[4] + '"></td></tr>';
                    }
                    j += "</table>";
                    contents = "<fieldset><legend>" + procedure + "</legend>";
                    if (j === '<table></table>') {
                        $("#AOEtemplate_" + count).css('display', 'none');
                        $("#AOE_" + count).html("");
                    }
                    else {
                        $("#AOEtemplate_" + count).css('display', '');
                        $("#AOE_" + count).html(contents + j + "</fieldset>");
                    }
                } else {
                    alert("Failed");
                }
            }, 'json');
}

/**
 * Save Data
 */
var txt
function saveEditFrm() {
    if (status == 'pending') {
        var url = './saveData';//'./updateData';
        if (order != '') {
            $('#p').progressbar('setValue', 0);
            txt = 'Saving ';
            start();
            $('#lab').form('submit', {
                url: url,
                onSubmit: function() {
                    return;
                },
                success: function(result) {
                    var result = eval('(' + result + ')');
                    if (result.errorMsg) {
                        $.messager.show({
                            title: 'Error',
                            msg: result.errorMsg
                        });
                    }
                }
            });
        } else {
            alert('Please select a procedure order ... !');
        }
    } else {
        alert('Could not save, Order Status is ' + status);
    }
}

/**
 * Remove Procedure order and its group
 */
function removeOrder() {
    if (status == 'pending') {
        var url = './removeLabOrder';
        if (orderNo != '') {
            $('#p').progressbar('setValue', 0);
            txt = 'Removing ';
            start();
            $.ajax({
                type: "POST",
                cache: false,
                url: url,
                data: {
                    orderID: orderNo
                },
                success: function(data) {
                    //alert("Successfully removed");
                    removeAccord();
                },
                error: function(data) {
                    alert("Ajax Fail");
                }
            });
        } else {
            alert('Please select a procedure order ... !');
        }
    } else {
        alert('Could not remove, Order Status is ' + status);
    }
}

/**
 * Show progress information
 */
function start() {
    $("#p").css("display", "block");
    var value = $('#p').progressbar('getValue');

    if (value < 100) {
        value += Math.floor(Math.random() * 10);
        $('#p').progressbar('setValue', value);
        $('#p').progressbar({
            text: txt + ' ... {value}%'
        });
        setTimeout(arguments.callee, 200);
    } else {
        $("#p").css("display", "none");
    }
}
;

/**
 * Disable edit if status is not pending 
 */
function disableElements() {
    toggleDisabled(document.getElementById("orderlist"));
}
function toggleDisabled(el) {
    try {
        el.disabled = el.disabled ? false : true;
    }
    catch (E) {
    }
    if (el.childNodes && el.childNodes.length > 0) {
        for (var x = 0; x < el.childNodes.length; x++) {
            toggleDisabled(el.childNodes[x]);
        }
    }
}

function addRowEdit(thisId) {
    if (status == 'pending') {
        addRow(thisId);
    }
}

function cancelItemEdit(thisId) {
    if (status == 'pending') {
        cancelItem(thisId);
    }
}

function newOrderEdit() {
    if (status == 'pending') {
        newOrder();
    }
}

function removeOrderEdit() {
    if (status == 'pending') {
        removeOrder();
    }
}


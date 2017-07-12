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
 * getPatient
 * @param {type} inputString
 * @param {type} thisID
 * @param {type} url
 * @returns {undefined}
 */
function getPatient(inputString, thisID, url) {
    if (inputString.length == 0) {
        $("#patdiv").html("");
        $("#patdiv").css('display', 'none');
        return;
    }
    $.post(url, {
        type: "getPatient",
        inputValue: inputString
    },
    function(data) {
        if (data.response == true) {
            //alert(data.patientArray);
            if (data.patientArray.length > 0) {
                patientArray = data.patientArray;
                j = '<ul class="suggestion">';
                for (var patient in patientArray) {
                    splitArr = patientArray[patient].split("|-|");
                    //alert('"'+splitArr[3]+'"');
                    j += "<li onclick=loadPatient('" + splitArr[0].replace(/\s+/gi, "&#160;") + "','" + splitArr[1] + "')><a href='#'>" + splitArr[0] + " - " + splitArr[1] + "</a></li>";
                }
                j += "</ul>";
                //alert(j);
                $("#patdiv").css('display', 'block');
                $("#patdiv").html(j);
            } else {
                $("#patdiv").html("");
                $("#patdiv").css('display', 'none');
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
 * loadPatient
 * @param {type} patname
 * @param {type} pid
 * @returns {undefined}
 */
function loadPatient(patname, pid) {
    $('#search_patient').val(patname);
    $('#patient_id').val(pid);
    $("#patdiv").css('display', 'none');
}
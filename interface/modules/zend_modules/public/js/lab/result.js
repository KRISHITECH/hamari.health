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
 * Result View Screen
 */

$(function() {
    $('#txtSearch').keypress(function(e) {
        if (e.which == 13) {
            submitPage();
        }
    });
    $('#txtDob').keypress(function(e) {
        if (e.which == 13) {
            submitPage();
        }
    });
    if ($('#dg').length) {
        $('#dg').edatagrid({
            url: './result/resultShow', // show all the pending results
            saveUrl: './result/resultUpdate', // save the result 
            updateUrl: './result/resultUpdate'
        })
    }
});

// Search options, Status and Ordered Date
function doSearch() {
    document.getElementById("frmsearch").submit();
}

// Result request with order id
function getResult(target) {
    var loc = window.location;
    var ajax_url = "./result/getLabResultPDF";
    if (String(loc).match(/\/index/))
        ajax_url = "../result/getLabResultPDF";
    var order_id = target;
    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: ajax_url,
        data: {
            order_id: order_id
        },
        success: function(data) {
            $.each(data, function(jsonIndex, jsonValue) {
                if (jsonValue['return'] == 1) {
                    alert(jsonValue['msg']);
                } else if (jsonValue['return'] == 0) {
                    var order_id = jsonValue['order_id'];
                    if (String(loc).match(/\/index/))
                        window.location.assign("../result/getLabResultPDF?order_id=" + order_id);
                    else
                        window.location.assign("./result/getLabResultPDF?order_id=" + order_id);
                }
            });

        },
        error: function(data) {
            alert('Ajax Fail');
        }
    });

}

// Requisition request with order id
function getRequisition(target) {
    var loc = window.location;
    var ajax_url = "./result/getLabRequisitionPDF";
    if (String(loc).match(/\/index/))
        ajax_url = "../result/getLabRequisitionPDF";
    var order_id = target;
    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: ajax_url,
        data: {
            order_id: order_id
        },
        success: function(data) {
            $.each(data, function(jsonIndex, jsonValue) {
                if (jsonValue['return'] == 1) {
                    alert(jsonValue['msg']);
                } else if (jsonValue['return'] == 0) {
                    var order_id = jsonValue['order_id'];
                    if (String(loc).match(/\/index/))
                        window.location.assign("../result/getLabRequisitionPDF?order_id=" + order_id);
                    else
                        window.location.assign("./result/getLabRequisitionPDF?order_id=" + order_id);
                }
            });

        },
        error: function(data) {
            alert('Ajax Fail');
        }
    });
}
/**
 * getLabelDownload
 * @param {type} target
 * @returns {undefined}
 */
function getLabelDownload(target) {
    var order_id = target;
    //alert('Order Id is ..' + order_id);
    var loc = window.location;
    if (String(loc).match(/\/index/))
        window.location.assign("./../result/getLabelDownload?order_id=" + order_id);
    else
        window.location.assign("./result/getLabelDownload?order_id=" + order_id);
}
/**
 * showcomments
 * @param {type} id
 * @returns {undefined}
 */
function showcomments(id) {
    $('#full_comments_' + id).toggle('slow');
}

/*
 Show Popup and edit Result Status, Fecility, Comments and Notes
 */
function editComments(report_id) {

    $('#rep_id').val(report_id);
    $('#dlg').dialog('open').dialog('setTitle', 'Result');
    $('#formResultNotes').val("");
    $('#formResultComments').val("");
    $("input[name=formResultFacility]").val("");
    $("input[name=formResultStatus]").val("");
    $('#cc1').combobox('setValue', "");
    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "./result/getResultComments",
        data: {
            prid: report_id
        },
        success: function(data) {
            $.each(data, function(entryIndex, entry) {
                $("input[name=formResultStatus]").val(entry['result_status']);
                $("input[name=formResultFacility]").val(entry['facility']);
                $('#formResultComments').val(entry['comments']);
                $('#formResultNotes').val(entry['notes']);
                $('#cc1').combobox('setValue', $.trim(entry['result_status']));


            });
        },
        error: function(data) {
            //alert("Ajax Fail");
        }
    });
    $('#fm').form('load');
    url = '';
}

//save comments with procedure_report_id
function saveComments() {

    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "./result/insertLabComments",
        data: {
            procedure_report_id: $('#rep_id').val(),
            result_status: $("input[name=formResultStatus]").val(),
            facility: $("input[name=formResultFacility]").val(),
            comments: $("#formResultComments").val() + '\n' + $("#formResultNotes").val(),
            notes: $("#formResultNotes").val()

        },
        success: function(data) {
            alert("Comments saved successfully");
            $('#dlg').dialog('close');

        },
        error: function(data) {
            alert('Ajax Fail');
        }
    });
    $('#dlg').dialog('close');
}
/**
 * movenext
 * @returns {undefined}
 */
function movenext() {

    winloc = "'" + window.location + "'";
    var pageno = document.getElementById('pageno').value
    var fromdiag = $('#fromdiag').val();
    var lab = $("#lab_id").combobox('getValue');
    var searchStatusOrder = $("#searchStatusOrder").combobox('getValue');
    var searchStatusReport = $("#cc").combobox('getValue');
    var searchStatusResult = $("#searchStatusResult").combobox('getValue');
    var searchenc = $("#searchenc").combobox('getValue');
    var searchtest = $("#searchtest").val();
    var dtFrom = document.getElementsByName('dtFrom')[0].value;
    var dtTo = document.getElementsByName('dtTo')[0].value;
    if (winloc.indexOf("index") != "-1")
        window.location.assign("../result/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchenc=" + searchenc + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);
    else
        window.location.assign("./result/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchenc=" + searchenc + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);

}
/**
 * movenext_new
 * @returns {undefined}
 */
function movenext_new() {

    winloc = "'" + window.location + "'";
    var pageno = document.getElementById('pageno').value;
    var fromdiag = $('#fromdiag').val();
    var lab = $("#lab_id").combobox('getValue');
    var searchStatusOrder = $("#searchStatusOrder").combobox('getValue');
    var searchStatusReport = $("#cc").combobox('getValue');
    var searchStatusResult = $("#searchStatusResult").combobox('getValue');
    var searchpid_id = $("#searchpid_id").val();
    var searchpid_name = $("#searchpid_name").val();
    var searchtest = $("#searchtest").val();
    var dtFrom = document.getElementsByName('dtFrom')[0].value;
    var dtTo = document.getElementsByName('dtTo')[0].value;
    if (winloc.indexOf("index") != "-1")
        window.location.assign("../resultnew/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchpid_id=" + searchpid_id + "&searchpid_name=" + searchpid_name + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);
    else
        window.location.assign("./resultnew/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchpid_id=" + searchpid_id + "&searchpid_name=" + searchpid_name + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);

}

/**
 * movenext1
 * @param {type} pageno
 * @returns {undefined}
 */
function movenext1(pageno) {

    winloc = "'" + window.location + "'";
    //alert(winloc);
    //var pageno = document.getElementById('pageno').value;
    var fromdiag = $('#fromdiag').val();
    var lab = $("#lab_id").combobox('getValue');
    var searchStatusOrder = $("#searchStatusOrder").combobox('getValue');
    var searchStatusReport = $("#cc").combobox('getValue');
    var searchStatusResult = $("#searchStatusResult").combobox('getValue');
    var searchenc = $("#searchenc").combobox('getValue');
    var searchtest = $("#searchtest").val();
    var dtFrom = document.getElementsByName('dtFrom')[0].value;
    var dtTo = document.getElementsByName('dtTo')[0].value;
    if (winloc.indexOf("index") != "-1")
        window.location.assign("../result/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchenc=" + searchenc + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);
    else
        window.location.assign("./result/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchenc=" + searchenc + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);

}
/**
 * movenext1_new
 * @param {type} pageno
 * @returns {undefined}
 */
function movenext1_new(pageno) {

    winloc = "'" + window.location + "'";
    //alert(winloc);
    //var pageno = document.getElementById('pageno').value;
    var fromdiag = $('#fromdiag').val();
    var lab = $("#lab_id").combobox('getValue');
    var searchStatusOrder = $("#searchStatusOrder").combobox('getValue');
    var searchStatusReport = $("#cc").combobox('getValue');
    var searchStatusResult = $("#searchStatusResult").combobox('getValue');
    var searchpid_id = $("#searchpid_id").val();
    var searchpid_name = $("#searchpid_name").val();
    var searchtest = $("#searchtest").val();
    var dtFrom = document.getElementsByName('dtFrom')[0].value;
    var dtTo = document.getElementsByName('dtTo')[0].value;
    if (winloc.indexOf("index") != "-1")
        window.location.assign("../resultnew/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchpid_id=" + searchpid_id + "&searchpid_name=" + searchpid_name + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);
    else
        window.location.assign("./resultnew/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchpid_id=" + searchpid_id + "&searchpid_name=" + searchpid_name + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);

}
/**
 * movefirstlast
 * @param {type} pageno
 * @returns {undefined}
 */
function movefirstlast(pageno) {

    winloc = "'" + window.location + "'";
    var fromdiag = $('#fromdiag').val();
    var lab = $("#lab_id").combobox('getValue');
    var searchStatusOrder = $("#searchStatusOrder").combobox('getValue');
    var searchStatusReport = $("#cc").combobox('getValue');
    var searchStatusResult = $("#searchStatusResult").combobox('getValue');
    var searchenc = $("#searchenc").combobox('getValue');
    var searchtest = $("#searchtest").val();
    var dtFrom = document.getElementsByName('dtFrom')[0].value;
    var dtTo = document.getElementsByName('dtTo')[0].value;
    if (winloc.indexOf("index") != "-1")
        window.location.assign("../result/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchenc=" + searchenc + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);
    else
        window.location.assign("./result/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchenc=" + searchenc + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);

}
/**
 * movefirstlast_new
 * @param {type} pageno
 * @returns {undefined}
 */
function movefirstlast_new(pageno) {

    winloc = "'" + window.location + "'";
    var fromdiag = $('#fromdiag').val();
    var lab = $("#lab_id").combobox('getValue');
    var searchStatusOrder = $("#searchStatusOrder").combobox('getValue');
    var searchStatusReport = $("#cc").combobox('getValue');
    var searchStatusResult = $("#searchStatusResult").combobox('getValue');
    var searchpid_id = $("#searchpid_id").val();
    var searchpid_name = $("#searchpid_name").val();
    var searchtest = $("#searchtest").val();
    var dtFrom = document.getElementsByName('dtFrom')[0].value;
    var dtTo = document.getElementsByName('dtTo')[0].value;
    if (winloc.indexOf("index") != "-1")
        window.location.assign("../resultnew/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchpid_id=" + searchpid_id + "&searchpid_name=" + searchpid_name + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);
    else
        window.location.assign("./resultnew/index?pageno=" + pageno + "&fromdiag=" + fromdiag + "&searchStatusOrder=" + searchStatusOrder + "&searchStatusReport=" + searchStatusReport + "&searchStatusResult=" + searchStatusResult + "&lab=" + lab + "&searchpid_id=" + searchpid_id + "&searchpid_name=" + searchpid_name + "&searchtest=" + searchtest + "&dtFrom=" + dtFrom + "&dtTo=" + dtTo);

}


// Freezing cells 
//$("table> tr:last").hide();? ...
$(function() {

    //$('#labresult tr:last').hide();
    if ($('#dg').length) {
        $('#dg').edatagrid({
            rowStyler: function(index, row) {
                if (row.editor == 1) {
                    return 'background-color:#E0ECFF;color:#0E2D5F;';
                }
            },
            onLoadSuccess: function() {
                var lastIndex = $('#dg').datagrid('getRows').length - 1;
                if (lastIndex > 0) {
                    $("#datagrid-row-r2-1-" + lastIndex).hide();
                    $("#datagrid-row-r2-2-" + lastIndex).hide();
                }
            },
            onSelect: function(rowIndex, rowData) {
                var row = $('#dg').datagrid('getSelected');
                var fieldValue = $.trim(rowData.procedure_name);
                var editor = rowData.editor;
                if (editor == 1) {
                    //alert('Disabled');
                    $('#dg').edatagrid('cancelRow');
                    $('#dg').edatagrid('cancelEdit');
                    $('#dg').focus();
                }
                if (rowIndex == 0) {
                    $('#datagrid-row-r2-2-0').focus();
                }

                if (fieldValue.length == 0 || editor == 1) {
                    var opts = $('#dg').datagrid('getColumnOption', 'date_report');
                    opts.editor = {
                        type: '',
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'date_collected');
                    opts.editor = {
                        type: '',
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'specimen_num');
                    opts.editor = {
                        type: '',
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'report_status');
                    opts.editor = {
                        type: '',
                    };
                    if (editor == 1) {
                        var opts = $('#dg').datagrid('getColumnOption', 'abnormal');
                        opts.editor = {
                            type: '',
                        };
                        var opts = $('#dg').datagrid('getColumnOption', 'result');
                        opts.editor = {
                            type: '',
                        };
                        var opts = $('#dg').datagrid('getColumnOption', 'pt2_units');
                        opts.editor = {
                            type: '',
                        };
                        var opts = $('#dg').datagrid('getColumnOption', 'pt2_range');
                        opts.editor = {
                            type: '',
                        };
                    }
                } else {
                    var opts = $('#dg').datagrid('getColumnOption', 'date_report');
                    opts.editor = {
                        type: 'datebox',
                        options: {
                            required: true
                        }
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'date_collected');
                    opts.editor = {
                        type: 'datebox',
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'specimen_num');
                    opts.editor = {
                        type: 'text',
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'report_status');
                    opts.editor = {
                        type: 'combobox',
                        options: {
                            valueField: 'value',
                            textField: 'label',
                            url: './result/getLabOptions?opt=status&optId=report',
                            required: false
                        }
                    };
                    //if (editor == 0) {
                    var opts = $('#dg').datagrid('getColumnOption', 'abnormal');
                    opts.editor = {
                        type: 'combobox',
                        options: {
                            valueField: 'value',
                            textField: 'label',
                            url: './result/getLabOptions?opt=abnormal&optId=abnormal',
                            required: false
                        }
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'result');
                    opts.editor = {
                        type: 'text',
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'pt2_units');
                    opts.editor = {
                        type: 'text',
                    };
                    var opts = $('#dg').datagrid('getColumnOption', 'pt2_range');
                    opts.editor = {
                        type: 'text',
                    };
                    //}
                }
                fieldValue = '';
                //$('#dg').datagrid('unselectRow',rowIndex);
                return;
            },
        });
    }
});
/**
 * submitPage
 * @returns {undefined}
 */
function submitPage() {
    $('#unassociated').attr('action', 'unassociated?type=resultsonly');
    $('#unassociated').submit();
}

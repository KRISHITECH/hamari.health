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
 * providers.js
 */

$(function() {
    // Providers (Add or Edit) Dialog Box
    $('#dlgProvider').dialog({
        title: 'Providers',
        width: 850,
        height: 430,
        closed: true,
        cache: false,
        autoOpen: false,
        iconCls: 'icon-providers',
        resizable: true,
        minimizable: true,
        maximizable: true,
        modal: true
    });

    // Procedure Provider Data Grid
    $('#dgProvider').datagrid({
        // Double click on the data grid row and edit option
        onDblClickRow: function(rowIndex, rowData) {
            editProvider();
        },
        onClickRow: function(rowIndex, rowData) {
            $("#editPProvider").css("display", "block");
            $("#destroyPProvider").css("display", "block");
        }
    });
});

var url;
// New Procedure Provider
function newProvider() {
    $('#dlg').dialog('open').dialog('setTitle', 'New Procedure Provider');
    $('#fmProcedureProvider').form('clear');
    url = './provider/saveProcedureProvider';
}

// Edit Procedure Provider
function editProvider() {
    var row = $('#dgProvider').datagrid('getSelected');
    if (row) {
        $('#DorP').combobox('setValue', row.DorP);
        $('#protocol').combobox('setValue', row.protocol);
        $('#ppid').val(row.ppid);
        $('#dlg').dialog('open').dialog('setTitle', 'Edit Procedure Provider');
        $('#fmProcedureProvider').form('load', row);
        url = './provider/saveProcedureProvider?id=' + row.ppid;
    }
}

// Save Procedure Provider (New Or Update)
function saveProviders() {
    if ($('#remote_host').val()) {
        getMirthLabId();
    }
    $('#fmProcedureProvider').form('submit', {
        url: url,
        onSubmit: function() {
            return $(this).form('validate');
        },
        success: function(response) {//alert(response.toSource());
            var result = eval('(' + response + ')');
            if (result.errorMsg) {
                $.messager.show({
                    title: 'Error',
                    msg: result.errorMsg
                });
            } else {
                $('#dlg').dialog('close');
                $('#dgProvider').datagrid('reload');
            }
        }
    });
}

// Delete Procedure Provider
function destroyProvider() {
    var row = $('#dgProvider').datagrid('getSelected');
    if (row) {
        $.messager.confirm('Confirm', 'Are you sure you want to destroy this provider?', function(r) {
            if (r) {
                $.post('./provider/deleteProcedureProvider', {ppid: row.ppid}, function(result) {
                    if (result.success) {
                        $('#dgProvider').datagrid('reload');
                    } else {
                        $.messager.show({
                            title: 'Error',
                            msg: result.errorMsg
                        });
                    }
                }, 'json');
            }
        });
    }
}
/**
 * getMirthLabId
 * @returns {undefined}
 */
function getMirthLabId() {
    $.ajax({
        type: "POST",
        url: "./provider/getMirthProviderId",
        async: false,
        data: {
            username: $('#login').val(),
            password: $('#password').val(),
            host: $('#remote_host').val(),
            send_fac_id: $('#send_fac_id').val()
        },
        success: function(thedata) {
            if (thedata['lab_id'] == null) {
                alert("Could not find a matching remote Lab Id");
            }
            $('#mirth_lab_id').val(thedata['lab_id']);
            $('#mirth_lab_name').val(thedata['lab_name']);
        },
        error: function() {
            alert("Failed to get the remote Lab Id");
        }
    });
}
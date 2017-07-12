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
var mycolumns = [[
        {field: 'id', title: 'Type ID', width: 50, sortable: true},
        {field: 'name', title: 'Name', width: 150, sortable: true},
        {field: 'value', title: 'Value', width: 400, resizable: false}
    ]];

var addcolumns = [[
        {field: 'name', title: 'Name', width: 150, sortable: true},
        {field: 'value', title: 'Value', width: 400, resizable: false}
    ]];

/**
 * addConfig
 * @returns {undefined}
 */


function addConfig()
{
    clearAll();
    document.getElementById('edit_div').innerHTML = "";
    document.getElementById('add_div').style.display = "block";
    //alert("ADD ITEM");

    collapseCategory('order', '');
    collapseCategory('result', '');
    collapseCategory('reccomendation', '');

    expandCategory('group', '');
    //alert("ADD ITEM");
    //alert('add '+document.getElementById('action').value);
    document.getElementById('action').value = "add";
    /*//alert('add '+document.getElementById('action').value);
     $('#pg').propertygrid({						
     url: './configuration/getConfigAddPageDeatils',  
     showGroup: true,  
     columns: addcolumns,
     width:700,
     height:'auto'//500
     });
     */
    $('#w').window('open');
    $('#save').show();
    keyWord = '';
    var $radios = $('#standard_code');
    if ($radios.is(':checked') === false) {
        $radios.filter('[value=icd9]').attr('checked', true);
    }

    //collapseChildGroups();			
}
/**
 * deleteItem
 * @param {type} row_id
 * @returns {undefined}
 */
function deleteItem(row_id)
{
    var conf = confirm("Do You Really Want to Delete this Item ?");
    if (conf)
    {
        //alert("row id :"+row_id);
        $.ajax({
            type: "GET",
            cache: false,
            dataType: "json",
            url: "./configuration/deleteConfigDetails?type_id=" + row_id,
            data: {
            },
            success: function(data) {
                if (data.unremoved) {
                    alert("Already used for lab ordering. So it can't be deleted");
                    $('#tg').treegrid('reload');
                }
                else {
                    alert('Items Deleted Successfully');
                    $('#tg').treegrid('reload');
                }
                //alert("Ajax success");
            },
            error: function(data) {
                alert("Ajax Fail");
            }
        });


    }


}

var grp_inc;
var ord_inc;
var res_inc;
var rec_inc;

/**
 * editItem
 * @param {type} row_id
 * @returns {undefined}
 */

function editItem(row_id) {


    grp_inc = 0;
    ord_inc = 0;
    res_inc = 0;
    rec_inc = 0;

    document.getElementById('grp_count').value = grp_inc;
    document.getElementById('ord_count').value = ord_inc;
    document.getElementById('res_count').value = res_inc;
    document.getElementById('rec_count').value = rec_inc;

    document.getElementById('init_edit_id').value = row_id;

    document.getElementById('edit_div').innerHTML = "";
    var type;
    var count;

    //alert("edit");

    clearAll();
    document.getElementById('add_div').style.display = "none";





    document.getElementById('action').value = "edit";
    //alert("EDIT ITEM");
    //alert('going 2 reload'+row);





    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "./configuration/getConfigEditDeatils?type_id=" + row_id,
        data: {
        },
        success: function(data) {
            //alert("Ajax success"+data.toSource());


            var init_type = "";

            $.each(data, function(jsonIndex, jsonValue) {
                //alert("type id :"+jsonValue['type_id']);
                //parent_id = jsonValue['type_id'];

                if (init_type == "")
                {
                    init_type = jsonValue['group'];
                }

                type = jsonValue['group'];


                if (type == "grp")
                {
                    cloneDiv("group");
                    for (var field in jsonValue)
                    {
                        //alert(field+" : = "+jsonValue[field]);
                        if (field == "group")
                        {
                            continue;
                        }
                        document.getElementById(field + '_' + grp_inc).value = jsonValue[field];
                    }
                }
                else if (type == "ord")
                {
                    cloneDiv("order");
                    for (var field in jsonValue)
                    {
                        //alert(field+" : = "+jsonValue[field]);
                        if (field == "group")
                        {
                            continue;
                        }
                        if (field == 'order_from') {
                            for (var i = 0; i < document.getElementById(field + '_' + ord_inc).length; i++) {
                                var lab_arr = document.getElementById(field + '_' + ord_inc).options[i].value.split('|');
                                if (lab_arr[0] == jsonValue[field]) {
                                    document.getElementById(field + '_' + ord_inc).options[i].selected = true;
                                    break;
                                }
                            }
                        } else {
                            document.getElementById(field + '_' + ord_inc).value = jsonValue[field];
                        }
                    }
                }
                else if (type == "res")
                {
                    cloneDiv("result");
                    for (var field in jsonValue)
                    {
                        //alert(field+" : = "+jsonValue[field]);
                        if (field == "group")
                        {
                            continue;
                        }
                        document.getElementById(field + '_' + res_inc).value = jsonValue[field];
                    }
                }
                else if (type == "rec")
                {
                    cloneDiv("reccomendation");
                    for (var field in jsonValue)
                    {
                        //alert(field+" : = "+jsonValue[field]);
                        if (field == "group")
                        {
                            continue;
                        }
                        document.getElementById(field + '_' + rec_inc).value = jsonValue[field];
                    }
                }

            });

            document.getElementById('grp_count').value = grp_inc;
            document.getElementById('ord_count').value = ord_inc;
            document.getElementById('res_count').value = res_inc;
            document.getElementById('rec_count').value = rec_inc;

            //alert('init :'+init_type);
            if (init_type == "grp")
            {
                //alert('grp expnd');
                expandCategory("group", 1);
            }
            else if (init_type == "ord")
            {
                //alert('grp expnd');
                expandCategory("order", 1);
            }
            else if (init_type == "res")
            {
                //alert('grp expnd');
                expandCategory("result", 1);
            }
            else if (init_type == "rec")
            {
                //alert('grp expnd');
                expandCategory("reccomendation", 1);
            }

        },
        error: function(data) {
            alert("Ajax Fail");
        }
    });


    document.getElementById('edit_div').style.display = "block";

    $('#w').window('open');
    $('#save').show();
    keyWord = '';
    var $radios = $('#standard_code');
    if ($radios.is(':checked') === false) {
        $radios.filter('[value=icd9]').attr('checked', true);
    }






}

/**
 * collapseChildGroups
 * @param {type} rowid
 * @returns {undefined}
 */
function collapseChildGroups(rowid)
{
    $('#pg').propertygrid({
        onLoadSuccess: function(data)
        {
            $('#pg').propertygrid('collapseGroup');
            $('#pg').propertygrid('expandGroup', 0);
        }
    });

}
/**
 * collapseOtherGroups
 * @returns {undefined}
 */
function collapseOtherGroups()
{
    $('#pg').propertygrid({
        onExpand: function(row)
        {
            alert("N");
        }
    });

}






function loadRemote() {
    $('#ff').form('load', '../treegrid/treegrid_data2.json');
}

function sleep()
{

}




function saveItem()
{
    if ($('#order_name').val()) {
        if (!$('#order_procedurecode').val())
        {
            alert("Please Enter Procedure Code");
            return false;
        }
    }
    else {
        if ($('#order_procedurecode').val())
        {
            alert("Please Enter Procedure Name");
            return false;
        }
    }
    var parent_typeid = document.getElementById('init_edit_id').value;

    if ((document.getElementById('action').value == "add") || (document.getElementById('action').value == "addExist"))
    {
        addItem();
    }
    else
    {


        var input_arr = new Array();

        var grp_arr = new Array('group_name', 'group_description');

        var ord_arr = new Array('order_name', 'order_description', 'order_sequence', 'order_from', 'order_procedurecode',
                'order_standardcode', 'order_bodysite', 'order_specimentype', 'order_administervia', 'order_laterality');

        var res_arr = new Array('result_name', 'result_description', 'result_sequence', 'result_defaultunits', 'result_defaultrange', 'result_followupservices');

        var rec_arr = new Array('reccomendation_name', 'reccomendation_description', 'reccomendation_sequence',
                'reccomendation_defaultunits', 'reccomendation_defaultrange', 'reccomendation_followupservices');


        var column_arr = {'group_name': 'name', 'group_description': 'description',
            'order_name': 'name', 'order_description': 'description', 'order_sequence': 'seq', 'order_from': 'lab_id',
            'order_procedurecode': 'procedure_code', 'order_standardcode': 'standard_code', 'order_bodysite': 'body_site',
            'order_specimentype': 'specimen', 'order_administervia': 'route_admin', 'order_laterality': 'laterality',
            'result_name': 'name', 'result_description': 'description', 'result_sequence': 'seq',
            'reccomendation_name': 'name', 'reccomendation_description': 'description', 'reccomendation_sequence': 'seq',
            'result_defaultunits': 'units', 'result_defaultrange': 'range', 'result_followupservices': 'related_code',
            'reccomendation_defaultunits': 'units', 'reccomendation_defaultrange': 'range', 'reccomendation_followupservices': 'related_code'};

        var params;

        //alert('group count :'+$('#grp_count').val());
        //alert('ord count :'+$('#ord_count').val());
        //alert('res count :'+$('#res_count').val());
        //alert('rec count :'+$('#rec_count').val());


        for (var i = 1; i <= $('#grp_count').val(); i++)
        {
            if ($('#group_name_' + i).val().trim() != "")
            {
                params = "";
                //alert("index :"+index+" => "+input_arr[index]);
                params = "?procedure_type=grp&type_id=" + $('#group_type_id_' + i).val();

                for (var ind in grp_arr)
                {
                    if ($('#' + grp_arr[ind] + '_' + i).val().trim() != "")
                    {
                        params += "&" + column_arr[grp_arr[ind]] + "=" + $('#' + grp_arr[ind] + '_' + i).val();
                    }
                }
                //alert("submitting : group params : "+params);

                $.ajax({
                    type: "GET",
                    cache: false,
                    dataType: "json",
                    async: false,
                    url: "./configuration/saveConfigDetails" + params,
                    data: {
                    },
                    success: function(data) {
                        //alert("Ajax success"+data);
                        //alert("myObject is " + data.toSource());
                        $.each(data, function(jsonIndex, jsonValue) {
                            //alert("new type id :"+jsonValue['type_id']);								
                        });
                    },
                    error: function(data) {
                        alert("Ajax Fail");
                    }
                });



            }
        }

        for (var i = 1; i <= $('#ord_count').val(); i++)
        {
            if ($('#order_name_' + i).val().trim() != "")
            {
                params = "";
                //alert("index :"+index+" => "+input_arr[index]);
                params = "?procedure_type=grp&type_id=" + $('#order_type_id_' + i).val();

                for (var ind in ord_arr)
                {
                    if ($('#' + ord_arr[ind] + '_' + i).val().trim() != "")
                    {
                        params += "&" + column_arr[ord_arr[ind]] + "=" + $('#' + ord_arr[ind] + '_' + i).val();
                    }
                }
                //alert("submitting : order params : "+params);
                //return false;

                $.ajax({
                    type: "GET",
                    cache: false,
                    dataType: "json",
                    async: false,
                    url: "./configuration/saveConfigDetails" + params,
                    data: {
                    },
                    success: function(data) {
                        //alert("Ajax success"+data);
                        //alert("myObject is " + data.toSource());
                        $.each(data, function(jsonIndex, jsonValue) {
                            //alert("new type id :"+jsonValue['type_id']);								
                        });
                    },
                    error: function(data) {
                        alert("Ajax Fail");
                    }
                });

            }
        }

        for (var i = 1; i <= $('#res_count').val(); i++)
        {
            if ($('#result_name_' + i).val().trim() != "")
            {
                params = "";
                //alert("index :"+index+" => "+input_arr[index]);
                params = "?procedure_type=grp&type_id=" + $('#result_type_id_' + i).val();

                for (var ind in res_arr)
                {
                    if ($('#' + res_arr[ind] + '_' + i).val().trim() != "")
                    {
                        params += "&" + column_arr[res_arr[ind]] + "=" + $('#' + res_arr[ind] + '_' + i).val();
                    }
                }
                //alert("submitting : result params : "+params);
                //return false;

                $.ajax({
                    type: "GET",
                    cache: false,
                    dataType: "json",
                    async: false,
                    url: "./configuration/saveConfigDetails" + params,
                    data: {
                    },
                    success: function(data) {
                        //alert("Ajax success"+data);
                        //alert("myObject is " + data.toSource());
                        $.each(data, function(jsonIndex, jsonValue) {
                            //alert("new type id :"+jsonValue['type_id']);
                        });
                    },
                    error: function(data) {
                        alert("Ajax Fail");
                    }
                });

            }
        }

        for (var i = 1; i <= $('#rec_count').val(); i++)
        {
            if ($('#reccomendation_name_' + i).val().trim() != "")
            {
                params = "";
                //alert("index :"+index+" => "+input_arr[index]);
                params = "?procedure_type=grp&type_id=" + $('#reccomendation_type_id_' + i).val();

                for (var ind in rec_arr)
                {
                    if ($('#' + rec_arr[ind] + '_' + i).val().trim() != "")
                    {
                        params += "&" + column_arr[rec_arr[ind]] + "=" + $('#' + rec_arr[ind] + '_' + i).val();
                    }
                }
                //alert("submitting : reccomendation params : "+params);
                //return false;

                $.ajax({
                    type: "GET",
                    cache: false,
                    dataType: "json",
                    async: false,
                    url: "./configuration/saveConfigDetails" + params,
                    data: {
                    },
                    success: function(data) {
                        //alert("Ajax success"+data);
                        //alert("myObject is " + data.toSource());
                        $.each(data, function(jsonIndex, jsonValue) {
                            //alert("new type id :"+jsonValue['type_id']);
                        });
                    },
                    error: function(data) {
                        alert("Ajax Fail");
                    }
                });

            }
        }

        $('#w').window('close');

        keyWord = '';

        //alert('parent_typeid last '+parent_typeid);

        //$('#tg').treegrid('reload',parent_typeid); //$('#tt').treegrid('reload',rowID);
        $('#tg').treegrid('reload'); //$('#tt').treegrid('reload',rowID);


        $('#tg').treegrid({
            onLoadSuccess: function(row, data)
            {
                //alert("hihihihi");
                expandParents(parent_typeid);
            }
        });


        //alert('hi');
        //setTimeout(sleep,5000);
        //expandParents(parent_typeid);
        //$('#tg').treegrid('expandAll');



    }

}

function expandParents(parent_typeid)
{ //alert('hihihi');			
    //alert(" parent of parent_typeid "+parent_typeid+" : "+$('#tg').treegrid('getParent',parent_typeid));

    if ($('#tg').treegrid('getParent', parent_typeid))
    {
        var parid = ($('#tg').treegrid('getParent', parent_typeid)).id;
        //alert("expand :"+pid);
        //setTimeout(sleep,2000);
        $('#tg').treegrid('expand', parid);



        expandParents(parid);
    }
}



function cancelItem()
{
    $('#w').window('close');
    keyWord = '';
}

function addItem()
{
    //alert('checking');
    var input_arr = new Array();

    var grp_arr = new Array('group_name', 'group_description');

    var ord_arr = new Array('order_name', 'order_description', 'order_sequence', 'order_from', 'order_procedurecode',
            'order_standardcode', 'order_bodysite', 'order_specimentype', 'order_administervia', 'order_laterality');

    var res_arr = new Array('result_name', 'result_description', 'result_sequence', 'result_defaultunits', 'result_defaultrange', 'result_followupservices');

    var rec_arr = new Array('reccomendation_name', 'reccomendation_description', 'reccomendation_sequence',
            'reccomendation_defaultunits', 'reccomendation_defaultrange', 'reccomendation_followupservices');


    var column_arr = {'group_name': 'name', 'group_description': 'description',
        'order_name': 'name', 'order_description': 'description', 'order_sequence': 'seq', 'order_from': 'lab_id',
        'order_procedurecode': 'procedure_code', 'order_standardcode': 'standard_code', 'order_bodysite': 'body_site',
        'order_specimentype': 'specimen', 'order_administervia': 'route_admin', 'order_laterality': 'laterality',
        'result_name': 'name', 'result_description': 'description', 'result_sequence': 'seq',
        'reccomendation_name': 'name', 'reccomendation_description': 'description', 'reccomendation_sequence': 'seq',
        'result_defaultunits': 'units', 'result_defaultrange': 'range', 'result_followupservices': 'related_code',
        'reccomendation_defaultunits': 'units', 'reccomendation_defaultrange': 'range', 'reccomendation_followupservices': 'related_code'};


    var parent_id;
    parent_id = (document.getElementById('exist_typeid').value != "") ? document.getElementById('exist_typeid').value : 0;

    //alert('parent  :'+parent_id);

    var params;

    if ($('#group_name').val().trim() != "")
    {
        params = "";
        //alert("index :"+index+" => "+input_arr[index]);
        params = "?procedure_type=grp&parent=" + parent_id;

        for (var ind in grp_arr)
        {
            if ($('#' + grp_arr[ind]).val().trim() != "")
            {
                params += "&" + column_arr[grp_arr[ind]] + "=" + $('#' + grp_arr[ind]).val();
            }
        }
        //alert("submitting : group params : "+params);
        //return false;
        $.ajax({
            type: "GET",
            cache: false,
            dataType: "json",
            async: false,
            url: "./configuration/addConfigDetails" + params,
            data: {
            },
            success: function(data) {
                //alert("Ajax success"+data);
                //alert("myObject is " + data.toSource());
                $.each(data, function(jsonIndex, jsonValue) {
                    //alert("new type id :"+jsonValue['type_id']);
                    parent_id = jsonValue['type_id'];
                });
            },
            error: function(data) {
                alert("Ajax Fail");
            }
        });


    }

    if ($('#order_name').val().trim() != "")
    {
        params = "";
        //alert("index :"+index+" => "+input_arr[index]);
        params = "?procedure_type=ord&parent=" + parent_id;

        for (var ind in ord_arr)
        {
            if ($('#' + ord_arr[ind]).val().trim() != "")
            {
                params += "&" + column_arr[ord_arr[ind]] + "=" + $('#' + ord_arr[ind]).val();
            }
        }
        //alert("submitting : order params : "+params);

        $.ajax({
            type: "GET",
            cache: false,
            dataType: "json",
            async: false,
            url: "./configuration/addConfigDetails" + params,
            data: {
            },
            success: function(data) {
                //alert("Ajax success"+data);
                //alert("myObject is " + data.toSource());
                $.each(data, function(jsonIndex, jsonValue) {
                    //alert("new type id :"+jsonValue['type_id']);
                    parent_id = jsonValue['type_id'];
                });
            },
            error: function(data) {
                alert("Ajax Fail");
            }
        });
    }

    if ($('#result_name').val().trim() != "")
    {
        params = "";
        //alert("index :"+index+" => "+input_arr[index]);
        params = "?procedure_type=res&parent=" + parent_id;

        for (var ind in res_arr)
        {
            if ($('#' + res_arr[ind]).val().trim() != "")
            {
                params += "&" + column_arr[res_arr[ind]] + "=" + $('#' + res_arr[ind]).val();
            }
        }
        //alert("submitting : result params : "+params);
        $.ajax({
            type: "GET",
            cache: false,
            dataType: "json",
            async: false,
            url: "./configuration/addConfigDetails" + params,
            data: {
            },
            success: function(data) {
                //alert("Ajax success"+data);
                //alert("myObject is " + data.toSource());
                $.each(data, function(jsonIndex, jsonValue) {
                    //alert("new type id :"+jsonValue['type_id']);
                    parent_id = jsonValue['type_id'];
                });
            },
            error: function(data) {
                alert("Ajax Fail");
            }
        });
    }

    if ($('#reccomendation_name').val().trim() != "")
    {
        params = "";
        //alert("index :"+index+" => "+input_arr[index]);
        params = "?procedure_type=rec&parent=" + parent_id;

        for (var ind in rec_arr)
        {
            if ($('#' + rec_arr[ind]).val().trim() != "")
            {
                params += "&" + column_arr[rec_arr[ind]] + "=" + $('#' + rec_arr[ind]).val();
            }
        }
        //alert("submitting : reccomendation params : "+params);
        $.ajax({
            type: "GET",
            cache: false,
            dataType: "json",
            async: false,
            url: "./configuration/addConfigDetails" + params,
            data: {
            },
            success: function(data) {
                //alert("Ajax success"+data);
                //alert("myObject is " + data.toSource());
                $.each(data, function(jsonIndex, jsonValue) {
                    //alert("new type id :"+jsonValue['type_id']);
                    parent_id = jsonValue['type_id'];
                });
            },
            error: function(data) {
                alert("Ajax Fail");
            }
        });
    }

    $('#w').window('close');
    keyWord = '';
    alert('Configuration Added Successfully');
    $('#tg').treegrid('reload');

    if (document.getElementById('exist_typeid').value != "")
    {
        $('#tg').treegrid({
            onLoadSuccess: function(row, data)
            {
                //alert("hihihihi");
                $('#tg').treegrid('expand', document.getElementById('exist_typeid').value);
                expandParents(document.getElementById('exist_typeid').value);
            }
        });
    }

}



function addExist(row_id)
{

    clearAll();
    document.getElementById('edit_div').innerHTML = "";
    document.getElementById('add_div').style.display = "block";
    //alert("ADD ITEM");

    collapseCategory('order', '');
    collapseCategory('result', '');
    collapseCategory('reccomendation', '');

    expandCategory('group', '');



    document.getElementById('action').value = "addExist";


    document.getElementById('exist_typeid').value = row_id;

    $('#w').window('open');

}



function statusChange(div_id)
{
    //alert(div_id);


    var div_arr = div_id.split("_", 3);//group_indicator_1

    //alert(div_arr[0]+" , "+div_arr[1]+" , ID :"+div_arr[2])

    var category = div_arr[0];
    var id = div_arr[2];

    //return false;
    var indicator;

    if (category == "group")
    {
        //alert('group');
        //alert(document.getElementById('group_indicator_value').value);
        if (id)
        {
            indicator = document.getElementById('group_indicator_value_' + id).value;
        }
        else
        {
            indicator = document.getElementById('group_indicator_value').value;
        }

        //alert("indicator :"+indicator);
        if (indicator == "+")
        {
            //alert('expand grp id '+id);
            if (id)
            {
                expandCategory('group', id);
            }
            else
            {
                expandCategory('group', '');
            }

        }
        else
        {
            //alert('colpase grp id '+id);
            if (id)
            {
                collapseCategory('group', id);
            }
            else
            {
                collapseCategory('group', '');
            }
        }
    }
    else if (category == "order")
    {
        //alert(document.getElementById('order_indicator_value').value);
        if (id)
        {
            indicator = document.getElementById('order_indicator_value_' + id).value;
        }
        else
        {
            indicator = document.getElementById('order_indicator_value').value;
        }
        if (indicator == "+")
        {
            if (id)
            {
                expandCategory('order', id);
            }
            else
            {
                expandCategory('order', '');
            }

        }
        else
        {
            if (id)
            {
                collapseCategory('order', id);
            }
            else
            {
                collapseCategory('order', '');
            }
        }
    }
    else if (category == "result")
    {
        //alert(document.getElementById('result_indicator_value').value);
        if (id)
        {
            indicator = document.getElementById('result_indicator_value_' + id).value;
        }
        else
        {
            indicator = document.getElementById('result_indicator_value').value;
        }

        if (indicator == "+")
        {
            if (id)
            {
                expandCategory('result', id);
            }
            else
            {
                expandCategory('result', '');
            }
        }
        else
        {
            if (id)
            {
                collapseCategory('result', id);
            }
            else
            {
                collapseCategory('result', '');
            }
        }
    }
    else if (category == "reccomendation")
    {
        //alert(document.getElementById('reccomendation_indicator_value').value);
        if (id)
        {
            indicator = document.getElementById('reccomendation_indicator_value_' + id).value;
        }
        else
        {
            indicator = document.getElementById('reccomendation_indicator_value').value;
        }
        if (indicator == "+")
        {
            if (id)
            {
                expandCategory('reccomendation', id);
            }
            else
            {
                expandCategory('reccomendation', '');
            }
        }
        else
        {
            if (id)
            {
                collapseCategory('reccomendation', id);
            }
            else
            {
                collapseCategory('reccomendation', '');
            }
        }
    }
}
function clearAll()
{
    document.getElementById('config_form').reset();

    /*COLLAPSE ALL TYPE CATEGORIES*/
    /*collapseCategory('group');
     collapseCategory('order');
     collapseCategory('result');
     collapseCategory('reccomendation');*/
}


function collapseCategory(div, id)
{
    if (id != "")
    {
        document.getElementById(div + '_div_' + id).style.display = 'none';
        document.getElementById(div + '_indicator_value_' + id).value = "+";
        document.getElementById(div + '_indicator_' + id).innerHTML = "+";
    }
    else
    {
        document.getElementById(div + '_div').style.display = 'none';
        document.getElementById(div + '_indicator_value').value = "+";
        document.getElementById(div + '_indicator').innerHTML = "+";
    }
}

function expandCategory(div, id)
{
    //alert(div+" , "+id)
    if (id != "")
    {
        document.getElementById(div + '_div_' + id).style.display = 'block';
        document.getElementById(div + '_indicator_value_' + id).value = "-";
        document.getElementById(div + '_indicator_' + id).innerHTML = "-";
    }
    else
    {
        document.getElementById(div + '_div').style.display = 'block';
        document.getElementById(div + '_indicator_value').value = "-";
        document.getElementById(div + '_indicator').innerHTML = "-";
    }
}

function hideCategory(div)
{
    document.getElementById(div + '_title').style.display = 'none';
}

function showCategory(div)
{
    document.getElementById(div + '_title').style.display = 'block';
}


//CLONE DIVS ....
// Dynamically add new rows in the Procedure order

function cloneDiv(category) {

    //alert("clone");



    //var clone1 = $("#group_title_clone>*").clone(false).appendTo("#edit_div");
    var clone = $("#" + category + "_div_clone>*").clone(false).appendTo("#edit_div");




    if (category == "group")
    {
        grp_inc++;

        $('#edit_div').find('#' + category + '_title').attr('id', category + '_title_' + grp_inc);
        $('#edit_div').find('#' + category + '_indicator').attr('id', category + '_indicator_' + grp_inc);

        $('#edit_div').find('#' + category + '_indicator_value').attr('id', category + '_indicator_value_' + grp_inc);
        $('#edit_div').find('#' + category + '_type_id').attr('id', category + '_type_id_' + grp_inc);

        $('#edit_div').find('#' + category + '_div').attr('id', category + '_div_' + grp_inc);

        $('#edit_div').find('#' + category + '_name').attr('id', category + '_name_' + grp_inc);
        $('#edit_div').find('#' + category + '_description').attr('id', category + '_description_' + grp_inc);
    }
    else if (category == "order")
    {
        ord_inc++;


        $('#edit_div').find('#' + category + '_title').attr('id', category + '_title_' + ord_inc);
        $('#edit_div').find('#' + category + '_indicator').attr('id', category + '_indicator_' + ord_inc);

        $('#edit_div').find('#' + category + '_indicator_value').attr('id', category + '_indicator_value_' + ord_inc);
        $('#edit_div').find('#' + category + '_type_id').attr('id', category + '_type_id_' + ord_inc);

        $('#edit_div').find('#' + category + '_div').attr('id', category + '_div_' + ord_inc);

        $('#edit_div').find('#' + category + '_name').attr('id', category + '_name_' + ord_inc);
        $('#edit_div').find('#' + category + '_description').attr('id', category + '_description_' + ord_inc);
        $('#edit_div').find('#' + category + '_sequence').attr('id', category + '_sequence_' + ord_inc);

        $('#edit_div').find('#' + category + '_from').attr('id', category + '_from_' + ord_inc);
        $('#edit_div').find('#' + category + '_procedurecode').attr('id', category + '_procedurecode_' + ord_inc);
        $('#edit_div').find('#' + category + '_standardcode').attr('id', category + '_standardcode_' + ord_inc);
        $('#edit_div').find('#resultdiv_0').attr('id', 'resultdiv_' + ord_inc);
        $('#edit_div').find('#' + category + '_bodysite').attr('id', category + '_bodysite_' + ord_inc);
        $('#edit_div').find('#' + category + '_specimentype').attr('id', category + '_specimentype_' + ord_inc);
        $('#edit_div').find('#' + category + '_administervia').attr('id', category + '_administervia_' + ord_inc);
        $('#edit_div').find('#' + category + '_laterality').attr('id', category + '_laterality_' + ord_inc);


    }
    else if (category == "result")
    {
        res_inc++;

        $('#edit_div').find('#' + category + '_title').attr('id', category + '_title_' + res_inc);
        $('#edit_div').find('#' + category + '_indicator').attr('id', category + '_indicator_' + res_inc);

        $('#edit_div').find('#' + category + '_indicator_value').attr('id', category + '_indicator_value_' + res_inc);
        $('#edit_div').find('#' + category + '_type_id').attr('id', category + '_type_id_' + res_inc);

        $('#edit_div').find('#' + category + '_div').attr('id', category + '_div_' + res_inc);

        $('#edit_div').find('#' + category + '_name').attr('id', category + '_name_' + res_inc);
        $('#edit_div').find('#' + category + '_description').attr('id', category + '_description_' + res_inc);
        $('#edit_div').find('#' + category + '_sequence').attr('id', category + '_sequence_' + res_inc);
        $('#edit_div').find('#' + category + '_defaultunits').attr('id', category + '_defaultunits_' + res_inc);
        $('#edit_div').find('#' + category + '_defaultrange').attr('id', category + '_defaultrange_' + res_inc);
        $('#edit_div').find('#' + category + '_followupservices').attr('id', category + '_followupservices_' + res_inc);
    }
    else if (category == "reccomendation")
    {
        rec_inc++;

        $('#edit_div').find('#' + category + '_title').attr('id', category + '_title_' + rec_inc);
        $('#edit_div').find('#' + category + '_indicator').attr('id', category + '_indicator_' + rec_inc);

        $('#edit_div').find('#' + category + '_indicator_value').attr('id', category + '_indicator_value_' + rec_inc);
        $('#edit_div').find('#' + category + '_type_id').attr('id', category + '_type_id_' + rec_inc);

        $('#edit_div').find('#' + category + '_div').attr('id', category + '_div_' + rec_inc);

        $('#edit_div').find('#' + category + '_name').attr('id', category + '_name_' + rec_inc);
        $('#edit_div').find('#' + category + '_description').attr('id', category + '_description_' + rec_inc);
        $('#edit_div').find('#' + category + '_sequence').attr('id', category + '_sequence_' + rec_inc);
        $('#edit_div').find('#' + category + '_defaultunits').attr('id', category + '_defaultunits_' + rec_inc);
        $('#edit_div').find('#' + category + '_defaultrange').attr('id', category + '_defaultrange_' + rec_inc);
        $('#edit_div').find('#' + category + '_followupservices').attr('id', category + '_followupservices_' + rec_inc);
    }

}

/**
 * Check Procedure Code is exist
 */

function checkCode(thisValue, lab_id) {
    lab_arr = lab_id.split('|');
    loc_lab_id = lab_arr[0];
    $.ajax({
        type: "POST",
        cache: false,
        dataType: "json",
        url: "./configuration/checkProcedureCode",
        data: {
            code: thisValue,
            lab: loc_lab_id
        },
        success: function(data) {
            if (data == 1) {
                $('#save').hide();
                $('#dlg').dialog('open');
            } else {
                $('#save').show();
            }
        },
        error: function(data) {
            alert("Ajax Fail");
        }
    });
}
$(function() {
    $('#dlg').dialog('close');
    $('#save').show();
});

/**
 * Standard Code  Auto suggest
 */

function lookup(inputString, thisID) {
    arr = thisID.split("order_standardcode");
    var divid = '';
    if (arr[1]) {
        divid = arr[1];
    }
    inputString = $.trim(inputString.substring(inputString.lastIndexOf(';') + 1));
    if (inputString == '')
        return false;
    var codeType = $("#config_form input[type='radio']:checked").val();
    $.post("./configuration/getStandardCode", {
        queryString: inputString,
        codeType: codeType
    },
    function(data) {
        if (data.response == true) {
            if (data.resultArray.length > 0) {
                resultArray = data.resultArray;
                j = '<ul class="suggestion">';
                if (codeType == 'PROD') {
                    for (var results in resultArray) {
                        splitArr = resultArray[results].split("|-|");
                        j += "<li onclick=loadResult('" + splitArr[0] + "','" + codeType + "','" + divid + "')><a href='#'>" + splitArr[0] + " - " + splitArr[1] + "," + splitArr[2] + "</a></li>";
                    }
                } else {
                    for (var results in resultArray) {
                        splitArr = resultArray[results].split("|-|");
                        j += "<li onclick=loadResult('" + splitArr[0] + "','" + codeType + "','" + divid + "')><a href='#'>" + splitArr[0] + " - " + splitArr[1] + "</a></li>";
                    }
                }
                j += "</ul>";
                $("#resultdiv" + divid).css('display', 'block');
                $("#resultdiv" + divid).html(j);
            }
            else {
                $("#resultdiv" + divid).html("");
                $("#resultdiv" + divid).css('display', 'none');
            }
        } else {
            alert("Failed");
            console.log('could not add');
        }
    }, 'json');
}

var keyWord = '';
function loadResult(data, codeType, divid) {

    keyWord += codeType + ':' + data + ';';

    $('#order_standardcode' + divid).val(keyWord);
    $("#resultdiv" + divid).css('display', 'none');
}

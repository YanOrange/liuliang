layui.define(function (exports) {
    const MOD_NAME = "field";

    var field={};

    let tableId = layui.context.get("laytpDevtoolCurdTableId");
    let table = layui.context.get("laytpDevtoolCurdTable");
    $(document).ready(function () {
        $(document).off("click", ".add-item").on("click", ".add-item", function () {
            let clickObj = $(this);
            let template = '<tr>' +
                '<td align="right">' +
                '<input type="text" class="layui-input" name="addition[value][]" autocomplete="off" />' +
                '</td>' +
                '<td>' +
                '<input type="text" class="layui-input" name="addition[text][]" autocomplete="off" />' +
                '</td>' +
                '<td align="center">' +
                '<input {{# if(d.formType === "checkbox"){ }}type="checkbox"{{# }else{ }}type="radio"{{# } }} lay-skin="primary" name="addition[default][]" /> ' +
                '</td>' +
                '<td>' +
                '<a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-delete del-item"></a>' +
                '</td>' +
                '</tr>';
            clickObj.parent().parent().before(layui.laytpl(template).render({formType: formType}));
            layui.form.render('radio');
            layui.form.render('checkbox');
        });

        $(document).off("click", ".add-xm-select-item").on("click", ".add-xm-select-item", function () {
            let clickObj = $(this);
            let template = '<tr>' +
                '<td align="right">' +
                '<input type="text" class="layui-input" name="addition[value][]" autocomplete="off" />' +
                '</td>' +
                '<td>' +
                '<input type="text" class="layui-input" name="addition[text][]" autocomplete="off" />' +
                '</td>' +
                '<td align="center">' +
                '<input {{# if(d.single_multi_type === "multi"){ }}type="checkbox"{{# }else{ }}type="radio"{{# } }} lay-skin="primary" name="addition[default][]" /> ' +
                '</td>' +
                '<td>' +
                '<a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-delete del-item"></a>' +
                '</td>' +
                '</tr>';
            clickObj.parent().parent().before(layui.laytpl(template).render({single_multi_type: $("#single_multi_type").val()}));
            layui.form.render('radio');
            layui.form.render('checkbox');
        });

        $(document).off("click", ".add-color-picker").on("click", ".add-color-picker", function () {
            let clickObj = $(this);
            let template =
                '<tr>' +
                '   <td>' +
                '       <div class="colorPicker"' +
                '           data-name="addition[colors][]"' +
                '           data-id="addition_colors_{{d.randomKey}}"' +
                '       ></div>' +
                '   </td>' +
                '   <td>' +
                '       <a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-delete del-item"></a>' +
                '   </td>' +
                '</tr>'
            ;
            clickObj.parent().parent().before(layui.laytpl(template).render({
                formType: formType,
                randomKey: Math.random() * 10000000000000000000
            }));
            layui.laytpForm.render(clickObj.parent().parent().prev());
        });

        $(document).off("click", ".del-item").on("click", ".del-item", function () {
            let clickObj = $(this);
            clickObj.parent().parent().remove();
        });

        layui.form.on('select(data-from)', function (data) {
            selectDataFrom(data.value);
        });

        layui.form.on('select(colorType)', function (data) {
            layui.each($('.colorPicker'), function (key, item) {
                if (data.value === "hex") {
                    $(item).data('alpha', false);
                }
                $(item).data('format', data.value);
                laytpForm.initData.colorPicker();
                setTimeout(function () {
                    $('[class="layui-icon layui-colorpicker-trigger-i layui-icon-down"]', '#color_picker_' + $(item).data('id')).click();
                    $('button[class="layui-btn layui-btn-sm"][colorpicker-events="confirm"]').click();
                }, 1);
            });
            if (data.value === "hex") {
                $('input[lay-filter="openAlpha"]').prop('checked', false);
                layui.form.render('checkbox');
            }
        });

        layui.form.on('checkbox(openAlpha)', function (data) {
            layui.each($('.colorPicker'), function (key, item) {
                $(item).data('alpha', data.elem.checked);
                $(item).data('format', 'rgb');
                $(item).data('format', 'rgb');
                $('#colorType').html("<option value=\"hex\">hex</option>" +
                    "<option value=\"rgb\" selected=\"selected\">rgb</option>");
                layui.form.render('select');
                laytpForm.initData.colorPicker();
                setTimeout(function () {
                    $('[class="layui-icon layui-colorpicker-trigger-i layui-icon-down"]', '#color_picker_' + $(item).data('id')).click();
                    $('button[class="layui-btn layui-btn-sm"][colorpicker-events="confirm"]').click();
                }, 1);
            });
        });

        layui.form.on('select(select-table)', function (data) {
            selectDataFromTable(data.value);
        });

        layui.form.on('select(linkage-select-table)', function (data) {
            selectLinkageSearchTable(data.value);
        });

        //选择数据存储类型
        layui.form.on('select(select-data-type)', function (obj) {
            if (facade.inArray(obj.value, ["float", "decimal"])) {
                $("#lengthDiv").hide();
                $("input[name='limit']").removeAttr('lay-verify');
                $("#precisionDiv").show();
                $("input[name='precision']").attr('lay-verify', 'required');
                $("#scaleDiv").show();
                $("#defaultVal").show();
                $("input[name='default']").val(0);
            } else if (facade.inArray(obj.value, ["integer", "biginteger"])) {
                $("#defaultVal").show();
                $("input[name='default']").val(0);
                $("#precisionDiv").hide();
                $("#scaleDiv").hide();
                $("#lengthDiv").show();
                $("input[name='limit']").attr('lay-verify', 'required');
            } else if (facade.inArray(obj.value, ["datetime", "text"])) {
                $("#precisionDiv").hide();
                $("#scaleDiv").hide();
                $("#defaultVal").hide();
                $("#lengthDiv").hide();
                $("input[name='limit']").removeAttr('lay-verify');
            } else {
                $("#defaultVal").show();
                $("#lengthDiv").show();
                $("input[name='limit']").attr('lay-verify', 'required');
                $("#precisionDiv").hide();
                $("input[name='precision']").removeAttr('lay-verify', 'required');
                $("#scaleDiv").hide();
            }
        });
    });

    window.setTableId = function (params) {
        if(params.arr.length > 0){
            tableId = params.arr[0].id;
            table = params.arr[0].table;
        }else{
            tableId = 0;
            table = "";
        }
    };

    window.selectDataFrom = function (value, editData) {
        var isEdit = true;
        if (typeof editData === "undefined") {
            isEdit = false;
            editData = {
                addition: {
                    table_id: "",
                    single_multi_type: $('#single_multi_type').val()
                }
            }
        }
        if (value === "table") {
            let xmSelectSetDataTable =
                '    <div class="layui-row layui-form-item">' +
                '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
                '           <label class="layui-form-label layui-form-required">数据表</label>' +
                '           <div class="layui-input-block">' +
                '           <div class="xmSelect"' +
                '               data-name="addition[table_id]"' +
                '               data-sourceType="route"' +
                '               data-source="/plugin/curd/curd.table/index"' +
                '               data-textField="table"' +
                '               data-subTextField="comment"' +
                '               data-valueField="table"' +
                '               data-radio="true"' +
                '               data-placeholder="请选择数据表"' +
                '               data-onchange="selectDataFromTable"' +
                '               data-selected="{{ d.addition.table_id }}"' +
                '           >' +
                '           </div>' +
                // '           <select class="layui-select" name="addition[table_id]" lay-filter="select-table"' +
                // '                data-source="/plugin/curd/curd.table/index"' +
                // '                data-valueField="table"\n' +
                // '                data-showField="table"\n' +
                // '                data-placeholder="请选择数据表"\n' +
                // '                data-selected="{{ d.addition.table_id }}"\n' +
                // '           ></select>' +
                '           </div>' +
                '       </div>' +
                '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
                '           <label class="layui-form-label layui-form-required" title="默认不限制，仅多选有效">主标题字段</label>' +
                '           <div class="layui-input-block">' +
                '               <select class="layui-select" name="addition[title_field]" id="titleField">' +
                '                   <option value="">请选择字段</option>' +
                '               </select>' +
                '           </div>' +
                '       </div>' +
                '    </div>' +
                '    <div class="layui-row layui-form-item">' +
                '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
                '           <label class="layui-form-label layui-form-required">副标题字段</label>' +
                '           <div class="layui-input-block">' +
                '               <select class="layui-select" name="addition[sub_title_field]" id="subTitleField">' +
                '                   <option value="">请选择字段</option>' +
                '               </select>' +
                '           </div>' +
                '       </div>' +
                '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
                '           <label class="layui-form-label layui-form-required">图标字段</label>' +
                '           <div class="layui-input-block">' +
                '               <select class="layui-select" name="addition[icon_field]" id="iconField">' +
                '                   <option value="">请选择字段</option>' +
                '               </select>' +
                '           </div>' +
                '       </div>' +
                '    </div>'
            ;
            $("#setData").html(layui.laytpl(xmSelectSetDataTable).render(editData));
            if(!isEdit){
                layui.form.render('select');
                layui.laytpForm.render("#setData");
            }
            // layui.laytpForm.render("#setData");
        } else if (value === "data") {
            //定义有多个选项的表单元素Html
            let optionsTemplate =
                '<table class="layui-table">' +
                '<thead>' +
                '<tr>' +
                '<th>待选项的值</th>' +
                '<th>待选项的文本</th>' +
                '<th>默认选中</th>' +
                '<th>删除</th>' +
                '</tr>' +
                '</thead>' +
                '<tbody>' +
                '{{# let key; }}' +
                '{{# for(key in d.addition.value){ }}' +
                '<tr>' +
                '<td align="right">' +
                '<input type="text" class="layui-input" name="addition[value][]" value="{{d.addition.value[key]}}" />' +
                '</td>' +
                '<td>' +
                '<input type="text" class="layui-input" name="addition[text][]" value="{{d.addition.text[key]}}" />' +
                '</td>' +
                '<td>' +
                '<input {{# if(d.addition.single_multi_type === "multi"){ }}type="checkbox"{{# }else{ }}type="radio"{{# } }} {{# if(facade.inArray(d.addition.value[key], d.addition.default)){ }}checked="checked"{{# } }}  name="addition[default][]" lay-skin="primary" /> ' +
                '</td>' +
                '<td>' +
                '<a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-delete del-item"></a>' +
                '</td>' +
                '</tr>' +
                '{{# } }}' +
                '<tr>' +
                '<td colspan="4">' +
                '<a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-add-1 add-xm-select-item">追加选项</a>' +
                '</td>' +
                '</tr>' +
                '</tbody>' +
                '</table>'
            ;

            $("#setData").html(layui.laytpl(optionsTemplate).render(editData));
            layui.form.render('radio');
            layui.form.render('checkbox');
        }
    }

    window.selectDataFromTable = function (table, editData) {
        if(table.arr){
            table = table.arr[0].table;
        }
        if (typeof editData === "undefined") {
            editData = {
                addition: {
                    title_field: "",
                    sub_title_field: "",
                    icon_field: ""
                }
            }
        }
        facade.ajax({
            route: "/plugin/curd/curd/getFieldList",
            data: {"table": table},
            successAlert: false
        }).then(function (res) {
            if (res.code === 0) {
                $("#titleField").html('<option value="">请选择字段</option>');
                $("#subTitleField").html('<option value="">请选择字段</option>');
                $("#iconField").html('<option value="">请选择字段</option>');
                let key;
                let data = res.data.data;
                for (key in res.data.data) {
                    if (editData.addition.title_field === data[key]["field"]) {
                        $("#titleField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                    } else {
                        $("#titleField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                    }
                    if (editData.addition.sub_title_field === data[key]["field"]) {
                        $("#subTitleField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                    } else {
                        $("#subTitleField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                    }
                    if (editData.addition.icon_field === data[key]["field"]) {
                        $("#iconField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                    } else {
                        $("#iconField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                    }
                }
                layui.form.render('select');
            }
        });
    }

    window.linkageField = function (tableParam, editData) {
        facade.ajax({
            route: "/plugin/curd/curd/getFieldList",
            data: {table: table},
            successAlert: false
        }).then(function (res) {
            if (res.code === 0) {
                $("#leftField").html('<option value="">请选择字段，不选表示当前字段为联动下拉框中第一个下拉框</option>');
                $("#rightField").html('<option value="">请选择字段，不选表示当前字段为联动下拉框中最后一个下拉框</option>');
                let key;
                let data = res.data.data;
                for (key in res.data.data) {
                    if (editData.addition.left_field === data[key]["field"]) {
                        $("#leftField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                    } else {
                        $("#leftField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                    }
                    if (editData.addition.right_field === data[key]["field"]) {
                        $("#rightField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                    } else {
                        $("#rightField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                    }
                }
                layui.form.render('select');
            }
        });
    }

    window.selectLinkageSearchTable = function (table, editData) {
        if (typeof editData === "undefined") {
            editData = {
                addition: {
                    show_field: "",
                    search_field: ""
                }
            }
        }
        facade.ajax({
            route: "/plugin/curd/curd/getFieldList",
            data: {table: table},
            successAlert: false
        }).then(function (res) {
            if (res.code === 0) {
                $("#linkageShowField").html('<option value="">请选择字段</option>');
                $("#linkageSearchField").html('<option value="">请选择字段</option>');
                let key;
                let data = res.data.data;
                for (key in res.data.data) {
                    if (editData.addition.show_field === data[key]["field"]) {
                        $("#linkageShowField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                    } else {
                        $("#linkageShowField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                    }
                    if (editData.addition.search_field === data[key]["field"]) {
                        $("#linkageSearchField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                    } else {
                        $("#linkageSearchField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                    }
                }
                layui.form.render('select');
            }
        });
    }

    let formType;
    let pluginConf = layui.context.get('pluginConf');
    // 为方便编辑页面渲染，选择表单元素下拉框后回调私有方法
    window.formTypeChangePrivate = function (formTypeParam, editData) {
        formType = formTypeParam;
        if (typeof editData === "undefined") {
            editData = {
                addition: {
                    max: "",
                    default: "",
                    close_value: "",
                    close_text: "",
                    open_value: "",
                    open_text: "",
                    group_name: "",
                    left_field: "",
                    right_field: "",
                    width: "",
                    height: "",
                    dir: "",
                    url: "",
                    mime: "",
                    size: "",
                    color: "",
                    search_val: "",
                    type: "",
                    upload_type: "local",
                    via_server: "via",
                },
                pluginConf: pluginConf
            };
        } else {
            editData.pluginConf = pluginConf;
        }
        //定义没有附加设置的表单元素数组
        let noHtmlArr = ["admin_user_id", "password", "textarea"];
        //定义有多个选项的表单元素数组
        let optionsArr = ["select", "radio", "checkbox"];
        //定义有多个选项的表单元素Html
        let optionsTemplate =
            '<table class="layui-table">' +
            '<thead>' +
            '<tr>' +
            '<th>待选项的值</th>' +
            '<th>待选项的文本</th>' +
            '<th>默认选中</th>' +
            '<th>删除</th>' +
            '</tr>' +
            '</thead>' +
            '<tbody>' +
            '{{# let key; }}' +
            '{{# for(key in d.addition.value){ }}' +
            '<tr>' +
            '<td align="right">' +
            '<input type="text" class="layui-input" name="addition[value][]" value="{{d.addition.value[key]}}" />' +
            '</td>' +
            '<td>' +
            '<input type="text" class="layui-input" name="addition[text][]" value="{{d.addition.text[key]}}" />' +
            '</td>' +
            '<td>' +
            '<input {{# if(d.form_type === "checkbox"){ }}type="checkbox" {{# if(typeof d.addition.default != "undefined" && d.addition.value[key] === d.addition.default[key]){ }}checked="checked"{{# } }}{{# }else{ }}type="radio" {{# if(d.addition.value[key] === d.addition.default){ }}checked="checked"{{# } }}{{# } }} name="addition[default][]" lay-skin="primary" /> ' +
            '</td>' +
            '<td>' +
            '<a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-delete del-item"></a>' +
            '</td>' +
            '</tr>' +
            '{{# } }}' +
            '<tr>' +
            '<td colspan="4">' +
            '<a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-add-1 add-item">追加选项</a>' +
            '</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>'
        ;
        //定义有附加设置的表单元素数组具体的附加设置的html
        let inputTemplate =
            '<table class="layui-table">' +
            '<tbody>' +
            '<tr>' +
            '<td align="right">输入验证</td>' +
            '<td>' +
            '   <select name="addition[verify]">' +
            '       <option value="">不限制</option>' +
            '       <option value="email" {{# if(d.addition.verify === "email"){ }}selected="selected"{{# } }}>Email</option>' +
            '       <option value="phone" {{# if(d.addition.verify === "phone"){ }}selected="selected"{{# } }}>手机号码</option>' +
            '       <option value="number" {{# if(d.addition.verify === "number"){ }}selected="selected"{{# } }}>数字</option>' +
            '       <option value="url" {{# if(d.addition.verify === "url"){ }}selected="selected"{{# } }}>链接</option>' +
            '       <option value="identity" {{# if(d.addition.verify === "identity"){ }}selected="selected"{{# } }}>身份证</option>' +
            '   </select>' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<td align="right">开启表格编辑</td>' +
            '<td>' +
            '   <input type="hidden" name="addition[open_table_edit]" id="open_table_edit-0" value="0"/>' +
            '   <input type="checkbox" name="addition[open_table_edit]" id="open_table_edit" value="1" title="开启表格编辑" {{# if(d.addition.open_table_edit === "1"){ }}checked="checked"{{# } }} />' +
            '</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>';

        let switchTemplate =
            '<table class="layui-table">' +
            '<tbody>' +
            '<tr>' +
            '<td align="right">' +
            '关闭状态的值' +
            '</td>' +
            '<td>' +
            '<input type="text" class="layui-input" name="addition[close_value]" value="{{d.addition.close_value}}" autocomplete="off" />' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<td align="right">' +
            '关闭状态的文本' +
            '</td>' +
            '<td>' +
            '<input type="text" class="layui-input" name="addition[close_text]" value="{{d.addition.close_text}}" autocomplete="off" />' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<td align="right">' +
            '打开状态的值' +
            '</td>' +
            '<td>' +
            '<input type="text" class="layui-input" name="addition[open_value]" value="{{d.addition.open_value}}" autocomplete="off" />' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<td align="right">' +
            '打开状态的文本' +
            '</td>' +
            '<td>' +
            '<input type="text" class="layui-input" name="addition[open_text]" value="{{d.addition.open_text}}" autocomplete="off" />' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<td align="right">' +
            '默认状态' +
            '</td>' +
            '<td>' +
            '<select name="addition[default_status]">' +
            '<option {{# if(d.addition.default_status == "close"){ }}selected="selected"{{# } }} value="close">关闭</option>' +
            '<option {{# if(d.addition.default_status == "open"){ }}selected="selected"{{# } }} value="open">打开</option>' +
            '</select>' +
            '</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>';

        let xm_selectTemplate =
            '<div class="layui-card">' +
            '  <div class="layui-card-header">基础设置</div>' +
            '  <div class="layui-card-body">' +
            '    <div class="layui-row layui-form-item">' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">单选/多选</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" id="single_multi_type" name="addition[single_multi_type]">' +
            '                   <option value="single" {{# if(d.addition.single_multi_type === "single"){ }}selected="selected"{{# } }}>单选</option>' +
            '                   <option value="multi" {{# if(d.addition.single_multi_type === "multi"){ }}selected="selected"{{# } }}>多选</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required" title="默认不限制，仅多选有效">可选上限</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[max]" value="{{ d.addition.max }}" placeholder="默认不限制，仅多选有效" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">下拉方向</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[direction]">' +
            '                   <option value="">自动</option>' +
            '                   <option value="up" {{# if(d.addition.direction === "up"){ }}selected="selected"{{# } }}>向上</option>' +
            '                   <option value="down" {{# if(d.addition.direction === "down"){ }}selected="selected"{{# } }}>向下</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">数据来源</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[data_from_type]" lay-filter="data-from">' +
            '                   <option value="">请选择数据来源</option>' +
            '                   <option value="data" {{# if(d.addition.data_from_type === "data"){ }}selected="selected"{{# } }}>自定义</option>' +
            '                   <option value="table" {{# if(d.addition.data_from_type === "table"){ }}selected="selected"{{# } }}>MySQL数据库</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '  </div>' +
            '  <div class="layui-card-header">数据设置</div>' +
            '  <div class="layui-card-body" id="setData">' +
            '   请选择数据来源方式' +
            '  </div>' +
            '</div>'
        ;

        let linkage_selectTemplate =
            '<div class="layui-card">' +
            '  <div class="layui-card-header">基础设置</div>' +
            '  <div class="layui-card-body">' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">分组名</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[group_name]" placeholder="分组名，例：地区设置。相同分组名在同一个表单item中" value="{{d.addition.group_name}}" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">左关联字段</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[left_field]" id="leftField">' +
            '                   <option value="">请选择字段，不选表示当前字段为联动下拉框中第一个下拉框</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">右关联字段</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[right_field]" id="rightField">' +
            '                   <option value="">请选择字段，不选表示当前字段为联动下拉框中最后一个下拉框</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '  </div>' +
            '  <div class="layui-card-header">下拉数据SQL [select 显示,id from 数据来源 where 查询字段 查询条件 查询的值]</div>' +
            '  <div class="layui-card-body">' +
            '    <div class="layui-row layui-form-item">' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required" title="数据来源（表）">数据来源（表）</label>' +
            '           <div class="layui-input-block">' +
            '           <select class="layui-select" name="addition[table_id]" lay-filter="linkage-select-table"' +
            '                data-source="/plugin/curd/curd.table/index"' +
            '                data-valueField="table"\n' +
            '                data-showField="table"\n' +
            '                data-placeholder="请选择数据表"\n' +
            '                data-selected="{{ d.addition.table_id }}"\n' +
            '                lay-search="true"\n' +
            '           ></select>' +
            '           </div>' +
            '       </div>' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">显示字段</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[show_field]" id="linkageShowField">' +
            '                   <option value="">请选择字段</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">查询字段</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[search_field]" id="linkageSearchField">' +
            '                   <option value="">请选择字段</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">查询条件</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[search_condition]" id="searchCondition">' +
            '                   <option value="=" {{# if(d.addition.search_condition === "="){ }}selected="selected"{{# } }}>=</option>' +
            '                   <option value="LIKE" {{# if(d.addition.search_condition === "LIKE"){ }}selected="selected"{{# } }}>LIKE</option>' +
            '                   <option value="IN" {{# if(d.addition.search_condition === "IN"){ }}selected="selected"{{# } }}>IN</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">查询的值</label>' +
            '           <div class="layui-input-block">' +
            '               <input class="layui-input" name="addition[search_val]" id="linkageSearchVal" value="{{d.addition.search_val}}" placeholder="请输入搜索的值，默认0，仅第一个下拉框有效" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '  </div>' +
            '</div>'
        ;

        let laydateTemplate =
            '<table class="layui-table">' +
            '<tbody>' +
            '<tr>' +
            '<td align="right">时间格式</td>' +
            '<td>' +
            '   <select name="addition[date_type]">' +
            '       <option value="datetime" {{# if(d.addition.date_type === "datetime"){ }}selected="selected"{{# } }}>年-月-日 时:分:秒</option>' +
            '       <option value="date" {{# if(d.addition.date_type === "date"){ }}selected="selected"{{# } }}>年-月-日</option>' +
            '       <option value="time" {{# if(d.addition.date_type === "time"){ }}selected="selected"{{# } }}>时:分:秒</option>' +
            '       <option value="month" {{# if(d.addition.date_type === "month"){ }}selected="selected"{{# } }}>年-月</option>' +
            '       <option value="year" {{# if(d.addition.date_type === "year"){ }}selected="selected"{{# } }}>年</option>' +
            '   </select>' +
            '</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>'
        ;

        let color_pickerTemplate =
            '<div class="layui-card">' +
            '  <div class="layui-card-header">基础设置</div>' +
            '  <div class="layui-card-body">' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">默认颜色</label>' +
            '           <div class="layui-input-block colorPicker"' +
            '                data-name="addition[color]"' +
            '                data-id="addition_color"' +
            '                data-color="{{d.addition.color}}"' +
            '           ></div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">颜色格式</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[format]" id="colorType" lay-filter="colorType">' +
            '                   <option value="hex" {{# if(d.addition.format === "hex"){ }}selected="selected"{{# } }}>hex</option>' +
            '                   <option value="rgb" {{# if(d.addition.format === "rgb"){ }}selected="selected"{{# } }}>rgb</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '       <div class="layui-col-lg6 layui-col-md6 layui-col-sm6 layui-col-xs6">' +
            '           <label class="layui-form-label layui-form-required">开启透明度</label>' +
            '           <div class="layui-input-block">' +
            '               <input lay-filter="openAlpha" type="checkbox" name="addition[alpha]" value="1" title="开启" {{# if(d.addition.alpha === "1"){ }}checked="checked"{{# } }} />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '  </div>' +
            '  <div class="layui-card-header">预定义颜色设置</div>' +
            '  <div class="layui-card-body">' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">开启预定义</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="checkbox" name="addition[predefine]" value="1" title="开启" {{# if(d.addition.predefine === "1"){ }}checked="checked"{{# } }} />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">待选颜色</label>' +
            '           <div class="layui-input-block">' +
            '           <table class="layui-table">' +
            '               <thead>' +
            '                   <tr>' +
            '                       <th>颜色值</th>' +
            '                       <th>删除</th>' +
            '                   </tr>' +
            '               </thead>' +
            '               <tbody>' +
            '               {{# let key; }}' +
            '               {{# for(key in d.addition.colors){ }}' +
            '                   <tr>' +
            '                       <td>' +
            '                           <div class="colorPicker"' +
            '                                data-name="addition[colors][]"' +
            '                                data-id="addition_colors_{{key}}"' +
            '                                data-color="{{d.addition.colors[key]}}"' +
            '                                data-format="{{d.addition.format}}"' +
            '                           ></div>' +
            '                       </td>' +
            '                       <td>' +
            '                           <a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-delete del-item"></a>' +
            '                       </td>' +
            '                   </tr>' +
            '               {{# } }}' +
            '                   <tr>' +
            '                       <td colspan="4">' +
            '                           <a class="layui-btn layui-btn-primary layui-btn-sm layui-icon layui-icon-add-1 add-color-picker">追加选项</a>' +
            '                       </td>' +
            '                   </tr>' +
            '               </tbody>' +
            '           </table>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '  </div>' +
            '</div>'
        ;

        let uploadTemplate =
            '<div class="layui-card">' +
            '  <div class="layui-card-header">附加设置</div>' +
            '  <div class="layui-card-body">' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">文件类型</label>' +
            '           <div class="layui-input-block">' +
            '           <select name="addition[accept]">' +
            '               <option value="file" {{# if(d.addition.accept==="file"){ }}selected="selected"{{# } }}>任意文件</option>' +
            '               <option value="image" {{# if(d.addition.accept==="image"){ }}selected="selected"{{# } }}>图片</option>' +
            '               <option value="video" {{# if(d.addition.accept==="video"){ }}selected="selected"{{# } }}>视频</option>' +
            '               <option value="audio" {{# if(d.addition.accept==="audio"){ }}selected="selected"{{# } }}>音频</option>' +
            '           </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">上传方式</label>' +
            '           <div class="layui-input-block">' +
            '           <select name="addition[upload_type]">' +
            '               <option value="local" {{# if(d.addition.upload_type==="local"){ }}selected="selected"{{# } }}>本地上传</option>' +
            '               <option value="ali-oss" {{# if(d.addition.upload_type==="ali-oss"){ }}selected="selected"{{# } }}>阿里云OSS</option>' +
            '               <option value="qiniu-kodo" {{# if(d.addition.upload_type==="qiniu-kodo"){ }}selected="selected"{{# } }}>七牛云OSS</option>' +
            '           </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required" title="是否经过服务器">是否经过服务器</label>' +
            '           <div class="layui-input-block">' +
            '           <select name="addition[via_server]">' +
            '               <option value="via" {{# if(d.addition.via_server==="via"){ }}selected="selected"{{# } }}>经过服务器</option>' +
            '               <option value="unVia" {{# if(d.addition.via_server==="unVia"){ }}selected="selected"{{# } }}>不经过服务器</option>' +
            '           </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required" title="(图片)最大宽度，单位px，0表示不限制">(图片)最大宽度，单位px，0表示不限制</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[width]" value="{{d.addition.width}}" placeholder="允许上传图片的最大宽度，单位px，默认不限制" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required" title="(图片)最大高度，单位px，0表示不限制">(图片)最大高度，单位px，0表示不限制</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[height]" value="{{d.addition.height}}" placeholder="允许上传图片的最大高度，单位px，默认不限制" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">多文件模式</label>' +
            '           <div class="layui-input-block">' +
            '               <select class="layui-select" name="addition[multi]">' +
            '                   <option value="single" {{# if(d.addition.multi==="single"){ }}selected="selected"{{# } }}>单个文件</option>' +
            '                   <option value="multi" {{# if(d.addition.multi==="multi"){ }}selected="selected"{{# } }}>多个文件</option>' +
            '               </select>' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">最大文件数</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[max]" value="{{d.addition.max}}" placeholder="多文件模式下最多允许上传的文件个数" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">上传目录</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[dir]" value="{{d.addition.dir}}" placeholder="storage目录下的子目录，允许使用/指定多级目录" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required" title="文件上传地址">文件上传地址</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[url]" value="{{d.addition.url}}" placeholder="上传文件后端处理的路由地址，默认为/admin/common/upload" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required">mime类型</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[mime]" value="{{d.addition.mime}}" placeholder="允许上传的mime类型，*代表所有mime类型，多个以英文半角逗号隔开" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '    <div class="layui-row layui-form-item">' +
            '       <div>' +
            '           <label class="layui-form-label layui-form-required" title="文件size大小">文件size大小</label>' +
            '           <div class="layui-input-block">' +
            '               <input type="text" class="layui-input" name="addition[size]" value="{{d.addition.size}}" placeholder="允许上传的最大文件大小，单位b,k,kb,m,mb,g,gb" />' +
            '           </div>' +
            '       </div>' +
            '    </div>' +
            '  </div>' +
            '</div>'
        ;

        let editorTemplate =
            '<table class="layui-table">' +
            '<tbody>' +
            '<tr>' +
            '<td align="right">编辑器类型</td>' +
            '<td>' +
            '   <select name="addition[type]">' +
            '       {{# let key;for(key in d.pluginConf.editor){ }}' +
            '       <option value="{{d.pluginConf.editor[key]}}" {{# if(d.addition.type === d.pluginConf.editor[key]){ }}selected="selected"{{# } }}>{{d.pluginConf.editor[key]}}</option>' +
            '       {{# } }}' +
            '   </select>' +
            '</td>' +
            '</tr>' +
            '<tr>' +
            '<td align="right">编辑器上传方式</td>' +
            '<td>' +
            '   <select name="addition[upload_type]">' +
            '       <option {{# if(d.addition.upload_type === "local"){ }}selected="selected"{{# } }} value="local">本地上传</option>' +
            '       <option {{# if(d.addition.upload_type === "ali-oss"){ }}selected="selected"{{# } }} value="ali-oss">阿里云OSS</option>' +
            '       <option {{# if(d.addition.upload_type === "qiniu-kodo"){ }}selected="selected"{{# } }} value="qiniu-kodo">七牛云KODO</option>' +
            '   </select>' +
            '</td>' +
            '</tr>' +
            '</tbody>' +
            '</table>';

        if (noHtmlArr.indexOf(formType) !== -1) {
            $("#addition").html("<div style=\"padding: 9px 5px;\">无</div>");
        } else if (optionsArr.indexOf(formType) !== -1) {
            $("#addition").html(layui.laytpl(optionsTemplate).render(editData));
            layui.form.render();
        } else {
            $("#addition").html(layui.laytpl(eval(formType + "Template")).render(editData));
            if (formType === "editor" && !pluginConf) {
                facade.error('请先到插件市场安装一种编辑器');
            }
            if (formType === 'linkage_select') {
                linkageField(table, editData);
            }
            layui.form.render();
            layui.laytpForm.render("#addition");
        }
    };

    // 选择表单元素下拉框后回调
    window.formTypeChange = function (params) {
        if(params.arr.length > 0){
            let formType = params.arr[0].value;
            formTypeChangePrivate(formType);
        }
    };

    // 选择关联模型数据表后回调
    window.relationTableChange = function (params) {
        if(params.arr.length > 0){
            let table = params.arr[0].title;
            relationTableChangePrivate(table);
        }else{
            layui.$("#relationField").html('<option value="">请选择字段</option>');
            layui.$("#relationShowField").html('<option value="">请选择字段</option>');
            layui.$("input[name='relation[fun_name]']").val('');
            layui.form.render('select');
        }
    };

    // 为方便编辑页面渲染，选择关联模型数据表后回到私有方法
    window.relationTableChangePrivate = function (table, editData) {
        if (table) {
            if (typeof editData === "undefined" || editData.relation === null) {
                editData = {
                    relation: {
                        table: "",
                        type: "",
                        field: "",
                        show_field: "",
                        fun_name: "",
                    }
                }
            }
            facade.ajax({
                route: "/plugin/curd/curd/getFieldList",
                data: {table: table},
                successAlert: false
            }).then(function (res) {
                if (res.code === 0) {
                    layui.$("#relationField").html('<option value="">请选择字段</option>');

                    if (editData.relation.field === 'id') {
                        layui.$("#relationField").append("<option value='id' selected='selected'>id</option>");
                    } else {
                        layui.$("#relationField").append("<option value='id'>id</option>");
                    }

                    layui.$("#relationShowField").html('<option value="">请选择字段</option>');
                    let key;
                    let data = res.data.data;
                    for (key in data) {
                        if (editData.relation.field === data[key]["field"]) {
                            $("#relationField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                        } else {
                            $("#relationField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                        }
                        if (editData.relation.show_field === data[key]["field"]) {
                            $("#relationShowField").append("<option value='" + data[key]["field"] + "' selected='selected'>" + data[key]["field"] + "</option>");
                        } else {
                            $("#relationShowField").append("<option value='" + data[key]["field"] + "'>" + data[key]["field"] + "</option>");
                        }
                    }
                    layui.form.render('select');
                }
            });
        }
    }

    exports(MOD_NAME, field);
});

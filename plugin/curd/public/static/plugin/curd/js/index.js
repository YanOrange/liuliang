layui.use(["laytp"], function () {
    const funController = {};
    //添加数据表需要展示的html页面地址
    window.addTableHtml = "/plugin/curd/table/add.html";
    //编辑数据表需要展示的html页面地址
    window.editTableHtml = "/plugin/curd/table/edit.html";
    //删除数据表数据请求的后端路由地址
    window.delTableRoute = "/plugin/curd/curd.table/del";
    //数据表回收站需要展示的html页面地址
    window.recycleTableHtml = "/plugin/curd/table/recycle.html";
    //数据表表格编辑需要请求的后台路由地址
    window.tableEditTableRoute = "/plugin/curd/curd.table/tableEdit";

    //添加字段需要展示的html页面地址
    window.addFieldHtml = "/plugin/curd/field/add.html";
    //编辑字段需要展示的html页面地址
    window.editFieldHtml = "/plugin/curd/field/edit.html";
    //删除字段数据请求的后端路由地址
    window.delFieldRoute = "/plugin/curd/curd.field/del";
    //字段回收站需要展示的html页面地址
    window.recycleFieldHtml = "/plugin/curd/field/recycle.html";
    //字段表格编辑需要请求的后台路由地址
    window.fieldEditTableRoute = "/plugin/curd/curd.field/tableEdit";
    //生成常规CURD按钮弹框展示的html页面地址
    window.createNormalCurdHtml = "/plugin/curd/createNormalCurd.html";
    //生成无限极分类CURD按钮弹框展示的html页面地址
    window.createCategoryCurdHtml = "/plugin/curd/createCategoryCurd.html";

    layui.context.del("laytpDevtoolCurdTableId");

    //数据表表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#database-table"
            , id: "database-table"
            , url: facade.url("/plugin/curd/curd.table/index")
            , toolbar: "#database-table-toolbar"
            , defaultToolbar: []
            , where: where
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , page: {
                curr: page
            }
            , done: function(){
                layui.laytpTable.done(30);
            }
            , parseData: function (res) {
                return facade.parseTableData(res, true);
            }
            , cols: [[
                {field: "table", title: "表名"}
                , {field: "comment", title: "表注释"}
                , {
                    field: "operation",
                    title: "操作",
                    toolbar: "#database-table-bar",
                    fixed: "right",
                    align: "center",
                    width: 140
                }
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(database-table)", function (obj) {
            //自定义按钮点击事件
            switch(obj.event){
                //自定义按钮点击事件
                case "addTable":
                    let addOptions = {};
                    addOptions.title = "添加数据表";
                    addOptions.path = window.addTableHtml;
                    facade.popupDiv(addOptions);
                    break;
                case "recycleTable":
                    let recycleOptions = {};
                    recycleOptions.title = "数据表回收站";
                    recycleOptions.path = window.recycleTableHtml;
                    recycleOptions.width = "70%";
                    recycleOptions.height = "94%";
                    facade.popupDiv(recycleOptions);
                    break;
            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on("tool(database-table)", function (obj) {
            //自定义按钮点击事件
            switch(obj.event){
                //自定义按钮点击事件
                case "editTable":
                    let editOptions = {};
                    editOptions.title = "编辑数据表";
                    editOptions.path = editTableHtml + "?id=" + obj.data.id;
                    facade.popupDiv(editOptions);
                    break;
                //展示数据表字段列表按钮点击事件
                case "tableFields":
                    $(".empty").hide();
                    funController.fieldRender({
                        "search_param" : {
                            "table_id" : {
                                "value" : obj.data.id,
                                "condition" : "="
                            }
                        },
                        "all_data" : 1
                    });
                    layui.context.put("laytpDevtoolCurdTableId", obj.data.id);
                    layui.context.put("laytpDevtoolCurdTable", obj.data.table);
                    $('#table_id').val(obj.data.id);
                    obj.tr.parent().children("tr").css("background-color","");
                    obj.tr.parent().children("tr").children("td").children("div").css("color","");
                    obj.tr.css("background-color","#2d8cf0");
                    obj.tr.children("td").children("div").css("color","white");
                    break;
                case "delTable":
                    facade.popupConfirm({text: "确定删除么?", route: window.delTableRoute, data: {ids: [obj.data.id]}});
                    break;
            }
        });
    };

    //修改字段排序成功后的回调函数
    funController.fieldTableEditCallback = function(){
        $("#search-field-form-submit-btn").click();
    };

    //字段表格渲染
    funController.fieldRender = function (where) {
        where['all_data'] = 1;
        layui.table.render({
            elem: "#field-table"
            , id: "field-table"
            , url: facade.url("/plugin/curd/curd.field/index")
            , toolbar: "#field-table-toolbar"
            , defaultToolbar: []
            , where: where
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , parseData: function (res) {
                return facade.parseTableData(res, false);
            }
            , done: function(){
                layui.laytpTable.done(30);
            }
            , cols: [[
                {field: "field", title: "字段名"}
                , {field: "comment", title: "字段注释"}
                , {field: "form_type", title: "表单元素"}
                , {field: "show_sort", title: "排序", align: "center", templet:function(d){
                    return laytpForm.tableForm.editInput('show_sort', d, "/plugin/curd/curd.field/setShowSort", 'funController.fieldTableEditCallback');
                }}
                , {
                    field: "operation",
                    title: "操作",
                    toolbar: "#field-table-bar",
                    fixed: "right",
                    align: "center",
                    width: 140
                }
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(field-table)", function (obj) {
            //自定义按钮点击事件
            switch(obj.event){
                //自定义按钮点击事件
                case "addField":
                    let addOptions = {};
                    addOptions.title = "添加字段";
                    addOptions.path = window.addFieldHtml;
                    addOptions.width = '90%';
                    addOptions.height = '94%';
                    facade.popupDiv(addOptions);
                    break;
                case "recycleField":
                    let recycleOptions = {};
                    recycleOptions.title = "字段回收站";
                    recycleOptions.path = window.recycleFieldHtml;
                    recycleOptions.width = "70%";
                    recycleOptions.height = "94%";
                    facade.popupDiv(recycleOptions);
                    break;
                case "createNormalCurd":
                    let createNormalCurdOptions = {};
                    createNormalCurdOptions.title = "生成常规Curd";
                    createNormalCurdOptions.path = window.createNormalCurdHtml + "?table_id=" + $('#table_id').val();
                    createNormalCurdOptions.width = "80%";
                    createNormalCurdOptions.height = "80%";
                    facade.popupDiv(createNormalCurdOptions);
                    break;
                case "createCategoryCurd":
                    var tableId = $('#table_id').val();
                    facade.ajax({
                        route: "/plugin/curd/curd.table/info",
                        data: {id: tableId},
                        successAlert: false,
                        showLoading: true
                    }).done(function(res){
                        if(res.code === 0){
                            let createCategoryCurdOptions = {};
                            createCategoryCurdOptions.title = "生成无限极分类Curd";
                            createCategoryCurdOptions.path = window.createCategoryCurdHtml + "?table_id=" + tableId;
                            createCategoryCurdOptions.width = "80%";
                            createCategoryCurdOptions.height = "80%";
                            facade.popupDiv(createCategoryCurdOptions);
                        }
                    });
                    break;
            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on("tool(field-table)", function (obj) {
            //自定义按钮点击事件
            switch(obj.event){
                //自定义按钮点击事件
                case "editField":
                    let editOptions = {};
                    editOptions.title = "编辑字段";
                    editOptions.path = editFieldHtml + "?id=" + obj.data.id;
                    editOptions.width = '80%';
                    editOptions.height = '94%';
                    facade.popupDiv(editOptions);
                    break;
                case "delField":
                    facade.popupConfirm({
                        text: "确定删除么?",
                        route: window.delFieldRoute,
                        data: {ids: [obj.data.id]}
                    },function(){
                        $("button[lay-filter='laytp-search-field-form']").click();
                    });
                    break;
            }
        });
    };

    funController.tableRender();

    window.funController = funController;

    /**
     * 监听字段搜索表单提交事件
     */
    layui.form.on("submit(laytp-search-field-form)", function (obj) {
        if(layui.context.get("laytpDevtoolCurdTableId")){
            funController.fieldRender(obj.field);
        }else{
            facade.error('请选择数据表');
        }
        return false;
    });

    /**
     * 监听字段搜索表单重置事件
     */
    $(document).off("click", ".laytp-search-field-form-reset").on("click", ".laytp-search-field-form-reset", function () {
        if(layui.context.get("laytpDevtoolCurdTableId")) {
            funController.fieldRender({
                "search_param": {
                    "table_id": {
                        "value": layui.context.get("laytpDevtoolCurdTableId"),
                        "condition": "="
                    }
                },
            });
            $("#field").val('');
        }else{
            facade.error('请选择数据表');
        }
    });
});
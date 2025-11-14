layui.use(['laytp'], function () {
    const funRecycleController = {};
    //静态页面地址前缀
    if(facade.isAdminDomainModel()){
        window.htmlPrefix = "/plugin/curd/curd/field/";
    }else{
        window.htmlPrefix = "/admin/plugin/curd/curd/field/";
    }
    //后端接口地址前缀
    window.apiPrefix  = "/plugin/curd/curd.field/";

    //表格渲染
    funRecycleController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-recycle-field"
            , id: "laytp-recycle-field"
            , url: facade.url("/plugin/curd/curd.field/recycle")
            , toolbar: "#recycle-default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'recycle-refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print', 'exports']
            , where: where
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , loading: false
            , page: {
                curr: page
            }
            , parseData: function (res) { //res 即为原始返回的数据
                return facade.parseTableData(res, true);
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "table_id", title: "所属数据表"}
                , {field: "field", title: "字段名称"}
                , {field: "comment", title: "字段注释"}
                , {
                    field: "operation",
                    title: "操作",
                    toolbar: "#recycle-default-bar",
                    fixed: "right",
                    align: "center",
                    width: 140
                }
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-recycle-field)", function (obj) {
            // var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            // if (defaultTableToolbar.indexOf(obj.event) !== -1) {
            //     //默认按钮点击事件
            //     laytp.tableToolbar(obj);
            // } else {
            //     // //自定义按钮点击事件
            //     // switch(obj.event){
            //     // //自定义按钮点击事件
            //     // case "":
            //     //
            //     //     break;
            //     // }
            // }
            if (obj.event === "restore") {
                let checkData;
                let checkStatus = layui.table.checkStatus(obj.config.id);
                checkData = checkStatus.data;
                if (checkData.length === 0) {
                    facade.error("请选择数据");
                    return false;
                }
                let key;
                let ids = [];
                for (key in checkData) {
                    ids.push(checkData[key].id);
                }
                facade.ajax({
                    route: window.apiPrefix + 'restore',
                    data: {ids: ids.join(",")},
                    showLoading: true
                }).done(function (res) {
                    if (res.code === 0) {
                        $("button[lay-filter='laytp-search-field-form']").click();
                        funRecycleController.tableRender();
                    }
                });
            } else if (obj.event === "true-del") {
                let checkData;
                let checkStatus = layui.table.checkStatus(obj.config.id);
                checkData = checkStatus.data;
                if (checkData.length === 0) {
                    facade.error("请选择数据");
                    return false;
                }

                let key;
                let ids = [];
                for (key in checkData) {
                    ids.push(checkData[key].id);
                }
                facade.popupConfirm({
                    text: "真的在回收站删除么？此次删除将不能还原",
                    route: window.apiPrefix + 'trueDel',
                    data: {ids: ids.join(",")}
                },function(){
                    $("button[lay-filter='laytp-search-field-form']").click();
                    funRecycleController.tableRender();
                });
            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on('tool(laytp-recycle-field)', function (obj) {
            if (obj.event === "restore") {
                facade.ajax({route: window.apiPrefix + 'restore', data: {ids: obj.data.id}}).done(function (res) {
                    if (res.code === 0) {
                        $("button[lay-filter='laytp-search-field-form']").click();
                        funRecycleController.tableRender();
                    }
                });
            } else if (obj.event === "true-del") {
                facade.popupConfirm({
                    text: "真的在回收站删除么？此次删除将不能还原",
                    route: window.apiPrefix + '/trueDel',
                    data: {ids: obj.data.id}
                },function(){
                    funRecycleController.tableRender();
                });
            }
        });
    };

    funRecycleController.tableRender();

    window.funRecycleController = funRecycleController;
});
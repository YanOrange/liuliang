layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/bi_cost/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.bi_cost/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.bi_cost/index")
            , toolbar: "#default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print']
            , where: where
            , autoSort: false
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , loading: false
            , page: {
                curr: page
            }
            , parseData: function (res) { //res 即为原始返回的数据
                if (res.data.hasAllow == true) {
                    $("#uploadExcel").show()
                }
                return facade.parseTableData(res, true);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 80}
                , {field: "plantime", title: "投放日期", align: "center", width: 160}
                , {field: "store", title: "商店", align: "center", width: 160}
                , {field: "app_class_name", title: "类目", align: "center", width: 160}
                , {field: "app_name", title: "应用名称", align: "center", width: 160}
                , {field: "channel_name", title: "渠道名称", align: "center", width: 160}
                , {field: "cost", title: "消耗", align: "center", width: 160}
                , {field: "expose", title: "曝光量", align: "center", width: 160}
                , {field: "download", title: "下载量", align: "center", width: 160}
                , {field: "admin_user_username", title: "提交人", align: "center", width: 160}
                , {field: "admin_modify_username", title: "修改人", align: "center", width: 160}
                , {field: "create_time", title: "提交时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 160}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                if (obj.event == "search") {
                    if ($("#search-form").is(":hidden")) {
                        $("#uploadExcel").css("top", "266px")
                    } else {
                        $("#uploadExcel").css("top", "30px")
                    }
                }
                laytp.tableToolbar(obj);
            } else {
                // //自定义按钮点击事件
                // switch(obj.event){
                // //自定义按钮点击事件
                // case "":
                //
                //     break;
                // }
            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on("tool(laytp-table)", function (obj) {
            var defaultTableTool = layui.context.get("defaultTableTool");
            if (defaultTableTool.indexOf(obj.event) !== -1) {
                laytp.tableTool(obj);
            } else {
                // //自定义按钮点击事件
                // switch(obj.event){
                // //自定义按钮点击事件
                // case "":
                //
                //     break;
                // }
            }
        });
    };

    funController.tableRender();

    window.funController = funController;
});
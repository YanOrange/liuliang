layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/customer_distribute/");
    window.apiPrefix  = facade.compatibleApiRoute("/admin.customer_distribute/");
    funController.tableRender = function (where) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.customer_distribute/quality")
            , toolbar: "#default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'refresh',
                icon: 'layui-icon-refresh',
            }, 'filter']
            , where: where
            , autoSort: false
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , loading: false
            , parseData: function (res) { //res 即为原始返回的数据
                return facade.parseTableData(res, false);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                {field: "merchant_name", title: "商户名称", align: "center", width: "20%"}
                , {field: "days", title: "分配质量差连续反馈天数", align: "center", width: "20%", edit: "text"}
                , {field: "admin_user_username", title: "修改人", align: "center", width: "20%"}
                , {field: "update_time", title: "修改时间", align: "center", width: "20%"}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 140}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                laytp.tableToolbar(obj);
            }
        });

        // 监听编辑事件
        layui.table.on("edit(laytp-table)", function (obj) {
            $.post('/admin.customer_distribute/setDays', obj.data, function (res) {
                layui.layer.msg(res.msg);
            })
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
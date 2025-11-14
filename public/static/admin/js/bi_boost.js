layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/bi_boost/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.bi_boost/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.bi_boost/index")
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
                return facade.parseTableData(res, true);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 80}
                , {field: "threaddate", title: "线索日期", align: "center", width: 160}
                , {field: "teenager_count", title: "未成年人补量条数", align: "center", width: 160}
                , {field: "deletion_count", title: "秒删补量条数", align: "center", width: 160}
                , {field: "special_count", title: "特殊补量条数", align: "center", width: 160}
                , {field: "weixin_count", title: "加微信补量条数", align: "center", width: 160}
                , {field: "cpa", title: "CPA", align: "center", width: 160}
                , {field: "money", title: "补量金额", align: "center", width: 160}
                , {field: "merchant_name", title: "补量商户名称", align: "center", width: 160}
                , {field: "pre_sales_name", title: "所属前端销售名称", align: "center", width: 160}
                , {field: "after_sales_name", title: "所属后端销售名称", align: "center", width: 160}
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
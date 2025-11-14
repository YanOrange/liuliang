layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/wx_official_account/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.wx_official_account/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.wx_official_account/index")
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
                , {field: "account_name", title: "账号名称", align: "center"}
                , {field: "appId", title: "账号标识", align: "center"}
                , {field: "platform_id", title: "所属平台", align: "center", templet:'<div>{{# if(d.platform){ }}{{d.platform.platform_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "account_type", title: "账号主体类型", align: "center"}
                , {field: "admin_id", title: "负责人", align: "center", templet:'<div>{{# if(d.admin){ }}{{d.admin.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center"}
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
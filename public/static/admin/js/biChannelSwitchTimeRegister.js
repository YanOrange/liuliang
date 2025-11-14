layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/bi_channel_switch_time_register/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.bi_channel_switch_time_register/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.bi_channel_switch_time_register/index")
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
                    $("#uploadExcelBtn").show()
                }
                return facade.parseTableData(res, true);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                ,, {field: "id", title: "ID", align: "center", width: 80}
                , {field: "release_day", title: "开户日期", align: "center",width: 120,}
                , {field: "unix_release_time", title: "开户时间", align: "center",width: 120,}
                , {field: "unix_close_time", title: "关户时间", align: "center",width: 120,}
                , {field: "channel_name", title: "渠道", align: "center",width: 120,}
                , {field: "app_id", title: "应用名称", align: "center",width: 120, templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "app_class_id", title: "类目", align: "center", width: 120, templet:'<div>{{# if(d.appClass){ }}{{d.appClass.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "add_admin_id", title: "提交人", align: "center", width: 120, templet:'<div>{{# if(d.addAdmin){ }}{{d.addAdmin.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "up_admin_id", title: "修改人", align: "center", width: 120, templet:'<div>{{# if(d.upAdmin){ }}{{d.upAdmin.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 280}
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

            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on("tool(laytp-table)", function (obj) {
            var defaultTableTool = layui.context.get("defaultTableTool");
            if (defaultTableTool.indexOf(obj.event) !== -1) {
                laytp.tableTool(obj);
            } else {
                //自定义按钮点击事件
                switch(obj.event){
                    case "link-list":
                        facade.popupDiv({
                            title: "生成链接",
                            width:'850px',
                            height:'500px'
                            , path: "/admin/for_flow/link.html?id=" + obj.data.id
                        });
                        break;
                    case "data-analysis-list":
                        facade.popupDiv({
                            title: "数据分析"
                            , path: "/admin/flow_pv_uv/index.html?id=" + obj.data.id
                        });
                        break;
                }
            }
        });

        //监听表头排序事件
        layui.table.on('sort(laytp-table)', function(obj){
            layui.table.reload('laytp-table', {
                initSort: obj //记录初始排序，如果不设的话，将无法标记表头的排序状态。
                , where: {
                    "order_param" : {
                        "field" : obj.field,
                        "type" : obj.type
                    }
                }
            });
        });
    };

    funController.tableRender();

    window.funController = funController;
});
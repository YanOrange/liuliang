layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/overview_of_launch/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.OverviewOfLaunch/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.OverviewOfLaunch/index")
            , toolbar: "#default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print', 'exports']
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
                // , {field: "appName", title: "应用名称", align: "center", width: 160,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "appClass", title: "应用分类", align: "center", width: 160,templet:'<div>{{# if(d.class){ }}{{d.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "channel", title: "渠道", align: "center", width: 160,templet:'<div>{{# if(d.channel){ }}{{d.channel.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "platform", title: "投放平台", align: "center", width: 160}
                , {field: "plan_date", title: "投放日期", align: "center", width: 160}
                , {field: "point_rate", title: "返点", align: "center", width: 160}
                , {field: "cost", title: "投放消耗", align: "center", width: 160}
                , {field: "cost_back", title: "实际消耗", align: "center", width: 160}
                , {field: "expose", title: "曝光量", align: "center", width: 160}
                , {field: "download", title: "下载量", align: "center", width: 160}
                // , {field: "download_cost", title: "下载成本", align: "center", width: 160}
                , {field: "click", title: "点击数", align: "center", width: 160}
                , {field: "convert_active", title: "回传激活量", align: "center", width: 160}
                , {field: "convert_register", title: "回传注册量", align: "center", width: 160}
                , {field: "jiaFenNum", title: "加粉数", align: "center", width: 160}
                , {field: "adminUser", title: "创建人", align: "center", width: 160,templet:'<div>{{# if(d.adminUser){ }}{{d.adminUser.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "adminUserUpdate", title: "修改人", align: "center", width: 160,templet:'<div>{{# if(d.adminUserUpdate){ }}{{d.adminUserUpdate.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 140}
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
layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/app/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.app/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.app/index")
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
                , {field: "app_name", title: "应用名称", align: "center", width: 100}
                , {field: "app_class_id", title: "应用分类", align: "center", width: 100,templet:'<div>{{# if(d.class){ }}{{d.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "android_bundle_id", title: "安卓包名", align: "center"}
                , {field: "ios_bundle_id", title: "ios包名", align: "center"}
                , {
                    field: "is_login_show", title: "启动app是否需要登录", align: "center", templet: function (d) {
                        return laytpForm.tableForm.switch("is_login_show", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {
                    field: "is_landing_page", title: "是否开启落地页", align: "center", templet: function (d) {
                        return laytpForm.tableForm.switch("is_landing_page", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {field:'is_many_organization',title:'机构版本',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_many_organization',d.is_many_organization,{"value":["1","2","3","4","5"],"text":["单类目单机构","单类目多机构","多类目多机构","单机构2.0","单机构2.5"]}, true);
                    }}
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
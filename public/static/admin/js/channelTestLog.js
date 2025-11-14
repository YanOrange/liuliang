layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/channel_test_log/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.channel_test_log/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.channel_test_log/index")
            , toolbar: "#default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print']
            , where: where
            , autoSort: false
            , method: "GET"
            , cellMinWidth: 100
            , skin: 'line'
            , loading: false
            , page: {
                curr: page
            }
            , parseData: function (res) { //res 即为原始返回的数据
                return facade.parseTableData(res, true);
            }
            , done: function(res){
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "title", title: "测试名称", align: "center" ,width: 100}
                , {field: "channel_id", title: "渠道名称", width: 160, align: "center",templet:'<div>{{# if(d.app){ }}{{d.app.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "cycle_time", title: "测试周期（天）", width: 140, align: "center"}
                , {field: "target", title: "目标", align: "center"}
                , {field: "rate", title: "分量", align: "center"}
                , {field: "material_name", title: "素材名称", align: "center"}
                , {field: "app_img", title: "应用截图", align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.app_img);
                    }}
                , {field: "atmosphere_text", title: "氛围图文案", align: "center"}
                , {field: "atmosphere_img", title: "氛围图UI", align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.atmosphere_img);
                    }}
                , {field: "icon_text", title: "icon文案", align: "center"}
                , {field: "icon_img", title: "iconUI", align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.icon_img);
                    }}
                , {field: "aword", title: "一句话", align: "center"}
                , {field: "material_onshelf_time", title: "素材上架时间", align: "center", width: 160}
                , {field: "material_offshelf_time", title: "素材下架时间", align: "center", width: 160}
                , {field: "create_user_id", title: "创建人", align: "center",templet:'<div>{{# if(d.createUser){ }}{{d.createUser.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_user_id", title: "更新人", align: "center",templet:'<div>{{# if(d.updateUser){ }}{{d.updateUser.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "update_time", title: "更新时间", align: "center", width: 160}
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

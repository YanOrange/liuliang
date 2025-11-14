layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/advertiser_callback_channel/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.advertiser_callback_channel/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.advertiser_callback_channel/index")
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
                , {field: "channel", title: "渠道", align: "center"}
                , {field: "behavior_type", title: "用户行为", align: "center"}
                , {field:'attributional_type',title:'归因方式', width: 100,align:'center',templet:function(d){
                        return laytp.tableFormatter.status('attributional_type',d.attributional_type, {"value":["1","2"],"text":["广告主归因","平台归因"]}, true);
                    }
                }
                , {field:'callback_type',title:'回传方式', width: 100,align:'center',templet:function(d){
                        return laytp.tableFormatter.status('callback_type',d.callback_type,
                            {"value":["1","2"],"text":["生成线索","识别二维码"]}, true);
                    }
                }
                , {field:'is_callback',title:'是否回传', width: 100,align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_callback',d.is_callback, {"value":["0","1"],"text":["否","是"]}, true);
                    }
                }
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 220}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
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
                    case "landing-page-list":
                        facade.popupDiv({
                            title: "落地页列表"
                            , path: "/admin/landing_page/index.html?app_class_id=" + obj.data.id
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
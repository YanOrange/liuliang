layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/advertiser_callback_record/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.advertiser_callback_record/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.advertiser_callback_record/index")
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
                , {field: "id", title: "ID", align: "center"}
                , {field: "channel", title: "商店", align: "center"}
                , {
                    field: "channel_name", title: "渠道", align: "center"
                }
                , {field: "app_bundle_id", title: "包名", align: "center", width: 200}
                , {field:'cvType',title:'用户行为',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('cvType',d.cvType,
                            {"value":["register","active","submit","pay","key_behavior","secondary_retention","other","effective_consult","place_order"], "text":["注册","激活","表单提交","应用付费","关键行为","次留","其他","有效咨询","用户下单"]}, true);
                    }}
                , {field:'ascribeType',title:'归因类型',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('ascribeType',d.ascribeType,{"value":["0","1"],"text":["-","广告主归因"]}, true);
                    }}
                , {field:'is_callback',title:'是否回传',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_callback',d.is_callback,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field:'source',title:'回传来源',align:'center',width: 100,templet:function(d){
                        return laytp.tableFormatter.status('source',d.source,{"value":["1","2","3","4"],"text":["app","app信息流","app户1","app户2"]}, true);

                    }}
                , {field: "oaid", title: "oaid", align: "center"}
                , {field: "create_time", title: "创建时间", align: "center"}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                laytp.tableToolbar(obj);
            } else {
                switch(obj.event){
                    case "exportAdvertiserCallbackRecord":
                        window.location.href = '/admin.advertiser_callback_record/exportAdvertiserCallbackRecord';
                        break;
                }
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
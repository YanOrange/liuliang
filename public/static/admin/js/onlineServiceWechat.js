layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/online_service_wechat/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.online_service_wechat/");
    merchantId = facade.getUrlParam('merchant_id');
    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.online_service_wechat/index")
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
                , {field: "service_name", title: "客服名称", align: "center"}
                , {field:'btn_reply_type',title:'按钮回复',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('btn_reply_type',d.btn_reply_type, {"value":["1","2","3"],"text":["加V","复制微信","表单"]}, true);
                    }}
                , {field: "wechat_number", title: "微信号", align: "center", width: 120}
                , {field: "prompt_text", title: "提示文字", align: "center", width: 120}
                , {field: "auto_reply_content", title: "自动回复语", align: "center", width: 160}
                , {field: "auto_push_time", title: "自动推送时间", align: "center"}
                , {field: "speech_btn_desc", title: "话术按钮", align: "center"}
                , {
                    field: "bottom_jump_wechat", title: "底部加V", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("bottom_jump_wechat", d, {
                            "open": {"value": 1, "text": "显示"},
                            "close": {"value": 0, "text": "隐藏"}
                        });
                    }
                },
                //bottom_jump_wechat
                {
                    field: "transfer_service_btn", title: "转人工按钮", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("transfer_service_btn", d, {
                            "open": {"value": 1, "text": "显示"},
                            "close": {"value": 0, "text": "隐藏"}
                        });
                    }
                }
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: "10%"}
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
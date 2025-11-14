layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/distribution_collection_order_business/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.distribution_collection_order_business/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.distribution_collection_order_business/index")
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
                , {field: "phone", title: "用户手机号", align: "center", templet:'<div>{{# if(d.user){ }}{{d.user.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "channel_id", title: "所属渠道", align: "center", templet:'<div>{{# if(d.channel){ }}{{d.channel.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "用户注册时间", align: "center", templet:'<div>{{# if(d.user){ }}{{d.user.create_time}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "merchant_id", title: "商户名称", align: "center", templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "customer_id", title: "客服昵称", align: "center", templet:'<div>{{# if(d.customer){ }}{{d.customer.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "pay_time",title:'最近支付时间',align:'center'}
                // , {field: "pay_num", title: "支付次数", align: "center", width: 100}
                , {field: "pay_amount_origin", title: "支付金额", align: "center", width: 100}
                , {field: "pay_amount", title: "修改后金额", align: "center", width: 100}
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
                switch(obj.event){
                    //自定义按钮点击事件
                    case "detail":
                        facade.popupDiv({
                            title: "数据明细"
                            , path: "/admin/distribution_collection_order_detail/index.html?page_id=" + obj.data.uid + "&uid=" + obj.data.uid
                        });
                        break;
                }
            } else {
                //自定义按钮点击事件
                switch(obj.event){
                //自定义按钮点击事件
                case "detail":
                    facade.popupDiv({
                        title: "数据明细"
                        , path: "/admin/distribution_collection_order_detail/index.html?page_id=" + obj.data.uid + "&uid=" + obj.data.uid
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
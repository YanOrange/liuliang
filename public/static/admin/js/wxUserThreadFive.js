layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/wx_user_thread_five/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.wx_user_thread_five/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.wx_user_thread_five/index")
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
                , {field:'platform_id',title:'平台',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('platform_id',d.platform_id, {"value":["0","1","2"],"text":["-","抖音","小红书"]}, true);
                    }}
                , {field: "nickname", title: "姓名", align: "center"}
                , {field: "wechat_account", title: "微信号", align: "center"}
                , {field: "age", title: "年龄", align: "center"}
                , {field: "phone", title: "手机号", align: "center",width:120}
                , {field: "career", title: "职业", align: "center"}
                , {field: "hospital_sku", title: "sku", align: "center",width:150}
                , {field: "transaction_amount", title: "成交金额", align: "center",width:120}
                , {field: "accompanying_physician", title: "陪诊师", align: "center"}
                , {field:'is_repurchase',title:'是否复购',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_repurchase',d.is_repurchase,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "disease_type", title: "病种", align: "center",width:120}
                , {field: "city", title: "城市", align: "center"}
                , {field: "area", title: "地区", align: "center"}
                , {field: "hospital_name", title: "医院", align: "center",width:200}
                , {field: "visit_time", title: "就诊时间", align: "center",width:120}
                , {field: "add_wechat_time", title: "加微时间", align: "center",width:120}
                , {field: "transaction_time", title: "成交时间", align: "center",width:120}
                , {field: "payment_method", title: "支付方式", align: "center",width:120}
                , {
                    field: "is_deal", title: "是否结算", align: "center", width: 100, templet: function (d) {
                        return laytpForm.tableForm.switch("is_deal", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });

                    }
                }
                , {field: "sale_admin_user", title: "负责销售", align: "center",width:120}
                , {field: "head_name", title: "新媒体账号负责人", align: "center",width:120}
                , {field: "communicate_feature", title: "沟通特点", align: "center",width:120}
                , {field: "special_needs", title: "特殊需求", align: "center",width:120}
                , {field:'micro_type',title:'加微类型',align:'center',width:120,templet:function(d){
                        return laytp.tableFormatter.status('micro_type',d.micro_type,{"value":["1","2"],"text":["陪诊加微","学习加微"]}, true);
                    }}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
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
                switch(obj.event) {
                    case "exportAccompanying":
                        window.location.href = '/admin.wx_user_thread_five/exportAccompanying';
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
                //自定义按钮点击事件
                switch(obj.event){
                    case "":
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
layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/customer/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.customer/");
    merchantId = facade.getUrlParam('merchant_id');
    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.customer/index?merchant_id=" + facade.getUrlParam('merchant_id'))
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
                , {field: "merchant_id", title: "商户", align: "center",templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "account_name", title: "登录账号", align: "center", width: 120}
                , {field: "nickname", title: "昵称", align: "center", width: 120}
                , {
                   field: "avatar", title: "头像", align: "center", width: 80, templet: function (d) {
                        return laytp.tableFormatter.images(d.avatar);
                    }
                   }
                , {field: "wechat_number", title: "微信号", align: "center", width: 120}
                , {field: "customer_link", title: "获客链接", align: "center", width: 350}

                , {
                    field: "qr_code", title: "二维码", align: "center", width: 80, templet: function (d) {
                        return laytp.tableFormatter.images(d.qr_code);
                    }
                }
                , {field: "daily_intake_limit_nums", title: "当日线索分配数量限制", align: "center", width: 160}
                , {field: "app_intake_limit_nums", title: "当日APP线索预设量", align: "center", width: 160}
                , {field: "increase_intake_limit_nums", title: "当日APP线索加量数", align: "center", width: 160}
                , {field: "thread_count", title: "当天实际跑量", align: "center", width: 160}
                , {field: "valid_thread_nums_count", title: "当天无效线索补量条数", align: "center", width: 180}
                , {field: "assign_intake_limit_nums", title: "客服分配线索数量限制", align: "center", width: 160}
                , {field: "weight", title: "权重", align: "center"}
                , {
                    field: "thread_status", title: "线索状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("thread_status", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });

                    }
                }
                , {
                    field: "status", title: "账号状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "正常"},
                            "close": {"value": 0, "text": "禁用"}
                        });
                    }
                }
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: "10%"}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                if (obj.event == 'add') {
                    facade.popupDiv({
                        title: "添加客服"
                        , path: "/admin/customer/add.html?merchant_id=" + facade.getUrlParam('merchant_id')
                    });
                    return false;
                }
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
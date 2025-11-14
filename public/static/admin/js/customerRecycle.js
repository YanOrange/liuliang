layui.use(['laytp'], function () {
    const funRecycleController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/customer/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.customer/");

    //表格渲染
    funRecycleController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-recycle-table"
            , id: "laytp-recycle-table"
            , url: facade.url("/admin.customer/recycle")
            , toolbar: "#recycle-default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'recycle-refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print']
            , where: where
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
            , cols: [[ //表头
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
                , {
                    field: "qr_code", title: "二维码", align: "center", width: 80, templet: function (d) {
                        return laytp.tableFormatter.images(d.qr_code);
                    }
                }
                , {field: "daily_intake_limit_nums", title: "当日线索分配数量限制", align: "center", width: 160}
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
                , {
                    field: 'operation',
                    title: '操作',
                    toolbar: '#recycle-default-bar',
                    fixed: 'right',
                    align: 'center',
                    width: 140
                }
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-recycle-table)", function (obj) {
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                //默认按钮点击事件
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
        layui.table.on('tool(laytp-recycle-table)', function (obj) {
            var defaultTableTool = layui.context.get("defaultTableTool");
            if (defaultTableTool.indexOf(obj.event) !== -1) {
                laytp.tableTool(obj);
            } else {
                // //自定义按钮
                // switch(obj.event){
                // //自定义按钮点击事件
                // case '':
                //
                //     break;
                // }
            }
        });
    };

    funRecycleController.tableRender();

    window.funRecycleController = funRecycleController;
});
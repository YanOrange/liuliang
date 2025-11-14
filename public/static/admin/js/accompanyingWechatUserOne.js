layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/accompanying_wechat_user_one/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.accompanying_wechat_user_one/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.accompanying_wechat_user_one/index")
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
                , {field: "wechat_account", title: "微信账号", align: "center", width: 120}
                , {field: "wechat_name", title: "微信名称", align: "center", width: 120}
                , {field: "add_wechat_time", title: "加微时间", align: "center", width: 120}
                , {field: "datetime_range", title: "时间段", align: "center"}
                , {field: "nodeal_reason", title: "未成交原因", align: "center", width: 120}
                , {field: "intention", title: "意向度", align: "center", width: 120}
                , {field: "thread_source", title: "线索来源", align: "center"}
                , {field: "phone", title: "手机号", align: "center", width: 120}
                , {field: "nickname", title: "姓名", align: "center"}
                , {field: "province", title: "省份", align: "center"}
                , {field: "city", title: "城市", align: "center"}
                , {field: "sale_header", title: "销售负责人", align: "center"}
                , {field: "company_wechat_account", title: "公司微信号", align: "center"}
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

            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on("tool(laytp-table)", function (obj) {
            var defaultTableTool = layui.context.get("defaultTableTool");
            if (defaultTableTool.indexOf(obj.event) !== -1) {
                laytp.tableTool(obj);
            } else {
                //自定义按钮点击事件

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
layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/jzd_staging_list/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.jzd_staging_list/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.jzd_staging_list/index")
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
                if (res.data.hasAllow == true) {
                    $("#uploadExcelBtn").show()
                }
                return facade.parseTableData(res, true);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                ,, {field: "id", title: "ID", align: "center", width: 80}
                , {field: "user_mobile", title: "用户手机号", align: "center",width: 100,}
                , {field: "user_name", title: "用户姓名", align: "center",width: 100,}
                , {field: "payback_time", title: "回款时间", align: "center",width: 100,}
                , {field: "should_nums", title: "应回笔数", align: "center",width: 100,}
                , {field: "should_amount", title: "应回金额", align: "center",width: 100,}
                , {field: "actual_nums", title: "实回笔数", align: "center",width: 100,}
                , {field: "actual_amount", title: "实回金额", align: "center",width: 100,}
                , {field: "overdue_nums", title: "逾期笔数", align: "center",width: 100,}
                , {field: "overdue_amount", title: "逾期金额", align: "center",width: 100,}
                , {field: "overdueyh_nums", title: "逾期已回笔数", align: "center",width: 100,}
                , {field: "overdueyh_amount", title: "逾期已回金额", align: "center",width: 100,}
                , {field: "staging_platform", title: "分期平台", align: "center",width: 100,}
                , {field: "customer_id", title: "客服", align: "center",width: 100,templet:'<div>{{# if(d.customer){ }}{{d.customer.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "refund_nums", title: "退款笔数", align: "center",width: 100,}
                , {field: "refund_amount", title: "退款金额", align: "center",width: 100,}
                , {field: "refund_course_nums", title: "净退款退课数", align: "center",width: 100,}
                , {field: "overdue_seven_day_nums", title: "逾期7天以上笔数", align: "center",width: 100,}
                , {field: "overdue_seven_day_amount", title: "逾期7天以上金额", align: "center",width: 100,}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                if (obj.event == "search") {
                    if ($("#search-form").is(":hidden")) {
                        $("#uploadExcel").css("top", "266px")
                    } else {
                        $("#uploadExcel").css("top", "30px")
                    }
                }
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
                    case "link-list":
                        facade.popupDiv({
                            title: "生成链接",
                            width:'850px',
                            height:'500px'
                            , path: "/admin/for_flow/link.html?id=" + obj.data.id
                        });
                        break;
                    case "data-analysis-list":
                        facade.popupDiv({
                            title: "数据分析"
                            , path: "/admin/flow_pv_uv/index.html?id=" + obj.data.id
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
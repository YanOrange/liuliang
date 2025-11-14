layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/bi_yqh5_oppo_data/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.bi_yqh5_oppo_data/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.bi_yqh5_oppo_data/index")
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
                , {field: "id", title: "ID", align: "center", width: 80}
                , {field: "nick_name", title: "姓名", align: "center",width: 120,}
                , {field: "phone", title: "手机号", align: "center",width: 120,}
                , {field: "advertiser_id", title: "广告ID", align: "center",width: 120,}
                , {field: "land_page_name", title: "落地页名称", align: "center",width: 120,}
                , {field: "land_page_id", title: "落地页ID", align: "center",width: 120,}
                , {field: "form_name", title: "表单名称", align: "center",width: 120,}
                , {field: "submit_time", title: "提交时间", align: "center",width: 120,}
                , {field: "connect_status", title: "接通状态", align: "center",width: 120,}
                , {field: "follow_status", title: "跟进状态", align: "center",width: 120,}
                , {field: "thread_id", title: "线索ID", align: "center",width: 120,}
                , {field: "inside_thread_id", title: "内部线索ID", align: "center",width: 120,}
                , {field: "province", title: "省份", align: "center",width: 120,}
                , {field: "city", title: "城市", align: "center",width: 120,}
                , {field: "lahei_status", title: "拉黑状态", align: "center",width: 120,}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 280}
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
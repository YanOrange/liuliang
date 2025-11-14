layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/single/resource/");
    //后端接口地址前缀
    window.apiPrefix = facade.compatibleApiRoute("/admin.single.resource/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.single.resource/index")
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
            , done: function () {
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center"}
                , {field: "title", title: "资源名称", align: "center", width: 180}
                , {field: "merchant_names", title: "所属商户", align: "center", width: 180}
                , {field: "app_names", title: "所属应用", align: "center", width: 180}
                , {
                    field: "resource_type", title: "类型", align: "center", templet: function (d) {
                        return laytp.tableFormatter.status('resource_type', d.resource_type, {
                            "value": ["1", "2"],
                            "text": ["热门", "推荐"]
                        }, true);
                    }, width: 80
                }
                , {
                    field: "status", title: "状态", align: "center", templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "正常"},
                            "close": {"value": 0, "text": "禁用"}
                        }, 'laytp-table-switch', true);
                    }, width: 80
                }
                , {
                    field: 'sort', title: '排序', width: 100, align: 'center', templet: function (d) {
                        return laytpForm.tableForm.editInput('sort', d, '/admin.single.resource/setSort');
                    }
                }
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {
                    field: "operation",
                    title: "操作",
                    align: "center",
                    fixed: 'right',
                    toolbar: "#default-bar",
                    width: 270
                }
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
                switch (obj.event) {
                    // //自定义按钮点击事件
                    case "evaluate":
                        let options = {};
                        options.title = "评价";
                        options.path = facade.compatibleHtmlPath(window.htmlPrefix) + "evaluate.html?id=" + obj.data.id;
                        facade.popupDiv(options);
                        break;
                    case "showEvaluateList":
                        facade.popupDiv({
                            title: "评价列表"
                            , path: "/admin/single/resource_evaluate/index.html?be_evaluated_id=" + obj.data.id
                        });
                        break;
                }

            }
        });

        //监听表头排序事件
        layui.table.on('sort(laytp-table)', function (obj) {
            layui.table.reload('laytp-table', {
                initSort: obj //记录初始排序，如果不设的话，将无法标记表头的排序状态。
                , where: {
                    "order_param": {
                        "field": obj.field,
                        "type": obj.type
                    }
                }
            });
        });
    };

    funController.tableRender();

    window.funController = funController;
});
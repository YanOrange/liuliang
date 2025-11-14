layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/single/course_evaluate/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.single.course_evaluate/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.single.course_evaluate/index?be_evaluated_id=" + facade.getUrlParam('be_evaluated_id'))
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
                , {field: "course_title", title: "所属课程", align: "center", width: 150}
                , {field: "nickname", title: "昵称", align: "center", width: 150}
                , {field: "phone", title: "手机号", align: "center", width: 120}
                , {field: "content", title: "内容", align: "center", width: 300}
                , {
                    field: "score", title: "评分", align: "center", width: 80, templet: function (d) {
                        return d.score + '颗星';
                    }
                }
                , {
                    field: 'sort', title: '排序', align: 'center', width: 80, templet: function (d) {
                        return laytpForm.tableForm.editInput('sort', d, '/admin.single.course_evaluate/setSort');
                    }
                }
                , {field: "evaluate_time", title: "评论时间", align: "center", width: 160}
                , {
                    field: "is_real", title: "评价类型", align: "center", width: 90, templet: function (d) {
                        return laytpForm.tableForm.switch("is_real", d, {
                            "open": {"value": 2, "text": "虚拟"},
                            "close": {"value": 1, "text": "真实"}
                        }, 'laytp-table-switch', true);
                    }
                }
                , {
                    field: "status", title: "显示状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "显示"},
                            "close": {"value": 0, "text": "隐藏"}
                        });
                    }
                }
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 210}
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
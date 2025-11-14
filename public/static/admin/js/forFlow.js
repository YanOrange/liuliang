layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/for_flow/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.for_flow/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.for_flow/index")
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
                , {field: "for_flow_title", title: "投流名称", align: "center"}
                , {field: "merchant_names", title: "所属商户", align: "center"}
                , {field:'page_type',title:'类型', width: 100,align:'center',templet:function(d){
                        return laytp.tableFormatter.status('page_type',d.page_type, {"value":["1","2","3","4","5","6","7"],"text":["图片类型","文章类型","问答类型","新+v模式","咨询类型","(新)咨询类型","(新含终止)咨询类型"]}, true);
                    }}
                , {field: "apply_nums_count", title: "报名总数", align: "center"}
                , {field: "pv_nums_count", title: "页面浏览量(pv)", align: "center"}
                , {field: "uv_nums_count", title: "页面访客数(uv)", align: "center"}
                , {field: "pv_button_count", title: "按钮点击量(pv)", align: "center"}
                , {field: "uv_button_count", title: "按钮点击客数(uv)", align: "center"}
                , {field: "register_nums_count", title: "注册用户数", align: "center"}
                , {field: "channel_nums", title: "渠道数量", align: "center"}
                , {
                    field: "status", title: "状态", align: "center", templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "启用"},
                            "close": {"value": 0, "text": "禁用"}
                        });
                    }
                }
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 280}
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
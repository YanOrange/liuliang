layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/banner/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.banner/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.banner/index?merchant_id=" + facade.getUrlParam('merchant_id'))
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
                , {field: "title", title: "标题", align: "center"}
                , {
                   field: "image", title: "背景图", align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.image);
                    }
                   }
                , {field: "merchant_names", title: "商户", align: "center"}
                , {field:'is_many_organization',title:'机构版本',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_many_organization',d.is_many_organization,{"value":["1","2","3","4","5","6","7"], "text":["单类目单机构","单类目多机构","多类目多机构","单机构2.0","单机构2.5","逾期版本1","逾期版本2"]}, true);
                    }}
                , {field:'jump_mode',title:'跳转方式',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('jump_mode',d.jump_mode,{"value":["0","1","2"],"text":["不跳转","站内跳转","站外跳转"]}, true);
                    }}
                , {field:'module_name',title:'应用模块',align:'center'}
                , {field:'course_title',title:'文章标题',align:'center'}
                , {
                    field: "status", title: "状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "正常"},
                            "close": {"value": 0, "text": "禁用"}
                        });
                    }
                }
                ,{field:'sort',title:'排序',align:'center', width: 100,templet:function(d){
                        return laytpForm.tableForm.editInput('sort',d,'/admin.banner/setSort');
                    }}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
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
layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/feedback/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.feedback/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.feedback/index")
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
                , {field: "content", title: "反馈内容", align: "center"}
                , {field: "nickname", title: "姓名", align: "center",templet:'<div>{{# if(d.user){ }}{{d.user.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "phone", title: "手机号", align: "center",templet:'<div>{{# if(d.user){ }}{{d.user.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "contact", title: "联系方式", align: "center"}
                , {field: "channel", title: "渠道", align: "center",templet:'<div>{{# if(d.user.channelpro){ }}{{d.user.channelpro.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "app", title: "应用", align: "center",templet:'<div>{{# if(d.user.app){ }}{{d.user.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "class", title: "类目", align: "center",templet:'<div>{{# if(d.user.class){ }}{{d.user.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "customer_name", title: "负责销售", align: "center"}
                , {field: "law_name", title: "负责法务", align: "center"}
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
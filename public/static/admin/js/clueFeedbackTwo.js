layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/clue_feedback_two/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.clue_feedback_two/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.clue_feedback_two/index")
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
            , done: function(res){
                if(res.data[0].feedback_status == 21){
                    $("[data-field='auditor_two_time']").css('display','none');
                    $("[data-field='auditor_two']").css('display','none');
                    $("[lay-event='edit']").css('display','none');
                    $("[lay-event='info']").css('display','block');
                    $("[lay-event='info1']").css('display','none');
                }
                if(res.data[0].feedback_status == 20){
                    $("[lay-event='info']").css('display','none');
                    $("[lay-event='edit']").css('display','block');
                    $("[lay-event='info1']").css('display','none');
                }
                if(res.data[0].feedback_status == 30){
                    $("[lay-event='edit']").css('display','none');
                    $("[lay-event='info']").css('display','none');
                    $("[lay-event='info1']").css('display','block');
                }
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 80}
                , {field: "thread_external_id", title: "微信昵称", align: "center", width: 100, templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.wx_nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "thread_external_id", title: "用户名称", align: "center", width: 100, templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "thread_external_id", title: "手机号", align: "center", width: 100, templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "clue_problem_id", title: "线索问题", align: "center", width: 100, templet:'<div>{{# if(d.problem){ }}{{d.problem.title}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "thread_external_id", title: "所属商户", align: "center", width: 100, templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "thread_external_id", title: "线索价格", align: "center", width: 100, templet:'<div>{{# if(d.threadExternal){ }}{{d.threadExternal.thread_price}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "thread_external_id", title: "线索创建时间", align: "center", width: 100, templet:'<div>{{# if(d.threadExternal){ }}{{d.threadExternal.create_time}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "提交时间", align: "center", width: 100}
                , {field: "submitterr", title: "提交人", align: "center", width: 100, templet:'<div>{{# if(d.subMerchant){ }}{{d.subMerchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "auditor_one_time", title: "一级审核时间", align: "center", width: 100}
                , {field: "auditor_one", title: "一级审核人", align: "center", width: 100, templet:'<div>{{# if(d.auditorOne){ }}{{d.auditorOne.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "auditor_two_time", title: "二级审核时间", align: "center", width: 100}
                , {field: "auditor_two", title: "二级审核人", align: "center", width: 100, templet:'<div>{{# if(d.auditorTwo){ }}{{d.auditorTwo.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 200}
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
                switch(obj.event){
                //自定义按钮点击事件
                    case "info":
                        facade.popupDiv({
                            title: "查看原因"
                            , path: "/admin/clue_feedback_two/info.html?id=" + obj.data.id
                        });
                        break;
                    case "info1":
                        facade.popupDiv({
                            title: "查看"
                            , path: "/admin/clue_feedback_two/info1.html?id=" + obj.data.id
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
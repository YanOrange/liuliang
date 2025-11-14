layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/merchant_recharge_detail/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.merchant_recharge_detail/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.merchant_recharge_detail/index")
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
                , {field: "title", title: "标题", align: "center", width: 120}
                , {field: "recharge_amount", title: "充值金额", align: "center", width: 100}
                , {field: "merchant_id", title: "商户名称", align: "center", width: 120,templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "operate_id", title: "操作人", align: "center",width: 120,templet:'<div>{{# if(d.operator){ }}{{d.operator.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "recharge_type", title: "充值类型", align: "center", width: 120}
                ,{field:'supplement_nums',title:'补量条数',align:'center',width: 90,templet:function(d){
                        return laytpForm.tableForm.editInput('supplement_nums',d,'/admin.merchant_recharge_detail/setSupplementNums');
                    }}
                ,{field:'remark',title:'备注',align:'center',width: 300,templet:function(d){
                        return laytpForm.tableForm.editInput('remark',d,'/admin.merchant_recharge_detail/setRemark');
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
                 switch(obj.event){
                     case "recharge":
                         facade.popupDiv({
                             title: "充值"
                             , path: "/admin/merchant_recharge_detail/recharge.html"
                         });
                         break;
                 }
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
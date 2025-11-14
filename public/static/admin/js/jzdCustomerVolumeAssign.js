layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/jzd_customer_volume_assign");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.jzd_customer_volume_assign/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.jzd_customer_volume_assign/index")
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
                , {field: "customer_id", title: "账号id", align: "center", width: 100}
                , {field: "account_name", title: "登录账号", align: "center", width: 100}
                , {field: "nickname", title: "昵称", align: "center", width: 100}
                , {field: "is_new", title: "入职类型", align: "center", width: 100,templet:function(d){
                        return laytp.tableFormatter.status('is_new',d.is_new,{"value":["0","1"],"text":["5天外","5天内"]}, true);
                    }}
                , {field: "merchant_organization_id", title: "组织架构", align: "center", width: 100,templet:'<div>{{# if(d.organization){ }}{{d.organization.name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "day_night_shift", title: "排班类型", align: "center",templet:function(d){
                        return laytp.tableFormatter.status('day_night_shift',d.customer.day_night_shift,{"value":["0","1","2"],"text":["全天","早班","晚班"]}, true);
                    }}
                , {field: "daily_intake_time_period", title: "上班时间", align: "center", width: 100,templet:'<div>{{# if(d.customer){ }}{{d.customer.daily_intake_time_period}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "app_intake_limit_nums", title: "App报名量", align: "center",minWidth:100,minHeight:100,edit: 'text',style: 'outline: 1px solid #e6e6e6;outline-offset: -8px;padding-left:10px;'}
                , {field: "register_intake_limit_nums", title: "App注册量", align: "center",minWidth:100,minHeight:100,edit: 'text',style: 'outline: 1px solid #e6e6e6;outline-offset: -8px;padding-left:10px;'}
                , {field: "assign_intake_limit_nums", title: "客服量", align: "center",minWidth:100,minHeight:100,edit: 'text',style: 'outline: 1px solid #e6e6e6;outline-offset: -8px;padding-left:10px;'}
                , {field: "old_daily_intake_limit_nums", title: "老量", align: "center",minWidth:100,minHeight:100,edit: 'text',style: 'outline: 1px solid #e6e6e6;outline-offset: -8px;padding-left:10px;'}
                , {field: "thread_status", title: "分量状态", align: "center", templet:function(d){
                        return laytp.tableFormatter.status('thread_status',d.thread_status,{"value":["0","1"],"text":["关闭","开启"]}, true);
                    }}
                , {field: "status", title: "账号状态", align: "center", templet:function(d){
                        return laytp.tableFormatter.status('status',d.status,{"value":["0","1"],"text":["禁用","正常"]}, true);
                    }}
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
                switch(obj.event){
                    case "volume":
                        facade.popupDiv({
                            title: "生成流量"
                            , path: "/admin/jzd_customer_volume_assign/volume.html"
                        });
                        break;
                    case "synchronous":
                        facade.popupDiv({
                            title: "同步到商户"
                            , path: "/admin/jzd_customer_volume_assign/synchronous.html"
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

        layui.table.on('edit(laytp-table)', function (obj) {
            //var data = layui.table.cache['laytp-table'];
            var customerUrl = '/admin.jzd_customer_volume_assign/setAppCustomerNums';
            var customerField = obj.field;
            var fieldValue = 0;
            var id = obj.data.id;
            if(customerField == 'app_intake_limit_nums'){
                fieldValue = obj.data.app_intake_limit_nums;
                facade.ajax({
                    route:customerUrl,
                    data: {id:id,app_intake_limit_nums:fieldValue}
                }).done(function(res){
                    if(res.code === 0){
                        //parent.layui.layer.close(parent.layui.layer.getFrameIndex(window.name));//关闭当前页
                        parent.layui.table.reload("laytp-table");
                    }
                });
            }else if(customerField == 'register_intake_limit_nums'){
                fieldValue = obj.data.register_intake_limit_nums;
                facade.ajax({
                    route:customerUrl,
                    data: {id:id,register_intake_limit_nums:fieldValue}
                }).done(function(res){
                    if(res.code === 0){
                        //parent.layui.layer.close(parent.layui.layer.getFrameIndex(window.name));//关闭当前页
                        parent.layui.table.reload("laytp-table");
                    }
                });
            }else if(customerField == 'assign_intake_limit_nums'){
                fieldValue = obj.data.assign_intake_limit_nums;
                facade.ajax({
                    route:customerUrl,
                    data: {id:id,assign_intake_limit_nums:fieldValue}
                }).done(function(res){
                    if(res.code === 0){
                        //parent.layui.layer.close(parent.layui.layer.getFrameIndex(window.name));//关闭当前页
                        parent.layui.table.reload("laytp-table");
                    }
                });
            }else if(customerField == 'old_daily_intake_limit_nums'){
                fieldValue = obj.data.old_daily_intake_limit_nums;
                facade.ajax({
                    route:customerUrl,
                    data: {id:id,old_daily_intake_limit_nums:fieldValue}
                }).done(function(res){
                    if(res.code === 0){
                        //parent.layui.layer.close(parent.layui.layer.getFrameIndex(window.name));//关闭当前页
                        parent.layui.table.reload("laytp-table");
                    }
                });
            }
            // facade.ajax({
            //     route:'/admin.jzd_customer_volume_assign/setAppCustomerNums',
            //     data: {id:id,customerField:fieldValue}
            // }).done(function(res){
            //     if(res.code === 0){
            //         //parent.layui.layer.close(parent.layui.layer.getFrameIndex(window.name));//关闭当前页
            //         parent.layui.table.reload("laytp-table");
            //     }
            // });
        });
    };

    funController.tableRender();

    window.funController = funController;
});
layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/channel_point_rate/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.channel_point_rate/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.channel_point_rate/index")
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
                , {field: "id", title: "Id", align: "center", width: 80}
                , {field: "point_date", title: "日期", align: "center", width: 100}
                , {field: "app_class_id", title: "应用分类", align: "center", width: 100,templet:'<div>{{# if(d.class){ }}{{d.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "channel_id", title: "渠道名称", align: "center", width: 200, templet: '<div>{{# if(d.channel){ }}{{d.channel.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "point_rate", title: "返点", align: "center"}
                , {field: "cost", title: "填报成本", align: "center"}
                , {field: "point_rate", title: "投放方式", align: "center", templet: '<div>{{# if(d.channel){ }}{{d.channel.channelPromotion.name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "point_rate", title: "投放平台", align: "center", templet: '<div>{{# if(d.channel){ }}{{d.channel.channelPlatform.name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "channelDeliveryMode", title: "投放模式", align: "center", templet: '<div>{{# if(d.channel.channelDeliveryMode){ }}{{d.channel.channelDeliveryMode}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "actual_consumption", title: "实际消耗", align: "center"}
                , {field: "admin_id", title: "提交人", align: "center",width: 100, templet: '<div>{{# if(d.admin){ }}{{d.admin.nickname}}{{# }else{ }}-{{# } }}</div>'}
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
                var arrayId =new Array();
                for (var i = 0; i <= 10; i++) {
                    if(i == 0){
                        var channelPointId = $(".layui-form-checked").parent().parent().parent().children().eq(i+1).children('div').html();
                        if($.isNumeric(channelPointId)){
                            arrayId.push(channelPointId);
                        }
                    }else{
                        var channelPointId = $(".layui-form-checked").parent().parent().parent().children().eq(i*10+1).children('div').html()
                        if($.isNumeric(channelPointId)){
                            arrayId.push(channelPointId);
                        }
                    }
                }
                var idStr = arrayId.join(',');

                // //自定义按钮点击事件
                switch(obj.event){
                    case "edit-all-channel":
                        facade.popupDiv({
                            title: "批量编辑渠道"
                            , path: "/admin/channel_point_rate/edit_all_channel.html?ids="+idStr
                        });
                        break;
                    case "edit-all-point":
                        facade.popupDiv({
                            title: "批量编辑返点"
                            , path: "/admin/channel_point_rate/edit_all_point.html?ids="+idStr
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
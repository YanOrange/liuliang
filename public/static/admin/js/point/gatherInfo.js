layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/point/gather_info/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.point_data/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.point_data/index?page_id=" + facade.getUrlParam('page_id') + "&event_id=" + facade.getUrlParam('event_id'))
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
                , {field: "id", title: "ID", align: "center", width: 100}
                , {field: "channel_id", title: "渠道", align: "center",width: 150,templet:'<div>{{# if(d.channel){ }}{{d.channel.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "app_id", title: "包名", align: "center",width: 150,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "uid", title: "用户ID", align: "center", width: 100}
                , {field: "uid", title: "用户姓名", align: "center",templet:'<div>{{# if(d.user){ }}{{d.user.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "last_page_id", title: "上一个页面", align: "center",templet:'<div>{{# if(d.lastPage){ }}{{d.lastPage.page_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "select_title_desc", title: "选项标题名称", align: "center",width: 150}
                , {field: "select_value_desc", title: "选项标题值", align: "center",width: 150}
                , {field: "select_title_id", title: "选项标题ID", align: "center",width: 150}
                , {field: "select_value_id", title: "选项值ID", align: "center",width: 150}
                , {field: "sigin_button", title: "报名按钮点击次数", align: "center",width:150}
                , {field: "sigin_success", title: "报名成功", align: "center", width: 150, templet: function (d) {
                        return laytp.tableFormatter.status('sigin_success',d.sigin_success,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "sigin_success_time", title: "报名成功时间", align: "center",width:150}
                , {field: "thread_id", title: "报名线索ID", align: "center",width:150}
                , {field: "is_new_user", title: "是否是24小时内该APP注册新用户", align: "center", width: 150, templet: function (d) {
                        return laytp.tableFormatter.status('is_new_user',d.is_new_user,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "sign_duration_days", title: "用户已报名(站外)时长天数", align: "center",width:150}
                , {field: "phone_brand", title: "手机品牌", align: "center", width: 150}
                , {field: "phone_model", title: "手机机型", align: "center", width: 150}
                , {field: "app_version", title: "APP版本", align: "center", width: 150}
                , {field: "browse_duration", title: "浏览时长(s)", align: "center", width: 150}
                , {field:'capital_page_position',title:'留资页前位置',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('capital_page_position',d.capital_page_position, {"value":["0","1","2"], "text":["无","前置","后置"]}, true);
                    }}
                , {field: "create_time", title: "创建时间", align: "center", width: 250}
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
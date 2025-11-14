layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/point/login/");
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
                , {field:'click_phone_textbox',title:'是否点击手机号文本框',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('click_phone_textbox',d.click_phone_textbox,{"value":["0","1"], "text":["否","是"]}, true);
                    }}
                , {field: "click_phone_textbox_count", title: "手机号文本框点击次数", align: "center", width: 150}
                , {field: "click_phone_textbox_time", title: "点击手机号文本框时间", align: "center", width: 150}
                , {field:'input_phone_value',title:'是否输入手机号',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('input_phone_value',d.input_phone_value,{"value":["0","1"], "text":["否","是"]}, true);
                    }}
                , {field:'input_phone_code',title:'是否输入手机验证码',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('input_phone_code',d.input_phone_code,{"value":["0","1"], "text":["否","是"]}, true);
                    }}
                , {field: "click_phone_code_textbox_count", title: "点击验证码文本框次数", align: "center", width: 150}
                , {field: "input_phone_code_time", title: "输入手机验证码时间", align: "center", width: 150}
                , {field:'click_login_button',title:'是否点击登录按钮',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('click_login_button',d.click_login_button,{"value":["0","1"], "text":["否","是"]}, true);
                    }}
                , {field: "click_login_button_count", title: "点击登录按钮次数", align: "center", width: 150}
                , {field: "click_login_button_time", title: "点击登录按钮时间", align: "center", width: 150}
                , {field:'is_login_success',title:'是否登录成功',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_login_success',d.is_login_success,{"value":["0","1"], "text":["否","是"]}, true);
                    }}
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
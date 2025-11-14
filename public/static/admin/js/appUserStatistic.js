layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/app_user_statistic/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.app_user_statistic/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.app_user_statistic/index")
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
                $('*[lay-tips]').on('mouseenter', function(){
                    let content = $(this).attr('lay-tips');
                    this.index = layer.tips('<div style="padding: 10px; font-size: 14px; color: #eee;">'+ content + '</div>', this, {
                        time: -1
                        ,maxWidth: 280
                        ,tips: [3, '#656770']
                    });
                }).on('mouseleave', function(){
                    layer.close(this.index);
                });
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 100}
                , {field: "app_name", title: "应用名称", align: "center",width: 150}
                , {field: "app_register_num", title: "注册总人数", align: "center",width: 150}
                , {field: "app_yesterday_register_num", title: "昨天注册人数", align: "center",width: 150}
                , {field: "app_yesterday_active_num", title: '昨天活跃用户<i class="layui-icon alone-tips" lay-tips="统计昨天此应用下启动过应用的用户（去重），启动过一次的用户即视为活跃用户，包括新用户与老用户"></i>', align: "center",width: 150}
                , {field: "app_yesterday_percapita_start_num", title: '昨天人均启动次数<i class="layui-icon alone-tips" lay-tips="统计昨天此应用人均启动的次数（启动次数相加）÷启动人数"></i>', align: "center",width: 150}
                , {field: "app_yesterday_percapita_use_time", title: '昨天人均使用时长<i class="layui-icon alone-tips" lay-tips="统计昨天此应用平均使用时长（所有人时长相加）÷使用人数"></i>', align: "center",width: 150}
                , {field: "create_time", title: "创建时间", align: "center", width: 250}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 300}
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
                // //自定义按钮点击事件
                    case "burying_page_list":
                        facade.popupDiv({
                            title: "页面列表"
                            , path: "/admin/app_burying_point_page/index.html"
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
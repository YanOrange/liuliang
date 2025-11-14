layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/channel/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.channel/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.channel/index")
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
                , {field: "channel_name", title: "渠道名称", align: "center", width: 150}

                , {field: "channel_app_name", title: "渠道应用名称", align: "center", minWidth:100,minHeight:100,edit: 'text',style: 'outline: 1px solid #e6e6e6;outline-offset: -8px;padding-left:10px;'}
                ,{field:'new_media_auto_msg_channel_name',title:'新媒体自动回复匹配渠道名称',align:'center',width: 200,templet:function(d){
                        return laytpForm.tableForm.editInput('new_media_auto_msg_channel_name',d,'/admin.channel/setNewMediaAutoMsgChannelName');
                    }}
                , {field: "app_id", title: "关联应用", align: "center",width: 150,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "user_material_btn_desc", title: "用户信息按钮文案", align: "center", width: 150}
                , {
                    field: "is_customer_link",
                   /* readonly: true,
                    disabled: true,*/
                    title: "企业微信获客链接", align: "center", width: 180, templet: function (d) {
                        return laytpForm.tableForm.switch("is_customer_link", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {
                    field: "is_suspension_btn",
                   /* readonly: true,
                    disabled: true,*/
                    title: "逾期悬浮按钮", align: "center", width: 180, templet: function (d) {
                        return laytpForm.tableForm.switch("is_suspension_btn", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }        
                , {
                    field: "is_login_show", title: "启动app是否需要登录", align: "center",width: 250, width: 150,templet: function (d) {
                        return laytpForm.tableForm.switch("is_login_show", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {
                    field: "is_wx_auth", title: "是否需要微信授权", align: "center", width: 140, templet: function (d) {
                        return laytpForm.tableForm.switch("is_wx_auth", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "is_landing_page", title: "是否开启留资落地页", align: "center", width: 170,templet: function (d) {
                        return laytpForm.tableForm.switch("is_landing_page", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {
                    field: "is_speed_feed", title: "是否开启个别商户加速进量", align: "center", width: 200,templet: function (d) {
                        return laytpForm.tableForm.switch("is_speed_feed", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {
                    field: "is_speed_feed", title: "是否开启个别商户减速进量", align: "center", width: 200,templet: function (d) {
                        return laytpForm.tableForm.switch("is_slow", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {
                    field: "is_article_show", title: "是否开启显示文章", align: "center", width: 150,templet: function (d) {
                        return laytpForm.tableForm.switch("is_article_show", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                 , {
                    field: "is_delivery", title: "是否交付", align: "center", width: 150,templet: function (d) {
                        return laytpForm.tableForm.switch("is_delivery", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "is_putaway", title: "是否上架", align: "center", width: 200,templet: function (d) {
                        return laytpForm.tableForm.switch("is_putaway", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "is_open", title: "是否开户", align: "center", width: 200,templet: function (d) {
                        return laytpForm.tableForm.switch("is_open", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "is_put", title: "是否投放", align: "center", width: 150,templet: function (d) {
                        return laytpForm.tableForm.switch("is_put", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "is_more_apply", title: "多次报名", align: "center", width: 150,templet: function (d) {
                        return laytpForm.tableForm.switch("is_more_apply", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "is_show_personal_statement", title: "是否显示个人信息声明", align: "center", width: 200,templet: function (d) {
                        return laytpForm.tableForm.switch("is_show_personal_statement", d, {
                            "open": {"value": 1, "text": "显示"},
                            "close": {"value": 0, "text": "隐藏"}
                        });
                    }
                }
                , {
                    field: "is_ldy", title: "首页报名是否跳落地页", align: "center", width: 200,templet: function (d) {
                        return laytpForm.tableForm.switch("is_ldy", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {field: "cost_price", title: "成本价", align: "center"}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: "30%"}
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
                switch(obj.event){
                    case "speed-slow-channel-merchant":
                        facade.popupDiv({
                            title: "商户周期"
                            , path: "/admin/speed_slow_channel_merchant/index.html?channel_id=" + obj.data.id
                        });
                        break;
                    case "overdue-customer":
                        facade.popupDiv({
                            title: "关联销售"
                            , path: "/admin/overdue_app_customer/index.html?channel_id=" + obj.data.id
                        });
                        break;
                    case "setChannelConfig":
                        facade.popupDiv({
                            title: "上包配置"
                             ,width: "1200px"
                            , path: "/admin/channel/setChannelConfig.html?channel_id=" + obj.data.id
                        });
                        break;
                    case "channel-ldy-list":
                        facade.popupDiv({
                            title: "渠道落地页"
                            , path: "/admin/channel_ldy_list/index.html?channel_id=" + obj.data.id
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

        //编辑采购数量
        layui.table.on('edit(laytp-table)', function (obj) {
            //var data = layui.table.cache['laytp-table'];
            var id = obj.data.id;
            var channel_app_name = obj.data.channel_app_name;
            facade.ajax({
                route:'/admin.channel/setChannelAppName',
                data: {id:id,channel_app_name:channel_app_name}
            }).done(function(res){
                if(res.code === 0){
                    //parent.layui.layer.close(parent.layui.layer.getFrameIndex(window.name));//关闭当前页
                    parent.layui.table.reload("laytp-table");
                }
            });
        });

    };

    funController.tableRender();

    window.funController = funController;
});
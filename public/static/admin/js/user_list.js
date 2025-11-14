layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/user_list/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.user_list/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.user_list/index")
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
                , {field: "channel_id", title: "渠道", align: "center",width: 120,templet:'<div>{{# if(d.channelpro){ }}{{d.channelpro.channel_name}}{{# }else{ }}{{d.channel}}{{# } }}</div>'}
                , {field: "app_id", title: "应用", align: "center",width: 120,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "class_id", title: "应用分类", align: "center",width: 120,templet:'<div>{{# if(d.class){ }}{{d.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'apply_nums_count',title:'是否报名',align:'center',width: 100,templet:function(d){
                        if (d.apply_nums) {
                            return '已报名';
                        }
                        return '未报名';
                    }}
                , {field:'apply_nums_count',title:'报名次数',width: 100,align:'center'}
                , {field:'wecom_external_userid',title:'是否加微',align:'center',width: 100,templet:function(d){
                        if (d.wecom_external_userid) {
                            return '已加微';
                        }
                        return '未加微';
                    }}
                , {field:'wx_nickname',title:'微信昵称',align:'center',width: 100}
                , {
                    field: "avatar", title: "头像", align: "center" ,width: 100,templet: function (d) {
                        return laytp.tableFormatter.images(d.avatar);
                    }
                }
                , {field: "nickname", title: "昵称", align: "center",width: 100}
                , {field: "phone", title: "手机号", align: "center",width: 140}
                , {
                    field: "status", title: "账号状态", align: "center",width: 100,templet: function (d) {
                        /*return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });*/
                        if(d.status == 0){
                            return '<span style="color: red">已禁用</span>';
                        }else if(d.status == 2){
                            return '<span style="color: red">已注销</span>';
                        }
                        return '<span style="color: green">正常</span>';
                    }
                }
                , {field:'sex',title:'性别',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('sex',d.sex,{"value":["0","1","2"],"text":["-","男","女"]}, true);
                    }}
                , {field:'age_range',title:'年龄',align:'center'}
                , {field:'identity',title:'身份',align:'center'}
                , {field:'education',title:'学历',align:'center'}
                , {field:'fd_money',title:'房贷月供金额',align:'center'}
                , {field:'fd_overdue',title:'房贷逾期情况',align:'center'}
                , {field:'fd_amount',title:'房贷剩余金额',align:'center'}
                , {field:'jyd_demand',title:'经营贷需求',align:'center'}
                , {field:'jyd_overdue',title:'经营贷逾期情况',align:'center'}
                , {field:'jyd_PayAbility',title:'经营贷是否接受定金',align:'center'}
                , {field:'jyd_amount',title:'经营贷待还本金',align:'center'}
                , {field:'source',title:'用户来源',align:'center',width: 100,templet:function(d) {
                        return laytp.tableFormatter.status('source', d.source, {
                            "value": ["1", "2", "3"],
                            "text": ["app", "h5信息流", "app信息流"]
                        }, true);
                    }}
                , {field:'is_like_games',title:'是否喜欢游戏',align:'center'}
                , {field: "flow_id", title: "投流名称", align: "center",width: 120,templet:'<div>{{# if(d.flow){ }}{{d.flow.for_flow_title}}{{# }else{ }}-{{# } }}</div>'}

                , {field: "login_time", title: "登陆时间", align: "center",width: 160}
                , {field: "login_ip", title: "登陆ip", align: "center",width: 160}
                , {field: "app_start_total", title: "应用启动次数", align: "center",width: 160}
                , {field: "app_use_time", title: "人均使用时长", align: "center",width: 160}
                , {field: "lately_start_app_time", title: "最后活跃时间", align: "center",width: 160}
                , {field: "province", title: "省", align: "center",width: 160}
                , {field: "city", title: "市", align: "center",width: 160}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 180}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 180}
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
                    case "user_detail":
                        facade.popupDiv({
                            title: "用户详情"
                            , path: "/admin/user_list/detail.html?id=" + obj.data.id
                        });
                        break;
                    case "block":
                        facade.popupConfirm({
                            text: "是否确认拉黑该用户？",
                            route: "/admin.user_list/setBlock",
                            data: {id:obj.data.id}
                        }, function(){
                            layui.table.reload("laytp-table");
                            funController.tableRender();//重新渲染菜单
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
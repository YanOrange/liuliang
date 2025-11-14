layui.use(['laytp'], function () {
    const funRecycleController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/user_list/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.user_list/");

    //表格渲染
    funRecycleController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-recycle-table"
            , id: "laytp-recycle-table"
            , url: facade.url("/admin.user_list/recycle")
            , toolbar: "#recycle-default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'recycle-refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print']
            , where: where
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
            , cols: [[ //表头
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 80}
                , {field: "channel_id", title: "渠道", align: "center",width: 120,templet:'<div>{{# if(d.channelpro){ }}{{d.channelpro.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "app_id", title: "应用", align: "center",width: 120,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "class_id", title: "应用分类", align: "center",width: 120,templet:'<div>{{# if(d.class){ }}{{d.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "avatar", title: "头像", align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.avatar);
                    }
                }
                , {field: "nickname", title: "昵称", align: "center"}
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
                , {field:'age_range_id',title:'年龄',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('age_range_id',d.age_range_id,{"value":["0","1","2","3","4","5","6"],"text":["-","18岁以下","18-24岁","25-29岁","30-34岁","35-49岁","50以上"]}, true);
                    }}
                , {field:'identity_id',title:'身份',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('identity_id',d.identity_id,{"value":["0","1","2","3"],"text":["-","学生","职场","脱产"]}, true);
                    }}
                , {field:'education_id',title:'学历',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('education_id',d.education_id,{"value":["0","1","2","3","4"],"text":["-","高中及以下","大专","本科","硕士及以上"]}, true);
                    }}
                , {field: "login_time", title: "登陆时间", align: "center",width: 160}
                , {field: "login_ip", title: "登陆ip", align: "center",width: 160}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {
                    field: 'operation',
                    title: '操作',
                    toolbar: '#recycle-default-bar',
                    fixed: 'right',
                    align: 'center',
                    width: 140
                }
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-recycle-table)", function (obj) {
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                //默认按钮点击事件
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
        layui.table.on('tool(laytp-recycle-table)', function (obj) {
            var defaultTableTool = layui.context.get("defaultTableTool");
            if (defaultTableTool.indexOf(obj.event) !== -1) {
                laytp.tableTool(obj);
            } else {
                // //自定义按钮
                // switch(obj.event){
                // //自定义按钮点击事件
                // case '':
                //
                //     break;
                // }
            }
        });
    };

    funRecycleController.tableRender();

    window.funRecycleController = funRecycleController;
});
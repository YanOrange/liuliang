layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/thread_transformation/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.thread_transformation/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.thread_transformation/index")
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
                if(res.data[0].is_under_eighteen_thread == 1){
                    $("[data-field='operation']").css('display','none');
                }

                // 客户主管
                if(res.data[0].isCustomer){
                    // $("[data-field='merchant_id']").css('display','none');
                    // $("[data-field='channel_id']").css('display','none');
                    // $("[data-field='app_id']").css('display','none');
                    // $("[data-field='course_id']").css('display','none');
                    // $("[data-field='age']").css('display','none');
                    // $("[data-field='identity_id']").css('display','none');
                    // $("[data-field='education_id']").css('display','none');
                    // $("[data-field='is_has_computer']").css('display','none');
                    // $("[data-field='zhaiwu_leixing']").css('display','none');
                    // $("[data-field='zhaiwu_monney']").css('display','none');
                    // $("[data-field='is_discern_qrcode']").css('display','none');
                    // $("[data-field='source']").css('display','none');
                    // $("[data-field='tag_names']").css('display','none');
                    // $("[data-field='flow_id']").css('display','none');
                    // $("[data-field='province']").css('display','none');
                    // $("[data-field='city']").css('display','none');
                    $("[data-field='admin_id']").css('display','none');
                }

                if(res.data[0].isCustomerLeader) {
                    $("[data-field='admin_id']").css('display','block');
                }

                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 60}
                , {field:'is_assign',title:'线索状态',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_assign',d.is_assign,{"value":["0","1","2","3"],"text":["待分配","已被分配","-","已分配"]}, true);
                    }}
                , {field: "class_id", title: "应用分类", align: "center",width: 120,templet:'<div>{{# if(d.class){ }}{{d.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "nickname", title: "用户昵称", align: "center",width: 120,templet:'<div>{{# if(d.user){ }}{{d.user.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "wx_nickname", title: "用户微信昵称", align: "center",width: 120,templet:'<div>{{# if(d.user){ }}{{d.user.wx_nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "avatar", title: "用户头像", align: "center",width: 120,templet:function (d) {
                        return laytp.tableFormatter.images(d.user.avatar);
                    }
                }

                , {field: "phone", title: "用户手机号", align: "center",width: 120,templet:'<div>{{# if(d.user){ }}{{d.user.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'thread_type',title:'产品类型',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('thread_type',d.thread_type,{"value":["0","1","2","3","4"],"text":["-","0元纯表单","1分纯表单","0元加微信","1分加微信"]}, true);
                    }}
                , {field: "customer_id", title: "客服昵称", align: "center",width: 120,templet:'<div>{{# if(d.customer){ }}{{d.customer.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "customer_id", title: "客服微信号", align: "center",width: 120,templet:'<div>{{# if(d.customer){ }}{{d.customer.wechat_number}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "admin_id", title: "分配人", align: "center",width: 120,templet:'<div>{{# if(d.admin){ }}{{d.admin.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
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
                switch(obj.event){
                    case "add-user":
                        facade.popupDiv({
                            title: "添加用户"
                            , path: "/admin/thread_transformation/add_user.html"
                        });
                        break;
                }
            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on("tool(laytp-table)", function (obj) {
            console.log(obj);
            var defaultTableTool = layui.context.get("defaultTableTool");
            if (defaultTableTool.indexOf(obj.event) !== -1) {
                laytp.tableTool(obj);
            } else {
                // //自定义按钮点击事件
                switch(obj.event){
                //自定义按钮点击事件
                    case "assign-info":
                        facade.popupDiv({
                            title: "手动分配"
                            , path: "/admin/thread_transformation/assign.html?id=" + obj.data.id
                        });
                        break;
                    case "assign-one":
                        facade.popupDiv({
                            title: "自动分配（一对一）"
                            , path: "/admin/thread_transformation/assign_one.html?id=" + obj.data.id
                        });
                        break;
                    case "assign-more":
                        facade.popupDiv({
                            title: "自动分配（一对多）"
                            , path: "/admin/thread_transformation/assign_more.html?id=" + obj.data.id
                        });
                        break;
                    case "assign-supplement":
                        facade.popupDiv({
                            title: "客服补量"
                            , path: "/admin/thread_transformation/assign_supplement.html?id=" + obj.data.id
                        });
                        break;
                    case "send-message":
                        if(obj.data.source == 1) {
                            layer.open({
                                type: 1,
                                title: '发送短信----' + obj.data.user.nickname,
                                area: ['500px', '250px'],
                                content: '【旭翱】同学你好，感谢你报名【' + obj.data.course.title + '】课程，我是你的辅导老师，为了更好为你提供服务请前往微信与老师沟通。', //注意，如果str是object，那么需要字符拼接。
                                btn: ['提交', '取消'],
                                yes: function (index, layero) {
                                    //按钮【按钮一】的回调
                                    let data = {'thread_id': obj.data.id};
                                    $.post('/admin.thread_user_merchant/sendMessage', data, function (res) {
                                        if (res.code == 0) {
                                            layer.msg('发送成功');
                                            layer.close(index);
                                        } else {
                                            layer.msg(res.msg);
                                        }
                                    });
                                }
                            });
                        }else{
                            layer.msg('站外暂不支持发短信');
                        }
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
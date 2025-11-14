layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/thread_external/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.thread_external/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.thread_external/index")
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
                , {field: "id", title: "ID", align: "center", width: 60}
                , {field: "merchant_id", title: "商户", align: "center", width: 120,templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "uid", title: "用户昵称", align: "center",width: 120,templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "uid", title: "用户微信昵称", align: "center",width: 120,templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.wx_nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "uid", title: "用户手机号", align: "center",width: 120,templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "source_id", title: "来源", align: "center",width: 120,templet:'<div>{{# if(d.threadSource){ }}{{d.threadSource.title}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "remarks", title: "备注", align: "center",width: 120,templet:'<div>{{# if(d.remarks){ }}{{d.remarks}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "textarea", title: "线索描述", align: "center",width: 120,templet:'<div>{{# if(d.textarea){ }}{{d.textarea}}{{# }else{ }}-{{# } }}</div>'}
                ,{field:'admin_remarks',title:'管理员备注',align:'center',width: 200,templet:function(d){
                    return laytpForm.tableForm.editInput('admin_remarks',d,'/admin.threadExternal/setAdminRemarks');
                }}
                , {field: "uid", title: "阶段", align: "center",width: 120,templet:function(d){
                        if(d.status == 99){
                            return '已放弃';
                        }else{
                            if(d.customer_id){
                                if(d.status == 1){
                                    return '跟进中';
                                }else if(d.status == 2){
                                    return '转化中';
                                }else if(d.status == 3){
                                    return '已成功';
                                }
                            }else{
                                return '待分配';
                            }
                        }
                    }
                }

                // , {field: "age", title: "用户年龄", align: "center",width: 120}
                // , {field:'identity',title:'身份',align:'center',templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.identity}}{{# }else{ }}-{{# } }}</div>'}
                // , {field:'education',title:'学历',align:'center',templet:'<div>{{# if(d.userExternal){ }}{{d.userExternal.education}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "customer_id", title: "客服昵称", align: "center",width: 120,templet:'<div>{{# if(d.customer){ }}{{d.customer.nickname}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "customer_id", title: "客服微信号", align: "center",width: 120,templet:'<div>{{# if(d.customer){ }}{{d.customer.wechat_number}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "province", title: "省份", align: "center", width: 100}
                , {field: "city", title: "市", align: "center", width: 100}
                // , {field: "uid", title: "客户兴趣", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.interest}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "uid", title: "是否到课", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.toclass}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "uid", title: "是否定金", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.deposit}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "uid", title: "是否成交", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.success}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "uid", title: "是否接听电话", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.answer_phone}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "uid", title: "是否回复", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.reply}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "uid", title: "是否删除", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.del}}{{# }else{ }}-{{# } }}</div>'}
                // , {field: "uid", title: "是否加V", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.add_wechat}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 180},
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 100}
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
                    case "exportThread":
                        window.location.href = '/admin.thread_external/exportThread';
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
                //自定义按钮点击事件
                switch(obj.event){
                    //自定义按钮点击事件
                    case "showDetail":
                        facade.popupDiv({
                            title: "线索详情"
                            , path: "/admin/thread_external/thread_detail.html?&id=" + obj.data.id
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
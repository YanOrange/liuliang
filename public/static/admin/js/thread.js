layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/thread/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.thread/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.thread/index")
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

                // 客户主管
                if(res.data[0].isCustomer){
                    // $("[data-field='merchant_id']").css('display','none');
                    $("[data-field='channel_id']").css('display','none');
                    $("[data-field='app_id']").css('display','none');
                    $("[data-field='course_id']").css('display','none');

                    $("[data-field='status']").css('display','none');
                    // $("[data-field='age']").css('display','none');
                    $("[data-field='identity_id']").css('display','none');
                    $("[data-field='education_id']").css('display','none');
                    $("[data-field='is_has_computer_id']").css('display','none');
                    $("[data-field='zw_mold']").css('display','none');
                    $("[data-field='zw_money']").css('display','none');
                    $("[data-field='is_discern_qrcode']").css('display','none');
                    $("[data-field='thread_type']").css('display','none');
                    // $("[data-field='source']").css('display','none');
                    $("[data-field='assign_mode']").css('display','none');
                    $("[data-field='is_assign']").css('display','none');
                    $("[data-field='is_free_try']").css('display','none');
                    $("[data-field='tag_names']").css('display','none');
                    $("[data-field='flow_id']").css('display','none');
                    //$("[data-field='customer_nickname']").css('display','none');
                    $("[data-field='customer_wechat_number']").css('display','none');
                    $("[data-field='province']").css('display','none');
                    $("[data-field='city']").css('display','none');
                    $("[data-field='interest']").css('display','none');
                    $("[data-field='toclass']").css('display','none');
                    $("[data-field='deposit']").css('display','none');
                    $("[data-field='success']").css('display','none');
                    $("[data-field='answer_phone']").css('display','none');
                    $("[data-field='reply']").css('display','none');
                    $("[data-field='del']").css('display','none');
                    $("[data-field='add_wechat']").css('display','none');
                    // $("[data-field='create_time']").css('display','none');
                    $("[data-field='operation']").css('display','none');
                    $("[data-field='is_has_computer']").css('display','none');
                    $("[data-field='zhaiwu_leixing']").css('display','none');
                    $("[data-field='zhaiwu_monney']").css('display','none');
                    $("[data-field='jiankang_wenti']").css('display','none');
                    $("[data-field='need']").css('display','none');
                    $("[data-field='cuishou_zhuangtai']").css('display','none');
                    $("[data-field='tk_touru_monney']").css('display','none');
                    $("[data-field='overdue_time']").css('display','none');
                }else{
                    $("[data-field='zhaiwu_leixing']").css('display','none');
                    $("[data-field='need']").css('display','none');
                    $("[data-field='cuishou_zhuangtai']").css('display','none');
                    $("[data-field='interest']").css('display','none');
                    $("[data-field='toclass']").css('display','none');
                    $("[data-field='deposit']").css('display','none');
                    $("[data-field='success']").css('display','none');
                    $("[data-field='answer_phone']").css('display','none');
                    $("[data-field='reply']").css('display','none');
                    $("[data-field='del']").css('display','none');
                    $("[data-field='add_wechat']").css('display','none');

                }

                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 60}
                , {field: "create_time", title: "创建时间", align: "center", width: 300},
                , {field: "merchant_id", title: "商户", align: "center", width: 120,templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "channel_id", title: "渠道", align: "center",width: 120,templet:'<div>{{# if(d.channelpro){ }}{{d.channelpro.channel_name}}{{# }else{ }}{{d.channel}}{{# } }}</div>'}
                , {field: "app_id", title: "应用", align: "center",width: 120,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "class_id", title: "应用分类", align: "center",width: 120,templet:'<div>{{# if(d.class){ }}{{d.class.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "course_id", title: "课程标题", align: "center",width: 200,templet:'<div>{{# if(d.course){ }}{{d.course.title}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "nickname", title: "用户昵称", align: "center",width: 120,templet:'<div>{{# if(d.user){ }}{{d.user.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "wx_nickname", title: "用户微信昵称", align: "center",width: 120,templet:'<div>{{# if(d.user){ }}{{d.user.wx_nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "wx_number", title: "用户微信号", align: "center",width: 120, templet:'<div>{{# if(d.user){ }}{{d.user.wx_number}}{{# }else{ }}-{{# } }}</div>',minWidth:100,minHeight:100,edit: 'text',style: 'outline: 1px solid #e6e6e6;outline-offset: -8px;padding-left:10px;'}
                , {field: "phone", title: "用户手机号", align: "center",width: 120,templet:'<div>{{# if(d.user){ }}{{d.user.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'is_register',title:'注册产生线索',align:'center',width: 150,templet:function(d){
                        return laytp.tableFormatter.status('is_register',d.is_register, {"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "baidu_status", title: "百度反馈", align: "center", width: 100}
                , {field: "status", title: "阶段", align: "center",width: 120,templet:function(d){
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
                , {field: "age", title: "用户年龄", align: "center",width: 120}
                , {field:'sex',title:'性别',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('sex',d.user.sex, {"value":["0","1","2"],"text":["-","男","女"]}, true);
                    }}
                , {field:'identity_id',title:'身份',align:'center',templet:'<div>{{# if(d.user){ }}{{d.user.identity}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'education_id',title:'学历',align:'center',templet:'<div>{{# if(d.user){ }}{{d.user.education}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'is_has_computer',title:'是否有电脑',align:'center',width: 150,templet:'<div>{{# if(d.user){ }}{{d.user.is_has_computer_id}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'debt_range',title:'债务类型',align:'center',width: 150,templet:'<div>{{# if(d){ }}{{d.debt_range}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'money_range',title:'债务金额',align:'center',width: 150,templet:'<div>{{# if(d){ }}{{d.money_range}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'need',title:'您的需求',align:'center',width: 150,templet:'<div>{{# if(d.user){ }}{{d.user.need_id}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'cuishou_zhuangtai',title:'催收状态',align:'center',width: 150,templet:'<div>{{# if(d.user){ }}{{d.user.cuishou_zhuangtai}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'jiankang_wenti',title:'健康问题',align:'center', width: 150,templet:'<div>{{# if(d.user){ }}{{d.user.jiankang_wenti}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'tk_touru_monney',title:'学习海外短视频预计投入金额',align:'center', width: 150,templet:'<div>{{# if(d.user){ }}{{d.user.tk_touru_monney}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'overdue_time',title:'逾期时长',align:'center', width: 150,templet:'<div>{{# if(d){ }}{{d.overdue_time}}{{# }else{ }}-{{# } }}</div>'}
                , {field:'is_discern_qrcode',title:'是否长按识别二维码',align:'center',width: 200,templet:function(d){
                        if (d.is_discern_qrcode == 0) {
                            return '否';
                        }
                        if (d.is_discern_qrcode == 1) {
                            if (d.is_wecom_qrcode == 1) {
                                return '是[真](获客链接)';
                            }
                            return '是[真]';
                        }
                        if (d.is_discern_qrcode == 2) {
                            return '是[假]';
                        }
                    }}
                , {field:'thread_type',title:'产品类型',align:'center',templet:function(d){
                        return laytp.tableFormatter.status('thread_type',d.thread_type,{"value":["0","1","2","3","4"],"text":["-","0元纯表单","1分纯表单","0元加微信","1分加微信"]}, true);
                    }}
                , {field:'source',title:'线索来源',align:'center',width: 100,templet:function(d){
                        return laytp.tableFormatter.status('source',d.source,{"value":["1","2","3","4"],"text":["app","h5信息流","app信息流","转化追踪"]}, true);

                    }}
                , {field:'assign_mode',title:'分配类型',align:'center',width: 100,templet:function(d){
                        return laytp.tableFormatter.status('assign_mode',d.assign_mode, {"value":["0","1","2","3","4"],"text":["-","手动一对一","自动一对一","自动加V率","自动一对多"]}, true);
                    }}
                , {field:'is_assign',title:'线索途径来源',align:'center',width: 150,templet:function(d){
                        return laytp.tableFormatter.status('is_assign',d.is_assign,{"value":["0","1","2","3"],"text":["用户报名","用户报名","用户报名","分配线索"]}, true);
                    }}
                , {field:'is_free_try',title:'是否免测状态',align:'center',width: 150,templet:function(d){
                        return laytp.tableFormatter.status('is_free_try',d.is_free_try,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "tag_names", title: "线索标签", align: "center",width: 120}
                , {field: "flow_id", title: "投流名称", align: "center",width: 120,templet:'<div>{{# if(d.flow){ }}{{d.flow.for_flow_title}}{{# }else{ }}-{{# } }}</div>'}

                , {field: "customer_nickname", title: "客服昵称", align: "center",width: 120,templet:'<div>{{# if(d.customer){ }}{{d.customer.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "customer_wechat_number", title: "客服微信号", align: "center",width: 120,templet:'<div>{{# if(d.customer){ }}{{d.customer.wechat_number}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "province", title: "省份", align: "center", width: 100}
                , {field: "city", title: "市", align: "center", width: 100}
                , {field: "interest", title: "客户兴趣", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.interest}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "toclass", title: "是否到课", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.toclass}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "deposit", title: "是否定金", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.deposit}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "success", title: "是否成交", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.success}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "answer_phone", title: "是否接听电话", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.answer_phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "reply", title: "是否回复", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.reply}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "del", title: "是否删除", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.del}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "add_wechat", title: "是否加v", align: "center",width: 120,templet:'<div>{{# if(d.follow_action){ }}{{d.follow_action.add_wechat}}{{# }else{ }}-{{# } }}</div>'}
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
                        window.location.href = '/admin.thread/exportThread';
                        break;
                    case "assignThread":
                        facade.popupDiv({
                            title: "商户线索分配"
                            , path: "/admin/thread/assign_thread.html"
                        });
                        break;
                    case "assignThreadId":
                        facade.popupDiv({
                            title: "站内商户线索分配"
                            , path: "/admin/thread/assign_thread_id.html"
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
                //自定义按钮点击事件
                switch(obj.event){
                    //自定义按钮点击事件
                    case "showDetail":
                        facade.popupDiv({
                            title: "线索详情"
                            , path: "/admin/thread/thread_detail.html?&id=" + obj.data.id
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

        //编辑微信号
        layui.table.on('edit(laytp-table)', function (obj) {
            //var data = layui.table.cache['laytp-table'];
            var id = obj.data.id;
            var wx_number = obj.data.wx_number;
            facade.ajax({
                route:'/admin.thread/setWxNumber',
                data: {id:id,wx_number:wx_number}
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
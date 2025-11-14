layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/merchant/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.merchant/");
    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.merchant/index")
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
                if(res.data.length == 0){
                    //无数据返回
                    return;
                }
                //商务干事
                if(res.data[0].admin_role_id == 6){
                    $("[data-field='total_amount']").css('display','none');
                    $("[data-field='consume_thread_nums']").css('display','none');
                    $("[data-field='is_form']").css('display','none');
                    $("[data-field='is_jump_miniprogram']").css('display','none');
                    $("[data-field='status']").css('display','none');
                    $("[data-field='customer_explain_status']").css('display','none');
                    $("[data-field='is_source']").css('display','none');
                    $("[data-field='is_has_computer_rate_limit']").css('display','none');
                    $("[data-field='assign_thread_limit_nums']").css('display','none');
                    $("[data-field='increment_rate']").css('display','none');
                }
                // 客户主管
                if(res.data[0].isCustomer){
                    $("[data-field='account_name']").css('display','none');
                    $("[data-field='landing_page_thread_switch']").css('display','none');
                    $("[data-field='leisure_price']").css('display','none');
                    $("[data-field='peak_price']").css('display','none');
                    $("[data-field='assign_thread_limit_nums']").css('display','none');
                    $("[data-field='is_allow_set_switch']").css('display','none');
                    $("[data-field='app_class_id']").css('display','none');
                    $("[data-field='total_amount']").css('display','none');
                    $("[data-field='residue_amount']").css('display','none');
                    $("[data-field='consume_thread_nums']").css('display','none');
                    $("[data-field='is_form']").css('display','none');
                    $("[data-field='is_jump_miniprogram']").css('display','none');
                    $("[data-field='status']").css('display','none');
                    $("[data-field='is_free_try']").css('display','none');
                    $("[data-field='is_assign']").css('display','none');
                    $("[data-field='customer_explain_status']").css('display','none');
                    $("[data-field='is_source']").css('display','none');
                    $("[data-field='is_has_computer_rate_limit']").css('display','none');
                    $("[data-field='create_time']").css('display','none');
                    $("[data-field='update_time']").css('display','none');
                }
                layui.laytpTable.done();
            }
            , cols: [[
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 80}
                , {field: "account_name", title: "登录账号", align: "center", width: 120}
                , {field: "merchant_name", title: "商户名称", align: "center", width: 120}
                // , {field: "app_class_id", title: "应用分类", align: "center",width: 140,templet:'<div>{{# if(d.appClass){ }}{{d.appClass.app_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "is_switch", title: "进量状态", align: "center", width: 100, templet: function (d) {
                        return laytpForm.tableForm.switch("is_switch", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });

                    }
                }
                , {
                    field: "landing_page_thread_switch", title: "留资页落地页线索开关", align: "center", width: 140, templet: function (d) {
                        return laytpForm.tableForm.switch("landing_page_thread_switch", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                ,{field:'rank',title:'首页排名',align:'center',width: 85,templet:function(d){
                        return laytpForm.tableForm.editInput('rank',d,'/admin.merchant/setRank');
                    }}
                , {field: "residue_amount", title: "账户剩余金额", align: "center", width: 110}    
                , {field: "totay_thread_limit_nums", title: "当天线索数量限制", align: "center", width: 135}
                , {field: "total_thread_num_count", title: "当天已跑总量", align: "center", width: 160}
                , {field: "app_thread_num_count", title: "当天已跑付费流量", align: "center", width: 150}
                , {field: "valid_thread_num_count", title: "当天无效线索补量条数", align: "center", width: 180}
                , {field: "register_thread_num_count", title: "当天已跑注册量", align: "center", width: 150}
                , {field: "customer_assign_thread_num_count", title: "当天客服分配量", align: "center", width: 150}
                , {field: "customer_thread_num_count", title: "当天客服手动补量", align: "center", width: 135}
                , {field: "customer_form_thread_num_count", title: "当天客服纯表单补量", align: "center", width: 150}
                , {field: "nature_thread_num_count", title: "当天自然补量", align: "center", width: 110}
                , {field: "leisure_price", title: "闲时线索单价", align: "center", width: 110}
                , {field: "peak_price", title: "高峰线索单价", align: "center", width: 120}
                , {field: "total_amount", title: "账户总额", align: "center", width: 100}
                , {field:'app_class_id',title:'类目',align:'center',templet:function(d){
                        if (d.appClass) {
                            return d.appClass.app_class_name;
                        }
                        return '-';
                    }}
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
                    field: "is_register",
                   /* readonly: true,
                    disabled: true,*/
                    title: "注册量", align: "center", width: 140, templet: function (d) {
                        return laytpForm.tableForm.switch("is_register", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }    
                , {
                    field: "is_customer",
                   /* readonly: true,
                    disabled: true,*/
                    title: "客服功能", align: "center", width: 140, templet: function (d) {
                        return laytpForm.tableForm.switch("is_customer", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {field: "assign_thread_limit_nums", title: "当天分配线索数量限制", align: "center", width: 140}
                , {field: "increment_rate", title: "自动线索加V率", align: "center", width: 140}
                , {
                    field: "is_allow_set_switch",
                    title: "是否允许设置进量状态", align: "center", width: 100, templet: function (d) {
                        return laytpForm.tableForm.switch("is_allow_set_switch", d, {
                            "open": {"value": 1, "text": "允许"},
                            "close": {"value": 0, "text": "禁用"}
                        });

                    }
                }
                , {field: "consume_thread_nums", title: "已消耗线索数量", align: "center", width: 140}
                , {
                    field: "is_form", title: "是否是纯表单", align: "center", width: 130, templet: function (d) {
                        return laytpForm.tableForm.switch("is_form", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });

                    }
                }
                , {
                    field: "is_jump_miniprogram", title: "是否跳转小程序", align: "center", width: 130, templet: function (d) {
                        return laytpForm.tableForm.switch("is_jump_miniprogram", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });

                    }
                }
                , {
                    field: "status", title: "账号状态", align: "center", width: 100, templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "正常"},
                            "close": {"value": 0, "text": "禁用"}
                        });
                    }
                }
                , {
                    field: "is_free_try", title: "免测状态", align: "center", width: 100, templet: function (d) {
                        return laytpForm.tableForm.switch("is_free_try", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "is_assign", title: "分配状态", align: "center", width: 100, templet: function (d) {
                        return laytpForm.tableForm.switch("is_assign", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
                , {
                    field: "customer_explain_status", title: "客服文案状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("customer_explain_status", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
                , {
                    field: "is_edit_totay_thread_limit_nums", title: "设置每天线索数量限制状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("is_edit_totay_thread_limit_nums", d, {
                            "open": {"value": 1, "text": "正常"},
                            "close": {"value": 0, "text": "禁用"}
                        });
                    }
                }
                , {field:'is_source',title:'商户来源', width: 100,align:'center',templet:function(d){
                        return laytp.tableFormatter.status('is_source',d.is_source,{"value":["1","2"],"text":["站内","站外"]}, true);
                    }}
                , {field: "is_has_computer_rate_limit", title: "有电脑用户线索百分比限制", align: "center", width: 140}

                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: "20%"}
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
                //自定义按钮点击事件
                switch(obj.event){
                    case "customer-list":
                        facade.popupDiv({
                            title: "客服列表"
                            , path: "/admin/customer/index.html?merchant_id=" + obj.data.id
                        });
                        break;
                    case "article-list":
                        facade.popupDiv({
                            title: "推荐阅读列表"
                            , path: "/admin/article/index.html?merchant_id=" + obj.data.id
                        });
                        break;
                    case "banner-list":
                        facade.popupDiv({
                            title: "品宣列表"
                            , path: "/admin/banner/index.html?merchant_id=" + obj.data.id
                        });
                        break;
                    case "setIsSwitchInput":
                        facade.popupDiv({
                            title: "进量设置"
                            , path: "/admin/merchant/setIsSwitchInput.html?id=" + obj.data.id
                        });
                        break;
                    case "setSupplement":
                        facade.popupDiv({
                            title: "补量设置"
                            , path: "/admin/merchant/setSupplement.html?id=" + obj.data.id
                        });
                        break;
                    case "thread-list":
                        facade.popupDiv({
                            title: "线索统计"
                            , path: "/admin/merchant_thread_record/index.html?merchant_id=" + obj.data.id
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
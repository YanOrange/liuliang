layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/ThreadLawyer/");
    //后端接口地址前缀
    window.apiPrefix = facade.compatibleApiRoute("/admin.ThreadLawyer/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.ThreadLawyer/index")
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
            , done: function (res, curr, count) {
                layui.laytpTable.done();
                $(".layui-table-cell").css('overflow', 'visible');
            }
            , cols: [[
                { type: "checkbox" }
                , { field: "id", title: "ID", align: "center", width: 60 }
                , { field: "merchant_id", title: "商户", align: "center", width: 120, templet: '<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>' }
                , {
                    field: "channel", title: "来源", align: "center", width: 120, templet: function (d) {
                        if (d.channel && d.channel_id) {
                            return '内部';
                        } else {
                            if (d.source_id) {
                                return d.threadSource.title;
                            }

                            if (d.channel) {
                                return d.channel;
                            }
                        }
                    }
                }
                , { field: "channel", title: "渠道", align: "center", width: 120, templet: '<div>{{# if(d.channel){ }}{{d.channel}}{{# }else{ }}-{{# } }}</div>' }
                , { field: "userPhone", title: "用户手机号", align: "center", width: 120, templet: '<div>{{# if(d.user){ }}{{d.user.phone}}{{# }else{ }}-{{# } }}</div>' }
                , { field: "thread_user_name", title: "用户昵称", align: "center", width: 120, templet: '<div>{{# if(d.thread_user_name){ }}{{d.thread_user_name}}{{# }else{ }}-{{# } }}</div>' }
                , { field: "consulting_type", title: "咨询类型", align: "center", width: 120, templet: '<div>{{# if(d.gather_user_info){ }}{{d.gather_user_info}}{{# }else{ }}-{{# } }}</div>' }
                , { field: "uid", title: "客服昵称", align: "center", width: 120, templet: '<div>{{# if(d.customer){ }}{{d.customer.nickname}}{{# }else{ }}-{{# } }}</div>' }
                , { field: "userWx_number", title: "客服微信号", align: "center", width: 120, templet: '<div>{{# if(d.customer){ }}{{d.customer.wechat_number}}{{# }else{ }}-{{# } }}</div>' }
                , { field: "province", title: "省份", align: "center", width: 100}
                , { field: "city", title: "市", align: "center", width: 100}
                , {
                    field: "uid", title: "跟进状态", align: "center", width: 120, templet: function (d) {
                        var statusSelect = [
                            {
                                'val': 99,
                                'name': '已放弃'
                            },
                            {
                                'val': 1,
                                'name': '待跟进'
                            },
                            {
                                'val': 2,
                                'name': '跟进中'
                            },
                            {
                                'val': 3,
                                'name': '已完成'
                            }
                        ]
                        var html = '<select class="select-demo-primary"  lay-verify="required" lay-filter="stateSelect">';
                        for (let i = 0; i < statusSelect.length; i++) {
                            if (statusSelect[i].val == d.status) {
                                html += `<option value="${statusSelect[i].val}" selected>${statusSelect[i].name}</option>`
                            } else {
                                html += `<option value="${statusSelect[i].val}">${statusSelect[i].name}</option>`
                            }
                        }
                        html + '</select>'
                        return html
                    }
                }, {
                    field: "uid", title: "跟进情况", align: "center", width: 120, templet: function (d) {
                        var statusSelect = [
                            {
                                'val': 0,
                                'name': '正常'
                            },
                            {
                                'val': 1,
                                'name': '拒绝'
                            },
                            {
                                'val': 2,
                                'name': '无法接通'
                            },
                            {
                                'val': 4,
                                'name': '无意向客户'
                            },
                            {
                                'val': 5,
                                'name': '潜在客户'
                            },
                            {
                                'val': 6,
                                'name': '强意向客户'
                            }
                        ]
                        var html = '<select class="select-demo-primary"  lay-verify="required" lay-filter="stateSelect1">';
                        for (let i = 0; i < statusSelect.length; i++) {
                            if (statusSelect[i].val == d.situation) {
                                html += `<option value="${statusSelect[i].val}" selected>${statusSelect[i].name}</option>`
                            } else {
                                html += `<option value="${statusSelect[i].val}">${statusSelect[i].name}</option>`
                            }
                        }
                        html + '</select>'
                        return html
                    }
                }
                , {
                    field: "situation_describe", title: "跟进情况描述", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.editInput('situation_describe', d, '/admin.ThreadLawyer/setFind');
                    }
                }
                , {
                    field: "signed_amount ", title: "签单金额", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.editInput('signed_amount', d, '/admin.ThreadLawyer/setFind');
                    }
                }
                , {
                    field: "actual_received_amount", title: "实收金额", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.editInput('actual_received_amount', d, '/admin.ThreadLawyer/setFind');
                    }
                }
                , { field: "create_time", title: "创建时间", align: "center", width: 180 },
                , { field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 100 }
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                laytp.tableToolbar(obj);
            } else {
                switch (obj.event) {
                    case "exportThread":
                        window.location.href = '/admin.thread_lawyer/exportThread';
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
                switch (obj.event) {
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
        layui.table.on('sort(laytp-table)', function (obj) {
            layui.table.reload('laytp-table', {
                initSort: obj //记录初始排序，如果不设的话，将无法标记表头的排序状态。
                , where: {
                    "order_param": {
                        "field": obj.field,
                        "type": obj.type
                    }
                }
            });
        });

        // 下拉框事件 跟进状态
        layui.form.on('select(stateSelect)', function (data) {//获取当前行tr对象
            var elem = data.othis.parents('tr');
            //第一列的值是ID，取ID来判断
            var ID = elem.first().find('td').eq(1).text();
            // 获取选中项的值
            var selectedValue = data.value;
            let datas = {
                id: ID,
                status: selectedValue,
                thread_type: '2',
            }
            facade.ajax({
                route: '/admin.ThreadLawyer/setThreadLawyerStatus',
                data: datas,
            }).done(function (res) {
                if (res.code === 0) {
                    location.reload();
                }
            });
        })

        // 下拉框事件 跟进情况
        layui.form.on('select(stateSelect1)', function (data) {//获取当前行tr对象
            var elem = data.othis.parents('tr');
            //第一列的值是ID，取ID来判断
            var ID = elem.first().find('td').eq(1).text();
            // 获取选中项的值
            var selectedValue = data.value;
            let datas = {
                id: ID,
                situation: selectedValue,
                thread_type: '2',
            }
            facade.ajax({
                route: '/admin.ThreadLawyer/setThreadLawyerSituation',
                data: datas,
            }).done(function (res) {
                if (res.code === 0) {
                    location.reload();
                }
            });
        })
    };

    funController.tableRender();

    window.funController = funController;
});
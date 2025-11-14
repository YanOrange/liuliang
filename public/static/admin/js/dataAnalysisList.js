layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/app_for_flow/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.app_for_flow/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.app_for_flow/dataAnalysisList?id=" + facade.getUrlParam('id'))
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
                , {field: "nickname", title: "姓名", align: "center",width:100,templet:'<div>{{# if(d.user){ }}{{d.user.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "phone", title: "手机号", align: "center",width:120,templet:'<div>{{# if(d.user){ }}{{d.user.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "merchant_id", title: "推荐商户", align: "center",width:100,templet:function(d){
                        if (d.merchant) {
                            return d.merchant.merchant_name;
                        }
                        return '-';
                }}
                , {field: "is_discern_qrcode", title: "是否长按", align: "center",width:100,templet:function(d) {
                            return laytp.tableFormatter.status('is_discern_qrcode', d.is_discern_qrcode, {
                                "value": ["0", "1"],
                                "text": [ "否", "是"]
                            }, true);

                    }}
                , {field: "app_id", title: "应用", align: "center",width: 140,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "填写时间", align: "center",width:160}
                , {field:'age_range_id',title:'年龄',align:'center',width:100,templet:function(d){
                        if (d.user) {
                            return laytp.tableFormatter.status('age_range_id',d.user.age_range_id,{"value":["0","1","2","3","4","5","6"],"text":["-","未满18岁","18-24岁","25-29岁","30-34岁","35-49岁","50以上"]}, true);
                        }
                        return '-';
                    }}
                , {field:'identity_id',title:'身份',align:'center',width:100,templet:function(d){
                        if (d.user) {
                            return laytp.tableFormatter.status('identity_id', d.user.identity_id, {
                                "value": ["0", "1", "2", "3", "4", "5"],
                                "text": ["-", "学生", "职场", "自由职业", "全职宝妈", "公职职业编"]
                            }, true);
                        }
                        return '-';
                    }}
                , {field:'education_id',title:'学历',align:'center',width:100,templet:function(d) {
                        if (d.user) {
                            return laytp.tableFormatter.status('education_id', d.user.education_id, {
                                "value": ["0", "1", "2", "3", "4"],
                                "text": ["-", "高中以下", "高中及职高", "大专", "本科及以上"]
                            }, true);
                        }
                        return '-';
                }}
                , {field:'study_goal_id',title:'学习需求',align:'center',width:100,templet:function(d) {
                        if (d.user) {
                            return laytp.tableFormatter.status('study_goal_id', d.user.study_goal_id, {
                                "value": ["0", "1", "2", "3"],
                                "text": ['-', "兼职/副业", "技能提升", '兴趣爱好']
                            }, true);
                        }
                        return '-';
                }}
                , {field:'is_has_computer_id',title:'是否有电脑',width:100,align:'center',templet:function(d) {
                        if (d.user) {
                            return laytp.tableFormatter.status('is_has_computer_id', d.user.is_has_computer_id, {
                                "value": ["1", "2"],
                                "text": ["无", "有"]
                            }, true);
                        }
                        return '-';
                }}
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
                //自定义按钮点击事件
                switch(obj.event){
                    case "data-analysis-list":
                        facade.popupDiv({
                            title: "数据分析"
                            , path: "/admin/app_for_flow/data_analysis_list.html?id=" + obj.data.id
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
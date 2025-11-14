layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/flow_pv_uv/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.flow_pv_uv/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.flow_pv_uv/index?id=" + facade.getUrlParam('id'))
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
                , {field: "id", title: "ID", align: "center"}
                , {field: "nickname", title: "用户昵称", align: "center"}
                , {field: "start_time", title: "进入专题时间", align: "center",width:160}
                , {field: "channel", title: "渠道", align: "center"}
                , {field: "thread_id", title: "是否报名", align: "center",templet:function(d){
                        if (d.thread_id) {
                            return '已报名';
                        }
                        return '未报名';
                    }}
                , {field: "thread_id", title: "是否识别二维码", align: "center",width:140,templet:function(d){
                        if (d.thread) {
                           if (d.thread.is_discern_qrcode) {
                               return '是';
                           }
                           return '否';
                        }
                        return '否';
                    }}
                , {field: "thread_id", title: "商户", align: "center",templet:function(d){
                        if (d.thread) {
                            if (d.thread.merchant) {
                                return d.thread.merchant.merchant_name;
                            }
                            return '-';
                        }
                        return '-';
                    }}
                , {field: "thread_id", title: "报名时间", align: "center",width:160,templet:function(d){
                        if (d.thread) {
                            return d.thread.create_time;
                        }
                        return '-';
                 }}
                , {field:'age_range_id',title:'年龄段',align:'center',templet:function(d){
                        if (d.user) {
                            return laytp.tableFormatter.status('age_range_id',d.user.age_range_id,{"value":["0","1","2","3","4","5","6"],"text":["-","未满18岁","18-24岁","25-29岁","30-34岁","35-49岁","50以上"]}, true);

                        }
                        return '-';
                    }}
                , {field:'identity_id',title:'身份',align:'center',templet:function(d){
                        if (d.user) {
                            return laytp.tableFormatter.status('identity_id',d.user.identity_id,{"value":["0","1","2","3","4","5"],"text":["-","学生","职场","自由职业","全职宝妈","公职职业编"]}, true);

                        }
                        return '-';
                    }}
                , {field:'education_id',title:'学历',align:'center',templet:function(d){
                        if (d.user) {
                            return laytp.tableFormatter.status('education_id',d.user.education_id,{"value":["0","1","2","3","4"],"text":["-","高中以下","高中及职高","大专","本科及以上"]}, true);
                        }
                        return '-';
                    }}
                , {field: "duration", title: "浏览时长(秒)", align: "center"}
                , {field: "create_time", title: "退出时间", align: "center",width:160}
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
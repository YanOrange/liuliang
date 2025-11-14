layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/wx_user_thread_bzhan/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.wx_user_thread_bzhan/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.wx_user_thread_bzhan/index")
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
                , {field: "wx_number", title: "微信账号", align: "center",templet:'<div>{{# if(d.user){ }}{{d.user.wx_number}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "wx_nickname", title: "微信昵称", align: "center", minWidth:100,minHeight:100,edit: 'text',style: 'outline: 1px solid #e6e6e6;outline-offset: -8px;padding-left:10px;'}
                , {field: "phone", title: "手机号", align: "center",templet:'<div>{{# if(d.user){ }}{{d.user.phone}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "channel", title: "渠道", align: "center"}
                , {field: "merchant_id", title: "商户名称", align: "center", templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "customer_nickname", title: "客服昵称", align: "center",templet:'<div>{{# if(d.customer){ }}{{d.customer.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 220}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                laytp.tableToolbar(obj);
            } else {
                switch(obj.event) {
                    case "exportThread":
                        window.location.href = '/admin.wx_user_thread_bzhan/exportThread';
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
                    case "":
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
            var wx_nickname = obj.data.wx_nickname;
            facade.ajax({
                route:'/admin.wx_user_thread_bzhan/setWxNickname',
                data: {id:id,wx_nickname:wx_nickname}
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
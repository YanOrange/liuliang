layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/website/website_leave_message/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.website.website_leave_message/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.website.website_leave_message/index")
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
                , {field: "id", title: "ID", align: "center"}
                , {field: "company_name", title: "企业名称", align: "center"}
                , {field: "real_name", title: "真实姓名", align: "center"}
                , {field: "mobile", title: "手机号", align: "center"}
                , {field: "mailbox", title: "企业邮箱", align: "center"}
                , {
                    field: "status", title: "跟进状态", align: "center",width: 100,templet: function (d) {
                        if(d.status == 1){
                            return '<span style="color: red">已跟进</span>';
                        }
                        return '<span style="color: green">待跟进</span>';
                    }
                }
                , {field: "follow_id", title: "跟进人", align: "center", width: 100,templet:'<div>{{# if(d.followUser){ }}{{d.followUser.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "follow_time", title: "跟进时间", align: "center"}
                , {field: "create_time", title: "创建时间", align: "center"}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar"}
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
                switch(obj.event){
                    case "followUser":
                        facade.popupDiv({
                            title: obj.data.status == 0 ? "跟进" : '查看'
                            ,width: "600px"
                            ,height: "600px"
                            , path: "/admin/website/website_leave_message/followUser.html?&id=" + obj.data.id
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
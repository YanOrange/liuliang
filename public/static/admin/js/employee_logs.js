layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/employee_performance/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.employee_performance/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.employee_performance/logs?info_id=" + facade.getUrlParam('id'))
            , toolbar: "#default-toolbar"
            , where: where
            , autoSort: false
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , loading: false
            , page: {
                curr: page
            },totalRow: true
            , parseData: function (res) { //res 即为原始返回的数据
                return facade.parseTableData(res, true);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                , {field: "id", title: "序号", align: "center", width: 80, totalRowText: '合计扣款金额'}
                , {field: "happen_time", title: "事发时间", align: "center", width: 180}
                , {field: "reason", title: "扣款理由", align: "center", width: 160}
                , {field: "single_money", title: "￥扣款金额", align: "center", width: 160, totalRow: true}
                , {field: "admin_user_id", title: "创建人", align: "center", width: 160}
                , {field: "create_time", title: "创建事发时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 200}
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
                if (obj.event == 'discard') {
                    facade.popupConfirm({text: "真的删除么?", route: window.apiPrefix + "discard", data: {id: obj.data.id}},function(res){
                        if(res.code === 0){
                            obj.del();
                        }
                    });
                }
                if (obj.event == 'rework') {
                    let options = {};
                    options.title = "编 辑";
                    options.path = facade.compatibleHtmlPath(window.htmlPrefix) + "rework.html?id=" + obj.data.id;
                    facade.popupDiv(options);
                }
            }
        });
    };

    funController.tableRender();

    window.funController = funController;
});
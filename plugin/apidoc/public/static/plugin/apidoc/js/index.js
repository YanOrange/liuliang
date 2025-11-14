layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/plugin/apidoc/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleHtmlPath("/plugin/apidoc/index/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/plugin/apidoc/index/index")
            , toolbar: "#default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print', 'exports']
            , where: where
            , method: "GET"
            , cellMinWidth: 120
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
                {type: 'checkbox', fixed: 'left'}
                , {field: 'id', title: 'ID', align: 'center', width: 80, fixed: 'left'}
                , {field: 'title', title: '标题', align: 'center'}
                , {field: 'des', title: '描述', align: 'center'}
                , {field: 'create_time', title: '创建时间', align: 'center'}
                , {field:'operation',title:'操作',align:'center',toolbar:'#default-bar',width:150,fixed:'right'}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                laytp.tableToolbar(obj);
                //其他自定义按钮点击事件
            } else {
                //自定义按钮点击事件
                switch (obj.event) {
                    //生成Api文档
                    case "create":
                        facade.ajax({
                            route: "/plugin/apidoc/index/create",
                            showLoading: true
                        });
                        break;
                    //查看Api文档
                    case "open":
                        window.open(facade.getAdminApiDomain() + "/api.html");
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
                // //自定义按钮点击事件
                // switch(obj.event){
                // //自定义按钮点击事件
                // case "":
                //
                //     break;
                // }
            }
        });
    };

    funController.tableRender();

    window.funController = funController;
});
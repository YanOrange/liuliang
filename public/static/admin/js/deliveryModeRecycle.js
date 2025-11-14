layui.use(['laytp'], function () {
    const funRecycleController = {};
    //静态页面地址前缀
    window.htmlPrefix = "/admin/delivery_mode/";
    //后端接口地址前缀
    window.apiPrefix  = "/admin.delivery_mode/";

    //表格渲染
    funRecycleController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-recycle-table"
            , id: "laytp-recycle-table"
            , url: facade.url("/admin.delivery_mode/recycle")
            , toolbar: "#recycle-default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'recycle-refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print', 'exports']
            , where: where
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
            , cols: [[ //表头
                {type: 'checkbox'}
                , {field: 'id', title: 'ID', align: 'center', width: 60}
                , {field: 'pid', title: '上级ID', align: 'center', width: 80}
                , {field: 'name', title: '名称'}
                , {
                    field: 'operation',
                    title: '操作',
                    toolbar: '#recycle-default-bar',
                    fixed: 'right',
                    align: 'center',
                    width: 140
                }
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on('toolbar(laytp-recycle-table)', function (obj) {
            if (obj.event === "restore") {
                let checkStatus = layui.table.checkStatus(obj.config.id);
                let checkData = checkStatus.data;
                if (checkData.length === 0) {
                    facade.error("请选择数据");
                    return false;
                }
                let key;
                let ids = [];
                for (key in checkData) {
                    ids.push(checkData[key].id);
                }
                facade.ajax({
                    route: window.apiPrefix + "restore",
                    data: {ids: ids.join(",")},
                    showLoading: true
                }).done(function (res) {
                    if (res.code === 0) {
                        parent.funController.tableRender();
                        funRecycleController.tableRender();
                        parent.parent.renderMenu();//重新渲染菜单
                    }
                });
            }else{
                var defaultTableToolbar = layui.context.get("defaultTableToolbar");
                if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                    //默认按钮点击事件
                    laytp.tableToolbar(obj);
                } else {
                    // //自定义按钮点击事件
                    // switch(obj.event){
                    // //自定义按钮点击事件
                    // case '':
                    //
                    //     break;
                    // }
                }
            }
        });

        //监听数据表格[操作列]按钮点击事件
        layui.table.on('tool(laytp-recycle-table)', function (obj) {
            if (obj.event === "restore") {
                facade.ajax({route: window.apiPrefix + "restore", data: {ids: [obj.data.id]}}).done(function (res) {
                    if (res.code === 0) {
                        parent.funController.tableRender();
                        funRecycleController.tableRender();
                        parent.parent.renderMenu();//重新渲染菜单
                    }
                });
            } else {
                var defaultTableTool = layui.context.get("defaultTableTool");
                if (defaultTableTool.indexOf(obj.event) !== -1) {
                    laytp.tableTool(obj);
                } else {
                    // //自定义按钮
                    // switch(obj.event){
                    // //自定义按钮点击事件
                    // case '':
                    //
                    //     break;
                }
            }
        });
    };

    funRecycleController.tableRender();

    window.funRecycleController = funRecycleController;
});
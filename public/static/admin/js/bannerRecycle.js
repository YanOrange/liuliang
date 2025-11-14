layui.use(['laytp'], function () {
    const funRecycleController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/banner/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.banner/");

    //表格渲染
    funRecycleController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-recycle-table"
            , id: "laytp-recycle-table"
            , url: facade.url("/admin.banner/recycle")
            , toolbar: "#recycle-default-toolbar"
            , defaultToolbar: [{
                title: '刷新',
                layEvent: 'recycle-refresh',
                icon: 'layui-icon-refresh',
            }, 'filter', 'print']
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
                {type: "checkbox"}
                , {field: "id", title: "ID", align: "center", width: 80}
                , {field: "title", title: "标题", align: "center"}
                , {
                    field: "image", title: "背景图", align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.image);
                    }
                }
                , {field: "app_names", title: "应用", align: "center", width: 200}
                , {field: "course_id", title: "课程标题", align: "center", width: 200,templet:'<div>{{# if(d.course){ }}{{d.course.title}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "status", title: "状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "正常"},
                            "close": {"value": 0, "text": "禁用"}
                        });
                    }
                }
                ,{field:'sort',title:'排序',align:'center', width: 100,templet:function(d){
                        return laytpForm.tableForm.editInput('sort',d,'/admin.banner/setSort');
                    }}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
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
        layui.table.on("toolbar(laytp-recycle-table)", function (obj) {
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                //默认按钮点击事件
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
        layui.table.on('tool(laytp-recycle-table)', function (obj) {
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
                // }
            }
        });
    };

    funRecycleController.tableRender();

    window.funRecycleController = funRecycleController;
});
layui.use(['laytp'], function () {
    const funRecycleController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/live_course/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.part.live_course/");

    //表格渲染
    funRecycleController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-recycle-table"
            , id: "laytp-recycle-table"
            , url: facade.url("/admin.part.live_course/recycle")
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
                , {field: "title", title: "标题", align: "center", width: 200}
                , {field: "tag_names", title: "标签", align: "center", width: 200}
                , {field: "cate_names", title: "所属分类", align: "center", width: 200}
                , {field: "compensation_desc", title: "价格", align: "center", width: 100}
                ,{field:'virtual_apply_nums',title:'报名人数',align:'center', width: 100,templet:function(d){
                        return laytpForm.tableForm.editInput('virtual_apply_nums',d,'/admin.part.part_course/setVirtualApplyNums');
                    }}
                , {field: "merchant_id", title: "商户", align: "center", width: 120,templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "app_names", title: "应用", align: "center", width: 200}
                ,{field:'sort',title:'排序',align:'center', width: 100,templet:function(d){
                        return laytpForm.tableForm.editInput('sort',d,'/admin.part.part_course/setSort');
                    }}
                , {
                    field: "status", title: "状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "上架"},
                            "close": {"value": 0, "text": "下架"}
                        });
                    }
                }
                , {
                    field: "is_recommend", title: "今日推荐", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("is_recommend", d, {
                            "open": {"value": 1, "text": "是"},
                            "close": {"value": 0, "text": "否"}
                        });
                    }
                }
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
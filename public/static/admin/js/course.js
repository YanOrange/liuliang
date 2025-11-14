layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/course/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.course/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.course/index?course_type=0")
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
                , {field: "title", title: "标题", align: "center", width: 200}
                , {
                    field: "video_cover_image", title: "视频封面图", width: 120, align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.video_cover_image);
                    }
                }
                , {
                    field: "video_url", title: "视频", width: 120, align: "center", templet: function (d) {
                        return laytp.tableFormatter.video(d.video_url);
                    }
                }
                , {
                    field: "video_burning_time", title: "视频时长", width: 120, align: "center"
                }
               // , {field: "app_names", title: "应用", align: "center", width: 200}
                , {field: "merchant_id", title: "商户", align: "center",templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "status", title: "状态", align: "center", width: 120, templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "上架"},
                            "close": {"value": 0, "text": "下架"}
                        });
                    }
                }
                , {field: "entry_fee", title: "报名费", align: "center", width: 200}
                , {field: "btn_desc", title: "按钮", align: "center", width: 200}
                , {field: "confirm_btn_desc", title: "确认报名", align: "center", width: 200}
                ,{field:'sort',title:'排序',align:'center',templet:function(d){
                        return laytpForm.tableForm.editInput('sort',d,'/admin.course/setSort');
                    }}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "update_time", title: "修改时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 140}
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
                // //自定义按钮点击事件
                // switch(obj.event){
                // //自定义按钮点击事件
                // case "":
                //
                //     break;
                // }
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
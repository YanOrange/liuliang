layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/single/video_article/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.single.video_article/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.single.video_article/index")
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
                , {field:'type',title:'类型',align:'center',width: 100,templet:function(d){
                        if (d.type == 1) {
                            return '课程';
                        }
                        return '文章';
                    }}
                , {field: "title", title: "标题", align: "center",width: 120}
                , {
                    field: "video_image", title: "视频封面图", width: 120, align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.video_image);
                    }
                }
                , {field: "app_names", title: "应用", align: "center",width: 120}
                , {field: "class_id", title: "应用类目", align: "center",width: 120,templet:'<div>{{# if(d.class){ }}{{d.class.part_class_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "course_id", title: "绑定课程", align: "center",width: 120,templet:'<div>{{# if(d.course){ }}{{d.course.title}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "sort",title:'排序',width: 100,align:'center'}
                , {field: "play_nums", title: "播放/阅读", align: "center"}
                , {field: "tag_id", title: "标签", align: "center",width: 100,templet:'<div>{{# if(d.tag){ }}{{d.tag.tag_name}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "status", title: "状态", align: "center",width: 100,templet: function (d) {
                        return laytpForm.tableForm.switch("status", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });
                    }
                }
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
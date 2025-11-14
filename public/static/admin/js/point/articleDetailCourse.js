layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/point/article_detail_course/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.point_data/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.point_data/index?page_id=" + facade.getUrlParam('page_id') + "&event_id=" + facade.getUrlParam('event_id'))
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
                , {field: "id", title: "ID", align: "center", width: 100}
                , {field: "channel_id", title: "渠道", align: "center",width: 150,templet:'<div>{{# if(d.channel){ }}{{d.channel.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "app_id", title: "包名", align: "center",width: 150,templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "uid", title: "用户ID", align: "center", width: 100}
                , {field: "uid", title: "用户姓名", align: "center",templet:'<div>{{# if(d.user){ }}{{d.user.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "merchant_id", title: "商户名称", align: "center",width: 150,templet:'<div>{{# if(d.merchant){ }}{{d.merchant.merchant_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "article_id", title: "文章名称", align: "center",width: 150,templet:'<div>{{# if(d.article){ }}{{d.article.title}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "course_id", title: "课程名称", align: "center",width: 150,templet:'<div>{{# if(d.course){ }}{{d.course.title}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "video_play_status", title: "视频播放状态", align: "center", width: 150, templet: function (d) {
                        return laytp.tableFormatter.status('video_play_status',d.video_play_status,{"value":["0","1","2","3"],"text":["未播放","已播放","播放完成"]}, true);
                    }}
                , {field: "video_play_time", title: "视频播放时间", align: "center",width:150}
                , {field: "apply_status", title: "报名状态", align: "center", width: 150, templet: function (d) {
                        return laytp.tableFormatter.status('apply_status',d.apply_status,{"value":["0","1"],"text":["未报名","已报名"]}, true);
                    }}
                , {field: "course_id", title: "按钮文案", align: "center",width: 150,templet:'<div>{{# if(d.course){ }}{{d.course.btn_desc}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 250}
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
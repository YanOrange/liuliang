layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/learn_course_section_joint/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.learn_course_section_joint/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.learn_course_section_joint/index?section_pid=" + facade.getUrlParam('section_pid') + "&course_id=" + facade.getUrlParam('course_id'))
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
                , {field: "section_title", title: "课程章节名称", align: "center", width: 200}
                , {field:'video_type',title:'视频类型', width: 100,align:'center',templet:function(d){
                        return laytp.tableFormatter.status('video_type',d.video_type, {"value":["1","2"],"text":["预习课","公开课"]}, true);
                    }}
                , {field: "course_video_url", title: "视频", align: "center"}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 250}
            ]]
        });

        //监听数据表格顶部左侧按钮点击事件
        layui.table.on("toolbar(laytp-table)", function (obj) {
            //默认按钮点击事件，包括添加按钮和回收站按钮
            var defaultTableToolbar = layui.context.get("defaultTableToolbar");
            if (defaultTableToolbar.indexOf(obj.event) !== -1) {
                if (obj.event == 'add') {
                    facade.popupDiv({
                        title: "添加课程节"
                        , path: "/admin/learn_course_section_joint/add.html?section_pid=" + facade.getUrlParam('section_pid') + "&course_id=" + facade.getUrlParam('course_id')
                    });
                    return false;
                }
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
                // @date 22-10-09 预览
                if (obj.event == 'detail') {
                    var id = obj.data.id;
                    var tmp = window.open('_blank');
                    tmp.location = 'detail.html?id=' + id;
                } else {
                    laytp.tableTool(obj);
                }
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
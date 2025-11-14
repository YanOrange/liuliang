layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/point/h5_flow/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.point_data/h5Index");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.point_data/h5Index?page_id=" + facade.getUrlParam('page_id') + "&event_id=" + facade.getUrlParam('event_id'))
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
                , {field: "h5_uid", title: "用户ID", align: "center"}
                , {field: "channel_id", title: "渠道", align: "center",width: 150,templet:'<div>{{# if(d.channel){ }}{{d.channel.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "page_type", title: "页面类型", align: "center", width: 160, templet: function (d) {
                        return laytp.tableFormatter.status('page_type',d.forFlow.page_type, {"value":["1","2","3","4","5","6","7"],"text":["图片类型","文章类型","问答类型","新+v类型","咨询类型","(新)咨询类型","(新含终止)咨询类型"]}, true);
                    }}
                , {field: "flow_title", title: "投流名称", align: "center",width: 150,templet:'<div>{{# if(d.forFlow){ }}{{d.forFlow.for_flow_title}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "browse_duration", title: "浏览时长", align: "center",width: 150}
                , {field: "is_slide", title: "是否滑动", align: "center", width: 160, templet: function (d) {
                        return laytp.tableFormatter.status('is_slide',d.is_slide,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "is_slide_bottom", title: "是否滑动底部", align: "center", width: 160, templet: function (d) {
                        return laytp.tableFormatter.status('is_slide',d.is_slide,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "slide_nums", title: "滑动次数", align: "center",width: 150}
                , {field: "video_play_status", title: "视频播放状态", align: "center", width: 160, templet: function (d) {
                        return laytp.tableFormatter.status('video_play_status',d.video_play_status,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "is_input_phone", title: "是否输入手机号", align: "center", width: 160, templet: function (d) {
                        return laytp.tableFormatter.status('is_input_phone',d.is_input_phone,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "merchant_id", title: "商户", align: "center", width: 100,templet:'<div>{{# if(d.merchant){ }}{{d.channel.channel_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "is_qr_code", title: "是否加微", align: "center", width: 160, templet: function (d) {
                        return laytp.tableFormatter.status('is_qr_code',d.is_qr_code,{"value":["0","1"],"text":["否","是"]}, true);
                    }}
                , {field: "pop_stay_times", title: "停留弹框时长", align: "center", width: 100}
                , {field: "pop_status", title: "弹框状态", align: "center", width: 160, templet: function (d) {
                        return laytp.tableFormatter.status('pop_status',d.pop_status,{"value":["0","1","2"],"text":["-","提交","关闭"]}, true);
                    }}
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
layui.use(['laytp'], function () {
    const funRecycleController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/landing_page/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.landing_page/");

    //表格渲染
    funRecycleController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-recycle-table"
            , id: "laytp-recycle-table"
            , url: facade.url("/admin.landing_page/recycle")
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
                , {
                    field: "landing_page_type", title: "落地页类型", width: 160, align: "center", templet: function (d) {
                        if (d.landing_page_type === 1) {
                            return '课程落地页';
                        }
                        return '留资页落地页';
                    }
                }
                , {
                    field: "is_pay", title: "是否付费", width: 160, align: "center", templet: function (d) {
                        if (d.is_pay || (d.course && d.course.entry_fee > 0)) {
                            return "<font color='red'><strong>付费</strong></font>";
                        }
                        return "<font color='blue'><strong>免费</strong></font>";
                    }
                }
                , {field: "app_id", title: "关联应用", width: 160, align: "center",templet:'<div>{{# if(d.app){ }}{{d.app.app_name}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "expose_period_num", title: "曝光周期", width: 160, align: "center"}
                , {field: "weight", title: "权重", width: 160, align: "center"}
                , {
                    field: "course_id", title: "商户", width: 160, align: "center", templet: function (d) {
                        if (d.course) {
                            if (d.course.merchant) {
                                return d.course.merchant.merchant_name;
                            }
                        }
                        return '-';
                    }
                }
                , {field: "course_id", title: "课程", align: "center", width: 160,templet:'<div>{{# if(d.course){ }}{{d.course.title}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "video_url", title: "视频", width: 120, align: "center", templet: function (d) {
                        if (d.landing_page_type === 1) {
                            if (d.course) {
                                return laytp.tableFormatter.video(d.course.video_url);
                            }
                            return '-';
                        }
                        return laytp.tableFormatter.video(d.video_url);
                    }
                }
                , {field: "course_id", title: "视频价格", align: "center", width: 160,templet:'<div>{{# if(d.course){ }}{{d.course.entry_fee}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "landing_image", title: "落地页图片", width: 100, align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.landing_image);
                    }
                }
                , {
                    field: "lamp_back_image", title: "跑马灯背景图", width: 100, align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.lamp_back_image);
                    }
                }
                , {
                    field: "end_image", title: "落地页尾图", width: 100, align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.end_image);
                    }
                }
                , {
                    field: "desc_image", title: "描述图片", width: 100, align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.desc_image);
                    }
                }
                , {field: "lamp_font_color", title: "跑马灯字体颜色", align: "center", width: 100}
                , {
                    field: "is_lamp", title: "是否显示跑马灯", align: "center", width: 100, templet: function (d) {
                        return laytpForm.tableForm.switch("is_lamp", d, {
                            "open": {"value": 1, "text": "开启"},
                            "close": {"value": 0, "text": "关闭"}
                        });

                    }
                }
                , {field: "expose_num", title: "曝光次数", width: 120, align: "center"}
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
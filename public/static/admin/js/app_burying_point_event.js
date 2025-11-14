layui.use(["laytp"], function () {
    const funController = {};
    //静态页面地址前缀
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/app_burying_point_event/");
    //后端接口地址前缀
    window.apiPrefix  = facade.compatibleApiRoute("/admin.app_burying_point_event/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.app_burying_point_event/index?page_id=" + facade.getUrlParam('page_id'))
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
                , {field: "event_name", title: "事件名称", align: "center", width: 160}
                , {field: "event_id", title: "事件ID", align: "center", width: 160}
                , {field: "page_id", title: "页面名称", align: "center", width: 160,templet:'<div>{{# if(d.page){ }}{{d.page.page_name}}{{# }else{ }}-{{# } }}</div>'}
                , {
                    field: "event_image", title: "事件截图", align: "center", templet: function (d) {
                        return laytp.tableFormatter.images(d.event_image);
                    }
                }
                , {field: "event_pv", title: "浏览量(PV)", align: "center", width: 160}
                , {field: "event_uv", title: "访客数(UV)", align: "center", width: 160}
                , {field: "admin_id", title: "创建人", align: "center", width: 160,templet:'<div>{{# if(d.admUser){ }}{{d.admUser.nickname}}{{# }else{ }}-{{# } }}</div>'}
                , {field: "create_time", title: "创建时间", align: "center", width: 160}
                , {field: "operation", title: "操作", align: "center", fixed: 'right', toolbar: "#default-bar", width: 300}
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
            console.log(obj.event);
            var defaultTableTool = layui.context.get("defaultTableTool");
            if (defaultTableTool.indexOf(obj.event) !== -1) {
                laytp.tableTool(obj);
            } else {
                // //自定义按钮点击事件
                switch(obj.event){
                    //自定义按钮点击事件
                    case "burying_point_data":
                        if(obj.data.event_id == 'event_user_protocol_privacy')
                        {
                            facade.popupDiv({
                                title: "用户协议"
                                , path: "/admin/point/user_agreement/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_login')
                        {
                            facade.popupDiv({
                                title: "登录页面"
                                , path: "/admin/point/login/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_show')
                        {
                            facade.popupDiv({
                                title: "首页"
                                , path: "/admin/point/show/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_show_theme')
                        {
                            facade.popupDiv({
                                title: "首页卡片"
                                , path: "/admin/point/show/theme.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_show_banner')
                        {
                            facade.popupDiv({
                                title: "轮播广告"
                                , path: "/admin/point/show_banner/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_show_case_article_click')
                        {
                            facade.popupDiv({
                                title: "首页案例文章点击"
                                , path: "/admin/point/show_article/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_show_main_button')
                        {
                            facade.popupDiv({
                                title: "首页主按钮"
                                , path: "/admin/point/show_course_btn/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_landing_page' || obj.data.event_id == 'event_small_lessons' || obj.data.event_id == 'event_big_lessons' )
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_landing_page') title = '落地页面'
                            if(obj.data.event_id == 'event_small_lessons') title = '小课详情'
                            if(obj.data.event_id == 'event_big_lessons') title = '大课落地页'
                            facade.popupDiv({
                                title: "落地页面"
                                , path: "/admin/point/landing/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_gather_info_after')
                        {
                            facade.popupDiv({
                                title: "留资后置弹框"
                                , path: "/admin/point/gather_info_btn/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_gather_info_after_select')
                        {
                            facade.popupDiv({
                                title: "留资后置弹框选项"
                                , path: "/admin/point/gather_info/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }

                        if(obj.data.event_id == 'event_landing_btn')
                        {
                            facade.popupDiv({
                                title: "报名按钮"
                                , path: "/admin/point/landing_btn/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_landing_confirm_btn' || obj.data.event_id == 'event_apply_success')
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_landing_confirm_btn') title = '确认按钮'
                            if(obj.data.event_id == 'event_apply_success') title = '报名成功'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/landing_confirm_btn/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_show_many_organization')
                        {
                            facade.popupDiv({
                                title: "多机构"
                                , path: "/admin/point/show_many_organization/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }

                        if(obj.data.event_id == 'event_show_course_btn')
                        {
                            facade.popupDiv({
                                title: "好课推荐按钮"
                                , path: "/admin/point/show_course_btn/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }

                        if(obj.data.event_id == 'event_article_detail')
                        {
                            facade.popupDiv({
                                title: "文章详情页"
                                , path: "/admin/point/article_detail/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_article_detail_course')
                        {
                            facade.popupDiv({
                                title: "文章详情课程"
                                , path: "/admin/point/article_detail_course/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_course_detail')
                        {
                            facade.popupDiv({
                                title: "好课详情页"
                                , path: "/admin/point/course_detail/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_course_detail_btn')
                        {
                            facade.popupDiv({
                                title: "课程详情页报名按钮"
                                , path: "/admin/point/course_detail_confirm_btn/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_course_detail_confirm_btn')
                        {
                            facade.popupDiv({
                                title: "课程详情页确认报名按钮"
                                , path: "/admin/point/course_detail_confirm_btn/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'evevnt_my_course')
                        {
                            facade.popupDiv({
                                title: "我的课程"
                                , path: "/admin/point/my_course/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_my_course_wx' || obj.data.event_id == 'event_add_wx')
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_my_course_wx') title = '我的课程添加微信'
                            if(obj.data.event_id == 'event_add_wx') title = '添加微信'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/my_course_wx/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_feedback_btn')
                        {
                            facade.popupDiv({
                                title: "管理咨询提交"
                                , path: "/admin/point/feedback/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_my' || obj.data.event_id == 'event_my_edit' || obj.data.event_id == 'event_my_course' || obj.data.event_id == 'event_my_feedback' || obj.data.event_id == 'event_my_setting' || obj.data.event_id == 'event_feddback' || obj.data.event_id == 'event_setting' || obj.data.event_id == 'event_setting_logout' || obj.data.event_id == 'event_setting_exit' || obj.data.event_id == 'event_seek_advice' || obj.data.event_id == 'event_main_service')
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_my') title = '我的页面'
                            if(obj.data.event_id == 'event_my_edit') title = '我的页面编辑'
                            if(obj.data.event_id == 'event_my_course') title = '我的课程'
                            if(obj.data.event_id == 'event_my_feedback') title = '咨询管理'
                            if(obj.data.event_id == 'event_my_setting') title = '设置'
                            if(obj.data.event_id == 'event_feddback') title = '咨询管理'
                            if(obj.data.event_id == 'event_setting') title = '设置页面'
                            if(obj.data.event_id == 'event_setting_logout') title = '注销'
                            if(obj.data.event_id == 'event_setting_exit') title = '退出登录'
                            if(obj.data.event_id == 'event_seek_advice') title = '我的服务'
                            if(obj.data.event_id == 'event_main_service') title = '更多咨询'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/my_page/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_show_advertising' || obj.data.event_id == 'event_show_advertising_close' || obj.data.event_id == 'event_show_advertising_click')
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_show_advertising') title = '弹窗广告访问'
                            if(obj.data.event_id == 'event_show_advertising_close') title = '弹窗广告关闭'
                            if(obj.data.event_id == 'event_show_advertising_click') title = '弹窗广告点击'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/show_advertising/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_mch5' || obj.data.event_id == 'event_mch5_btn' || obj.data.event_id == 'event_mch5_close')
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_mch5') title = '莓茶h5页面'
                            if(obj.data.event_id == 'event_mch5_btn') title = '莓茶h5页面添加微信'
                            if(obj.data.event_id == 'event_mch5_close') title = '莓茶h5页面关闭'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/mch5/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_bind_phone_btn' || obj.data.event_id == 'event_bind_phone')
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_bind_phone_btn') title = '绑定手机号按钮'
                            if(obj.data.event_id == 'event_bind_phone') title = '绑定手机号'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/bind_phone/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'event_wx_mini_program' || obj.data.event_id == 'event_wx_mini_program_discern_qrcode')
                        {
                            var title = ''
                            if(obj.data.event_id == 'event_wx_mini_program') title = '微信小程序页面'
                            if(obj.data.event_id == 'event_wx_mini_program_discern_qrcode') title = '微信小程序长按识别二维码'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/wx_mini_program/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        if(obj.data.event_id == 'h5_event_index' || obj.data.event_id == 'h5_event_submit_btn' || obj.data.event_id == 'h5_event_pop_page' || obj.data.event_id == 'h5_event_pop_page_btn')
                        {
                            var title = ''
                            if(obj.data.event_id == 'h5_event_index') title = 'h5首页'
                            if(obj.data.event_id == 'h5_event_submit_btn') title = 'h5页面提交按钮'
                            if(obj.data.event_id == 'h5_event_pop_page') title = '弹框页面'
                            if(obj.data.event_id == 'h5_event_pop_page_btn') title = '弹框按钮'
                            facade.popupDiv({
                                title: title
                                , path: "/admin/point/h5_flow/index.html?page_id=" + obj.data.page_id + "&event_id=" + obj.data.id
                            });
                        }
                        break;
                    }
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
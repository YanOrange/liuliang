layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/customer_distribute/");
    window.apiPrefix  = facade.compatibleApiRoute("/admin.customer_distribute/");

    funController.tableRender = function (where) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.customer_distribute/classify")
            , where: where
            , autoSort: false
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , loading: false
            , parseData: function (res) { //res 即为原始返回的数据
                return facade.parseTableData(res, false);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                {field: "app_class_name", title: "类目", align: "center", width: 160}
                , {field: "total", title: "客服待分配量", align: "center", width: 160, edit: "text"}
                ,{field:'kfp_nums',title:'可分配数量',align:'center',width: 160,templet:function(d){
                        return laytpForm.tableForm.editInput('kfp_nums',d,'/admin.customer_distribute/setKfpNums');
                    }}
                ,{field:'bhf_nums',title:'不回复数量',align:'center',width: 160,templet:function(d){
                        return laytpForm.tableForm.editInput('bhf_nums',d,'/admin.customer_distribute/setBhfNums');
                    }}
                ,{field:'invalid_nums',title:'无效数量',align:'center',width: 160,templet:function(d){
                        return laytpForm.tableForm.editInput('invalid_nums',d,'/admin.customer_distribute/setInvalidNums');
                    }}
                ,{field:'del_nums',title:'删除数量',align:'center',width: 160,templet:function(d){
                        return laytpForm.tableForm.editInput('del_nums',d,'/admin.customer_distribute/setDelNums');
                    }}
                ,{field:'nonage_nums',title:'未成年数量',align:'center',width: 160,templet:function(d){
                        return laytpForm.tableForm.editInput('nonage_nums',d,'/admin.customer_distribute/setNonageNums');
                    }}
                , {field: "admin_user_username", title: "修改人", align: "center", width: "25%"}
                , {field: "update_time", title: "修改时间", align: "center", width: "25%"}
            ]]
        });

        // 监听编辑事件
        layui.table.on("edit(laytp-table)", function (obj) {
            $.post('/admin.customer_distribute/setSetting', obj.data, function (res) {
                layui.layer.msg(res.msg);
            })
        });

        layui.table.render({
            elem: "#laytp-scale"
            , id: "laytp-scale"
            , url: facade.url("/admin.customer_distribute/scale")
            , where: where
            , autoSort: false
            , method: "GET"
            , cellMinWidth: 80
            , skin: 'line'
            , loading: false
            , parseData: function (res) { //res 即为原始返回的数据
                return facade.parseTableData(res, false);
            }
            , done: function(){
                layui.laytpTable.done();
            }
            , cols: [[
                {field: "level", title: "等级", align: "center", width: "25%"}
                , {field: "val", title: "预设分配比例（%）", align: "center", width: "25%", edit: "text"}
                , {field: "admin_user_username", title: "修改人", align: "center", width: "25%"}
                , {field: "update_time", title: "修改时间", align: "center", width: "25%"}
            ]]
        });

        // 监听编辑事件
        layui.table.on("edit(laytp-scale)", function (obj) {
            $.post('/admin.customer_distribute/setScale', obj.data, function (res) {
                layui.layer.msg(res.msg);
            })
        });
    };

    funController.tableRender();

    window.funController = funController;
});
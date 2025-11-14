layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/customer_distribute/");
    window.apiPrefix  = facade.compatibleApiRoute("/admin.customer_distribute/");
    funController.tableRender = function (where) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.customer_distribute/distribute")
            , where: where
            , autoSort: false
            , method: "GET"
            , totalRow: true
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
                {field: "merchant_name", title: "商户", align: "center", width: 100}
                , {field: "app_class_name", title: "类目", align: "center", width: 100}
                , {field: "reg_days", title: "注册天数", align: "center", width: 100}
                //, {field: "class_type", title: "客户分类", align: "center", width: 100}
                , {field: "feedback_days", title: "分配质量差连续反馈天数", align: "center", width: 160}
                , {field: "level", title: "质量控制等级", align: "center", width: 160}
                , {field: "needs", title: "需求量", align: "center", width: 160}
                , {field: "pre_scale", title: "预设分配比例%", align: "center", width: 160}
                , {field: "compute_needs", title: "需求量计算值", align: "center", width: 160}
                , {field: "compute_scale", title: "计算分配比例%", align: "center", width: 160}
                , {field: "distribute_count", title: "可分配条数", align: "center", width: 160}
            ]]
        });
    };

    funController.tableRender();

    window.funController = funController;
});
layui.use(["laytp"], function () {
    const funController = {};
    window.htmlPrefix = facade.compatibleHtmlPath("/admin/employee_performance/");
    //后端接口地址前缀
    window.apiPrefix = facade.compatibleApiRoute("/admin.employee_performance/");

    //表格渲染
    funController.tableRender = function (where, page) {
        layui.table.render({
            elem: "#laytp-table"
            , id: "laytp-table"
            , url: facade.url("/admin.employee_performance/index")
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
            },totalRow: true
            , parseData: function (res) { //res 即为原始返回的数据
                return facade.parseTableData(res, true);
            }
            , done: function (res) {
                merge(res)
                layui.laytpTable.done();
            }
            , cols: [[
                , {field: "dept_id", title: "部门", align: "left", width: 100, totalRowText: '扣款总额'}
                , {field: "employee_id", title: "被考核人", align: "left", width: 100}
                , {field: "start_time", title: "考核事发时间", align: "center", width: 180}
                , {field: "content", title: "考核内容", align: "center", width: 160}
                , {field: "description", title: "考核说明", align: "center", width: 160}
                , {field: "money", title: "封顶金额", align: "center", width: 160}
                , {field: "single_money", title: "单次扣款金额", align: "center", width: 160}
                , {field: "count", title: "已扣款次数", align: "center", width: 160}
                , {field: "total", title: "已扣款金额", align: "center", width: 160, totalRow: true}
                , {field: "check_id", title: "考核人", align: "center", width: 160}
                , {field: "admin_user_id", title: "创建人", align: "center", width: 160}
                , {field: "create_time", title: "创建事发时间", align: "center", width: 160}
                , {
                    field: "operation",
                    title: "操作",
                    align: "center",
                    fixed: 'right',
                    toolbar: "#default-bar",
                    width: 200
                }
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
                if (obj.event == 'raise') {
                    let options = {};
                    options.title = "记 录";
                    options.path = facade.compatibleHtmlPath(window.htmlPrefix) + "raise.html?id=" + obj.data.iid;
                    facade.popupDiv(options);
                }
                if (obj.event == 'logs') {
                    let options = {};
                    options.title = "记录日志";
                    options.path = facade.compatibleHtmlPath(window.htmlPrefix) + "logs.html?id=" + obj.data.iid;
                    facade.popupDiv(options);
                }
            }
        });
    };

    funController.tableRender();

    window.funController = funController;
});

function merge(res) {
    var data = res.data;
    var mergeIndex = 0;//定位需要添加合并属性的行数
    var mark = 1; //这里涉及到简单的运算，mark是计算每次需要合并的格子数
    var columsName = ['department_name', 'appraisee_name', 'assess_date'];//需要合并的列名称
    var columsIndex = [0, 1, 2];//需要合并的列索引值

    for (var k = 0; k < columsName.length; k++) { //这里循环所有要合并的列
        var trArr = $(".layui-table-body>.layui-table").find("tr");//所有行
        for (var i = 1; i < res.data.length; i++) { //这里循环表格当前的数据
            var tdCurArr = trArr.eq(i).find("td").eq(columsIndex[k]);//获取当前行的当前列
            var tdPreArr = trArr.eq(mergeIndex).find("td").eq(columsIndex[k]);//获取相同列的第一列

            if (data[i]['performance_id'] == data[i - 1]['performance_id'] && data[i][columsName[k]] === data[i - 1][columsName[k]]) { //后一行的值与前一行的值做比较，相同就需要合并
                mark += 1;
                tdPreArr.each(function () {//相同列的第一列增加rowspan属性
                    $(this).attr("rowspan", mark);
                });
                tdCurArr.each(function () {//当前行隐藏
                    $(this).css("display", "none");
                });
            } else {
                mergeIndex = i;
                mark = 1;//一旦前后两行的值不一样了，那么需要合并的格子数mark就需要重新计算
            }
        }
        mergeIndex = 0;
        mark = 1;
    }
}
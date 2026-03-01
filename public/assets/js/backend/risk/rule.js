define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            var table = $("#table");
            table.bootstrapTable({
                url: 'risk/rule/index',
                pk: 'id',
                sortName: 'weigh',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'name', title: '规则名称', operate: 'LIKE'},
                        {field: 'type', title: '规则类型', searchList: {"device":"设备检测","behavior":"行为检测","ip":"IP检测","frequency":"频率检测"}},
                        {field: 'score', title: '风险分数'},
                        {field: 'enabled', title: '状态', searchList: {"0":"禁用","1":"启用"}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: '权重'},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

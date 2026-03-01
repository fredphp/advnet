define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            var table = $("#table");
            table.bootstrapTable({
                url: 'risk/userrisk/index',
                pk: 'id',
                sortName: 'risk_score',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'username', title: '用户名'},
                        {field: 'nickname', title: '昵称'},
                        {field: 'risk_score', title: '风险分数', sortable: true},
                        {field: 'risk_level', title: '风险等级', searchList: {"low":"低","medium":"中","high":"高","critical":"严重"}},
                        {field: 'status', title: '状态', searchList: {"normal":"正常","frozen":"冻结","banned":"封禁"}},
                        {field: 'updatetime', title: '更新时间', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

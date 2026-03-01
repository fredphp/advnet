define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            var table = $("#table");
            table.bootstrapTable({
                url: 'invite/relation/index',
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'inviter_name', title: '邀请人用户名'},
                        {field: 'inviter_nickname', title: '邀请人昵称'},
                        {field: 'invitee_name', title: '被邀请人用户名'},
                        {field: 'invitee_nickname', title: '被邀请人昵称'},
                        {field: 'level', title: '邀请等级', searchList: {"1":"一级","2":"二级","3":"三级"}},
                        {field: 'createtime', title: '邀请时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
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

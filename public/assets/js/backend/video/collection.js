define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            var table = $("#table");
            table.bootstrapTable({
                url: 'video/collection/index',
                pk: 'id',
                sortName: 'weigh',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'title', title: '合集标题', operate: 'LIKE'},
                        {field: 'cover', title: '封面', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'video_count', title: '视频数量'},
                        {field: 'status', title: '状态', searchList: {"0":"禁用","1":"启用"}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: '权重', sortable: true},
                        {field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
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

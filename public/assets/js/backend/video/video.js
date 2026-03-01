define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            var table = $("#table");
            table.bootstrapTable({
                url: 'video/video/index',
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'title', title: '视频标题', operate: 'LIKE'},
                        {field: 'user_id', title: '发布者ID'},
                        {field: 'duration', title: '时长(秒)'},
                        {field: 'view_count', title: '播放量'},
                        {field: 'reward_coin', title: '奖励金币'},
                        {field: 'status', title: '状态', searchList: {"0":"待审核","1":"已发布","2":"已下架","3":"已封禁","4":"草稿"}},
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

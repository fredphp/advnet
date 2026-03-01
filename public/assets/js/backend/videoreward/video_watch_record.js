define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            var table = $("#table");
            table.bootstrapTable({
                url: 'videoreward/video_watch_record/index',
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'video_id', title: '视频ID'},
                        {field: 'watch_duration', title: '观看时长(秒)'},
                        {field: 'watch_progress', title: '观看进度(%)'},
                        {field: 'is_completed', title: '是否完成', searchList: {"0":"否","1":"是"}},
                        {field: 'reward_status', title: '奖励状态'},
                        {field: 'reward_coin', title: '奖励金币'},
                        {field: 'createtime', title: '观看时间', formatter: Table.api.formatter.datetime},
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

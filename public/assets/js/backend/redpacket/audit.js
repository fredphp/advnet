define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/audit/index',
                    detail_url: 'redpacket/audit/detail',
                    del_url: 'redpacket/audit/del',
                    multi_url: 'redpacket/audit/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'user.username', title: '用户名', operate: 'LIKE'},
                        {field: 'user.nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'task_id', title: '任务ID'},
                        {field: 'task.name', title: '任务名称', operate: 'LIKE'},
                        {field: 'duration', title: '完成时长(秒)', operate: 'BETWEEN'},
                        {field: 'progress', title: '进度(%)', operate: 'BETWEEN'},
                        {field: 'status', title: '状态', searchList: {
                            "0": "已领取待完成",
                            "1": "已完成待审核",
                            "2": "审核通过待发放",
                            "3": "已发放",
                            "4": "审核拒绝",
                            "5": "已过期",
                            "6": "已取消"
                        }, formatter: Table.api.formatter.status},
                        {field: 'audit_status', title: '审核状态', searchList: {
                            "0": "待审核",
                            "1": "通过",
                            "2": "拒绝"
                        }, formatter: Table.api.formatter.status},
                        {field: 'reward_coin', title: '奖励金币'},
                        {field: 'createtime', title: '参与时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'end_time', title: '完成时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
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

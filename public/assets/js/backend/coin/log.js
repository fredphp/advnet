define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            var table = $("#table");
            table.bootstrapTable({
                url: 'coin/log/index',
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'username', title: '用户名'},
                        {field: 'type', title: '流水类型', searchList: {"video_reward":"视频奖励","task_reward":"任务奖励","withdraw":"提现","invite_reward":"邀请奖励","red_packet":"红包","commission":"分佣","recharge":"充值","admin_add":"后台增加","admin_deduct":"后台扣除"}},
                        {field: 'amount', title: '金币数量'},
                        {field: 'balance_after', title: '变动后余额'},
                        {field: 'title', title: '标题'},
                        {field: 'createtime', title: '时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            Table.api.bindevent(table);
        },
        statistics: function () {
            Controller.api.loadStatistics();
            $('#start_date, #end_date').on('change', function () {
                Controller.api.loadStatistics();
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'coin/log/statistics',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-income').text(data.total_stats.total_income || 0);
                            $('#total-expense').text(data.total_stats.total_expense || 0);
                            $('#total-count').text(data.total_stats.total_count || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

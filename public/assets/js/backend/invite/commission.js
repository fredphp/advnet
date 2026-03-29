define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/commission/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'invite_commission_log',
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
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'source', title: '来源', searchList: {"video":"视频观看","withdraw":"提现","task":"任务","red_packet":"红包","game":"游戏"}},
                        {field: 'commission_amount', title: '佣金金额', sortable: true},
                        {field: 'commission_rate', title: '佣金比例(%)'},
                        {field: 'coin_amount', title: '金币数量', sortable: true},
                        {field: 'level', title: '层级', searchList: {"1":"一级","2":"二级","3":"三级"}},
                        {field: 'status', title: '状态', searchList: {"0":"待结算","1":"已结算","2":"已取消","3":"已冻结"}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
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
                    url: 'invite/commission/statistics',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            // 总体统计
                            if (data.total_stats) {
                                $('#total-count').text(data.total_stats.total_count || 0);
                                $('#total-amount').text(data.total_stats.total_amount || 0);
                                $('#avg-amount').text(data.total_stats.avg_amount || 0);
                            }
                            // 来源统计
                            if (data.source_stats) {
                                var sourceHtml = '';
                                $.each(data.source_stats, function (i, item) {
                                    sourceHtml += '<tr><td>' + item.source + '</td><td>' + item.count + '</td><td>' + item.amount + '</td></tr>';
                                });
                                $('#source-stats').html(sourceHtml);
                            }
                            // 用户排行
                            if (data.top_users) {
                                var userHtml = '';
                                $.each(data.top_users, function (i, item) {
                                    userHtml += '<tr><td>' + (i + 1) + '</td><td>' + (item.nickname || item.username) + '</td><td>' + item.commission_count + '</td><td>' + item.total_amount + '</td></tr>';
                                });
                                $('#top-users').html(userHtml);
                            }
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

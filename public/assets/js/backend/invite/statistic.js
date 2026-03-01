define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 统计页面初始化
            Controller.api.loadStatistics();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'invite/statistic/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-invites').text(data.total_stats.total_invites || 0);
                            $('#unique-inviters').text(data.total_stats.unique_inviters || 0);
                            $('#total-commission-count').text(data.commission_stats.total_count || 0);
                            $('#total-commission').text(data.commission_stats.total_commission || 0);

                            // 邀请排行榜
                            var topHtml = '';
                            $.each(data.top_inviters || [], function (i, item) {
                                topHtml += '<tr><td>' + (i + 1) + '</td><td>' + item.nickname + '</td><td>' + item.total_invite_count + '</td><td>' + item.total_commission + '</td></tr>';
                            });
                            $('#top-inviters').html(topHtml);

                            // 分佣来源
                            var sourceHtml = '';
                            $.each(data.commission_by_source || [], function (i, item) {
                                sourceHtml += '<tr><td>' + item.source + '</td><td>' + item.count + '</td><td>' + item.amount + '</td></tr>';
                            });
                            $('#commission-source').html(sourceHtml);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

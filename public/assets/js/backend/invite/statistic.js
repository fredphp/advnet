define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/statistic/index',
                    table: 'invite_statistic',
                }
            });

            // 统计页面初始化
            Controller.api.loadStatistics();

            // 绑定日期变更事件
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
                    url: 'invite/statistic/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;

                            // 总体统计
                            if (data.total_stats) {
                                $('#total-invites').text(data.total_stats.total_invites || 0);
                                $('#unique-inviters').text(data.total_stats.unique_inviters || 0);
                            }

                            // 分佣统计
                            if (data.commission_stats) {
                                $('#total-commission-count').text(data.commission_stats.total_count || 0);
                                $('#total-commission').text(data.commission_stats.total_commission || 0);
                            }

                            // 邀请排行榜
                            if (data.top_inviters) {
                                var topHtml = '';
                                $.each(data.top_inviters, function (i, item) {
                                    topHtml += '<tr>' +
                                        '<td>' + (i + 1) + '</td>' +
                                        '<td>' + (item.nickname || item.username || '-') + '</td>' +
                                        '<td>' + (item.total_invite_count || 0) + '</td>' +
                                        '<td>' + (item.total_commission || 0) + '</td>' +
                                        '</tr>';
                                });
                                $('#top-inviters').html(topHtml);
                            }

                            // 分佣来源
                            if (data.commission_by_source) {
                                var sourceHtml = '';
                                $.each(data.commission_by_source, function (i, item) {
                                    sourceHtml += '<tr>' +
                                        '<td>' + (item.source || '-') + '</td>' +
                                        '<td>' + (item.count || 0) + '</td>' +
                                        '<td>' + (item.amount || 0) + '</td>' +
                                        '</tr>';
                                });
                                $('#commission-source').html(sourceHtml);
                            }

                            // 每日趋势
                            if (data.daily_stats && data.daily_stats.length > 0) {
                                Controller.api.renderChart(data.daily_stats);
                            }
                        }
                    }
                });
            },
            renderChart: function (dailyStats) {
                // 如果页面有图表容器，渲染趋势图
                var chartContainer = document.getElementById('trend-chart');
                if (chartContainer && typeof echarts !== 'undefined') {
                    var dates = [];
                    var counts = [];
                    var amounts = [];

                    $.each(dailyStats, function (i, item) {
                        dates.push(item.date);
                        counts.push(item.count || 0);
                        amounts.push(item.amount || 0);
                    });

                    var chart = echarts.init(chartContainer);
                    var option = {
                        tooltip: {
                            trigger: 'axis'
                        },
                        legend: {
                            data: ['邀请数', '佣金金额']
                        },
                        xAxis: {
                            type: 'category',
                            data: dates
                        },
                        yAxis: [
                            {
                                type: 'value',
                                name: '邀请数'
                            },
                            {
                                type: 'value',
                                name: '佣金金额'
                            }
                        ],
                        series: [
                            {
                                name: '邀请数',
                                type: 'line',
                                data: counts
                            },
                            {
                                name: '佣金金额',
                                type: 'line',
                                yAxisIndex: 1,
                                data: amounts
                            }
                        ]
                    };
                    chart.setOption(option);
                }
            }
        }
    };
    return Controller;
});

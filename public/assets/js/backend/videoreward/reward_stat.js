define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'videoreward/reward_stat/index',
                    userstat_url: 'videoreward/reward_stat/userstat',
                    table: 'user_daily_reward_stat',
                }
            });

            // 加载统计数据
            Controller.api.loadStats();

            // 绑定日期筛选事件
            $('#btn-search').on('click', function() {
                Controller.api.loadStats();
            });

            // 绑定日期快捷选择
            $('.date-quick-btn').on('click', function() {
                var days = $(this).data('days');
                var endDate = new Date();
                var startDate = new Date();
                startDate.setDate(startDate.getDate() - days);
                $('#start_date').val(startDate.toISOString().split('T')[0]);
                $('#end_date').val(endDate.toISOString().split('T')[0]);
                Controller.api.loadStats();
            });

            // 导出功能
            $('#btn-export').on('click', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                window.location.href = 'videoreward/reward_stat/export?start_date=' + startDate + '&end_date=' + endDate;
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStats: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                // 加载总体统计
                $.ajax({
                    url: 'videoreward/reward_stat/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            // 更新统计卡片
                            if (data.total) {
                                $('#total-reward-count').text(data.total.total_count || 0);
                                $('#total-reward-coin').text(data.total.total_coin || 0);
                            }
                            // 更新每日统计图表
                            if (data.daily && data.daily.length > 0) {
                                Controller.api.renderChart(data.daily);
                            }
                            // 更新视频排行
                            if (data.video_rank && data.video_rank.length > 0) {
                                Controller.api.renderVideoRank(data.video_rank);
                            }
                            // 更新用户排行
                            if (data.user_rank && data.user_rank.length > 0) {
                                Controller.api.renderUserRank(data.user_rank);
                            }
                        }
                    }
                });
            },
            renderChart: function(data) {
                var dates = [];
                var counts = [];
                var coins = [];
                
                data.forEach(function(item) {
                    dates.push(item.date_key);
                    counts.push(item.total_count || 0);
                    coins.push(item.total_coin || 0);
                });

                // 这里可以使用ECharts或其他图表库渲染
                // 简单示例：直接填充数据
                if (window.rewardChart) {
                    window.rewardChart.xAxis.data = dates;
                    window.rewardChart.series[0].data = counts;
                    window.rewardChart.series[1].data = coins;
                }
            },
            renderVideoRank: function(data) {
                var html = '';
                data.forEach(function(item, index) {
                    html += '<tr>';
                    html += '<td>' + (index + 1) + '</td>';
                    html += '<td>' + (item.title || '-') + '</td>';
                    html += '<td>' + (item.reward_count || 0) + '</td>';
                    html += '<td>' + (item.reward_coin_total || 0) + '</td>';
                    html += '</tr>';
                });
                $('#video-rank-table tbody').html(html);
            },
            renderUserRank: function(data) {
                var html = '';
                data.forEach(function(item, index) {
                    html += '<tr>';
                    html += '<td>' + (index + 1) + '</td>';
                    html += '<td>' + (item.nickname || '-') + '</td>';
                    html += '<td>' + (item.total_earn || 0) + '</td>';
                    html += '<td>' + (item.balance || 0) + '</td>';
                    html += '</tr>';
                });
                $('#user-rank-table tbody').html(html);
            }
        }
    };
    return Controller;
});

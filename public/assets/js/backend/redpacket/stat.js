define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 加载统计数据
            Controller.api.loadStatistics();

            // 绑定日期选择器事件
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
                    url: 'redpacket/stat/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1 && ret.data) {
                            var data = ret.data;

                            // 任务统计卡片
                            $('#total-tasks').text(data.task.total || 0);

                            // 领取统计卡片
                            $('#total-grabs').text(data.collect.total_grabs || 0);
                            $('#total-amount').text(data.collect.total_amount || 0);
                            $('#total-collected').text(data.collect.total_collected || 0);

                            // 详细统计
                            $('#total-base-amount').text(data.collect.total_base_amount || 0);
                            $('#total-accumulate-amount').text(data.collect.total_accumulate_amount || 0);
                            $('#total-clicks').text(data.collect.total_clicks || 0);

                            // 平均金额
                            var avgAmount = 0;
                            if (data.collect.total_collected > 0) {
                                avgAmount = Math.round((data.collect.total_amount || 0) / data.collect.total_collected);
                            }
                            $('#avg-amount').text(avgAmount);

                            // 任务状态分布
                            if (data.task) {
                                var statusMap = {
                                    'pending': '待发送',
                                    'normal': '进行中',
                                    'finished': '已抢完',
                                    'expired': '已过期'
                                };
                                var statusHtml = '';
                                $.each(statusMap, function(key, label) {
                                    statusHtml += '<tr>' +
                                        '<td>' + label + '</td>' +
                                        '<td>' + (data.task[key] || 0) + '</td>' +
                                        '</tr>';
                                });
                                $('#task-status-stats').html(statusHtml);
                            }

                            // 类型分布
                            if (data.type && data.type.length > 0) {
                                var typeMap = {
                                    'chat': '普通聊天',
                                    'download': '下载App',
                                    'miniapp': '小程序游戏',
                                    'adv': '广告时长',
                                    'video': '观看视频'
                                };
                                var typeHtml = '';
                                $.each(data.type, function (i, item) {
                                    var typeName = typeMap[item.type] || item.type;
                                    typeHtml += '<tr>' +
                                        '<td>' + typeName + '</td>' +
                                        '<td>' + item.count + '</td>' +
                                        '</tr>';
                                });
                                $('#type-stats').html(typeHtml);
                            } else {
                                $('#type-stats').html('<tr><td colspan="2" class="text-center text-muted">暂无数据</td></tr>');
                            }

                            // 每日统计
                            if (data.daily && data.daily.length > 0) {
                                var dailyHtml = '';
                                $.each(data.daily, function (i, item) {
                                    dailyHtml += '<tr>' +
                                        '<td>' + item.date + '</td>' +
                                        '<td>' + item.count + '</td>' +
                                        '<td>' + (item.collected || 0) + '</td>' +
                                        '<td>' + (item.amount || 0) + '</td>' +
                                        '</tr>';
                                });
                                $('#daily-stats').html(dailyHtml);
                            } else {
                                $('#daily-stats').html('<tr><td colspan="4" class="text-center text-muted">暂无数据</td></tr>');
                            }
                        } else {
                            console.error('统计数据加载失败:', ret.msg || '未知错误');
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX请求失败:', error);
                    }
                });
            }
        }
    };
    return Controller;
});

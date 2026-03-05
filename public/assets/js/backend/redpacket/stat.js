define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/stat/index',
                    table: '',
                }
            });

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
                            // 任务统计
                            if (data.task) {
                                $('#total-tasks').text(data.task.total || 0);
                                $('#total-task-amount').text(data.task.total_amount || 0);
                                $('#received-amount').text(data.task.receive_amount || 0);
                                $('#complete-count').text(data.task.complete_count || 0);
                            } else {
                                $('#total-tasks').text(0);
                                $('#total-task-amount').text(0);
                                $('#received-amount').text(0);
                                $('#complete-count').text(0);
                            }
                            // 参与统计
                            if (data.participation) {
                                $('#total-participations').text(data.participation.total || 0);
                                $('#pending-count').text(data.participation.pending || 0);
                                $('#wait-audit-count').text(data.participation.wait_audit || 0);
                                $('#pass-count').text(data.participation.pass || 0);
                                $('#rewarded-count').text(data.participation.rewarded || 0);
                                $('#rejected-count').text(data.participation.rejected || 0);
                            } else {
                                $('#total-participations').text(0);
                                $('#pending-count').text(0);
                                $('#wait-audit-count').text(0);
                                $('#pass-count').text(0);
                                $('#rewarded-count').text(0);
                                $('#rejected-count').text(0);
                            }
                            // 每日统计
                            if (data.daily && data.daily.length > 0) {
                                var dailyHtml = '';
                                $.each(data.daily, function (i, item) {
                                    dailyHtml += '<tr>' +
                                        '<td>' + item.date + '</td>' +
                                        '<td>' + item.count + '</td>' +
                                        '<td>' + (item.reward_coin || 0) + '</td>' +
                                        '</tr>';
                                });
                                $('#daily-stats').html(dailyHtml);
                            } else {
                                $('#daily-stats').html('<tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr>');
                            }
                            // 类型统计
                            if (data.type && data.type.length > 0) {
                                var typeHtml = '';
                                $.each(data.type, function (i, item) {
                                    typeHtml += '<tr>' +
                                        '<td>' + item.type + '</td>' +
                                        '<td>' + item.count + '</td>' +
                                        '<td>' + (item.amount || 0) + '</td>' +
                                        '</tr>';
                                });
                                $('#type-stats').html(typeHtml);
                            } else {
                                $('#type-stats').html('<tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr>');
                            }
                        } else {
                            console.error('统计数据加载失败:', ret.msg || '未知错误');
                            // 显示错误提示
                            $.toast({
                                heading: '错误',
                                text: ret.msg || '统计数据加载失败',
                                icon: 'error',
                                position: 'top-right'
                            });
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('AJAX请求失败:', error);
                        $.toast({
                            heading: '错误',
                            text: '网络请求失败，请稍后重试',
                            icon: 'error',
                            position: 'top-right'
                        });
                    }
                });
            }
        }
    };
    return Controller;
});

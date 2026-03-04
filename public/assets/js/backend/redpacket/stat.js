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
                        if (ret.code == 1) {
                            var data = ret.data;
                            // 任务统计
                            if (data.task) {
                                $('#total-tasks').text(data.task.total || 0);
                                $('#total-task-amount').text(data.task.total_amount || 0);
                                $('#received-amount').text(data.task.receive_amount || 0);
                                $('#complete-count').text(data.task.complete_count || 0);
                            }
                            // 参与统计
                            if (data.participation) {
                                $('#total-participations').text(data.participation.total || 0);
                                $('#pending-count').text(data.participation.pending || 0);
                                $('#wait-audit-count').text(data.participation.wait_audit || 0);
                                $('#pass-count').text(data.participation.pass || 0);
                                $('#rewarded-count').text(data.participation.rewarded || 0);
                                $('#rejected-count').text(data.participation.rejected || 0);
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
                            }
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

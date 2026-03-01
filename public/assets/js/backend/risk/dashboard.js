define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/dashboard/index',
                    table: '',
                }
            });

            // 加载仪表盘数据
            Controller.api.loadDashboard();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadDashboard: function () {
                $.ajax({
                    url: 'risk/dashboard/index',
                    type: 'GET',
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            // 今日统计
                            if (data.today_stats) {
                                $('#today-violations').text(data.today_stats.violation_count || 0);
                                $('#today-bans').text(data.today_stats.ban_count || 0);
                                $('#today-warns').text(data.today_stats.warn_count || 0);
                            }

                            // 风险用户统计
                            if (data.risk_user_stats) {
                                var riskHtml = '';
                                $.each(data.risk_user_stats, function (i, item) {
                                    riskHtml += '<tr><td>' + item.risk_level + '</td><td>' + item.count + '</td></tr>';
                                });
                                $('#risk-user-stats').html(riskHtml);
                            }

                            // 用户状态统计
                            if (data.user_status_stats) {
                                var statusHtml = '';
                                $.each(data.user_status_stats, function (i, item) {
                                    statusHtml += '<tr><td>' + item.status + '</td><td>' + item.count + '</td></tr>';
                                });
                                $('#user-status-stats').html(statusHtml);
                            }

                            // 最近封禁记录
                            if (data.recent_bans) {
                                var banHtml = '';
                                $.each(data.recent_bans, function (i, item) {
                                    banHtml += '<tr>' +
                                        '<td>' + item.id + '</td>' +
                                        '<td>' + (item.username || '-') + '</td>' +
                                        '<td>' + item.ban_type + '</td>' +
                                        '<td>' + item.reason + '</td>' +
                                        '<td>' + (item.ban_source == 'auto' ? '自动' : '手动') + '</td>' +
                                        '<td>' + Fast.api.toDateString(item.createtime) + '</td>' +
                                        '</tr>';
                                });
                                $('#recent-bans').html(banHtml);
                            }

                            // 规则触发统计
                            if (data.rule_trigger_stats) {
                                var ruleHtml = '';
                                $.each(data.rule_trigger_stats, function (i, item) {
                                    ruleHtml += '<tr>' +
                                        '<td>' + item.rule_name + '</td>' +
                                        '<td>' + item.trigger_count + '</td>' +
                                        '</tr>';
                                });
                                $('#rule-trigger-stats').html(ruleHtml);
                            }

                            // 风险预警
                            if (data.alerts && data.alerts.length > 0) {
                                var alertHtml = '';
                                $.each(data.alerts, function (i, item) {
                                    alertHtml += '<div class="alert alert-warning">' +
                                        '<strong>' + item.title + '</strong>: ' + item.message +
                                        '</div>';
                                });
                                $('#risk-alerts').html(alertHtml);
                            }
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

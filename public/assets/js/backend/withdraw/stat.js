define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form, Echarts) {
    var Controller = {
        index: function () {
            // 初始化日期选择器
            Controller.api.initDatepicker();
            
            // 加载统计数据
            Controller.api.loadStatistics();

            // 绑定事件
            Controller.api.bindEvents();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            
            initDatepicker: function () {
                require(['bootstrap-datetimepicker'], function () {
                    $('.datetimepicker').datetimepicker({
                        format: 'YYYY-MM-DD',
                        locale: 'zh-cn',
                        useCurrent: false
                    });
                });
            },
            
            bindEvents: function () {
                // 查询按钮
                $('#btn-search').on('click', function () {
                    Controller.api.loadStatistics();
                });
                
                // 今日
                $('#btn-today').on('click', function () {
                    var today = new Date();
                    var dateStr = today.toISOString().split('T')[0];
                    $('#start_date').val(dateStr);
                    $('#end_date').val(dateStr);
                    Controller.api.loadStatistics();
                });
                
                // 本月
                $('#btn-month').on('click', function () {
                    var today = new Date();
                    var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                    $('#start_date').val(firstDay.toISOString().split('T')[0]);
                    $('#end_date').val(today.toISOString().split('T')[0]);
                    Controller.api.loadStatistics();
                });
                
                // 本年
                $('#btn-year').on('click', function () {
                    var today = new Date();
                    var firstDay = new Date(today.getFullYear(), 0, 1);
                    $('#start_date').val(firstDay.toISOString().split('T')[0]);
                    $('#end_date').val(today.toISOString().split('T')[0]);
                    Controller.api.loadStatistics();
                });
            },
            
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'withdraw/stat/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            
                            // 更新汇总统计
                            Controller.api.updateSummary(data.summary);
                            
                            // 渲染每日趋势图表
                            Controller.api.renderDailyChart(data.daily_trend);
                            
                            // 渲染状态分布图表
                            Controller.api.renderStatusChart(data.status_distribution);
                            
                            // 渲染用户排行
                            Controller.api.renderTopUsers(data.top_users);
                            
                            // 渲染每日统计明细
                            Controller.api.renderDailyStats(data.daily_stats);
                        } else {
                            $.toast({
                                heading: '错误',
                                text: ret.msg || '获取数据失败',
                                icon: 'error'
                            });
                        }
                    },
                    error: function () {
                        $.toast({
                            heading: '错误',
                            text: '网络请求失败',
                            icon: 'error'
                        });
                    }
                });
            },
            
            updateSummary: function (summary) {
                $('#stat-total-amount').text((summary.total_amount || 0).toFixed(2));
                $('#stat-total-count').text(summary.total_count || 0);
                $('#stat-pending-amount').text((summary.pending_amount || 0).toFixed(2));
                $('#stat-pending-count').text(summary.pending_count || 0);
                $('#stat-approved-amount').text((summary.approved_amount || 0).toFixed(2));
                $('#stat-approved-count').text(summary.approved_count || 0);
                $('#stat-success-amount').text((summary.success_amount || 0).toFixed(2));
                $('#stat-success-count').text(summary.success_count || 0);
                $('#stat-rejected-amount').text((summary.rejected_amount || 0).toFixed(2));
                $('#stat-rejected-count').text(summary.rejected_count || 0);
                $('#stat-approve-rate').text(summary.approve_rate || 0);
            },
            
            renderDailyChart: function (data) {
                var chartDom = document.getElementById('chart-daily');
                var myChart = Echarts.init(chartDom);
                
                var dates = [];
                var totalAmounts = [];
                var successAmounts = [];
                
                data.forEach(function (item) {
                    dates.push(item.date);
                    totalAmounts.push(item.total_amount);
                    successAmounts.push(item.success_amount);
                });
                
                var option = {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'cross'
                        }
                    },
                    legend: {
                        data: ['申请金额', '成功金额']
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: dates
                    },
                    yAxis: {
                        type: 'value',
                        axisLabel: {
                            formatter: '¥{value}'
                        }
                    },
                    series: [
                        {
                            name: '申请金额',
                            type: 'line',
                            smooth: true,
                            areaStyle: {
                                opacity: 0.3
                            },
                            data: totalAmounts
                        },
                        {
                            name: '成功金额',
                            type: 'line',
                            smooth: true,
                            areaStyle: {
                                opacity: 0.3
                            },
                            data: successAmounts
                        }
                    ]
                };
                
                myChart.setOption(option);
                
                // 响应式
                $(window).resize(function () {
                    myChart.resize();
                });
            },
            
            renderStatusChart: function (data) {
                var chartDom = document.getElementById('chart-status');
                var myChart = Echarts.init(chartDom);
                
                var pieData = data.map(function (item) {
                    return {
                        name: item.name,
                        value: item.count
                    };
                });
                
                var option = {
                    tooltip: {
                        trigger: 'item',
                        formatter: '{b}: {c}笔 ({d}%)'
                    },
                    legend: {
                        orient: 'vertical',
                        left: 'left',
                        top: 'center'
                    },
                    series: [
                        {
                            name: '状态分布',
                            type: 'pie',
                            radius: ['40%', '70%'],
                            center: ['60%', '50%'],
                            avoidLabelOverlap: false,
                            label: {
                                show: false,
                                position: 'center'
                            },
                            emphasis: {
                                label: {
                                    show: true,
                                    fontSize: '16',
                                    fontWeight: 'bold'
                                }
                            },
                            labelLine: {
                                show: false
                            },
                            data: pieData
                        }
                    ]
                };
                
                myChart.setOption(option);
                
                $(window).resize(function () {
                    myChart.resize();
                });
            },
            
            renderTopUsers: function (data) {
                var html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-muted">暂无数据</td></tr>';
                } else {
                    data.forEach(function (item, index) {
                        var rankClass = '';
                        if (index === 0) rankClass = 'text-danger';
                        else if (index === 1) rankClass = 'text-warning';
                        else if (index === 2) rankClass = 'text-info';
                        
                        html += '<tr>';
                        html += '<td><strong class="' + rankClass + '">' + (index + 1) + '</strong></td>';
                        html += '<td>' + item.user_id + '</td>';
                        html += '<td>' + item.withdraw_count + '次</td>';
                        html += '<td>¥' + item.total_amount.toFixed(2) + '</td>';
                        html += '<td class="text-success">¥' + item.success_amount.toFixed(2) + '</td>';
                        html += '</tr>';
                    });
                }
                $('#top-amount-list').html(html);
            },
            
            renderDailyStats: function (data) {
                var html = '';
                if (data.length === 0) {
                    html = '<tr><td colspan="5" class="text-center text-muted">暂无数据</td></tr>';
                } else {
                    data.forEach(function (item) {
                        html += '<tr>';
                        html += '<td>' + item.date + '</td>';
                        html += '<td>' + item.apply_count + '笔</td>';
                        html += '<td>¥' + item.apply_amount.toFixed(2) + '</td>';
                        html += '<td class="text-success">' + item.success_count + '笔</td>';
                        html += '<td class="text-success">¥' + item.success_amount.toFixed(2) + '</td>';
                        html += '</tr>';
                    });
                }
                $('#daily-stats-list').html(html);
            }
        }
    };
    return Controller;
});

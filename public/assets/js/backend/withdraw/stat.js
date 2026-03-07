define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'moment'], function ($, undefined, Backend, Table, Form, Moment) {
    var Controller = {
        index: function () {
            // 初始化日期选择器
            $('.datetimepicker').datetimepicker({
                format: 'YYYY-MM-DD',
                locale: 'zh-cn',
                useCurrent: false
            });

            // 加载统计数据
            Controller.api.loadStatistics();

            // 绑定刷新按钮
            $('.btn-refresh').on('click', function () {
                Controller.api.loadStatistics();
            });

            // 绑定日期选择器事件
            $('#start_date, #end_date').on('dp.change', function () {
                Controller.api.loadStatistics();
            });

            // 每5分钟自动刷新
            setInterval(function () {
                Controller.api.loadStatistics();
            }, 300000);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                // 显示加载中
                $('.panel-body h2 strong').text('...');

                $.ajax({
                    url: 'withdraw/stat/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            
                            // 计算总数用于百分比
                            var totalCount = data.total_count || 0;
                            
                            // 更新顶部卡片
                            $('#total-amount').text(Controller.api.formatMoney(data.total_amount || 0));
                            $('#total-count').text(data.total_count || 0);
                            
                            $('#pending-amount').text(Controller.api.formatMoney(data.pending_amount || 0));
                            $('#pending-count').text(data.pending_count || 0);
                            
                            $('#completed-amount').text(Controller.api.formatMoney(data.completed_amount || 0));
                            $('#completed-count').text(data.completed_count || 0);
                            
                            $('#rejected-amount').text(Controller.api.formatMoney(data.rejected_amount || 0));
                            $('#rejected-count').text(data.rejected_count || 0);

                            // 更新表格数据
                            $('#pending-amount-2').text(Controller.api.formatMoney(data.pending_amount || 0));
                            $('#pending-count-2').text(data.pending_count || 0);
                            $('#pending-percent').text(Controller.api.calcPercent(data.pending_count, totalCount));
                            
                            $('#completed-amount-2').text(Controller.api.formatMoney(data.completed_amount || 0));
                            $('#completed-count-2').text(data.completed_count || 0);
                            $('#completed-percent').text(Controller.api.calcPercent(data.completed_count, totalCount));
                            
                            $('#rejected-amount-2').text(Controller.api.formatMoney(data.rejected_amount || 0));
                            $('#rejected-count-2').text(data.rejected_count || 0);
                            $('#rejected-percent').text(Controller.api.calcPercent(data.rejected_count, totalCount));
                        } else {
                            Toastr.error(ret.msg || '加载统计数据失败');
                        }
                    },
                    error: function () {
                        Toastr.error('网络错误，请稍后重试');
                        // 重置显示
                        $('.panel-body h2 strong').text('0');
                    }
                });
            },
            // 格式化金额
            formatMoney: function (amount) {
                return parseFloat(amount).toFixed(2);
            },
            // 计算百分比
            calcPercent: function (count, total) {
                if (total == 0) return '0%';
                return (count / total * 100).toFixed(1) + '%';
            }
        }
    };
    return Controller;
});

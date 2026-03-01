define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/stat/index',
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
                    url: 'withdraw/stat/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            // 总体统计
                            $('#total-amount').text(data.total_amount || 0);
                            $('#total-count').text(data.total_count || 0);
                            $('#pending-amount').text(data.pending_amount || 0);
                            $('#pending-count').text(data.pending_count || 0);
                            $('#completed-amount').text(data.completed_amount || 0);
                            $('#completed-count').text(data.completed_count || 0);
                            $('#rejected-amount').text(data.rejected_amount || 0);
                            $('#rejected-count').text(data.rejected_count || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

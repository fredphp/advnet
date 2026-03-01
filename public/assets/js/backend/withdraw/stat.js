define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
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
                    url: 'withdraw/stat/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-amount').text(data.total_amount || 0);
                            $('#total-count').text(data.total_count || 0);
                            $('#pending-amount').text(data.pending_amount || 0);
                            $('#pending-count').text(data.pending_count || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

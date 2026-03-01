define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            Controller.api.loadStats();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStats: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'videoreward/reward_stat/index',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-watches').text(data.total_watches || 0);
                            $('#total-coins').text(data.total_coins || 0);
                            $('#unique-users').text(data.unique_users || 0);
                            $('#cheat-blocks').text(data.cheat_blocks || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

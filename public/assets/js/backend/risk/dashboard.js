define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
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
                            $('#total-users').text(data.total_users || 0);
                            $('#risk-users').text(data.risk_users || 0);
                            $('#banned-users').text(data.banned_users || 0);
                            $('#today-risk').text(data.today_risk || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

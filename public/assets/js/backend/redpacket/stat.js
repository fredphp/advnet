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
                $.ajax({
                    url: 'redpacket/stat/index',
                    type: 'GET',
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-tasks').text(data.total_tasks || 0);
                            $('#total-participations').text(data.total_participations || 0);
                            $('#total-coins').text(data.total_coins || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

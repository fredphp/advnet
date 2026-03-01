define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        coin: function () {
            Controller.api.bindevent();
        },
        withdraw: function () {
            Controller.api.bindevent();
        },
        invite: function () {
            Controller.api.bindevent();
        },
        risk: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

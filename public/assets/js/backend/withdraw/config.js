define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 配置页面，主要是表单
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function (ret) {
                    // 成功后刷新页面
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                });
            }
        }
    };
    return Controller;
});

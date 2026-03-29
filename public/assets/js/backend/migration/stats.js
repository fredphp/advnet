define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 页面加载完成后的初始化
            Controller.api.init();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            init: function () {
                // 刷新按钮事件
                $(document).on('click', '.btn-refresh', function() {
                    location.reload();
                });
            }
        }
    };
    return Controller;
});

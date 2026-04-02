define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 页面由 index.html 中的内联脚本处理所有逻辑
            // 这里不需要做任何事情
        },
        add: function () {
            // 添加规则弹窗中的表单绑定
            Controller.api.bindevent();
        },
        edit: function () {
            // 编辑规则弹窗中的表单绑定
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 绑定表单事件
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                // 表单验证和提交
                Form.api.bindevent($("form[role=form]"), function(data, ret) {
                    // 保存成功后的回调
                    if (ret.code === 1) {
                        Toastr.success(ret.msg || '保存成功');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    }
                }, function(data, ret) {
                    // 保存失败的回调
                    Toastr.error(ret.msg || '保存失败');
                });
                
                // 重置按钮
                $(document).on('click', '.btn-reset', function() {
                    setTimeout(function() {
                        location.reload();
                    }, 100);
                });
            }
        }
    };
    return Controller;
});

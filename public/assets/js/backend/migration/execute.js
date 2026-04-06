define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            Controller.api.bindevent();
            $('.btn-execute').on('click', function () {
                var action = $(this).data('action');
                Layer.confirm('确定要执行该迁移任务吗？', function (index) {
                    Fast.api.ajax({
                        url: 'migration/execute/index',
                        type: 'POST',
                        data: {action: action}
                    }, function (ret) {
                        Layer.close(index);
                        Toastr.success('执行成功');
                    });
                });
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

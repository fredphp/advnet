define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'coin/account/index',
                    add_url: 'coin/account/add',
                    del_url: 'coin/account/del',
                    multi_url: 'coin/account/multi',
                    table: 'coin_account',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'balance',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'balance', title: '可用余额', sortable: true, operate: 'BETWEEN'},
                        {field: 'frozen', title: '冻结金额', sortable: true, operate: 'BETWEEN'},
                        {field: 'total_earn', title: '累计获得', sortable: true, operate: 'BETWEEN'},
                        {field: 'total_spend', title: '累计消费', sortable: true, operate: 'BETWEEN'},
                        {field: 'total_withdraw', title: '累计提现', sortable: true, operate: 'BETWEEN'},
                        {field: 'today_earn', title: '今日获得', sortable: true},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        // {field: 'updatetime', title: '更新时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                            {
                                name: 'detail',
                                text: '详情',
                                title: '账户详情',
                                classname: 'btn btn-primary btn-xs btn-dialog',
                                icon: 'fa fa-list',
                                url: 'coin/account/detail',
                                extend: 'data-area=\'["800px","600px"]\''
                            },
                            {
                                name: 'adjust',
                                text: '调整',
                                title: '调整余额',
                                classname: 'btn btn-success btn-xs btn-dialog',
                                icon: 'fa fa-edit',
                                url: 'coin/account/adjust',
                                extend: 'data-area=\'["600px","520px"]\''
                            }
                        ]}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        detail: function () {
            Controller.api.bindevent();
        },
        adjust: function () {
            // 获取当前余额
            var currentBalance = parseInt($('#current-balance').text()) || 0;
            var currentType = 'add';
            var currentAmount = 0;

            // 更新预览
            function updatePreview() {
                var result;
                if (currentType === 'add') {
                    result = currentBalance + currentAmount;
                } else {
                    result = currentBalance - currentAmount;
                }
                $('#preview-result').text(result);
                $('#preview-result').removeClass('add deduct').addClass(currentType);
            }

            // 更新提交按钮样式
            function updateSubmitBtn() {
                var $btn = $('#submit-btn');
                var $text = $('#btn-text');
                $btn.removeClass('btn-add btn-deduct').addClass(currentType === 'add' ? 'btn-add' : 'btn-deduct');
                if (currentType === 'add') {
                    $text.text('确认增加金币');
                } else {
                    $text.text('确认扣除金币');
                }
            }

            // 类型按钮点击
            $('.type-btn').on('click', function() {
                currentType = $(this).data('type');
                $('#c-type').val(currentType);
                $('.type-btn').removeClass('active');
                $(this).addClass('active');
                updatePreview();
                updateSubmitBtn();
            });

            // 快捷金额按钮点击
            $('.quick-amount-btn').on('click', function() {
                currentAmount = parseInt($(this).data('amount')) || 0;
                $('#c-amount').val(currentAmount);
                $('.quick-amount-btn').removeClass('active');
                $(this).addClass('active');
                updatePreview();
            });

            // 自定义金额输入
            $('#c-amount').on('input', function() {
                currentAmount = parseInt($(this).val()) || 0;
                $('.quick-amount-btn').removeClass('active');
                updatePreview();
            });

            // 提交按钮点击
            $('#submit-btn').on('click', function() {
                var amount = parseInt($('#c-amount').val()) || 0;
                var type = $('#c-type').val();
                var remark = $('#c-remark').val();
                var ids = $('input[name="ids"]').val();

                // 验证金额
                if (amount <= 0) {
                    Toastr.error('请输入有效的金额');
                    return false;
                }

                // 确认操作
                var confirmText = type === 'add' ? '确定要增加 ' + amount + ' 金币吗？' : '确定要扣除 ' + amount + ' 金币吗？';
                if (!confirm(confirmText)) {
                    return false;
                }

                // 禁用按钮
                var $btn = $('#submit-btn');
                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> 处理中...');

                // 发送AJAX请求
                $.ajax({
                    url: window.location.href,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ids: ids,
                        type: type,
                        amount: amount,
                        remark: remark
                    },
                    success: function(ret) {
                        if (ret.code === 1) {
                            Toastr.success(ret.msg || '操作成功');
                            setTimeout(function() {
                                parent.Layer.closeAll();
                                if (parent.$) {
                                    parent.$('#table').bootstrapTable('refresh');
                                }
                            }, 500);
                        } else {
                            Toastr.error(ret.msg || '操作失败');
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function(xhr) {
                        Toastr.error('网络请求失败');
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });

                return false;
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

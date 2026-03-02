define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'member/user/index',
                    add_url: 'member/user/add',
                    edit_url: 'member/user/edit',
                    del_url: 'member/user/del',
                    multi_url: 'member/user/multi',
                    table: 'member_user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'coin_balance', title: '金币余额'},
                        {field: 'level', title: '等级'},
                        {field: 'status', title: '状态', searchList: {"normal":"正常","frozen":"冻结","banned":"封禁"}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '注册时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 充值金币按钮
            $(document).on('click', '.btn-recharge', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                $('#recharge-modal').modal('show');
                $('#recharge-form input[name="amount"]').val('');
                $('#recharge-form textarea[name="remark"]').val('');
            });

            // 确认充值
            $(document).on('click', '#btn-recharge-confirm', function () {
                var ids = Table.api.selectedids(table);
                var amount = $('#recharge-form input[name="amount"]').val();
                var remark = $('#recharge-form textarea[name="remark"]').val();
                
                if (!amount || amount <= 0) {
                    Toastr.error('请输入有效的充值金额');
                    return;
                }

                Fast.api.ajax({
                    url: 'member/user/recharge',
                    data: {user_id: ids[0], amount: amount, remark: remark}
                }, function (ret) {
                    $('#recharge-modal').modal('hide');
                    Toastr.success('充值成功');
                    table.bootstrapTable('refresh');
                });
            });

            // 扣除金币按钮
            $(document).on('click', '.btn-deduct', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                $('#deduct-modal').modal('show');
                $('#deduct-form input[name="amount"]').val('');
                $('#deduct-form textarea[name="remark"]').val('');
            });

            // 确认扣除
            $(document).on('click', '#btn-deduct-confirm', function () {
                var ids = Table.api.selectedids(table);
                var amount = $('#deduct-form input[name="amount"]').val();
                var remark = $('#deduct-form textarea[name="remark"]').val();
                
                if (!amount || amount <= 0) {
                    Toastr.error('请输入有效的扣除金额');
                    return;
                }

                if (!remark) {
                    Toastr.error('请填写扣除原因');
                    return;
                }

                Fast.api.ajax({
                    url: 'member/user/deduct',
                    data: {user_id: ids[0], amount: amount, remark: remark}
                }, function (ret) {
                    $('#deduct-modal').modal('hide');
                    Toastr.success('扣除成功');
                    table.bootstrapTable('refresh');
                });
            });

            // 导出用户
            $(document).on('click', '.btn-export', function () {
                var search = table.bootstrapTable('getOptions').searchText;
                var filter = table.bootstrapTable('getOptions').filter;
                window.location.href = 'member/user/export?' + $.param({search: search, filter: JSON.stringify(filter)});
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        statistics: function () {
            Controller.api.loadStatistics();
            $('#start_date, #end_date').on('change', function () {
                Controller.api.loadStatistics();
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'member/user/statistics',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-users').text(data.total_stats.total || 0);
                            $('#normal-users').text(data.total_stats.normal_count || 0);
                            $('#frozen-users').text(data.total_stats.frozen_count || 0);
                            $('#banned-users').text(data.total_stats.banned_count || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

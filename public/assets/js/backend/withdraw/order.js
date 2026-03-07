define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/order/index' + location.search,
                    detail_url: 'withdraw/order/detail',
                    approve_url: 'withdraw/order/approve',
                    reject_url: 'withdraw/order/reject',
                    complete_url: 'withdraw/order/complete',
                    multi_url: 'withdraw/order/multi',
                    export_url: 'withdraw/order/export',
                    table: 'withdraw_order',
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
                        {field: 'id', title: __('ID'), sortable: true},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'withdraw_name', title: '收款人'},
                        {field: 'withdraw_account', title: '收款账号'},
                        {field: 'coin_amount', title: '提现金币', sortable: true},
                        {field: 'cash_amount', title: '提现金额(元)', sortable: true},
                        {field: 'withdraw_type', title: '提现方式', formatter: function(value, row, index) { 
                            return '<span class="label label-success">微信</span>'; 
                        }},
                        {field: 'status', title: '状态', searchList: {"0":"待审核","1":"审核通过","2":"打款中","3":"提现成功","4":"审核拒绝","5":"打款失败","6":"已取消"}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '申请时间', operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '订单详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'withdraw/order/detail',
                                    extend: 'data-area=\'["800px","600px"]\''
                                },
                                {
                                    name: 'approve',
                                    text: '审核通过',
                                    title: '审核通过',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-check',
                                    url: 'withdraw/order/approve',
                                    confirm: '确认审核通过？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function(data, ret) {
                                        Layer.alert(ret.msg);
                                    },
                                    visible: function(row) {
                                        return row.status == 0; // 只有待审核状态才显示
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '审核拒绝',
                                    title: '审核拒绝',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-times',
                                    url: 'withdraw/order/reject',
                                    confirm: '确认拒绝？请确保已填写拒绝原因',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function(data, ret) {
                                        Layer.alert(ret.msg);
                                    },
                                    visible: function(row) {
                                        return row.status == 0; // 只有待审核状态才显示
                                    }
                                },
                                {
                                    name: 'complete',
                                    text: '通过打款',
                                    title: '确认打款',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-money',
                                    url: 'withdraw/order/complete',
                                    confirm: '确认打款？将通过微信支付转账到用户零钱',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function(data, ret) {
                                        Layer.alert(ret.msg);
                                    },
                                    visible: function(row) {
                                        return row.status == 0 || row.status == 1; // 待审核或审核通过状态
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 审核通过
            $(document).on('click', '.btn-approve', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要审核的记录');
                    return;
                }
                Layer.confirm('确认审核通过选中的记录？', {icon: 3, title: '提示'}, function(index) {
                    Fast.api.ajax({
                        url: 'withdraw/order/approve',
                        data: {ids: ids.join(',')},
                    }, function(data, ret) {
                        Toastr.success(ret.msg);
                        table.bootstrapTable('refresh');
                        Layer.close(index);
                    });
                });
            });

            // 审核拒绝
            $(document).on('click', '.btn-reject', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要拒绝的记录');
                    return;
                }
                Layer.prompt({title: '请输入拒绝原因', formType: 0}, function(value, index) {
                    Fast.api.ajax({
                        url: 'withdraw/order/reject',
                        data: {ids: ids.join(','), reason: value},
                    }, function(data, ret) {
                        Toastr.success(ret.msg);
                        table.bootstrapTable('refresh');
                        Layer.close(index);
                    });
                });
            });

            // 确认打款
            $(document).on('click', '.btn-complete', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要打款的记录');
                    return;
                }
                Layer.confirm('确认打款？将通过微信支付转账到用户零钱', {icon: 3, title: '提示'}, function(index) {
                    Fast.api.ajax({
                        url: 'withdraw/order/complete',
                        data: {ids: ids.join(',')},
                    }, function(data, ret) {
                        Toastr.success(ret.msg);
                        table.bootstrapTable('refresh');
                        Layer.close(index);
                    });
                });
            });

            // 导出
            $(document).on('click', '.btn-export', function() {
                window.location.href = 'withdraw/order/export' + location.search;
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        pending: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/order/pending',
                    detail_url: 'withdraw/order/detail',
                    table: '',
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'coin_amount', title: '金币数量'},
                        {field: 'amount', title: '提现金额(元)'},
                        {field: 'withdraw_type', title: '提现方式', formatter: function(value, row, index) { 
                            return '<span class="label label-success">微信</span>'; 
                        }},
                        {field: 'withdraw_account', title: '收款账号'},
                        {field: 'createtime', title: '申请时间', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '订单详情',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'withdraw/order/detail'
                                },
                                {
                                    name: 'approve',
                                    text: '审核通过',
                                    title: '审核通过',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-check',
                                    url: 'withdraw/order/approve',
                                    confirm: '确认审核通过？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '审核拒绝',
                                    title: '审核拒绝',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-times',
                                    url: 'withdraw/order/reject',
                                    confirm: '确认拒绝？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'complete',
                                    text: '通过打款',
                                    title: '确认打款',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-money',
                                    url: 'withdraw/order/complete',
                                    confirm: '确认打款？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            Table.api.bindevent(table);
        },
        statistics: function () {
            Controller.api.loadStatistics();
            $('#start_date, #end_date').on('change', function () {
                Controller.api.loadStatistics();
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'withdraw/order/statistics',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            if (data.total_stats) {
                                $('#total-count').text(data.total_stats.total_count || 0);
                                $('#total-amount').text(data.total_stats.total_amount || 0);
                                $('#completed-amount').text(data.total_stats.completed_amount || 0);
                                $('#rejected-amount').text(data.total_stats.rejected_amount || 0);
                            }
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

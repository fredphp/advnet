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
                                    url: function(row) {
                                        return 'withdraw/order/detail?order_no=' + encodeURIComponent(row.order_no);
                                    },
                                    extend: 'data-area=\'["800px","600px"]\''
                                },
                                {
                                    name: 'approve',
                                    text: '审核通过',
                                    title: '审核通过',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-check',
                                    url: function(row) {
                                        return 'withdraw/order/approve?order_no=' + encodeURIComponent(row.order_no);
                                    },
                                    hidden: function(row) {
                                        return row.status != 0;
                                    },
                                    extend: 'data-area=\'["800px","90%"]\''
                                },
                                {
                                    name: 'reject',
                                    text: '审核拒绝',
                                    title: '审核拒绝',
                                    classname: 'btn btn-xs btn-danger btn-dialog',
                                    icon: 'fa fa-times',
                                    url: function(row) {
                                        return 'withdraw/order/reject?order_no=' + encodeURIComponent(row.order_no);
                                    },
                                    hidden: function(row) {
                                        return row.status != 0;
                                    },
                                    extend: 'data-area=\'["600px","500px"]\''
                                },
                                {
                                    name: 'complete',
                                    text: '通过打款',
                                    title: '确认打款',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-money',
                                    url: function(row) {
                                        return 'withdraw/order/complete?order_no=' + encodeURIComponent(row.order_no);
                                    },
                                    hidden: function(row) {
                                        return row.status != 0 && row.status != 1;
                                    },
                                    extend: 'data-area=\'["800px","90%"]\''
                                }
                            ]
                        }
                    ]
                ]
            });

            // 审核通过 - 弹窗方式
            $(document).on('click', '.btn-approve', function() {
                var rows = table.bootstrapTable('getSelections');
                if (rows.length === 0) {
                    Toastr.error('请选择要审核的记录');
                    return;
                }
                if (rows.length > 1) {
                    Toastr.error('请选择单条记录进行审核');
                    return;
                }

                if (rows[0].status != 0) {
                    Toastr.error('只能审核待审核状态的订单');
                    return;
                }

                Fast.api.open('withdraw/order/approve?order_no=' + encodeURIComponent(rows[0].order_no), '审核通过', {
                    area: ['800px', '90%']
                });
            });

            // 审核拒绝 - 弹窗方式
            $(document).on('click', '.btn-reject', function() {
                var rows = table.bootstrapTable('getSelections');
                if (rows.length === 0) {
                    Toastr.error('请选择要拒绝的记录');
                    return;
                }
                if (rows.length > 1) {
                    Toastr.error('请选择单条记录进行操作');
                    return;
                }

                if (rows[0].status != 0) {
                    Toastr.error('只能拒绝待审核状态的订单');
                    return;
                }

                Fast.api.open('withdraw/order/reject?order_no=' + encodeURIComponent(rows[0].order_no), '审核拒绝', {
                    area: ['600px', '500px']
                });
            });

            // 确认打款 - 弹窗方式
            $(document).on('click', '.btn-complete', function() {
                var rows = table.bootstrapTable('getSelections');
                if (rows.length === 0) {
                    Toastr.error('请选择要打款的记录');
                    return;
                }
                if (rows.length > 1) {
                    Toastr.error('请选择单条记录进行打款');
                    return;
                }

                if (rows[0].status != 0 && rows[0].status != 1) {
                    Toastr.error('只能对待审核或审核通过状态的订单进行打款');
                    return;
                }

                Fast.api.open('withdraw/order/complete?order_no=' + encodeURIComponent(rows[0].order_no), '确认打款', {
                    area: ['800px', '90%']
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
                                    url: function(row) {
                                        return 'withdraw/order/detail?order_no=' + encodeURIComponent(row.order_no);
                                    }
                                },
                                {
                                    name: 'approve',
                                    text: '审核通过',
                                    title: '审核通过',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-check',
                                    url: function(row) {
                                        return 'withdraw/order/approve?order_no=' + encodeURIComponent(row.order_no);
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '审核拒绝',
                                    title: '审核拒绝',
                                    classname: 'btn btn-xs btn-danger btn-dialog',
                                    icon: 'fa fa-times',
                                    url: function(row) {
                                        return 'withdraw/order/reject?order_no=' + encodeURIComponent(row.order_no);
                                    }
                                },
                                {
                                    name: 'complete',
                                    text: '通过打款',
                                    title: '确认打款',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-money',
                                    url: function(row) {
                                        return 'withdraw/order/complete?order_no=' + encodeURIComponent(row.order_no);
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
        approve: function () {
            Controller.api.bindevent();
        },
        reject: function () {
            Controller.api.bindevent();
            // 处理拒绝原因选择
            $(document).on('change', '#reject-reason-select', function() {
                if ($(this).val() === 'custom') {
                    $('#custom-reason-group').show();
                } else {
                    $('#custom-reason-group').hide();
                }
            });
        },
        complete: function () {
            Controller.api.bindevent();
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

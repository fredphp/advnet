define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'bootstrap-datetimepicker'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 日期选择器
            $('#start_date, #end_date').datetimepicker({
                format: 'YYYY-MM-DD',
                locale: 'zh-cn'
            });

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/order/index' + location.search,
                    detail_url: 'withdraw/order/detail',
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
                        {field: 'user.nickname', title: '用户昵称'},
                        {field: 'coin_amount', title: '提现金币', sortable: true},
                        {field: 'cash_amount', title: '提现金额(元)', sortable: true},
                        {field: 'withdraw_type', title: '提现方式', searchList: {"alipay":"支付宝","wechat":"微信","bank":"银行卡"}, formatter: Table.api.formatter.status},
                        {field: 'withdraw_account', title: '收款账号'},
                        {field: 'withdraw_name', title: '收款人'},
                        {
                            field: 'status',
                            title: '状态',
                            searchList: {"0":"待审核","1":"待打款","2":"打款中","3":"提现成功","4":"审核拒绝","5":"打款失败","6":"已取消"},
                            formatter: function(value, row, index) {
                                var statusMap = {
                                    0: '<span class="label label-warning">待审核</span>',
                                    1: '<span class="label label-info">待打款</span>',
                                    2: '<span class="label label-primary">打款中</span>',
                                    3: '<span class="label label-success">提现成功</span>',
                                    4: '<span class="label label-danger">审核拒绝</span>',
                                    5: '<span class="label label-danger">打款失败</span>',
                                    6: '<span class="label label-default">已取消</span>'
                                };
                                return statusMap[value] || value;
                            }
                        },
                        {field: 'createtime', title: '申请时间', operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'approve',
                                    text: '审核通过',
                                    title: '审核通过',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-check',
                                    url: 'withdraw/order/approve',
                                    hidden: function(row) {
                                        // 只有待审核状态(status=0)才显示审核通过按钮
                                        return row.status != 0;
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '审核拒绝',
                                    title: '审核拒绝',
                                    classname: 'btn btn-xs btn-danger btn-dialog',
                                    icon: 'fa fa-times',
                                    url: 'withdraw/order/reject',
                                    hidden: function(row) {
                                        // 只有待审核状态(status=0)才显示审核拒绝按钮
                                        return row.status != 0;
                                    }
                                },
                                {
                                    name: 'complete',
                                    text: '确认打款',
                                    title: '确认打款',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-money',
                                    url: 'withdraw/order/complete',
                                    hidden: function(row) {
                                        // 只有审核通过/待打款状态(status=1)才显示确认打款按钮
                                        return row.status != 1;
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 日期筛选
            $(document).on('click', '.btn-filter-date', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                var url = 'withdraw/order/index';
                if (startDate) url += '?start_date=' + startDate;
                if (endDate) url += (startDate ? '&' : '?') + 'end_date=' + endDate;
                table.bootstrapTable('refresh', {url: $.fn.bootstrapTable.defaults.extend.index_url.split('?')[0] + '?' + (startDate ? 'start_date=' + startDate + '&' : '') + (endDate ? 'end_date=' + endDate : '')});
            });

            // 审核通过 - 弹窗方式（只能选择待审核状态的记录）
            $(document).on('click', '.btn-approve', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要审核的记录');
                    return;
                }
                if (ids.length > 1) {
                    Toastr.error('请选择单条记录进行审核');
                    return;
                }

                // 检查选中记录的状态
                var rows = table.bootstrapTable('getSelections');
                if (rows[0].status != 0) {
                    Toastr.error('只能审核待审核状态的订单');
                    return;
                }

                Fast.api.open('withdraw/order/approve/ids/' + ids[0], '审核通过', {
                    area: ['800px', '90%']
                });
            });

            // 审核拒绝 - 弹窗方式（只能选择待审核状态的记录）
            $(document).on('click', '.btn-reject', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要拒绝的记录');
                    return;
                }
                if (ids.length > 1) {
                    Toastr.error('请选择单条记录进行操作');
                    return;
                }

                // 检查选中记录的状态
                var rows = table.bootstrapTable('getSelections');
                if (rows[0].status != 0) {
                    Toastr.error('只能拒绝待审核状态的订单');
                    return;
                }

                Fast.api.open('withdraw/order/reject/ids/' + ids[0], '审核拒绝', {
                    area: ['600px', '500px']
                });
            });

            // 确认打款 - 弹窗方式（只能选择审核通过/待打款状态的记录）
            $(document).on('click', '.btn-complete', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要打款的记录');
                    return;
                }
                if (ids.length > 1) {
                    Toastr.error('请选择单条记录进行打款');
                    return;
                }

                // 检查选中记录的状态
                var rows = table.bootstrapTable('getSelections');
                if (rows[0].status != 1) {
                    Toastr.error('只能对审核通过（待打款）状态的订单进行打款');
                    return;
                }

                Fast.api.open('withdraw/order/complete/ids/' + ids[0], '确认打款', {
                    area: ['800px', '90%']
                });
            });

            // 导出
            $(document).on('click', '.btn-export', function() {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();
                var url = 'withdraw/order/export';
                if (startDate) url += '?start_date=' + startDate;
                if (endDate) url += (startDate ? '&' : '?') + 'end_date=' + endDate;
                window.location.href = url;
            });

            // 快速审核
            $(document).on('click', '.btn-quick-pending', function() {
                Fast.api.open('withdraw/order/pending', '待审核列表', {
                    area: ['90%', '90%']
                });
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
                        {field: 'username', title: '用户名'},
                        {field: 'nickname', title: '昵称'},
                        {field: 'coin_amount', title: '金币数量'},
                        {field: 'amount', title: '提现金额(元)'},
                        {field: 'withdraw_type', title: '提现方式', searchList: {
                            "alipay": "支付宝",
                            "wechat": "微信",
                            "bank": "银行卡"
                        }},
                        {field: 'account_no', title: '收款账号'},
                        {field: 'createtime', title: '申请时间', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
            $('#reject-reason-select').on('change', function() {
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

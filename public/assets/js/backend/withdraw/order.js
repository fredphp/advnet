define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/order/index',
                    detail_url: 'withdraw/order/detail',
                    del_url: 'withdraw/order/del',
                    multi_url: 'withdraw/order/multi',
                    table: '',
                }
            });

            // 初始化表格
            var table = $("#table");

            // 表格配置
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'coin_amount', title: '金币数量', sortable: true},
                        {field: 'amount', title: '提现金额(元)', sortable: true, operate: 'BETWEEN'},
                        {field: 'withdraw_type', title: '提现方式', formatter: function(value, row, index) { return '<span class="label label-success">微信</span>'; }},
                        {field: 'status', title: '状态', searchList: {
                            "0": "待审核",
                            "1": "审核通过",
                            "2": "打款中",
                            "3": "提现成功",
                            "4": "审核拒绝",
                            "5": "打款失败",
                            "6": "已取消"
                        }, formatter: Table.api.formatter.status},
                        {field: 'admin_name', title: '审核人'},
                        {field: 'createtime', title: '申请时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {field: 'updatetime', title: '更新时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, visible: false},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
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
                        {field: 'withdraw_type', title: '提现方式', formatter: function(value, row, index) { return '<span class="label label-success">微信</span>'; }},
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

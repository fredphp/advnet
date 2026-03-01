define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格
            var table = $("#table");

            // 表格配置
            table.bootstrapTable({
                url: 'withdraw/order/index',
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'username', title: '用户名'},
                        {field: 'coin_amount', title: '金币数量'},
                        {field: 'cash_amount', title: '提现金额(元)'},
                        {field: 'withdraw_type', title: '提现方式', searchList: {"alipay":"支付宝","wechat":"微信","bank":"银行卡"}},
                        {field: 'status', title: '状态', searchList: {"0":"待审核","1":"审核通过","2":"打款中","3":"提现成功","4":"审核拒绝","5":"打款失败","6":"已取消"}},
                        {field: 'createtime', title: '申请时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        pending: function () {
            Controller.index();
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
                            $('#total-count').text(data.total_stats.total_count || 0);
                            $('#total-amount').text(data.total_stats.total_amount || 0);
                            $('#completed-amount').text(data.total_stats.completed_amount || 0);
                            $('#rejected-amount').text(data.total_stats.rejected_amount || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

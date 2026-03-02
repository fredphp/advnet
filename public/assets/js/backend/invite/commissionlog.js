define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/commissionlog/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'invite_commission_log',
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
                        {field: 'user_id', title: '获益用户ID', sortable: true},
                        {field: 'user_nickname', title: '获益用户昵称', operate: false},
                        {field: 'parent_id', title: '来源用户ID', sortable: true},
                        {field: 'parent_nickname', title: '来源用户昵称', operate: false},
                        {field: 'source_type', title: '来源类型', searchList: {"video":"视频观看","withdraw":"提现","task":"任务","red_packet":"红包","game":"游戏"}},
                        {field: 'source_id', title: '来源ID'},
                        {field: 'order_amount', title: '订单金额'},
                        {field: 'commission_rate', title: '佣金比例(%)'},
                        {field: 'commission_amount', title: '佣金金额', sortable: true},
                        {field: 'coin_amount', title: '金币数量', sortable: true},
                        {field: 'level', title: '层级', searchList: {"1":"一级","2":"二级","3":"三级"}},
                        {field: 'status', title: '状态', searchList: {"0":"待结算","1":"已结算","2":"已取消","3":"已冻结"}, formatter: Table.api.formatter.status},
                        {field: 'settle_time', title: '结算时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

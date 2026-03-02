define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/commissionstat/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'user_commission_stat',
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
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'user_nickname', title: '用户昵称', operate: false},
                        {field: 'total_invite_count', title: '总邀请数', sortable: true},
                        {field: 'level1_count', title: '一级邀请', sortable: true},
                        {field: 'level2_count', title: '二级邀请', sortable: true},
                        {field: 'total_commission', title: '累计佣金', sortable: true},
                        {field: 'withdrawn_commission', title: '已提现佣金', sortable: true},
                        {field: 'pending_commission', title: '待结算佣金', sortable: true},
                        {field: 'frozen_commission', title: '冻结佣金', sortable: true},
                        {field: 'today_commission', title: '今日佣金', sortable: true},
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

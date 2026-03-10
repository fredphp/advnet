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
                        {field: 'updatetime', title: '更新时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
                                extend: 'data-area=\'["600px","450px"]\''
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
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

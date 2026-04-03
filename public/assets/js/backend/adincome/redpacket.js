define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'adincome/redpacket/index',
                    detail_url: 'adincome/redpacket/detail',
                    del_url: 'adincome/redpacket/del',
                    multi_url: '',
                    add_url: '',
                    edit_url: '',
                    table: 'ad_red_packet',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                search: false,
                showToggle: true,
                showColumns: true,
                columns: [
                    [
                        {field: 'state', checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: false},
                        {field: 'nickname', title: '昵称', operate: false},
                        {field: 'amount', title: '金额(金币)', sortable: true},
                        {field: 'source_text', title: '来源', operate: false},
                        {field: 'status_text', title: '状态', searchList: {0: '未领取', 1: '已领取', 2: '已过期'}},
                        {field: 'createtime_text', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'claim_time_text', title: '领取时间', operate: false},
                        {field: 'expire_time_text', title: '过期时间', operate: false},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate,
                            buttons: [
                                {name: 'detail', text: '详情', title: '详情', classname: 'btn btn-xs btn-info btn-dialog', icon: 'fa fa-list', url: 'adincome/redpacket/detail'}
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () {
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

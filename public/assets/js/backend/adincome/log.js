define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格
            Table.init({
                extend: {
                    index_url: 'adincome/log',
                    detail_url: 'adincome/log/detail',
                    del_url: 'adincome/log/del',
                    multi_url: 'adincome/log/multi',
                    table: 'table',
                },
                columns: [
                    [
                        {field: 'state', checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'username', title: '用户名', operate: false},
                        {field: 'nickname', title: '昵称', operate: false},
                        {field: 'ad_type_text', title: '广告类型', operate: false},
                        {field: 'ad_provider_text', title: '广告平台', operate: false},
                        {field: 'amount_coin', title: '总金币', sortable: true},
                        {field: 'platform_amount_coin', title: '平台抽成', operate: false},
                        {field: 'user_amount_coin', title: '用户获得', operate: false},
                        {field: 'status_text', title: '状态', searchList: {0: '待确认', 1: '已确认', 2: '已释放', 3: '已失效'}},
                        {field: 'createtime_text', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate,
                            buttons: [
                                {name: 'detail', text: '详情', title: '详情', classname: 'btn btn-xs btn-info btn-dialog', icon: 'fa fa-list', url: 'adincome/log/detail'}
                            ]
                        }
                    ]
                ]
            });
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'adincome/log/index',
                    detail_url: 'adincome/log/detail',
                    del_url: 'adincome/log/del',
                    multi_url: '',
                    add_url: '',
                    edit_url: '',
                    table: 'ad_income_log',
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
                        {field: 'ad_type_text', title: '广告类型', searchList: {'feed': '信息流广告', 'reward': '激励视频'}},
                        {field: 'ad_provider_text', title: '广告平台', searchList: {'uniad': 'uni-ad', 'csj': '穿山甲', 'ylh': '优量汇'}},
                        {field: 'amount_coin', title: '总金币', sortable: true},
                        {field: 'platform_amount_coin', title: '平台抽成', operate: false},
                        {field: 'user_amount_coin', title: '用户获得', sortable: true},
                        {field: 'status_text', title: '状态', searchList: {0: '待确认', 1: '已确认', 2: '已释放', 3: '已失效'}},
                        {field: 'createtime_text', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate,
                            buttons: [
                                {name: 'detail', text: '详情', title: '详情', classname: 'btn btn-xs btn-info btn-dialog', icon: 'fa fa-list', url: 'adincome/log/detail'}
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

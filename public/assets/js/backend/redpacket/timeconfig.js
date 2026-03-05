define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/timeconfig/index',
                    add_url: 'redpacket/timeconfig/add',
                    edit_url: 'redpacket/timeconfig/edit',
                    del_url: 'redpacket/timeconfig/del',
                    multi_url: 'redpacket/timeconfig/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'start_hour',
                sortOrder: 'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'name', title: __('配置名称'), operate: 'LIKE', align: 'left'},
                        {field: 'time_range_text', title: __('时间段'), operate: false, align: 'center'},
                        {field: 'base_min_reward', title: __('老用户基础下限'), sortable: true, operate: false, align: 'right'},
                        {field: 'base_max_reward', title: __('老用户基础上限'), sortable: true, operate: false, align: 'right'},
                        {field: 'accumulate_min_reward', title: __('老用户累加下限'), sortable: true, operate: false, align: 'right', visible: false},
                        {field: 'accumulate_max_reward', title: __('老用户累加上限'), sortable: true, operate: false, align: 'right', visible: false},
                        {field: 'new_user_base_min', title: __('新用户基础下限'), sortable: true, operate: false, align: 'right', visible: false},
                        {field: 'new_user_base_max', title: __('新用户基础上限'), sortable: true, operate: false, align: 'right', visible: false},
                        {field: 'new_user_accumulate_min', title: __('新用户累加下限'), sortable: true, operate: false, align: 'right', visible: false},
                        {field: 'new_user_accumulate_max', title: __('新用户累加上限'), sortable: true, operate: false, align: 'right', visible: false},
                        {field: 'weigh', title: __('权重'), sortable: true, align: 'center'},
                        {field: 'status', title: __('状态'), searchList: {"normal":__('正常'),"hidden":__('禁用')}, formatter: Table.api.formatter.status, align: 'center'},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, align: 'center'}
                    ]
                ],
                pagination: false,
                search: false,
                commonSearch: false,
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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
            }
        }
    };
    return Controller;
});

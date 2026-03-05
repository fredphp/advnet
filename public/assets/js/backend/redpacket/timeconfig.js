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
                        {field: 'name', title: '配置名称', operate: 'LIKE'},
                        {field: 'time_range_text', title: '时间段', operate: false},
                        {field: 'base_min_reward', title: '基础奖励下限', sortable: true, operate: false},
                        {field: 'base_max_reward', title: '基础奖励上限', sortable: true, operate: false},
                        {field: 'accumulate_min_reward', title: '累加奖励下限', sortable: true, operate: false},
                        {field: 'accumulate_max_reward', title: '累加奖励上限', sortable: true, operate: false},
                        {field: 'new_user_base_min', title: '新用户基础下限', sortable: true, operate: false, visible: false},
                        {field: 'new_user_base_max', title: '新用户基础上限', sortable: true, operate: false, visible: false},
                        {field: 'status', title: '状态', searchList: {"normal":"正常","hidden":"禁用"}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: '权重', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
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

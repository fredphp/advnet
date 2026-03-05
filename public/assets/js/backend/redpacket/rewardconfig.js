define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/rewardconfig/index',
                    add_url: 'redpacket/rewardconfig/add',
                    edit_url: 'redpacket/rewardconfig/edit',
                    del_url: 'redpacket/rewardconfig/del',
                    multi_url: 'redpacket/rewardconfig/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {field: 'name', title: __('配置名称'), operate: 'LIKE', align: 'left'},
                        {field: 'time_range_text', title: __('时间段'), operate: false, align: 'center', width: 120},
                        {field: 'today_amount_range_text', title: __('今日金额限制'), operate: false, align: 'center', width: 120},
                        {field: 'base_reward_range_text', title: __('老用户基础奖励'), operate: false, align: 'right', width: 100},
                        {field: 'accumulate_reward_range_text', title: __('老用户累加奖励'), operate: false, align: 'right', width: 100},
                        {field: 'new_user_base_range_text', title: __('新用户基础奖励'), operate: false, align: 'right', width: 100, visible: false},
                        {field: 'new_user_accumulate_range_text', title: __('新用户累加奖励'), operate: false, align: 'right', width: 100, visible: false},
                        {field: 'weigh', title: __('权重'), sortable: true, align: 'center', width: 60},
                        {field: 'status', title: __('状态'), searchList: {"normal":__('正常'),"hidden":__('禁用')}, formatter: Table.api.formatter.status, align: 'center', width: 80},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, align: 'center', width: 100}
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

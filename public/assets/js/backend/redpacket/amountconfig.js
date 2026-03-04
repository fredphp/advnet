define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/amountconfig/index',
                    add_url: 'redpacket/amountconfig/add',
                    edit_url: 'redpacket/amountconfig/edit',
                    del_url: 'redpacket/amountconfig/del',
                    multi_url: 'redpacket/amountconfig/multi',
                    table: 'red_packet_amount_config',
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
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'config_type', title: '配置类型', searchList: {
                            "new_user": "新用户红包",
                            "tier": "阶梯配置"
                        }, formatter: Table.api.formatter.status},
                        {field: 'name', title: '配置名称', operate: 'LIKE'},
                        {field: 'today_range_text', title: '今日领取区间', operate: false},
                        {field: 'base_reward_range_text', title: '基础奖励区间', operate: false},
                        {field: 'accumulate_reward_range_text', title: '累加奖励区间', operate: false},
                        {field: 'weigh', title: '排序权重', sortable: true},
                        {field: 'status', title: '状态', searchList: {"normal": "正常", "hidden": "禁用"}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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

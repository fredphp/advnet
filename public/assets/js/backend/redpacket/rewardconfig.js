define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/rewardconfig/index' + location.search,
                    add_url: 'redpacket/rewardconfig/add',
                    edit_url: 'redpacket/rewardconfig/edit',
                    del_url: 'redpacket/rewardconfig/del',
                    multi_url: 'redpacket/rewardconfig/multi',
                    table: 'red_packet_reward_config',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'min_amount', title: '今日金额下限', operate: false},
                        {field: 'max_amount', title: '今日金额上限', operate: false},
                        {field: 'start_time', title: '开始时间', operate: false},
                        {field: 'end_time', title: '结束时间', operate: false},
                        {field: 'base_min', title: '基础金额下限', operate: false},
                        {field: 'base_max', title: '基础金额上限', operate: false},
                        {field: 'accumulate_min', title: '累加金额下限', operate: false},
                        {field: 'accumulate_max', title: '累加金额上限', operate: false},
                        {field: 'max_reward', title: '封顶金额', operate: false},
                        {field: 'status', title: __('Status'), searchList: {"0": __('Disabled'), "1": __('Enabled')}, formatter: Table.api.formatter.status},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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

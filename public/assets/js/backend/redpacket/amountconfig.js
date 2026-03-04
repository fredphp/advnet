define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/amountconfig/index' + location.search,
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
                        {field: 'id', title: __('ID'), sortable: true},
                        {field: 'config_type_text', title: '配置类型', operate: false, searchList: {"new_user": "新用户红包", "base_amount": "基础额度", "accumulate_amount": "累加额度"}},
                        {field: 'name', title: '配置名称', operate: 'LIKE'},
                        {field: 'today_range_text', title: '今日领取区间', operate: false},
                        {field: 'reward_range_text', title: '奖励金额区间', operate: false},
                        {field: 'weigh', title: '权重', sortable: true},
                        {field: 'status_text', title: '状态', searchList: {"normal": "正常", "hidden": "禁用"}},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime, sortable: true},
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

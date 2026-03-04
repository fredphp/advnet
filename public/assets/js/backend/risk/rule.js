define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/rule/index',
                    add_url: 'risk/rule/add',
                    edit_url: 'risk/rule/edit',
                    del_url: 'risk/rule/del',
                    multi_url: 'risk/rule/multi',
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
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'name', title: '规则名称', operate: 'LIKE'},
                        {field: 'rule_code', title: '规则代码', operate: 'LIKE'},
                        {field: 'rule_type', title: '规则类型', searchList: {
                            "video": "视频",
                            "task": "任务",
                            "withdraw": "提现",
                            "redpacket": "红包",
                            "invite": "邀请",
                            "global": "全局"
                        }},
                        {field: 'threshold', title: '阈值', operate: 'BETWEEN', sortable: true},
                        {field: 'score_weight', title: '风险权重', operate: 'BETWEEN', sortable: true},
                        {field: 'action', title: '处理动作', searchList: {
                            "warn": "警告",
                            "block": "拦截",
                            "freeze": "冻结",
                            "ban": "封禁"
                        }},
                        {field: 'action_duration', title: '处罚时长(秒)'},
                        {field: 'enabled', title: '状态', searchList: {"0": "禁用", "1": "启用"}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: '权重', sortable: true},
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

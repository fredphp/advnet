define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/risklog/index',
                    detail_url: 'withdraw/risklog/detail',
                    del_url: 'withdraw/risklog/del',
                    multi_url: 'withdraw/risklog/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'rule_code', title: '规则代码'},
                        {field: 'rule_name', title: '规则名称'},
                        {field: 'risk_type', title: '风险类型', searchList: {
                            "video": "视频",
                            "task": "任务",
                            "withdraw": "提现",
                            "redpacket": "红包",
                            "invite": "邀请",
                            "global": "全局"
                        }},
                        {field: 'trigger_value', title: '触发值'},
                        {field: 'threshold', title: '阈值'},
                        {field: 'score_add', title: '增加分数'},
                        {field: 'action', title: '处理动作', searchList: {
                            "warn": "警告",
                            "block": "拦截",
                            "freeze": "冻结",
                            "ban": "封禁"
                        }},
                        {field: 'ip', title: 'IP地址', operate: 'LIKE'},
                        {field: 'createtime', title: '时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

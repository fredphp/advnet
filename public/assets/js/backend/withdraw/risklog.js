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
                        }, formatter: function(value, row, index) {
                            var map = {
                                "video": '<span class="label label-info">视频</span>',
                                "task": '<span class="label label-primary">任务</span>',
                                "withdraw": '<span class="label label-warning">提现</span>',
                                "redpacket": '<span class="label label-danger">红包</span>',
                                "invite": '<span class="label label-success">邀请</span>',
                                "global": '<span class="label label-default">全局</span>'
                            };
                            return map[value] || '<span class="label label-default">' + value + '</span>';
                        }},
                        {field: 'trigger_value', title: '触发值'},
                        {field: 'threshold', title: '阈值'},
                        {field: 'score_add', title: '增加分数'},
                        {field: 'risk_level', title: '风险等级', searchList: {
                            "1": "低风险",
                            "2": "中风险",
                            "3": "高风险"
                        }, formatter: function(value, row, index) {
                            var map = {
                                1: '<span class="label label-success">低风险</span>',
                                2: '<span class="label label-warning">中风险</span>',
                                3: '<span class="label label-danger">高风险</span>'
                            };
                            return map[value] || '<span class="label label-default">未知</span>';
                        }},
                        {field: 'handle_action', title: '处理动作', searchList: {
                            "pass": "通过",
                            "review": "人工审核",
                            "reject": "拒绝",
                            "freeze": "冻结"
                        }, formatter: function(value, row, index) {
                            var map = {
                                "pass": '<span class="label label-success">通过</span>',
                                "review": '<span class="label label-info">人工审核</span>',
                                "reject": '<span class="label label-warning">拒绝</span>',
                                "freeze": '<span class="label label-danger">冻结</span>'
                            };
                            return map[value] || '<span class="label label-default">' + value + '</span>';
                        }},
                        {field: 'ip', title: 'IP地址', operate: 'LIKE'},
                        {field: 'createtime', title: '时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate',
                            title: '操作',
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '风控记录详情',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'withdraw/risklog/detail',
                                    extend: 'data-area=\'["90%","90%"]\''
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 下拉菜单筛选功能
            $(document).on('click', '.dropdown-menu a[data-field]', function(e) {
                e.preventDefault();
                var field = $(this).data('field');
                var value = $(this).data('value');
                var options = table.bootstrapTable('getOptions');
                options.queryParams = function(params) {
                    params[field] = value;
                    return params;
                };
                table.bootstrapTable('refresh', {query: {offset: 0}});
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

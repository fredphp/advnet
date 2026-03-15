define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'risk/user_risk/index',
                    detail_url: 'risk/user_risk/detail',
                    del_url: 'risk/user_risk/del',
                    table: 'user_risk_score',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'total_score',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'user_id', title: '用户ID', sortable: true, width: 80},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'total_score', title: '风险分', sortable: true, width: 80,
                            formatter: function(value, row, index) {
                                var color = value >= 100 ? '#dc2626' : (value >= 50 ? '#f59e0b' : '#10b981');
                                return '<span style="color:' + color + ';font-weight:600;">' + value + '</span>';
                            }
                        },
                        {field: 'risk_level', title: '风险等级', width: 80,
                            formatter: function(value, row, index) {
                                var levelMap = {
                                    'safe': '<span class="badge" style="background:#d1fae5;color:#047857;">安全</span>',
                                    'low': '<span class="badge" style="background:#d1fae5;color:#047857;">低风险</span>',
                                    'medium': '<span class="badge" style="background:#fef3c7;color:#b45309;">中风险</span>',
                                    'high': '<span class="badge" style="background:#fee2e2;color:#b91c1c;">高风险</span>',
                                    'dangerous': '<span class="badge" style="background:#dc2626;color:#fff;">危险</span>'
                                };
                                return levelMap[value] || value;
                            }
                        },
                        {field: 'status', title: '状态', width: 80,
                            searchList: {"normal":"正常","frozen":"冻结","banned":"封禁"},
                            formatter: function(value, row, index) {
                                var statusMap = {
                                    'normal': '<span class="badge" style="background:#d1fae5;color:#047857;">正常</span>',
                                    'frozen': '<span class="badge" style="background:#fef3c7;color:#b45309;">冻结</span>',
                                    'banned': '<span class="badge" style="background:#fee2e2;color:#b91c1c;">封禁</span>'
                                };
                                return statusMap[value] || value;
                            }
                        },
                        {field: 'violation_count', title: '违规次数', sortable: true, width: 80},
                        {field: 'last_violation_time', title: '最后违规时间', formatter: Table.api.formatter.datetime, sortable: true, width: 150},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate, width: 150}
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

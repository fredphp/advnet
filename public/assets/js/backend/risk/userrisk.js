define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/userrisk/index',
                    detail_url: 'risk/userrisk/detail',
                    del_url: 'risk/userrisk/del',
                    multi_url: 'risk/userrisk/multi',
                    table: '',
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
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'total_score', title: '风险总分', sortable: true, operate: 'BETWEEN'},
                        {field: 'video_score', title: '视频风险分', visible: false},
                        {field: 'task_score', title: '任务风险分', visible: false},
                        {field: 'withdraw_score', title: '提现风险分', visible: false},
                        {field: 'redpacket_score', title: '红包风险分', visible: false},
                        {field: 'invite_score', title: '邀请风险分', visible: false},
                        {field: 'global_score', title: '全局风险分', visible: false},
                        {field: 'risk_level', title: '风险等级', searchList: {
                            "safe": "安全",
                            "low": "低",
                            "medium": "中",
                            "high": "高",
                            "dangerous": "危险"
                        }, formatter: Controller.api.formatter.riskLevel},
                        {field: 'status', title: '状态', searchList: {
                            "normal": "正常",
                            "frozen": "冻结",
                            "banned": "封禁"
                        }, formatter: Table.api.formatter.status},
                        {field: 'violation_count', title: '违规次数', sortable: true},
                        {field: 'last_violation_time', title: '最后违规时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'updatetime', title: '更新时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
            },
            formatter: {
                riskLevel: function (value, row, index) {
                    var colorMap = {
                        'safe': 'success',
                        'low': 'info',
                        'medium': 'warning',
                        'high': 'danger',
                        'dangerous': 'danger'
                    };
                    var textMap = {
                        'safe': '安全',
                        'low': '低',
                        'medium': '中',
                        'high': '高',
                        'dangerous': '危险'
                    };
                    return '<span class="label label-' + colorMap[value] + '">' + textMap[value] + '</span>';
                }
            }
        }
    };
    return Controller;
});

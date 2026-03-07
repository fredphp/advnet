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

            // 风险类型映射
            var riskTypeMap = {
                'video': '视频',
                'task': '任务',
                'withdraw': '提现',
                'redpacket': '红包',
                'invite': '邀请',
                'global': '全局'
            };

            // 处理动作映射
            var actionMap = {
                '': '待处理',
                'pass': '通过',
                'review': '人工审核',
                'reject': '拒绝',
                'freeze': '冻结'
            };

            // 风险等级映射
            var riskLevelMap = {
                0: '普通',
                1: '低风险',
                2: '中风险',
                3: '高风险'
            };

            // 风险类型格式化
            var riskTypeFormatter = function (value, row, index) {
                return riskTypeMap[value] || value || '-';
            };

            // 处理状态格式化
            var actionFormatter = function (value, row, index) {
                var text = actionMap[value] || '待处理';
                var className = '';
                switch (value) {
                    case 'pass':
                        className = 'label label-success';
                        break;
                    case 'review':
                        className = 'label label-warning';
                        break;
                    case 'reject':
                        className = 'label label-danger';
                        break;
                    case 'freeze':
                        className = 'label label-info';
                        break;
                    default:
                        className = 'label label-default';
                }
                return '<span class="' + className + '">' + text + '</span>';
            };

            // 风险等级格式化
            var riskLevelFormatter = function (value, row, index) {
                var level = parseInt(value) || 0;
                var text = riskLevelMap[level] || '普通';
                var className = '';
                switch (level) {
                    case 1:
                        className = 'label label-info';
                        break;
                    case 2:
                        className = 'label label-warning';
                        break;
                    case 3:
                        className = 'label label-danger';
                        break;
                    default:
                        className = 'label label-default';
                }
                return '<span class="' + className + '">' + text + '</span>';
            };

            // 行样式（根据风险等级变色）
            var rowStyle = function (row, index) {
                var level = parseInt(row.risk_level) || 0;
                switch (level) {
                    case 1:
                        return { classes: 'info' }; // 蓝色
                    case 2:
                        return { classes: 'warning' }; // 橘色
                    case 3:
                        return { classes: 'danger' }; // 红色
                    default:
                        return {};
                }
            };

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                rowStyle: rowStyle,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 80},
                        {field: 'user_id', title: '用户ID', sortable: true, width: 80},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'risk_type', title: '风险类型', searchList: riskTypeMap, formatter: riskTypeFormatter},
                        {field: 'risk_level', title: '风险等级', searchList: riskLevelMap, formatter: riskLevelFormatter},
                        {field: 'risk_score', title: '风险分', width: 80},
                        {field: 'ip', title: 'IP地址', operate: 'LIKE'},
                        {field: 'handle_action', title: '处理状态', searchList: actionMap, formatter: actionFormatter},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('详情'),
                                    title: __('风控记录详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'withdraw/risklog/detail',
                                    extend: 'data-area=\'["80%","80%"]\''
                                },
                                {
                                    name: 'pass',
                                    text: __('通过'),
                                    title: __('确认通过'),
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-check',
                                    url: 'withdraw/risklog/pass',
                                    confirm: '确认通过该记录？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                        Toastr.success(ret.msg);
                                    },
                                    error: function(data, ret) {
                                        Toastr.error(ret.msg);
                                    },
                                    hidden: function(row) {
                                        return row.handle_action && row.handle_action !== '';
                                    }
                                },
                                {
                                    name: 'review',
                                    text: __('人工审核'),
                                    title: __('确认人工审核'),
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-user',
                                    url: 'withdraw/risklog/review',
                                    confirm: '确认标记为人工审核？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                        Toastr.success(ret.msg);
                                    },
                                    error: function(data, ret) {
                                        Toastr.error(ret.msg);
                                    },
                                    hidden: function(row) {
                                        return row.handle_action && row.handle_action !== '';
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: __('拒绝'),
                                    title: __('拒绝原因'),
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-ban',
                                    url: 'withdraw/risklog/reject',
                                    confirm: '确认拒绝该记录？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                        Toastr.success(ret.msg);
                                    },
                                    error: function(data, ret) {
                                        Toastr.error(ret.msg);
                                    },
                                    hidden: function(row) {
                                        return row.handle_action && row.handle_action !== '';
                                    }
                                },
                                {
                                    name: 'freeze',
                                    text: __('冻结'),
                                    title: __('确认冻结用户'),
                                    classname: 'btn btn-xs btn-default btn-ajax',
                                    icon: 'fa fa-snowflake-o',
                                    url: 'withdraw/risklog/freeze',
                                    confirm: '确认冻结该用户？冻结后用户将无法登录！',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                        Toastr.success(ret.msg);
                                    },
                                    error: function(data, ret) {
                                        Toastr.error(ret.msg);
                                    },
                                    hidden: function(row) {
                                        return row.handle_action && row.handle_action !== '';
                                    }
                                }
                            ],
                            width: 200
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

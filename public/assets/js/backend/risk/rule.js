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

            // 规则类型图标映射
            var typeIcons = {
                'video': 'fa fa-video-camera',
                'task': 'fa fa-tasks',
                'withdraw': 'fa fa-money',
                'redpacket': 'fa fa-gift',
                'invite': 'fa fa-user-plus',
                'global': 'fa fa-globe'
            };
            
            // 规则类型中文名
            var typeNames = {
                'video': '视频',
                'task': '任务',
                'withdraw': '提现',
                'redpacket': '红包',
                'invite': '邀请',
                'global': '全局'
            };
            
            // 处理动作图标映射
            var actionIcons = {
                'warn': 'fa fa-exclamation-triangle',
                'block': 'fa fa-ban',
                'freeze': 'fa fa-snowflake-o',
                'ban': 'fa fa-lock'
            };
            
            // 处理动作中文名
            var actionNames = {
                'warn': '警告',
                'block': '拦截',
                'freeze': '冻结',
                'ban': '封禁'
            };

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                responseHandler: function(res) {
                    // 生成统计卡片
                    if (res && res.rows) {
                        Controller.renderStats(res.rows);
                    }
                    return res;
                },
                columns: [
                    [
                        {checkbox: true},
                        {
                            field: 'id', 
                            title: 'ID', 
                            sortable: true,
                            width: '60px',
                            formatter: function(value) {
                                return '<span style="font-weight:600; color:#667eea;">#' + value + '</span>';
                            }
                        },
                        {
                            field: 'rule_name', 
                            title: '规则名称', 
                            operate: 'LIKE',
                            formatter: function(value, row) {
                                var icon = typeIcons[row.rule_type] || 'fa fa-shield';
                                return '<div style="display:flex; align-items:center; gap:8px;">' +
                                    '<i class="' + icon + '" style="color:#667eea; font-size:16px;"></i>' +
                                    '<span style="font-weight:500;">' + value + '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            field: 'rule_code', 
                            title: '规则代码', 
                            operate: 'LIKE',
                            formatter: function(value) {
                                return '<code class="rule-code">' + value + '</code>';
                            }
                        },
                        {
                            field: 'rule_type', 
                            title: '规则类型', 
                            searchList: typeNames,
                            formatter: function(value) {
                                var icon = typeIcons[value] || 'fa fa-tag';
                                var name = typeNames[value] || value;
                                return '<span class="rule-type-badge ' + value + '">' +
                                    '<i class="' + icon + '"></i>' + name + '</span>';
                            }
                        },
                        {
                            field: 'description', 
                            title: '描述', 
                            operate: false,
                            formatter: function(value) {
                                if (!value) return '<span class="text-muted">-</span>';
                                return '<span class="desc-text" title="' + value + '">' + value + '</span>';
                            }
                        },
                        {
                            field: 'threshold', 
                            title: '阈值', 
                            operate: 'BETWEEN', 
                            sortable: true,
                            formatter: function(value) {
                                return '<span class="threshold-value">' + parseFloat(value).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'score_weight', 
                            title: '风险权重', 
                            operate: 'BETWEEN', 
                            sortable: true,
                            formatter: function(value) {
                                var percent = Math.min(value, 100);
                                var barClass = 'weight-low';
                                if (percent >= 50) barClass = 'weight-medium';
                                if (percent >= 80) barClass = 'weight-high';
                                
                                return '<div class="weight-progress">' +
                                    '<div class="weight-bar"><div class="weight-bar-fill ' + barClass + '" style="width:' + percent + '%;"></div></div>' +
                                    '<span style="font-size:12px; font-weight:500; color:#495057; min-width:30px;">' + value + '</span>' +
                                    '</div>';
                            }
                        },
                        {
                            field: 'action', 
                            title: '处理动作', 
                            searchList: actionNames,
                            formatter: function(value) {
                                var icon = actionIcons[value] || 'fa fa-cog';
                                var name = actionNames[value] || value;
                                return '<span class="action-badge ' + value + '">' +
                                    '<i class="' + icon + '"></i>' + name + '</span>';
                            }
                        },
                        {
                            field: 'action_duration', 
                            title: '处罚时长',
                            formatter: function(value, row) {
                                if (!value || value == 0) {
                                    return '<span class="duration-tag permanent"><i class="fa fa-infinity"></i> 永久</span>';
                                }
                                var duration = parseInt(value);
                                var text = '';
                                var icon = 'fa fa-clock-o';
                                if (duration < 60) {
                                    text = duration + '秒';
                                } else if (duration < 3600) {
                                    text = Math.floor(duration / 60) + '分钟';
                                } else if (duration < 86400) {
                                    text = Math.floor(duration / 3600) + '小时';
                                    icon = 'fa fa-hourglass-half';
                                } else {
                                    text = Math.floor(duration / 86400) + '天';
                                    icon = 'fa fa-calendar';
                                }
                                return '<span class="duration-tag"><i class="' + icon + '"></i> ' + text + '</span>';
                            }
                        },
                        {
                            field: 'enabled', 
                            title: '状态', 
                            searchList: {"0": "禁用", "1": "启用"}, 
                            formatter: function(value) {
                                if (value == 1) {
                                    return '<span class="status-enabled">启用中</span>';
                                }
                                return '<span class="status-disabled">已禁用</span>';
                            }
                        },
                        {
                            field: 'level', 
                            title: '优先级', 
                            sortable: true,
                            width: '80px',
                            formatter: function(value) {
                                var level = Math.min(Math.max(value, 1), 10);
                                return '<span class="level-badge level-' + level + '">' + level + '</span>';
                            }
                        },
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        
        // 渲染统计卡片
        renderStats: function(rows) {
            var stats = {
                video: { total: 0, enabled: 0 },
                task: { total: 0, enabled: 0 },
                withdraw: { total: 0, enabled: 0 },
                redpacket: { total: 0, enabled: 0 },
                invite: { total: 0, enabled: 0 },
                global: { total: 0, enabled: 0 }
            };
            
            var typeIcons = {
                'video': 'fa fa-video-camera',
                'task': 'fa fa-tasks',
                'withdraw': 'fa fa-money',
                'redpacket': 'fa fa-gift',
                'invite': 'fa fa-user-plus',
                'global': 'fa fa-globe'
            };
            
            var typeNames = {
                'video': '视频规则',
                'task': '任务规则',
                'withdraw': '提现规则',
                'redpacket': '红包规则',
                'invite': '邀请规则',
                'global': '全局规则'
            };
            
            // 统计各类型规则数量
            rows.forEach(function(row) {
                if (stats[row.rule_type]) {
                    stats[row.rule_type].total++;
                    if (row.enabled == 1) {
                        stats[row.rule_type].enabled++;
                    }
                }
            });
            
            // 生成HTML
            var html = '';
            Object.keys(stats).forEach(function(type) {
                var data = stats[type];
                html += '<div class="stat-card ' + type + '">' +
                    '<i class="stat-icon ' + typeIcons[type] + '"></i>' +
                    '<div class="stat-value">' + data.total + '</div>' +
                    '<div class="stat-label">' + typeNames[type] + '</div>' +
                    '<div class="stat-status"><i class="fa fa-check-circle"></i> 已启用 ' + data.enabled + ' 条</div>' +
                    '</div>';
            });
            
            $('#rule-stats').html(html);
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

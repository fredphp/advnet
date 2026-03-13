define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/banrecord/index',
                    del_url: 'risk/banrecord/del',
                    multi_url: 'risk/banrecord/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {
                            field: 'user_info', 
                            title: '用户信息', 
                            operate: false,
                            formatter: Controller.api.formatter.userInfo
                        },
                        {field: 'ban_reason', title: '封禁原因', operate: 'LIKE', formatter: Controller.api.formatter.reason},
                        {field: 'ban_type', title: '封禁类型', searchList: {
                            "temporary": "临时封禁",
                            "permanent": "永久封禁"
                        }, formatter: Controller.api.formatter.banType},
                        {field: 'ban_source', title: '封禁来源', searchList: {
                            "auto": "系统自动",
                            "manual": "手动封禁"
                        }, formatter: Controller.api.formatter.banSource},
                        {field: 'duration', title: '封禁时长', formatter: Controller.api.formatter.duration},
                        {field: 'end_time', title: '解封时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'admin_name', title: '操作人'},
                        {field: 'status', title: '状态', searchList: {
                            "active": "封禁中",
                            "released": "已解封",
                            "expired": "已过期"
                        }, formatter: Controller.api.formatter.status},
                        {field: 'createtime', title: '封禁时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Controller.api.formatter.operate}
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
                // 用户信息（合并显示用户名、昵称、手机号）
                userInfo: function (value, row, index) {
                    var html = '<div style="line-height: 1.6;">';
                    
                    // 用户名
                    if (row.username) {
                        html += '<div><strong>' + row.username + '</strong>';
                        if (row.nickname) {
                            html += ' <small class="text-muted">(' + row.nickname + ')</small>';
                        }
                        html += '</div>';
                    } else {
                        html += '<div>-</div>';
                    }
                    
                    // 手机号
                    if (row.mobile) {
                        html += '<div><small class="text-muted"><i class="fa fa-phone"></i> ' + row.mobile + '</small></div>';
                    }
                    
                    html += '</div>';
                    return html;
                },
                // 封禁原因
                reason: function (value, row, index) {
                    if (!value) return '-';
                    // 显示完整原因，鼠标悬停显示详情
                    return '<span title="' + value + '">' + (value.length > 20 ? value.substr(0, 20) + '...' : value) + '</span>';
                },
                // 封禁类型
                banType: function (value, row, index) {
                    var types = {
                        'temporary': '<span class="label label-warning">临时封禁</span>',
                        'permanent': '<span class="label label-danger">永久封禁</span>'
                    };
                    return types[value] || '<span class="label label-default">' + value + '</span>';
                },
                // 封禁来源
                banSource: function (value, row, index) {
                    var sources = {
                        'auto': '<span class="label label-info">系统自动</span>',
                        'manual': '<span class="label label-primary">手动封禁</span>'
                    };
                    return sources[value] || '<span class="label label-default">' + value + '</span>';
                },
                // 封禁时长
                duration: function (value, row, index) {
                    if (!value || value == 0) {
                        if (row.ban_type === 'permanent') {
                            return '<span class="text-danger">永久</span>';
                        }
                        return '-';
                    }
                    // 转换为可读格式
                    var days = Math.floor(value / 86400);
                    var hours = Math.floor((value % 86400) / 3600);
                    var minutes = Math.floor((value % 3600) / 60);

                    var result = [];
                    if (days > 0) result.push(days + '天');
                    if (hours > 0) result.push(hours + '小时');
                    if (minutes > 0) result.push(minutes + '分钟');

                    return result.join('') || value + '秒';
                },
                // 状态
                status: function (value, row, index) {
                    var statuses = {
                        'active': '<span class="label label-danger">封禁中</span>',
                        'released': '<span class="label label-success">已解封</span>',
                        'expired': '<span class="label label-default">已过期</span>'
                    };
                    return statuses[value] || '<span class="label label-default">' + value + '</span>';
                },
                // 操作按钮
                operate: function (value, row, index) {
                    var table = this.table;
                    var buttons = [];

                    // 查看详情按钮（包含用户信息）
                    buttons.push({
                        name: 'viewdetail',
                        text: '查看详情',
                        title: '封禁详情',
                        classname: 'btn btn-xs btn-info btn-dialog',
                        icon: 'fa fa-eye',
                        url: 'risk/banrecord/viewDetail?ids=' + row.id,
                        extend: 'data-area=\'["900px","700px"]\''
                    });

                    // 只有封禁中的记录才显示解封按钮
                    if (row.status === 'active') {
                        buttons.push({
                            name: 'release',
                            text: '解封',
                            title: '解封确认',
                            classname: 'btn btn-xs btn-success btn-dialog',
                            icon: 'fa fa-unlock',
                            url: 'risk/banrecord/releaseDialog?ids=' + row.id,
                            extend: 'data-area=\'["500px","350px"]\''
                        });
                    }

                    // 生成按钮HTML
                    var html = [];
                    $.each(buttons, function (i, btn) {
                        var icon = btn.icon ? '<i class="fa ' + btn.icon + '"></i> ' : '';
                        var className = btn.classname || 'btn btn-xs btn-default';
                        var extend = btn.extend || '';
                        html.push('<a href="javascript:;" class="' + className + '" data-url="' + btn.url + '" data-title="' + btn.title + '" ' + extend + '>' + icon + btn.text + '</a>');
                    });

                    return html.join(' ');
                }
            }
        }
    };
    return Controller;
});

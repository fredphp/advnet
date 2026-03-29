define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/blacklist/index',
                    add_url: 'risk/blacklist/add',
                    edit_url: 'risk/blacklist/edit',
                    del_url: 'risk/blacklist/del',
                    multi_url: 'risk/blacklist/multi',
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
                        {field: 'id', title: 'ID', sortable: true, width: '60px'},
                        {
                            field: 'type', 
                            title: '类型', 
                            width: '100px',
                            searchList: {
                                "user": "用户",
                                "ip": "IP地址",
                                "device": "设备ID",
                                "phone": "手机号"
                            },
                            formatter: Controller.api.formatter.type
                        },
                        {
                            field: 'value', 
                            title: '值', 
                            operate: 'LIKE',
                            formatter: Controller.api.formatter.value
                        },
                        {
                            field: 'reason', 
                            title: '原因', 
                            operate: 'LIKE',
                            formatter: Controller.api.formatter.reason
                        },
                        {
                            field: 'source', 
                            title: '来源', 
                            width: '100px',
                            searchList: {
                                "manual": "手动添加",
                                "auto": "系统自动",
                                "import": "批量导入"
                            },
                            formatter: Controller.api.formatter.source
                        },
                        {
                            field: 'expire_time', 
                            title: '过期时间', 
                            width: '150px',
                            formatter: Controller.api.formatter.expireTime, 
                            operate: 'RANGE', 
                            addclass: 'datetimerange'
                        },
                        {
                            field: 'enabled', 
                            title: '状态', 
                            width: '80px',
                            searchList: {"0": "禁用", "1": "启用"}, 
                            formatter: Controller.api.formatter.status
                        },
                        {
                            field: 'admin_name', 
                            title: '操作人', 
                            width: '100px'
                        },
                        {
                            field: 'createtime', 
                            title: '创建时间', 
                            width: '150px',
                            formatter: Table.api.formatter.datetime, 
                            operate: 'RANGE', 
                            addclass: 'datetimerange', 
                            sortable: true
                        },
                        {
                            field: 'operate', 
                            title: '操作', 
                            width: '120px',
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Controller.api.formatter.operate
                        }
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
            },
            formatter: {
                // 类型格式化
                type: function (value, row, index) {
                    var types = {
                        'user': '<span class="label label-primary"><i class="fa fa-user"></i> 用户</span>',
                        'ip': '<span class="label label-info"><i class="fa fa-globe"></i> IP地址</span>',
                        'device': '<span class="label label-warning"><i class="fa fa-mobile"></i> 设备ID</span>',
                        'phone': '<span class="label label-default"><i class="fa fa-phone"></i> 手机号</span>'
                    };
                    return types[value] || '<span class="label label-default">' + value + '</span>';
                },
                // 值格式化
                value: function (value, row, index) {
                    if (!value) return '-';
                    
                    // 如果是用户类型，尝试显示用户信息
                    if (row.type === 'user' && row.user_info) {
                        var user = row.user_info;
                        var html = '<div style="line-height: 1.5;">';
                        html += '<div><strong>ID: ' + value + '</strong></div>';
                        if (user.username) {
                            html += '<div><small>' + user.username;
                            if (user.nickname) {
                                html += ' (' + user.nickname + ')';
                            }
                            html += '</small></div>';
                        }
                        if (user.mobile) {
                            html += '<div><small class="text-muted"><i class="fa fa-phone"></i> ' + user.mobile + '</small></div>';
                        }
                        html += '</div>';
                        return html;
                    }
                    
                    // IP类型高亮显示
                    if (row.type === 'ip') {
                        return '<code style="background:#f8f9fa;padding:2px 6px;border-radius:3px;">' + value + '</code>';
                    }
                    
                    return value;
                },
                // 原因格式化
                reason: function (value, row, index) {
                    if (!value) return '<span class="text-muted">-</span>';
                    if (value.length > 30) {
                        return '<span title="' + value + '">' + value.substr(0, 30) + '...</span>';
                    }
                    return value;
                },
                // 来源格式化
                source: function (value, row, index) {
                    var sources = {
                        'auto': '<span class="label label-success"><i class="fa fa-cog"></i> 系统自动</span>',
                        'manual': '<span class="label label-primary"><i class="fa fa-hand-pointer-o"></i> 手动添加</span>',
                        'import': '<span class="label label-info"><i class="fa fa-upload"></i> 批量导入</span>'
                    };
                    return sources[value] || '<span class="label label-default">' + value + '</span>';
                },
                // 过期时间格式化
                expireTime: function (value, row, index) {
                    if (!value) {
                        return '<span class="label label-danger"><i class="fa fa-infinity"></i> 永久</span>';
                    }
                    var expireTime = value * 1000;
                    var now = new Date().getTime();
                    var date = new Date(expireTime);
                    var dateStr = date.getFullYear() + '-' + 
                                  String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                                  String(date.getDate()).padStart(2, '0') + ' ' +
                                  String(date.getHours()).padStart(2, '0') + ':' +
                                  String(date.getMinutes()).padStart(2, '0');
                    
                    if (expireTime < now) {
                        return '<span class="text-danger" title="已过期">' + dateStr + '</span>';
                    } else {
                        return '<span class="text-success">' + dateStr + '</span>';
                    }
                },
                // 状态格式化
                status: function (value, row, index) {
                    if (value == 1) {
                        return '<span class="label label-success"><i class="fa fa-check"></i> 启用</span>';
                    } else {
                        return '<span class="label label-default"><i class="fa fa-ban"></i> 禁用</span>';
                    }
                },
                // 操作按钮
                operate: function (value, row, index) {
                    var table = this.table;
                    var buttons = [];
                    
                    // 编辑按钮
                    buttons.push({
                        name: 'edit',
                        text: '编辑',
                        title: '编辑黑名单',
                        classname: 'btn btn-xs btn-success btn-dialog',
                        icon: 'fa fa-edit',
                        url: 'risk/blacklist/edit?ids=' + row.id,
                        extend: 'data-area=\'["600px","450px"]\''
                    });
                    
                    // 删除按钮
                    buttons.push({
                        name: 'del',
                        text: '删除',
                        title: '删除确认',
                        classname: 'btn btn-xs btn-danger btn-ajax',
                        icon: 'fa fa-trash',
                        url: 'risk/blacklist/del?ids=' + row.id,
                        confirm: '确认要删除这条记录吗？',
                        success: function(data, ret) {
                            table.bootstrapTable('refresh');
                        }
                    });
                    
                    // 生成按钮HTML
                    var html = [];
                    $.each(buttons, function (i, btn) {
                        var icon = btn.icon ? '<i class="fa ' + btn.icon + '"></i> ' : '';
                        var className = btn.classname || 'btn btn-xs btn-default';
                        var extend = btn.extend || '';
                        var confirm = btn.confirm ? 'data-confirm="' + btn.confirm + '"' : '';
                        html.push('<a href="javascript:;" class="' + className + '" data-url="' + btn.url + '" data-title="' + btn.title + '" ' + extend + ' ' + confirm + '>' + icon + btn.text + '</a>');
                    });
                    
                    return html.join(' ');
                }
            }
        }
    };
    return Controller;
});

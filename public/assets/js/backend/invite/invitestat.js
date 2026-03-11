define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/invitestat/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'user_invite_stat',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'total_invite_count',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, visible: false},
                        {
                            field: 'user_info', 
                            title: '用户信息', 
                            operate: false,
                            width: '180px',
                            formatter: function(value, row, index) {
                                var avatar = row.user_avatar || '/assets/img/avatar.png';
                                var html = '<div class="user-info-cell" style="cursor:pointer;" onclick="Controller.showInvitees(' + row.user_id + ', \'' + (row.user_nickname || row.username || '-') + '\')">';
                                html += '<img src="' + avatar + '" class="img-circle" style="width:36px;height:36px;margin-right:8px;float:left;">';
                                html += '<div style="float:left;">';
                                html += '<div style="font-weight:bold;color:#333;">' + (row.user_nickname || '-') + '</div>';
                                html += '<small class="text-muted">@' + (row.username || '-') + '</small>';
                                html += '</div></div>';
                                return html;
                            }
                        },
                        {
                            field: 'username',
                            title: '用户名搜索',
                            visible: false,
                            operate: 'LIKE'
                        },
                        {
                            field: 'level1_count',
                            title: '一级邀请',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var count = value || 0;
                                if (count > 0) {
                                    return '<a href="javascript:;" onclick="Controller.showInvitees(' + row.user_id + ', \'' + (row.user_nickname || row.username || '-') + '\')" class="btn btn-xs btn-success">' + count + ' 人 <i class="fa fa-arrow-right"></i></a>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'level2_count',
                            title: '二级邀请',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var count = value || 0;
                                if (count > 0) {
                                    return '<span class="label label-warning">' + count + ' 人</span>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'total_invite_count',
                            title: '总邀请数',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var count = (row.level1_count || 0) + (row.level2_count || 0);
                                if (count > 0) {
                                    return '<span class="label label-primary">' + count + ' 人</span>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'valid_invite_count',
                            title: '有效邀请',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                return value || 0;
                            }
                        },
                        {
                            field: 'today_invite_count',
                            title: '今日邀请',
                            sortable: true,
                            width: '80px',
                            formatter: function(value, row, index) {
                                var count = value || 0;
                                if (count > 0) {
                                    return '<span class="label label-danger">' + count + '</span>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'total_commission',
                            title: '累计佣金',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var val = parseFloat(value || 0);
                                return '<span style="color:#27ae60;">¥' + val.toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'withdrawn_commission',
                            title: '已提现佣金',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var val = parseFloat(value || 0);
                                return '<span style="color:#3498db;">¥' + val.toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'updatetime',
                            title: '更新时间',
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            sortable: true
                        },
                        {
                            field: 'operate',
                            title: '操作',
                            table: table,
                            width: '150px',
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'view_invitees',
                                    text: '查看邀请列表',
                                    title: '查看邀请列表',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-users',
                                    url: 'invite/invitestat/invitees?user_id={user_id}',
                                    callback: function(data) {
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'view_relation',
                                    text: '邀请关系',
                                    title: '邀请关系详情',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-sitemap',
                                    url: 'invite/relation/invitees?parent_id={user_id}',
                                    callback: function(data) {
                                        table.bootstrapTable('refresh');
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        
        // 显示邀请列表弹窗
        showInvitees: function(userId, nickname) {
            Fast.api.open('invite/invitestat/invitees?user_id=' + userId, nickname + ' 的邀请列表', {
                area: ['95%', '90%'],
                callback: function() {
                    // 刷新主表格
                    $("#table").bootstrapTable('refresh');
                }
            });
        },

        // 邀请列表页面
        invitees: function () {
            var userId = Fast.api.query('user_id');
            
            Table.api.init({
                extend: {
                    index_url: 'invite/invitestat/invitees?user_id=' + userId,
                    table: 'invite_relation',
                }
            });

            var table = $("#table");
            var currentLevel = 1; // 当前显示的层级
            var currentParentId = userId; // 当前查看的用户ID
            var parentStack = [{id: userId, level: 1}]; // 导航栈
            
            // 更新面包屑导航
            function updateBreadcrumb() {
                var html = '<ol class="breadcrumb" style="margin-bottom:10px;background:#f5f5f5;padding:10px;border-radius:4px;">';
                for (var i = 0; i < parentStack.length; i++) {
                    if (i === parentStack.length - 1) {
                        html += '<li class="active">' + (parentStack[i].nickname || '用户') + '</li>';
                    } else {
                        html += '<li><a href="javascript:;" onclick="Controller.navigateTo(' + i + ')">' + (parentStack[i].nickname || '用户') + '</a></li>';
                    }
                }
                html += '</ol>';
                $('.panel-heading').find('.breadcrumb').remove();
                $('.panel-heading').append(html);
            }

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                pagination: true,
                sidePagination: 'server',
                pageSize: 15,
                pageList: [10, 15, 20, 50],
                queryParams: function(params) {
                    params.parent_id = currentParentId;
                    return params;
                },
                responseHandler: function(res) {
                    // 更新统计数据
                    if (res.total !== undefined) {
                        $('#level1-count').text(res.level1_count || '-');
                        $('#level2-count').text(res.level2_count || '-');
                    }
                    return res;
                },
                columns: [
                    [
                        {field: 'id', title: 'ID', sortable: true, width: '60px'},
                        {
                            field: 'user_info', 
                            title: '用户信息', 
                            operate: false,
                            width: '160px',
                            formatter: function(value, row, index) {
                                var avatar = row.avatar || '/assets/img/avatar.png';
                                var html = '<div class="user-info-cell">';
                                html += '<img src="' + avatar + '" class="img-circle" style="width:32px;height:32px;margin-right:8px;float:left;">';
                                html += '<div style="float:left;">';
                                html += '<div style="font-weight:bold;">' + (row.nickname || row.username || '-') + '</div>';
                                html += '<small class="text-muted">ID: ' + row.user_id + '</small>';
                                html += '</div></div>';
                                return html;
                            }
                        },
                        {
                            field: 'level_num',
                            title: '关系层级',
                            operate: false,
                            width: '100px',
                            formatter: function(value, row, index) {
                                if (value === 1) {
                                    return '<span class="label label-primary">一级</span>';
                                } else {
                                    return '<span class="label label-warning">二级</span>';
                                }
                            }
                        },
                        {
                            field: 'sub_count',
                            title: '下级数量',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var count = value || 0;
                                if (count > 0) {
                                    return '<a href="javascript:;" onclick="Controller.viewSubInvitees(' + row.user_id + ', \'' + (row.nickname || '用户') + '\')" class="btn btn-xs btn-info">' + count + ' 人 <i class="fa fa-arrow-right"></i></a>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'balance',
                            title: '账户余额',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var val = parseFloat(value || 0);
                                return val.toFixed(2);
                            }
                        },
                        {
                            field: 'spend_total',
                            title: '消费总额',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                return '<span style="color:#e74c3c;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'withdraw_total',
                            title: '提现总额',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                return '<span style="color:#3498db;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'commission_total',
                            title: '产生佣金',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                return '<span style="color:#27ae60;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'createtime',
                            title: '绑定时间',
                            sortable: true,
                            formatter: Table.api.formatter.datetime,
                            operate: 'RANGE',
                            addclass: 'datetimerange'
                        },
                        {
                            field: 'operate',
                            title: '操作',
                            width: '80px',
                            formatter: function(value, row, index) {
                                var count = row.sub_count || 0;
                                if (count > 0) {
                                    return '<a href="javascript:;" onclick="Controller.viewSubInvitees(' + row.user_id + ', \'' + (row.nickname || '用户') + '\')" class="btn btn-xs btn-success"><i class="fa fa-users"></i> 查看下级</a>';
                                }
                                return '-';
                            }
                        }
                    ]
                ]
            });

            Table.api.bindevent(table);
            
            // 返回上级
            window.goBack = function() {
                if (parentStack.length > 1) {
                    parentStack.pop();
                    var prev = parentStack[parentStack.length - 1];
                    currentParentId = prev.id;
                    currentLevel = prev.level;
                    table.bootstrapTable('refresh');
                    updateBreadcrumb();
                }
            };
            
            // 导航到指定层级
            window.navigateTo = function(index) {
                if (index < parentStack.length - 1) {
                    parentStack = parentStack.slice(0, index + 1);
                    var target = parentStack[index];
                    currentParentId = target.id;
                    currentLevel = target.level;
                    table.bootstrapTable('refresh');
                    updateBreadcrumb();
                }
            };
        },
        
        // 查看下级邀请人
        viewSubInvitees: function(userId, nickname) {
            // 添加到导航栈
            if (typeof parentStack !== 'undefined') {
                parentStack.push({id: userId, level: parentStack.length + 1, nickname: nickname});
            }
            
            // 更新当前查看的用户
            currentParentId = userId;
            
            // 更新面包屑
            if (typeof updateBreadcrumb === 'function') {
                updateBreadcrumb();
            }
            
            // 刷新表格
            $("#table").bootstrapTable('refresh', {
                query: {parent_id: userId}
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

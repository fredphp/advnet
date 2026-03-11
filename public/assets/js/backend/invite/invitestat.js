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
                                var html = '<div class="user-info-cell" style="cursor:pointer;" onclick="Controller.showInvitees(' + row.user_id + ', \'' + (row.user_nickname || row.username || '-').replace(/'/g, "\\'") + '\', 1)">';
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
                                    return '<a href="javascript:;" onclick="Controller.showInvitees(' + row.user_id + ', \'' + (row.user_nickname || row.username || '-').replace(/'/g, "\\'") + '\', 1)" class="btn btn-xs btn-success">' + count + ' 人 <i class="fa fa-arrow-right"></i></a>';
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
                                    return '<a href="javascript:;" onclick="Controller.showInvitees(' + row.user_id + ', \'' + (row.user_nickname || row.username || '-').replace(/'/g, "\\'") + '\', 2)" class="btn btn-xs btn-warning">' + count + ' 人 <i class="fa fa-arrow-right"></i></a>';
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
                                    return '<a href="javascript:;" onclick="Controller.showInvitees(' + row.user_id + ', \'' + (row.user_nickname || row.username || '-').replace(/'/g, "\\'") + '\', 0)" class="label label-primary" style="cursor:pointer;">' + count + ' 人</a>';
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
        
        // 显示邀请列表弹窗 (level: 0=全部, 1=一级, 2=二级)
        showInvitees: function(userId, nickname, level) {
            var url = 'invite/invitestat/invitees?user_id=' + userId;
            if (level) {
                url += '&level=' + level;
            }
            Fast.api.open(url, nickname + ' 的邀请列表', {
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
            var initLevel = parseInt(Fast.api.query('level', 0)); // 0=全部, 1=一级, 2=二级
            
            // 使用全局对象来避免闭包问题
            window.InviteesController = {
                userId: userId,
                initLevel: initLevel || 0,
                currentLevel: initLevel || 0,
                currentParentId: userId,
                parentStack: [],
                rootNickname: '',
                table: null,
                
                // 初始化
                init: function() {
                    var self = this;
                    
                    // 获取根用户昵称
                    var rootNickname = $('#breadcrumb-root').text() || '主用户';
                    this.rootNickname = rootNickname;
                    this.parentStack = [{id: userId, nickname: rootNickname}];
                    
                    Table.api.init({
                        extend: {
                            index_url: 'invite/invitestat/invitees?user_id=' + this.userId,
                            table: 'invite_relation',
                        }
                    });

                    this.table = $("#table");
                    
                    // 绑定返回按钮事件
                    $('#btn-back').on('click', function() {
                        self.goBack();
                    });
                    
                    // 初始化表格
                    this.table.bootstrapTable({
                        url: $.fn.bootstrapTable.defaults.extend.index_url,
                        pk: 'id',
                        sortName: 'createtime',
                        sortOrder: 'desc',
                        pagination: true,
                        sidePagination: 'server',
                        pageSize: 15,
                        pageList: [10, 15, 20, 50],
                        queryParams: function(params) {
                            params.parent_id = self.currentParentId;
                            params.level = self.currentLevel;
                            return params;
                        },
                        responseHandler: function(res) {
                            // 更新统计数据
                            if (res.level1_count !== undefined) {
                                $('#level1-count').text(res.level1_count || 0);
                                $('#level2-count').text(res.level2_count || 0);
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
                                        } else if (value === 2) {
                                            return '<span class="label label-warning">二级</span>';
                                        } else {
                                            return '<span class="label label-default">' + (value || '-') + '</span>';
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
                                            return '<a href="javascript:;" onclick="window.InviteesController.viewSubInvitees(' + row.user_id + ', \'' + (row.nickname || '用户').replace(/'/g, "\\'") + '\')" class="btn btn-xs btn-info">' + count + ' 人 <i class="fa fa-arrow-right"></i></a>';
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
                                            return '<a href="javascript:;" onclick="window.InviteesController.viewSubInvitees(' + row.user_id + ', \'' + (row.nickname || '用户').replace(/'/g, "\\'") + '\')" class="btn btn-xs btn-success"><i class="fa fa-users"></i> 查看下级</a>';
                                        }
                                        return '-';
                                    }
                                }
                            ]
                        ]
                    });

                    Table.api.bindevent(this.table);
                },
                
                // 查看下级邀请人
                viewSubInvitees: function(targetUserId, nickname) {
                    // 添加到导航栈
                    this.parentStack.push({
                        id: targetUserId, 
                        nickname: nickname
                    });
                    
                    // 更新当前查看的用户
                    this.currentParentId = targetUserId;
                    this.currentLevel = 0; // 查看下级时显示全部
                    
                    // 更新面包屑导航
                    this.updateBreadcrumb();
                    
                    // 显示返回按钮
                    this.updateBackButton();
                    
                    // 刷新表格
                    this.table.bootstrapTable('refresh');
                },
                
                // 返回上一级
                goBack: function() {
                    if (this.parentStack.length <= 1) {
                        return; // 已经在根级别，无法返回
                    }
                    
                    // 移除当前级别
                    this.parentStack.pop();
                    
                    // 获取上一级
                    var prevLevel = this.parentStack[this.parentStack.length - 1];
                    
                    // 更新当前查看的用户
                    this.currentParentId = prevLevel.id;
                    this.currentLevel = this.parentStack.length === 1 ? this.initLevel : 0;
                    
                    // 更新面包屑导航
                    this.updateBreadcrumb();
                    
                    // 更新返回按钮
                    this.updateBackButton();
                    
                    // 刷新表格
                    this.table.bootstrapTable('refresh');
                },
                
                // 返回到指定层级
                goBackTo: function(index) {
                    if (index < 0 || index >= this.parentStack.length) {
                        return;
                    }
                    
                    // 截断导航栈
                    this.parentStack = this.parentStack.slice(0, index + 1);
                    
                    // 更新当前查看的用户
                    var targetLevel = this.parentStack[index];
                    this.currentParentId = targetLevel.id;
                    this.currentLevel = index === 0 ? this.initLevel : 0;
                    
                    // 更新面包屑导航
                    this.updateBreadcrumb();
                    
                    // 更新返回按钮
                    this.updateBackButton();
                    
                    // 刷新表格
                    this.table.bootstrapTable('refresh');
                },
                
                // 更新返回按钮显示状态
                updateBackButton: function() {
                    if (this.parentStack.length > 1) {
                        $('#btn-back').show();
                    } else {
                        $('#btn-back').hide();
                    }
                },
                
                // 更新面包屑导航
                updateBreadcrumb: function() {
                    var breadcrumbHtml = '<ol class="breadcrumb">';
                    for (var i = 0; i < this.parentStack.length; i++) {
                        var item = this.parentStack[i];
                        if (i === this.parentStack.length - 1) {
                            breadcrumbHtml += '<li class="active">' + item.nickname + '</li>';
                        } else {
                            breadcrumbHtml += '<li><a href="javascript:;" onclick="window.InviteesController.goBackTo(' + i + ')">' + item.nickname + '</a></li>';
                        }
                    }
                    breadcrumbHtml += '</ol>';
                    
                    $('#breadcrumb-container').html(breadcrumbHtml);
                    
                    // 如果有多级导航，显示面包屑容器
                    if (this.parentStack.length > 1) {
                        $('#breadcrumb-container').addClass('has-nav');
                    } else {
                        $('#breadcrumb-container').removeClass('has-nav');
                    }
                }
            };
            
            // 初始化控制器
            window.InviteesController.init();
        },

        // 查看下级邀请人（在弹窗页面调用）
        viewSubInvitees: function(userId, nickname) {
            if (typeof window.InviteesController !== 'undefined' && window.InviteesController.viewSubInvitees) {
                window.InviteesController.viewSubInvitees(userId, nickname);
            }
        },
        
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

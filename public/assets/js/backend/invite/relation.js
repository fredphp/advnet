define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/relation/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'total_invite_count',
                sortOrder: 'desc',
                search: false,
                showToggle: true,
                showColumns: true,
                columns: [
                    [
                        {field: 'id', title: '用户ID', sortable: true, width: '70px'},
                        {
                            field: 'user_info', 
                            title: '用户信息', 
                            operate: false,
                            width: '120px',
                            formatter: function(value, row, index) {
                                var avatar = row.avatar || '/assets/img/avatar.png';
                                var html = '<div class="user-info-cell">';
                                html += '<img src="' + avatar + '" class="img-circle" style="width:36px;height:36px;margin-right:8px;float:left;">';
                                html += '<div style="float:left;">';
                                html += '<div style="font-weight:bold;color:#333;">' + (row.nickname || '-') + '</div>';
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
                            field: 'level',
                            title: '等级',
                            sortable: true,
                            width: '60px',
                            formatter: function(value, row, index) {
                                return '<span class="label label-info">Lv.' + (value || 0) + '</span>';
                            }
                        },
                        {
                            field: 'invite_code',
                            title: '邀请码',
                            width: '80px',
                            formatter: function(value, row, index) {
                                if (!value || value === '-') {
                                    return '<span class="text-muted">未生成</span>';
                                }
                                return '<code style="background:#f5f5f5;padding:2px 6px;border-radius:3px;">' + value + '</code>';
                            }
                        },
                        {
                            field: 'parent_info',
                            title: '上级会员',
                            operate: false,
                            width: '120px',
                            formatter: function(value, row, index) {
                                if (row.parent_id > 0) {
                                    return '<a href="javascript:;" onclick="Controller.showParentInfo(' + row.parent_id + ')" class="text-primary">' + (row.parent_nickname || 'ID:' + row.parent_id) + '</a>';
                                }
                                return '<span class="text-muted">无</span>';
                            }
                        },
                        {
                            field: 'level1_count',
                            title: '一级下级',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var count = value || 0;
                                if (count > 0) {
                                    return '<span class="label label-primary">' + count + ' 人</span>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'level2_count',
                            title: '二级下级',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var count = value || 0;
                                if (count > 0) {
                                    return '<span class="label label-default">' + count + ' 人</span>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'total_invite_count',
                            title: '总下级数',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var count = (row.level1_count || 0) + (row.level2_count || 0);
                                if (count > 0) {
                                    return '<span class="label label-success">' + count + ' 人</span>';
                                }
                                return '<span class="text-muted">0</span>';
                            }
                        },
                        {
                            field: 'withdraw_total',
                            title: '下级提现',
                            sortable: true,
                            width: '100px',
                            formatter: function(value, row, index) {
                                var val = parseFloat(value || 0);
                                if (val > 0) {
                                    return '<span style="color:#e74c3c;">¥' + val.toFixed(2) + '</span>';
                                }
                                return '<span class="text-muted">-</span>';
                            }
                        },
                        {
                            field: 'commission_total',
                            title: '累计佣金',
                            sortable: true,
                            width: '90px',
                            formatter: function(value, row, index) {
                                var val = parseFloat(value || 0);
                                if (val > 0) {
                                    return '<span style="color:#27ae60;">¥' + val.toFixed(2) + '</span>';
                                }
                                return '<span class="text-muted">-</span>';
                            }
                        },
                        {
                            field: 'operate',
                            title: '操作',
                            table: table,
                            width: '200px',
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'view_invitees',
                                    text: '查看下级',
                                    title: '查看被邀请人名单',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-users',
                                    url: 'invite/relation/invitees?parent_id={id}',
                                    callback: function(data) {
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'rebind_parent',
                                    text: '重置上级',
                                    title: '重新绑定上级',
                                    classname: 'btn btn-xs btn-warning btn-click',
                                    icon: 'fa fa-exchange',
                                    click: function(e, row) {
                                        Controller.showRebindModal(row.id);
                                    }
                                },
                                {
                                    name: 'view_stat',
                                    text: '统计',
                                    title: '邀请统计详情',
                                    classname: 'btn btn-xs btn-info btn-click',
                                    icon: 'fa fa-bar-chart',
                                    click: function(e, row) {
                                        Controller.showStat(row.id);
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
            
            // 渠道筛选
            $(document).on('click', '[data-channel]', function() {
                var channel = $(this).data('channel');
                if (channel !== undefined && channel !== '') {
                    table.bootstrapTable('refresh', {
                        query: {filter: JSON.stringify({invite_channel: channel})}
                    });
                } else {
                    table.bootstrapTable('refresh', {
                        query: {filter: '{}'}
                    });
                }
            });
        },
        
        // 显示统计信息
        showStat: function(parentId) {
            $.ajax({
                url: 'invite/relation/stat',
                type: 'GET',
                data: {parent_id: parentId},
                dataType: 'json',
                success: function(ret) {
                    if (ret.code === 1) {
                        var data = ret.data;
                        var inviter = data.inviter || {};
                        var html = '<div class="stat-modal" style="padding:10px;">';
                        html += '<div class="row">';
                        
                        // 用户信息
                        html += '<div class="col-md-6"><div class="panel panel-default"><div class="panel-heading"><i class="fa fa-user"></i> 用户信息</div><div class="panel-body">';
                        html += '<p><strong>用户名：</strong>' + (inviter.username || '-') + '</p>';
                        html += '<p><strong>昵称：</strong>' + (inviter.nickname || '-') + '</p>';
                        html += '<p><strong>等级：</strong>Lv.' + (inviter.level || 0) + '</p>';
                        html += '<p><strong>邀请码：</strong>' + (inviter.invite_code || '-') + '</p>';
                        html += '</div></div></div>';
                        
                        // 邀请统计
                        html += '<div class="col-md-6"><div class="panel panel-default"><div class="panel-heading"><i class="fa fa-users"></i> 邀请统计</div><div class="panel-body">';
                        html += '<p><strong>一级下级：</strong>' + data.level1_count + ' 人</p>';
                        html += '<p><strong>二级下级：</strong>' + data.level2_count + ' 人</p>';
                        html += '<p><strong>总下级数：</strong>' + data.total_count + ' 人</p>';
                        html += '</div></div></div>';
                        
                        html += '</div><div class="row">';
                        
                        // 时间统计
                        html += '<div class="col-md-6"><div class="panel panel-default"><div class="panel-heading"><i class="fa fa-clock-o"></i> 时间统计</div><div class="panel-body">';
                        html += '<p><strong>今日新增：</strong>' + data.today_new + ' 人</p>';
                        html += '<p><strong>本周新增：</strong>' + data.week_new + ' 人</p>';
                        html += '<p><strong>本月新增：</strong>' + data.month_new + ' 人</p>';
                        html += '</div></div></div>';
                        
                        // 金额统计
                        html += '<div class="col-md-6"><div class="panel panel-default"><div class="panel-heading"><i class="fa fa-money"></i> 金额统计</div><div class="panel-body">';
                        html += '<p><strong>下级提现总额：</strong><span style="color:#e74c3c;">¥' + parseFloat(data.withdraw_total).toFixed(2) + '</span></p>';
                        html += '<p><strong>下级消费总额：</strong><span style="color:#3498db;">¥' + parseFloat(data.spend_total).toFixed(2) + '</span></p>';
                        html += '<p><strong>累计佣金：</strong><span style="color:#27ae60;">¥' + parseFloat(data.commission_total).toFixed(2) + '</span></p>';
                        html += '<p><strong>待结算佣金：</strong><span style="color:#f39c12;">¥' + parseFloat(data.pending_commission).toFixed(2) + '</span></p>';
                        html += '</div></div></div>';
                        
                        html += '</div></div>';
                        
                        Layer.alert(html, {
                            title: '邀请统计详情',
                            area: ['650px', 'auto'],
                            btn: ['关闭']
                        });
                    } else {
                        Toastr.error(ret.msg || '获取数据失败');
                    }
                },
                error: function() {
                    Toastr.error('网络错误');
                }
            });
        },
        
        // 显示重新绑定上级弹窗
        showRebindModal: function(userId) {
            Fast.api.open('invite/relation/rebind?user_id=' + userId, '重新绑定上级', {
                area: ['800px', '600px'],
                callback: function() {
                    $("#table").bootstrapTable('refresh');
                }
            });
        },
        
        // 显示上级会员信息
        showParentInfo: function(parentId) {
            $.ajax({
                url: 'invite/relation/getUserDetail',
                type: 'GET',
                data: {user_id: parentId},
                dataType: 'json',
                success: function(ret) {
                    if (ret.code === 1) {
                        var user = ret.data.user || {};
                        var html = '<div class="stat-modal" style="padding:10px;">';
                        html += '<div class="row">';
                        html += '<div class="col-md-6"><div class="panel panel-default"><div class="panel-heading"><i class="fa fa-user"></i> 会员信息</div><div class="panel-body">';
                        html += '<p><strong>用户ID：</strong>' + user.id + '</p>';
                        html += '<p><strong>用户名：</strong>' + (user.username || '-') + '</p>';
                        html += '<p><strong>昵称：</strong>' + (user.nickname || '-') + '</p>';
                        html += '<p><strong>等级：</strong>Lv.' + (user.level || 0) + '</p>';
                        html += '<p><strong>一级邀请：</strong>' + (user.level1_count || 0) + ' 人</p>';
                        html += '<p><strong>二级邀请：</strong>' + (user.level2_count || 0) + ' 人</p>';
                        html += '</div></div></div>';
                        html += '</div></div>';
                        
                        Layer.alert(html, {
                            title: '上级会员信息',
                            area: ['400px', 'auto'],
                            btn: ['关闭']
                        });
                    } else {
                        Toastr.error(ret.msg || '获取数据失败');
                    }
                }
            });
        },

        // 重新绑定上级页面
        rebind: function () {
            var userId = Fast.api.query('user_id');
            
            // 获取用户详情
            Controller.loadUserDetail(userId);
            
            // 先初始化表单（包括selectpage组件）
            Form.api.bindevent($("form[role=form]"));
            
            // 延迟绑定事件，确保selectpage已初始化
            setTimeout(function() {
                // 监听 selectpage 选择事件
                $('#select-new-parent').on('change', function(e) {
                    var newParentId = $(this).val();
                    console.log('selectpage change:', newParentId);
                    if (newParentId) {
                        Controller.loadNewParentDetail(newParentId);
                    } else {
                        $('#new-parent-card').hide();
                    }
                });
            }, 500);
            
            // 绑定确认按钮事件
            $('#btn-confirm-rebind').on('click', function() {
                Controller.doRebind();
            });
        },
        
        // 加载用户详情
        loadUserDetail: function(userId) {
            $.ajax({
                url: 'invite/relation/getUserDetail',
                type: 'GET',
                data: {user_id: userId},
                dataType: 'json',
                success: function(ret) {
                    if (ret.code === 1) {
                        var data = ret.data;
                        var user = data.user || {};
                        var currentParent = data.current_parent || {};
                        
                        // 填充当前用户信息
                        $('#current-user-id').text(user.id || '-');
                        $('#current-user-nickname').text(user.nickname || '-');
                        $('#current-user-username').text(user.username || '-');
                        $('#current-user-avatar').attr('src', user.avatar || '/assets/img/avatar.png');
                        $('#current-user-level1').text(user.level1_count || 0);
                        $('#current-user-level2').text(user.level2_count || 0);
                        
                        // 填充当前上级信息
                        if (currentParent && currentParent.id) {
                            $('#current-parent-id').text(currentParent.id);
                            $('#current-parent-nickname').text(currentParent.nickname || '-');
                            $('#current-parent-username').text(currentParent.username || '-');
                            $('#current-parent-avatar').attr('src', currentParent.avatar || '/assets/img/avatar.png');
                            $('#current-parent-level1').text(currentParent.level1_count || 0);
                            $('#current-parent-level2').text(currentParent.level2_count || 0);
                            $('#current-parent-card').show();
                        } else {
                            $('#current-parent-card').hide();
                        }
                        
                        // 保存用户ID
                        $('#rebind-user-id').val(userId);
                    } else {
                        Toastr.error(ret.msg || '获取用户信息失败');
                    }
                },
                error: function() {
                    Toastr.error('网络错误');
                }
            });
        },
        
        // 加载新上级详情
        loadNewParentDetail: function(newParentId) {
            var userId = $('#rebind-user-id').val();
            $.ajax({
                url: 'invite/relation/getNewParentDetail',
                type: 'GET',
                data: {new_parent_id: newParentId, user_id: userId},
                dataType: 'json',
                success: function(ret) {
                    if (ret.code === 1) {
                        var newParent = ret.data.new_parent || {};
                        
                        // 填充新上级信息
                        $('#new-parent-avatar').attr('src', newParent.avatar || '/assets/img/avatar.png');
                        $('#new-parent-nickname').text(newParent.nickname || '-');
                        $('#new-parent-username').text(newParent.username || '-');
                        $('#new-parent-stats').html(
                            '<span class="label label-primary" style="margin-right:5px;">一级: ' + (newParent.level1_count || 0) + ' 人</span>' +
                            '<span class="label label-warning">二级: ' + (newParent.level2_count || 0) + ' 人</span>'
                        );
                        
                        // 保存新上级ID
                        $('#new-parent-id').val(newParentId);
                        
                        // 显示新上级卡片
                        $('#new-parent-card').show();
                    } else {
                        Toastr.error(ret.msg || '获取新上级信息失败');
                        $('#new-parent-card').hide();
                    }
                },
                error: function() {
                    Toastr.error('网络错误');
                }
            });
        },
        
        // 执行重新绑定
        doRebind: function() {
            var userId = $('#rebind-user-id').val();
            // 直接从selectpage input获取值
            var newParentId = $('#select-new-parent').val();
            var reason = $('#bind-reason').val();
            
            if (!newParentId) {
                Toastr.error('请选择新上级会员');
                return;
            }
            
            Layer.confirm('确定要将该用户的上级变更为选中的会员吗？', function(index) {
                Layer.close(index);
                
                $.ajax({
                    url: 'invite/relation/rebindParent',
                    type: 'POST',
                    data: {
                        user_id: userId,
                        new_parent_id: newParentId,
                        reason: reason
                    },
                    dataType: 'json',
                    success: function(ret) {
                        if (ret.code === 1) {
                            Toastr.success(ret.msg || '绑定成功');
                            var index2 = parent.layer.getFrameIndex(window.name);
                            parent.layer.close(index2);
                        } else {
                            Toastr.error(ret.msg || '绑定失败');
                        }
                    },
                    error: function() {
                        Toastr.error('网络错误');
                    }
                });
            });
        },

        // 被邀请人列表页面
        invitees: function () {
            var parentId = Fast.api.query('parent_id');
            
            Table.api.init({
                extend: {
                    index_url: 'invite/relation/invitees?parent_id=' + parentId,
                    table: 'invite_relation',
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                search: false,
                showToggle: false,
                showColumns: false,
                columns: [
                    [
                        {field: 'id', title: 'ID', sortable: true, width: '60px'},
                        {field: 'user_id', title: '用户ID', sortable: true, width: '70px'},
                        {
                            field: 'user_info', 
                            title: '用户信息', 
                            operate: false,
                            formatter: function(value, row, index) {
                                var avatar = row.avatar || '/assets/img/avatar.png';
                                var html = '<div class="user-info-cell">';
                                html += '<img src="' + avatar + '" class="img-circle" style="width:32px;height:32px;margin-right:8px;float:left;">';
                                html += '<div style="float:left;">';
                                html += '<div style="font-weight:bold;">' + (row.nickname || row.username || '-') + '</div>';
                                html += '<small class="text-muted">' + (row.username || '-') + '</small>';
                                html += '</div></div>';
                                return html;
                            }
                        },
                        {
                            field: 'relation_level',
                            title: '关系层级',
                            operate: false,
                            width: '120px',
                            formatter: function(value, row, index) {
                                if (row.level_num === 1) {
                                    return '<span class="label label-primary">一级下级</span>';
                                } else {
                                    return '<span class="label label-warning">二级下级</span>';
                                }
                            }
                        },
                        {
                            field: 'user_level',
                            title: '等级',
                            sortable: true,
                            width: '60px',
                            formatter: function(value, row, index) {
                                return '<span class="label label-info">Lv.' + (value || 0) + '</span>';
                            }
                        },
                        {
                            field: 'invite_channel',
                            title: '邀请渠道',
                            width: '80px',
                            formatter: function(value, row, index) {
                                var channels = {link: '链接', qrcode: '二维码', share: '分享'};
                                return channels[value] || value || '-';
                            }
                        },
                        {
                            field: 'balance',
                            title: '账户余额',
                            sortable: true,
                            width: '80px',
                            formatter: function(value, row, index) {
                                return parseFloat(value || 0).toFixed(2);
                            }
                        },
                        {
                            field: 'spend_total',
                            title: '消费总额',
                            sortable: true,
                            width: '90px',
                            formatter: function(value, row, index) {
                                return '<span style="color:#e74c3c;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'withdraw_total',
                            title: '提现总额',
                            sortable: true,
                            width: '90px',
                            formatter: function(value, row, index) {
                                return '<span style="color:#e74c3c;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'commission_total',
                            title: '产生佣金',
                            sortable: true,
                            width: '90px',
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
                        }
                    ]
                ]
            });

            Table.api.bindevent(table);
        },
        
        // 迁移日志页面
        migrationlog: function() {
            Table.api.init({
                extend: {
                    index_url: 'invite/relation/migrationLog',
                    table: 'invite_relation_migration_log',
                }
            });

            var table = $("#table");

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                search: false,
                showToggle: false,
                showColumns: false,
                columns: [
                    [
                        {field: 'id', title: 'ID', sortable: true, width: '60px'},
                        {
                            field: 'user_info',
                            title: '用户',
                            operate: false,
                            width: '120px',
                            formatter: function(value, row, index) {
                                return (row.user_nickname || row.user_username || 'ID:' + row.user_id);
                            }
                        },
                        {
                            field: 'old_parent_info',
                            title: '原上级',
                            operate: false,
                            width: '120px',
                            formatter: function(value, row, index) {
                                if (row.old_parent_id > 0) {
                                    return row.old_parent_nickname || row.old_parent_username || 'ID:' + row.old_parent_id;
                                }
                                return '<span class="text-muted">无</span>';
                            }
                        },
                        {
                            field: 'new_parent_info',
                            title: '新上级',
                            operate: false,
                            width: '120px',
                            formatter: function(value, row, index) {
                                return row.new_parent_nickname || row.new_parent_username || 'ID:' + row.new_parent_id;
                            }
                        },
                        {
                            field: 'reason',
                            title: '变更原因',
                            width: '150px',
                            formatter: function(value, row, index) {
                                return value || '<span class="text-muted">-</span>';
                            }
                        },
                        {
                            field: 'admin_username',
                            title: '操作人',
                            width: '100px'
                        },
                        {
                            field: 'createtime',
                            title: '操作时间',
                            sortable: true,
                            formatter: Table.api.formatter.datetime,
                            width: '150px'
                        }
                    ]
                ]
            });

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

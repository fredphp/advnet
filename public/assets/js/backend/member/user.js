define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'member/user/index',
                    add_url: 'member/user/add',
                    edit_url: 'member/user/edit',
                    del_url: 'member/user/del',
                    multi_url: 'member/user/multi',
                    table: 'member_user',
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
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {field: 'username', title: '用户名', operate: 'LIKE', formatter: Controller.api.formatter.username},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'coin_balance', title: '金币余额', sortable: true, formatter: Controller.api.formatter.coin},
                        {field: 'frozen_coin', title: '冻结金币', sortable: true, formatter: Controller.api.formatter.coin},
                        {field: 'level', title: '等级', width: 60},
                        {field: 'status', title: '状态', searchList: {"normal":"正常","frozen":"冻结","banned":"封禁"}, formatter: Controller.api.formatter.status},
                        {field: 'createtime', title: '注册时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Controller.api.formatter.operate, width: 150}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // ==================== 金币操作 ====================
            
            // 充值金币
            $(document).on('click', '.btn-recharge', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                $('#recharge-user-info').html('<i class="fa fa-user"></i> 用户：' + (row.nickname || row.username) + ' (ID: ' + row.id + ')');
                $('#recharge-modal').modal('show');
                $('#recharge-form')[0].reset();
            });

            // 确认充值
            $(document).on('click', '#btn-recharge-confirm', function () {
                var ids = Table.api.selectedids(table);
                var amount = $('#recharge-form input[name="amount"]').val();
                var remark = $('#recharge-form textarea[name="remark"]').val();
                
                if (!amount || amount <= 0) {
                    Toastr.error('请输入有效的充值金额');
                    return;
                }

                Fast.api.ajax({
                    url: 'member/user/recharge',
                    data: {user_id: ids[0], amount: amount, remark: remark}
                }, function (ret) {
                    $('#recharge-modal').modal('hide');
                    Toastr.success('充值成功');
                    table.bootstrapTable('refresh');
                });
            });

            // 扣除金币
            $(document).on('click', '.btn-deduct', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                $('#deduct-user-info').html('<i class="fa fa-user"></i> 用户：' + (row.nickname || row.username) + ' | 当前余额：' + (row.coin_balance || 0) + ' 金币');
                $('#deduct-modal').modal('show');
                $('#deduct-form')[0].reset();
            });

            // 确认扣除
            $(document).on('click', '#btn-deduct-confirm', function () {
                var ids = Table.api.selectedids(table);
                var amount = $('#deduct-form input[name="amount"]').val();
                var reasonType = $('#deduct-form select[name="reason_type"]').val();
                var remark = $('#deduct-form textarea[name="remark"]').val();
                
                if (!amount || amount <= 0) {
                    Toastr.error('请输入有效的扣除金额');
                    return;
                }

                if (!reasonType) {
                    Toastr.error('请选择扣除原因');
                    return;
                }

                Fast.api.ajax({
                    url: 'member/user/deduct',
                    data: {user_id: ids[0], amount: amount, remark: reasonType + (remark ? ' - ' + remark : '')}
                }, function (ret) {
                    $('#deduct-modal').modal('hide');
                    Toastr.success('扣除成功');
                    table.bootstrapTable('refresh');
                });
            });

            // 金币流水
            $(document).on('click', '.btn-coin-log', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('coin/log?user_id=' + ids[0], '金币流水记录');
            });

            // 冻结金币
            $(document).on('click', '.btn-frozen-coin', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                Layer.prompt({
                    title: '冻结金币 - 用户：' + (row.nickname || row.username),
                    formType: 0,
                    value: row.coin_balance || 0
                }, function(value, index){
                    Fast.api.ajax({
                        url: 'coin/account/freeze',
                        data: {user_id: ids[0], amount: value}
                    }, function(ret){
                        Layer.close(index);
                        Toastr.success('冻结成功');
                        table.bootstrapTable('refresh');
                    });
                });
            });

            // ==================== 风控操作 ====================

            // 封禁用户
            $(document).on('click', '.btn-ban-user', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                $('#ban-user-info').html('<i class="fa fa-exclamation-triangle"></i> 即将封禁用户：' + (row.nickname || row.username) + ' (ID: ' + row.id + ')');
                $('#ban-modal').modal('show');
                $('#ban-form')[0].reset();
            });

            // 封禁类型切换
            $(document).on('change', 'select[name="ban_type"]', function () {
                if ($(this).val() === 'permanent') {
                    $('.ban-duration-group').hide();
                } else {
                    $('.ban-duration-group').show();
                }
            });

            // 确认封禁
            $(document).on('click', '#btn-ban-confirm', function () {
                var ids = Table.api.selectedids(table);
                var banType = $('#ban-form select[name="ban_type"]').val();
                var duration = $('#ban-form input[name="duration"]').val();
                var reasonType = $('#ban-form select[name="reason_type"]').val();
                var remark = $('#ban-form textarea[name="remark"]').val();

                if (!reasonType) {
                    Toastr.error('请选择封禁原因');
                    return;
                }

                Fast.api.ajax({
                    url: 'member/user/ban',
                    data: {
                        user_id: ids[0], 
                        ban_type: banType, 
                        duration: duration, 
                        reason: reasonType + (remark ? ' - ' + remark : '')
                    }
                }, function (ret) {
                    $('#ban-modal').modal('hide');
                    Toastr.success('封禁成功');
                    table.bootstrapTable('refresh');
                });
            });

            // 冻结账户
            $(document).on('click', '.btn-freeze-user', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                $('#freeze-user-info').html('<i class="fa fa-user"></i> 用户：' + (row.nickname || row.username) + ' | 当前状态：' + row.status);
                $('#freeze-modal').modal('show');
                $('#freeze-form')[0].reset();
            });

            // 确认冻结
            $(document).on('click', '#btn-freeze-confirm', function () {
                var ids = Table.api.selectedids(table);
                var duration = $('#freeze-form select[name="duration"]').val();
                var reason = $('#freeze-form textarea[name="reason"]').val();

                if (!reason) {
                    Toastr.error('请输入冻结原因');
                    return;
                }

                Fast.api.ajax({
                    url: 'member/user/freeze',
                    data: {user_id: ids[0], duration: duration, reason: reason}
                }, function (ret) {
                    $('#freeze-modal').modal('hide');
                    Toastr.success('冻结成功');
                    table.bootstrapTable('refresh');
                });
            });

            // 解冻账户
            $(document).on('click', '.btn-unfreeze-user', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                if (row.status === 'normal') {
                    Toastr.warning('该用户状态正常，无需解冻');
                    return;
                }
                Layer.confirm('确定要解冻用户 ' + (row.nickname || row.username) + ' 吗？', function(index){
                    Fast.api.ajax({
                        url: 'member/user/unfreeze',
                        data: {user_id: ids[0]}
                    }, function(ret){
                        Layer.close(index);
                        Toastr.success('解冻成功');
                        table.bootstrapTable('refresh');
                    });
                });
            });

            // 加入黑名单
            $(document).on('click', '.btn-add-blacklist', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                Layer.prompt({
                    title: '加入黑名单 - ' + (row.nickname || row.username),
                    formType: 2,
                    value: '违规操作'
                }, function(value, index){
                    Fast.api.ajax({
                        url: 'risk/blacklist/add',
                        data: {user_id: ids[0], type: 'user', reason: value}
                    }, function(ret){
                        Layer.close(index);
                        Toastr.success('已加入黑名单');
                    });
                });
            });

            // 加入白名单
            $(document).on('click', '.btn-add-whitelist', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                var row = table.bootstrapTable('getRowByUniqueId', ids[0]);
                Layer.confirm('确定要将用户 ' + (row.nickname || row.username) + ' 加入白名单吗？', function(index){
                    Fast.api.ajax({
                        url: 'risk/whitelist/add',
                        data: {user_id: ids[0]}
                    }, function(ret){
                        Layer.close(index);
                        Toastr.success('已加入白名单');
                    });
                });
            });

            // 查看风控详情
            $(document).on('click', '.btn-view-risk', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('risk/user_risk/detail?user_id=' + ids[0], '风控详情');
            });

            // 查看设备信息
            $(document).on('click', '.btn-view-devices', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('member/user/devices?user_id=' + ids[0], '设备信息');
            });

            // ==================== 用户信息 ====================

            // 用户详情
            $(document).on('click', '.btn-user-detail', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Controller.api.showUserDetail(ids[0]);
            });

            // 查看行为记录
            $(document).on('click', '.btn-view-behaviors', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('member/user/behaviors?user_id=' + ids[0], '行为记录');
            });

            // 查看邀请关系
            $(document).on('click', '.btn-view-invite', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('invite/relation?user_id=' + ids[0], '邀请关系');
            });

            // 查看观看记录
            $(document).on('click', '.btn-view-watch', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('video/watchrecord?user_id=' + ids[0], '观看记录');
            });

            // 查看提现记录
            $(document).on('click', '.btn-view-withdraw', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('withdraw/order?user_id=' + ids[0], '提现记录');
            });

            // 查看红包记录
            $(document).on('click', '.btn-view-redpacket', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length !== 1) {
                    Toastr.warning('请选择一个用户');
                    return;
                }
                Fast.api.open('redpacket/record?user_id=' + ids[0], '红包记录');
            });

            // ==================== 导出操作 ====================

            // 导出选中用户
            $(document).on('click', '.btn-export', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.warning('请选择要导出的用户');
                    return;
                }
                window.location.href = 'member/user/export?ids=' + ids.join(',');
            });

            // 导出全部用户
            $(document).on('click', '.btn-export-all', function () {
                var search = table.bootstrapTable('getOptions').searchText;
                var filter = table.bootstrapTable('getOptions').filter;
                window.location.href = 'member/user/export?' + $.param({search: search, filter: JSON.stringify(filter)});
            });

            // ==================== 批量操作 ====================

            // 批量操作按钮
            $(document).on('click', '.btn-batch-status', function () {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.warning('请选择要操作的用户');
                    return;
                }
                $('#batch-user-count strong').text(ids.length);
                $('#batch-modal').modal('show');
                $('#batch-form')[0].reset();
            });

            // 批量操作类型切换
            $(document).on('change', 'select[name="batch_action"]', function () {
                var val = $(this).val();
                if (val === 'batch_recharge' || val === 'batch_deduct') {
                    $('.batch-amount-group').show();
                } else {
                    $('.batch-amount-group').hide();
                }
            });

            // 确认批量操作
            $(document).on('click', '#btn-batch-confirm', function () {
                var ids = Table.api.selectedids(table);
                var action = $('#batch-form select[name="batch_action"]').val();
                var amount = $('#batch-form input[name="batch_amount"]').val();
                var remark = $('#batch-form textarea[name="batch_remark"]').val();

                if (!action) {
                    Toastr.error('请选择操作类型');
                    return;
                }

                if ((action === 'batch_recharge' || action === 'batch_deduct') && (!amount || amount <= 0)) {
                    Toastr.error('请输入有效的金币数量');
                    return;
                }

                Layer.confirm('确定要对 ' + ids.length + ' 个用户执行此操作吗？', function(index){
                    Fast.api.ajax({
                        url: 'member/user/batch',
                        data: {ids: ids, action: action, amount: amount, remark: remark}
                    }, function(ret){
                        Layer.close(index);
                        $('#batch-modal').modal('hide');
                        Toastr.success('批量操作成功');
                        table.bootstrapTable('refresh');
                    });
                });
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        statistics: function () {
            Controller.api.loadStatistics();
            $('#start_date, #end_date').on('change', function () {
                Controller.api.loadStatistics();
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'member/user/statistics',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-users').text(data.total_stats.total || 0);
                            $('#normal-users').text(data.total_stats.normal_count || 0);
                            $('#frozen-users').text(data.total_stats.frozen_count || 0);
                            $('#banned-users').text(data.total_stats.banned_count || 0);
                        }
                    }
                });
            },
            // 显示用户详情
            showUserDetail: function(userId) {
                $.ajax({
                    url: 'member/user/detail',
                    type: 'GET',
                    data: {ids: userId},
                    dataType: 'json',
                    success: function(ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            var user = data.user;
                            var coinAccount = data.coin_account || {};
                            var inviteStats = data.invite_stats || {};
                            var todayStats = data.today_stats || {};
                            var riskInfo = data.risk_info || {};

                            var html = '<div class="row">' +
                                '<div class="col-md-6">' +
                                    '<div class="detail-section">' +
                                        '<h5 style="margin-bottom:15px;color:#667eea;"><i class="fa fa-user"></i> 基本信息</h5>' +
                                        '<div class="detail-row"><span class="detail-label">用户ID</span><span class="detail-value">' + user.id + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">用户名</span><span class="detail-value">' + (user.username || '-') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">昵称</span><span class="detail-value">' + (user.nickname || '-') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">手机号</span><span class="detail-value">' + (user.mobile || '-') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">状态</span><span class="detail-value">' + Controller.api.formatStatus(user.status) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">等级</span><span class="detail-value">Lv.' + (user.level || 1) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">注册时间</span><span class="detail-value">' + (user.createtime ? new Date(user.createtime * 1000).toLocaleString() : '-') + '</span></div>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="col-md-6">' +
                                    '<div class="detail-section">' +
                                        '<h5 style="margin-bottom:15px;color:#f6c23e;"><i class="fa fa-coins"></i> 金币信息</h5>' +
                                        '<div class="detail-row"><span class="detail-label">金币余额</span><span class="detail-value text-success"><strong>' + (coinAccount.balance || 0) + '</strong></span></div>' +
                                        '<div class="detail-row"><span class="detail-label">冻结金币</span><span class="detail-value text-warning">' + (coinAccount.frozen || 0) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">累计收入</span><span class="detail-value">' + (coinAccount.total_income || 0) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">累计支出</span><span class="detail-value">' + (coinAccount.total_expense || 0) + '</span></div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                            '<div class="row" style="margin-top:15px;">' +
                                '<div class="col-md-6">' +
                                    '<div class="detail-section">' +
                                        '<h5 style="margin-bottom:15px;color:#1cc88a;"><i class="fa fa-users"></i> 邀请统计</h5>' +
                                        '<div class="detail-row"><span class="detail-label">邀请人数</span><span class="detail-value">' + (inviteStats.invite_count || 0) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">有效邀请</span><span class="detail-value">' + (inviteStats.valid_count || 0) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">累计佣金</span><span class="detail-value">' + (inviteStats.total_commission || 0) + '</span></div>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="col-md-6">' +
                                    '<div class="detail-section">' +
                                        '<h5 style="margin-bottom:15px;color:#e74a3b;"><i class="fa fa-shield-alt"></i> 风控信息</h5>' +
                                        '<div class="detail-row"><span class="detail-label">风险评分</span><span class="detail-value">' + (riskInfo.score || 0) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">风险等级</span><span class="detail-value">' + Controller.api.formatRiskLevel(riskInfo.level) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">封禁次数</span><span class="detail-value">' + (riskInfo.ban_count || 0) + '</span></div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>';

                            $('#user-detail-content').html(html);
                            $('#user-detail-modal').modal('show');
                        }
                    }
                });
            },
            formatStatus: function(status) {
                var statusMap = {
                    'normal': '<span class="status-badge normal"><i class="fa fa-check-circle"></i> 正常</span>',
                    'frozen': '<span class="status-badge frozen"><i class="fa fa-snowflake"></i> 冻结</span>',
                    'banned': '<span class="status-badge banned"><i class="fa fa-ban"></i> 封禁</span>'
                };
                return statusMap[status] || status;
            },
            formatRiskLevel: function(level) {
                var levelMap = {
                    'low': '<span class="badge" style="background:#d1fae5;color:#047857;">低风险</span>',
                    'medium': '<span class="badge" style="background:#fef3c7;color:#b45309;">中风险</span>',
                    'high': '<span class="badge" style="background:#fee2e2;color:#b91c1c;">高风险</span>',
                    'dangerous': '<span class="badge" style="background:#dc2626;color:#fff;">危险</span>'
                };
                return levelMap[level] || level;
            },
            // 表格格式化器
            formatter: {
                username: function(value, row, index) {
                    var avatar = row.avatar ? '<img src="' + row.avatar + '" style="width:24px;height:24px;border-radius:50%;margin-right:6px;">' : '<i class="fa fa-user-circle" style="font-size:24px;color:#ddd;margin-right:6px;"></i>';
                    return avatar + '<span>' + value + '</span>';
                },
                coin: function(value, row, index) {
                    if (!value || value == 0) return '<span style="color:#999;">0</span>';
                    return '<span style="color:#f6c23e;font-weight:600;">' + value + '</span>';
                },
                status: function(value, row, index) {
                    var statusMap = {
                        'normal': '<span class="status-badge normal"><i class="fa fa-check-circle"></i> 正常</span>',
                        'frozen': '<span class="status-badge frozen"><i class="fa fa-snowflake"></i> 冻结</span>',
                        'banned': '<span class="status-badge banned"><i class="fa fa-ban"></i> 封禁</span>'
                    };
                    return statusMap[value] || value;
                },
                operate: function(value, row, index) {
                    var that = $.extend({}, this);
                    var table = $(that.table).clone(true);
                    // 添加快捷操作按钮
                    var quickActions = '<div class="quick-actions">' +
                        '<span class="quick-action-btn ban" title="封禁" data-id="' + row.id + '"><i class="fa fa-ban"></i></span>' +
                        '<span class="quick-action-btn freeze" title="冻结" data-id="' + row.id + '"><i class="fa fa-snowflake"></i></span>' +
                        '<span class="quick-action-btn risk" title="风控" data-id="' + row.id + '"><i class="fa fa-shield-alt"></i></span>' +
                    '</div>';
                    
                    // 使用默认的操作按钮
                    return Table.api.formatter.operate.call(that, value, row, index);
                }
            }
        }
    };
    return Controller;
});

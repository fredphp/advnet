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
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Controller.api.formatter.operate, width: 200, align: 'left'}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 绑定操作按钮事件
            Controller.api.bindOperateEvents(table);
            
            // 快捷金额按钮
            $(document).off('click', '.btn-quick-amount').on('click', '.btn-quick-amount', function(e) {
                e.preventDefault();
                var amount = parseInt($(this).data('amount')) || 0;
                var $input = $('#recharge-form input[name="amount"]');
                var current = parseInt($input.val()) || 0;
                $input.val(current + amount);
                return false;
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
        behaviors: function () {
            // 获取用户ID
            var userId = Fast.api.query('user_id');
            
            // 初始化表格
            var table = $("#table");
            table.bootstrapTable({
                url: 'member/user/behaviors',
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                queryParams: function(params) {
                    params.user_id = userId;
                    return params;
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {field: 'behavior_type', title: '行为类型', formatter: Controller.api.formatter.behaviorType, width: 100},
                        {field: 'description', title: '行为描述'},
                        {field: 'ip', title: 'IP地址', width: 120},
                        {field: 'device_info', title: '设备信息', width: 150},
                        {field: 'createtime', title: '时间', formatter: Table.api.formatter.datetime, sortable: true, width: 150}
                    ]
                ]
            });
            
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        devices: function () {
            // 获取用户ID
            var userId = Fast.api.query('user_id');
            
            // 初始化表格
            var table = $("#table");
            table.bootstrapTable({
                url: 'member/user/devices',
                pk: 'id',
                sortName: 'last_seen',
                sortOrder: 'desc',
                queryParams: function(params) {
                    params.user_id = userId;
                    return params;
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {field: 'device_id', title: '设备ID'},
                        {field: 'device_type', title: '设备类型', width: 100},
                        {field: 'os', title: '操作系统', width: 100},
                        {field: 'browser', title: '浏览器', width: 100},
                        {field: 'last_seen', title: '最后活跃', formatter: Table.api.formatter.datetime, sortable: true, width: 150}
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
                            var data = ret.data || {};
                            var user = data.user || {};
                            var coinAccount = data.coin_account || {};
                            var inviteStats = data.invite_stats || {};
                            var riskInfo = data.risk_info || {};

                            var html = '<div class="row">' +
                                '<div class="col-md-6">' +
                                    '<div class="detail-section">' +
                                        '<h5 style="margin-bottom:15px;color:#667eea;"><i class="fa fa-user"></i> 基本信息</h5>' +
                                        '<div class="detail-row"><span class="detail-label">用户ID</span><span class="detail-value">' + (user.id || '-') + '</span></div>' +
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
                if (!status) return '<span class="status-badge normal"><i class="fa fa-check-circle"></i> 正常</span>';
                var statusMap = {
                    'normal': '<span class="status-badge normal"><i class="fa fa-check-circle"></i> 正常</span>',
                    'frozen': '<span class="status-badge frozen"><i class="fa fa-snowflake"></i> 冻结</span>',
                    'banned': '<span class="status-badge banned"><i class="fa fa-ban"></i> 封禁</span>'
                };
                return statusMap[status] || status;
            },
            formatRiskLevel: function(level) {
                if (!level) return '<span class="badge" style="background:#d1fae5;color:#047857;">低风险</span>';
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
                    var avatar = row.avatar ? '<img src="' + row.avatar + '" style="width:24px;height:24px;border-radius:50%;margin-right:6px;vertical-align:middle;">' : '<i class="fa fa-user-circle" style="font-size:24px;color:#ddd;margin-right:6px;vertical-align:middle;"></i>';
                    return avatar + '<span>' + value + '</span>';
                },
                coin: function(value, row, index) {
                    if (!value || value == 0) return '<span style="color:#999;">0</span>';
                    return '<span style="color:#f6c23e;font-weight:600;">' + value + '</span>';
                },
                status: function(value, row, index) {
                    if (!value) return '<span class="status-badge normal"><i class="fa fa-check-circle"></i> 正常</span>';
                    var statusMap = {
                        'normal': '<span class="status-badge normal"><i class="fa fa-check-circle"></i> 正常</span>',
                        'frozen': '<span class="status-badge frozen"><i class="fa fa-snowflake"></i> 冻结</span>',
                        'banned': '<span class="status-badge banned"><i class="fa fa-ban"></i> 封禁</span>'
                    };
                    return statusMap[value] || value;
                },
                behaviorType: function(value, row, index) {
                    var typeMap = {
                        'login': '<span class="behavior-type-badge login"><i class="fa fa-sign-in-alt"></i> 登录</span>',
                        'register': '<span class="behavior-type-badge register"><i class="fa fa-user-plus"></i> 注册</span>',
                        'withdraw': '<span class="behavior-type-badge withdraw"><i class="fa fa-money-bill"></i> 提现</span>',
                        'watch': '<span class="behavior-type-badge watch"><i class="fa fa-play-circle"></i> 观看</span>',
                        'task': '<span class="behavior-type-badge task"><i class="fa fa-tasks"></i> 任务</span>',
                        'share': '<span class="behavior-type-badge share"><i class="fa fa-share-alt"></i> 分享</span>'
                    };
                    return typeMap[value] || '<span class="behavior-type-badge other"><i class="fa fa-circle"></i> ' + (value || '其他') + '</span>';
                },
                operate: function(value, row, index) {
                    var that = this;
                    var table = $(that.table).clone(true);
                    var userId = row.id;
                    var userName = row.nickname || row.username || row.id;
                    var status = row.status;
                    
                    // 操作按钮HTML
                    var html = '<div class="btn-group-operate">';
                    
                    // 详情按钮
                    html += '<button type="button" class="btn btn-primary btn-xs" onclick="UserAPI.showDetail(' + userId + ')" title="详情"><i class="fa fa-eye"></i></button>';
                    
                    // 编辑按钮 - 使用 btn-dialog 类打开弹窗
                    html += '<a href="member/user/edit?ids=' + userId + '" class="btn btn-info btn-xs btn-dialog" data-area=\'["800px","600px"]\' title="编辑"><i class="fa fa-edit"></i></a>';
                    
                    // 金币操作下拉
                    html += '<div class="btn-group">';
                    html += '<button type="button" class="btn btn-warning btn-xs dropdown-toggle" data-toggle="dropdown" title="金币"><i class="fa fa-coins"></i> <span class="caret"></span></button>';
                    html += '<ul class="dropdown-menu dropdown-menu-right">';
                    html += '<li class="dropdown-header">金币操作</li>';
                    html += '<li><a href="javascript:;" onclick="UserAPI.recharge(' + userId + ',\'' + userName + '\')"><i class="fa fa-plus-circle text-success"></i> 充值金币</a></li>';
                    html += '<li><a href="javascript:;" onclick="UserAPI.deduct(' + userId + ',\'' + userName + '\',' + (row.coin_balance || 0) + ')"><i class="fa fa-minus-circle text-danger"></i> 扣除金币</a></li>';
                    html += '<li class="divider"></li>';
                    html += '<li><a href="coin/log?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-list-alt text-info"></i> 金币流水</a></li>';
                    html += '</ul></div>';
                    
                    // 风控操作下拉
                    html += '<div class="btn-group">';
                    html += '<button type="button" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" title="风控"><i class="fa fa-shield-alt"></i> <span class="caret"></span></button>';
                    html += '<ul class="dropdown-menu dropdown-menu-right">';
                    html += '<li class="dropdown-header">风控操作</li>';
                    if (status === 'banned') {
                        // 已封禁用户显示解封按钮
                        html += '<li><a href="javascript:;" onclick="UserAPI.unban(' + userId + ',\'' + userName + '\')"><i class="fa fa-unlock text-success"></i> 解封用户</a></li>';
                    } else {
                        html += '<li><a href="javascript:;" onclick="UserAPI.ban(' + userId + ',\'' + userName + '\')"><i class="fa fa-ban text-danger"></i> 封禁用户</a></li>';
                    }
                    if (status === 'normal') {
                        html += '<li><a href="javascript:;" onclick="UserAPI.freeze(' + userId + ',\'' + userName + '\')"><i class="fa fa-snowflake text-warning"></i> 冻结账户</a></li>';
                    }
                    if (status === 'frozen') {
                        html += '<li><a href="javascript:;" onclick="UserAPI.unfreeze(' + userId + ',\'' + userName + '\')"><i class="fa fa-unlock text-success"></i> 解冻账户</a></li>';
                    }
                    html += '<li class="divider"></li>';
                    html += '<li><a href="javascript:;" onclick="UserAPI.addBlacklist(' + userId + ',\'' + userName + '\')"><i class="fa fa-user-slash text-dark"></i> 加入黑名单</a></li>';
                    html += '<li><a href="javascript:;" onclick="UserAPI.addWhitelist(' + userId + ',\'' + userName + '\')"><i class="fa fa-user-check text-success"></i> 加入白名单</a></li>';
                    html += '</ul></div>';
                    
                    // 更多操作下拉
                    html += '<div class="btn-group">';
                    html += '<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" title="更多"><i class="fa fa-ellipsis-h"></i> <span class="caret"></span></button>';
                    html += '<ul class="dropdown-menu dropdown-menu-right">';
                    html += '<li class="dropdown-header">信息查看</li>';
                    html += '<li><a href="member/user/behaviors?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-history text-info"></i> 行为记录</a></li>';
                    html += '<li><a href="invite/relation/invitees?parent_id=' + userId + '" class="btn-dialog"><i class="fa fa-users text-success"></i> 邀请关系</a></li>';
                    html += '<li><a href="video/watchrecord?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-play-circle text-danger"></i> 观看记录</a></li>';
                    html += '<li><a href="withdraw/order?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-money-bill text-warning"></i> 提现记录</a></li>';
                    html += '<li><a href="redpacket/record?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-gift text-danger"></i> 红包记录</a></li>';
                    html += '<li class="divider"></li>';
                    html += '<li><a href="member/user/devices?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-mobile-alt text-secondary"></i> 设备信息</a></li>';
                    html += '<li><a href="risk/user_risk/detail?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-chart-line text-info"></i> 风控详情</a></li>';
                    html += '</ul></div>';
                    
                    html += '</div>';
                    
                    return html;
                }
            },
            // 绑定操作按钮事件
            bindOperateEvents: function(table) {
                // 充值确认
                $(document).on('click', '#btn-recharge-confirm', function () {
                    var userId = $('#recharge-form input[name="user_id"]').val();
                    var amount = $('#recharge-form input[name="amount"]').val();
                    var remark = $('#recharge-form textarea[name="remark"]').val();
                    
                    if (!amount || amount <= 0) {
                        Toastr.error('请输入有效的充值金额');
                        return;
                    }

                    Fast.api.ajax({
                        url: 'member/user/recharge',
                        data: {user_id: userId, amount: amount, remark: remark}
                    }, function (ret) {
                        $('#recharge-modal').modal('hide');
                        Toastr.success('充值成功');
                        table.bootstrapTable('refresh');
                    });
                });

                // 扣除确认
                $(document).on('click', '#btn-deduct-confirm', function () {
                    var userId = $('#deduct-form input[name="user_id"]').val();
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
                        data: {user_id: userId, amount: amount, remark: reasonType + (remark ? ' - ' + remark : '')}
                    }, function (ret) {
                        $('#deduct-modal').modal('hide');
                        Toastr.success('扣除成功');
                        table.bootstrapTable('refresh');
                    });
                });

                // 封禁类型切换
                $(document).on('change', 'select[name="ban_type"]', function () {
                    if ($(this).val() === 'permanent') {
                        $('.ban-duration-group').hide();
                    } else {
                        $('.ban-duration-group').show();
                    }
                });

                // 封禁确认
                $(document).on('click', '#btn-ban-confirm', function () {
                    var userId = $('#ban-form input[name="user_id"]').val();
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
                            user_id: userId, 
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

                // 冻结确认
                $(document).on('click', '#btn-freeze-confirm', function () {
                    var userId = $('#freeze-form input[name="user_id"]').val();
                    var duration = $('#freeze-form select[name="duration"]').val();
                    var reason = $('#freeze-form textarea[name="reason"]').val();

                    if (!reason) {
                        Toastr.error('请输入冻结原因');
                        return;
                    }

                    Fast.api.ajax({
                        url: 'member/user/freeze',
                        data: {user_id: userId, duration: duration, reason: reason}
                    }, function (ret) {
                        $('#freeze-modal').modal('hide');
                        Toastr.success('冻结成功');
                        table.bootstrapTable('refresh');
                    });
                });
            }
        }
    };
    
    // 全局API，供onclick调用
    window.UserAPI = {
        showDetail: function(userId) {
            Controller.api.showUserDetail(userId);
        },
        recharge: function(userId, userName) {
            $('#recharge-user-info').html('<i class="fa fa-user"></i> 用户：' + userName + ' (ID: ' + userId + ')');
            $('#recharge-form input[name="user_id"]').val(userId);
            $('#recharge-form input[name="amount"]').val('');
            $('#recharge-form textarea[name="remark"]').val('');
            $('#recharge-modal').modal('show');
        },
        deduct: function(userId, userName, balance) {
            $('#deduct-user-info').html('<i class="fa fa-user"></i> 用户：' + userName + ' | 当前余额：' + (balance || 0) + ' 金币');
            $('#deduct-form input[name="user_id"]').val(userId);
            $('#deduct-form')[0].reset();
            $('#deduct-modal').modal('show');
        },
        ban: function(userId, userName) {
            $('#ban-user-info').html('<i class="fa fa-exclamation-triangle"></i> 即将封禁用户：' + userName + ' (ID: ' + userId + ')');
            $('#ban-form input[name="user_id"]').val(userId);
            $('#ban-form')[0].reset();
            $('.ban-duration-group').show();
            $('#ban-modal').modal('show');
        },
        freeze: function(userId, userName) {
            $('#freeze-user-info').html('<i class="fa fa-user"></i> 用户：' + userName);
            $('#freeze-form input[name="user_id"]').val(userId);
            $('#freeze-form')[0].reset();
            $('#freeze-modal').modal('show');
        },
        unfreeze: function(userId, userName) {
            Layer.confirm('确定要解冻用户 ' + userName + ' 吗？', function(index){
                Fast.api.ajax({
                    url: 'member/user/unfreeze',
                    data: {user_id: userId}
                }, function(ret){
                    Layer.close(index);
                    Toastr.success('解冻成功');
                    $("#table").bootstrapTable('refresh');
                });
            });
        },
        unban: function(userId, userName) {
            Layer.confirm('确定要解封用户 ' + userName + ' 吗？', function(index){
                Fast.api.ajax({
                    url: 'member/user/unban',
                    data: {user_id: userId}
                }, function(ret){
                    Layer.close(index);
                    Toastr.success('解封成功');
                    $("#table").bootstrapTable('refresh');
                });
            });
        },
        addBlacklist: function(userId, userName) {
            Layer.prompt({
                title: '加入黑名单 - ' + userName,
                formType: 2,
                value: '违规操作'
            }, function(value, index){
                Fast.api.ajax({
                    url: 'risk/blacklist/add',
                    data: {user_id: userId, reason: value}
                }, function(ret){
                    Layer.close(index);
                    Toastr.success('已加入黑名单');
                });
            });
        },
        addWhitelist: function(userId, userName) {
            Layer.prompt({
                title: '加入白名单 - ' + userName,
                formType: 2,
                value: '信任用户'
            }, function(value, index){
                Fast.api.ajax({
                    url: 'risk/whitelist/add',
                    data: {user_id: userId, reason: value}
                }, function(ret){
                    Layer.close(index);
                    Toastr.success('已加入白名单');
                });
            });
        }
    };
    
    return Controller;
});

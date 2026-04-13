define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
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

            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 70},
                        {field: 'user_type', title: '会员类型', searchList: {"0":"真实会员","1":"系统会员"}, formatter: Controller.api.formatter.memberType, width: 100},
                        {field: 'username', title: '用户信息', operate: 'LIKE', formatter: Controller.api.formatter.userInfo, width: 180},
                        {field: 'mobile', title: '手机号', operate: 'LIKE', width: 120},
                        {field: 'coin_balance', title: '金币余额', sortable: true, formatter: Controller.api.formatter.coin, width: 100},
                        {field: 'frozen_coin', title: '冻结金币', sortable: true, formatter: Controller.api.formatter.coinFrozen, width: 100},
                        {field: 'invite_code', title: '邀请码', sortable: true, formatter:Controller.api.formatter.normal, width: 100},
                        {field: 'level', title: '等级', width: 60, formatter: Controller.api.formatter.level},
                        {field: 'status', title: '状态', searchList: {"normal":"正常","frozen":"冻结","banned":"封禁"}, formatter: Controller.api.formatter.status, width: 80},
                        {field: 'createtime', title: '注册时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, width: 150},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Controller.api.formatter.operate, width: 280, align: 'left'}
                    ]
                ]
            });

            Table.api.bindevent(table);
            Controller.api.bindOperateEvents(table);
            
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

            // 头像上传后同步更新头部预览
            var $avatarInput = $('#c-avatar');
            var $headerAvatar = $('#header-avatar');

            // 监听头像值变化，同步更新头部预览
            $avatarInput.on('change', function () {
                var val = $(this).val();
                if (val) {
                    // 处理相对路径拼接CDN
                    if (val.indexOf('://') === -1 && val.indexOf('/') === 0 && Config.upload && Config.upload.cdnurl) {
                        val = Config.upload.cdnurl + val;
                    }
                    $headerAvatar.attr('src', val);
                } else {
                    $headerAvatar.attr('src', '/assets/img/avatar.png');
                }
            });

            // 点击头部头像触发上传
            $headerAvatar.on('click', function () {
                $('#faupload-avatar').trigger('click');
            });
        },
        statistics: function () {
            Controller.api.loadStatistics();
            $('#start_date, #end_date').on('change', function () {
                Controller.api.loadStatistics();
            });
        },
        behaviors: function () {
            var userId = Fast.api.query('user_id');
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
            Table.api.bindevent(table);
        },
        devices: function () {
            var userId = Fast.api.query('user_id');
            var table = $("#table");
            table.bootstrapTable({
                url: 'member/user/devices',
                pk: 'id',
                sortName: 'last_login_time',
                sortOrder: 'desc',
                queryParams: function(params) {
                    params.user_id = userId;
                    return params;
                },
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {field: 'device_id', title: '设备ID', width: 150},
                        {field: 'device_type', title: '类型', formatter: Controller.api.formatter.deviceType, width: 70},
                        {field: 'device_brand', title: '品牌', width: 80},
                        {field: 'device_model', title: '型号', width: 100},
                        {field: 'risk_level', title: '风险', formatter: Controller.api.formatter.deviceRisk, width: 70},
                        {field: 'last_login_time', title: '最后登录', formatter: Table.api.formatter.datetime, sortable: true, width: 150}
                    ]
                ]
            });
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
                                        '<h5><i class="fa fa-user text-primary"></i> 基本信息</h5>' +
                                        '<div class="detail-row"><span class="detail-label">用户ID</span><span class="detail-value">' + (user.id || '-') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">用户名</span><span class="detail-value">' + (user.username || '-') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">昵称</span><span class="detail-value">' + (user.nickname || '-') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">会员类型</span><span class="detail-value">' + (parseInt(user.user_type) == 1 ? '<span class="status-badge normal" style="background:linear-gradient(135deg,#1cc88a,#13855c);color:#fff;"><i class="fa fa-robot"></i> 系统会员</span>' : '<span class="status-badge normal"><i class="fa fa-user"></i> 真实会员</span>') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">手机号</span><span class="detail-value">' + (user.mobile || '-') + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">状态</span><span class="detail-value">' + Controller.api.formatStatus(user.status) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">等级</span><span class="detail-value">Lv.' + (user.level || 1) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">注册时间</span><span class="detail-value">' + (user.createtime ? new Date(user.createtime * 1000).toLocaleString() : '-') + '</span></div>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="col-md-6">' +
                                    '<div class="detail-section">' +
                                        '<h5><i class="fa fa-coins text-warning"></i> 金币信息</h5>' +
                                        '<div class="detail-row"><span class="detail-label">金币余额</span><span class="detail-value text-warning"><strong>' + (coinAccount.balance || 0) + '</strong></span></div>' +
                                        '<div class="detail-row"><span class="detail-label">冻结金币</span><span class="detail-value text-muted">' + (coinAccount.frozen || 0) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">累计收入</span><span class="detail-value">' + (coinAccount.total_income || 0) + '</span></div>' +
                                        '<div class="detail-row"><span class="detail-label">累计支出</span><span class="detail-value">' + (coinAccount.total_expense || 0) + '</span></div>' +
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
            formatter: {
                memberType: function(value, row, index) {
                    var type = parseInt(value) || 0;
                    if (type == 1) {
                        return '<span class="status-badge" style="background:linear-gradient(135deg,#1cc88a 0%,#13855c 100%);color:#fff;"><i class="fa fa-robot"></i> 系统会员</span>';
                    }
                    return '<span class="status-badge" style="background:linear-gradient(135deg,#36b9cc 0%,#258396 100%);color:#fff;"><i class="fa fa-user"></i> 真实会员</span>';
                },
                userInfo: function(value, row, index) {
                    var initial = (row.username ? row.username.charAt(0).toUpperCase() : 'U');
                    var isSystem = parseInt(row.user_type) == 1;
                    var sysBadge = isSystem ? ' <span style="font-size:9px;background:#fff;color:#13855c;padding:1px 4px;border-radius:3px;font-weight:600;">SYS</span>' : '';

                    // 任何非空头像都尝试显示，通过 onerror 回退到默认字母头像
                    if (row.avatar) {
                        var avatarSrc = row.avatar;
                        // 相对路径自动拼接 CDN 前缀
                        if (avatarSrc.indexOf('://') === -1 && avatarSrc.indexOf('/') === 0) {
                            avatarSrc = Config.upload && Config.upload.cdnurl ? Config.upload.cdnurl + avatarSrc : avatarSrc;
                        }
                        var avatar = '<img src="' + avatarSrc + '" class="user-avatar" onerror="this.style.display=\'none\';this.nextElementSibling.style.display=\'flex\';" /><div class="user-avatar default' + (isSystem ? ' system-avatar' : '') + '" style="display:none;">' + initial + '</div>';
                        return '<div class="user-info-cell">' + avatar +
                            '<div class="user-name-text"><span class="name">' + (row.nickname || row.username || '-') + sysBadge + '</span>' +
                            '<span class="id">ID: ' + row.id + '</span></div></div>';
                    }

                    var avatarHtml = '<div class="user-avatar default' + (isSystem ? ' system-avatar' : '') + '">' + initial + '</div>';
                    return '<div class="user-info-cell">' + avatarHtml +
                        '<div class="user-name-text"><span class="name">' + (row.nickname || row.username || '-') + sysBadge + '</span>' +
                        '<span class="id">ID: ' + row.id + '</span></div></div>';
                },
                coin: function(value, row, index) {
                    if (!value || value == 0) return '<span class="coin-value"><i class="fa fa-circle"></i> 0</span>';
                    return '<span class="coin-value"><i class="fa fa-coins"></i> <strong>' + value + '</strong></span>';
                },
                coinFrozen: function(value, row, index) {
                    if (!value || value == 0) return '<span style="color:#9ca3af;">0</span>';
                    return '<span style="color:#6b7280;">' + value + '</span>';
                },
                level: function(value, row, index) {
                    return '<span style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:500;">Lv.' + (value || 1) + '</span>';
                },
                status: function(value, row, index) {
                    var statusMap = {
                        'normal': '<span class="status-badge normal"><i class="fa fa-check-circle"></i> 正常</span>',
                        'frozen': '<span class="status-badge frozen"><i class="fa fa-snowflake"></i> 冻结</span>',
                        'banned': '<span class="status-badge banned"><i class="fa fa-ban"></i> 封禁</span>'
                    };
                    return statusMap[value] || value;
                },
                behaviorType: function(value, row, index) {
                    var typeMap = {
                        'login': '<span class="badge" style="background:#dbeafe;color:#1e40af;"><i class="fa fa-sign-in-alt"></i> 登录</span>',
                        'register': '<span class="badge" style="background:#d1fae5;color:#047857;"><i class="fa fa-user-plus"></i> 注册</span>',
                        'withdraw': '<span class="badge" style="background:#fef3c7;color:#b45309;"><i class="fa fa-money-bill"></i> 提现</span>',
                        'watch': '<span class="badge" style="background:#fce7f3;color:#be185d;"><i class="fa fa-play-circle"></i> 观看</span>',
                        'task': '<span class="badge" style="background:#e0e7ff;color:#3730a3;"><i class="fa fa-tasks"></i> 任务</span>',
                        'share': '<span class="badge" style="background:#d1fae5;color:#059669;"><i class="fa fa-share-alt"></i> 分享</span>'
                    };
                    return typeMap[value] || '<span class="badge" style="background:#e5e7eb;color:#4b5563;">' + (value || '其他') + '</span>';
                },
                deviceType: function(value, row, index) {
                    var iconMap = {
                        'ios': '<i class="fa fa-apple" style="color:#333;"></i>',
                        'android': '<i class="fa fa-android" style="color:#3ddc84;"></i>',
                        'web': '<i class="fa fa-globe" style="color:#4285f4;"></i>'
                    };
                    return iconMap[value] || '<i class="fa fa-mobile-alt"></i> ' + (value || '-');
                },
                deviceRisk: function(value, row, index) {
                    var riskMap = {
                        'safe': '<span class="badge" style="background:#d1fae5;color:#047857;">安全</span>',
                        'suspicious': '<span class="badge" style="background:#fef3c7;color:#b45309;">可疑</span>',
                        'dangerous': '<span class="badge" style="background:#fee2e2;color:#b91c1c;">危险</span>',
                        'blacklist': '<span class="badge" style="background:#dc2626;color:#fff;">黑名</span>'
                    };
                    return riskMap[value] || value;
                },
                operate: function(value, row, index) {
                    var that = this;
                    var table = $(that.table).clone(true);
                    var userId = row.id;
                    var userName = row.nickname || row.username || row.id;
                    var status = row.status;
                    
                    var html = '<div class="btn-group-operate">';
                    
                    // 详情按钮
                    html += '<button type="button" class="btn btn-primary btn-xs" onclick="UserAPI.showDetail(' + userId + ')" title="查看详情"><i class="fa fa-eye"></i>详情</button>';
                    // 编辑按钮
                    html += '<a href="member/user/edit?ids=' + userId + '" class="btn btn-info btn-xs btn-dialog" data-area=\'["800px","600px"]\' title="编辑用户"><i class="fa fa-edit"></i>编辑</a>';
                    
                    // 金币操作下拉
                    html += '<div class="btn-group">';
                    html += '<button type="button" class="btn btn-warning btn-xs dropdown-toggle" data-toggle="dropdown" title="金币操作"><i class="fa fa-coins"></i>金币</button>';
                    html += '<ul class="dropdown-menu dropdown-menu-right">';
                    html += '<li class="dropdown-header">金币操作</li>';
                    html += '<li><a href="javascript:;" onclick="UserAPI.recharge(' + userId + ',\'' + userName + '\')"><i class="fa fa-plus-circle text-success"></i> 充值金币</a></li>';
                    html += '<li><a href="javascript:;" onclick="UserAPI.deduct(' + userId + ',\'' + userName + '\',' + (row.coin_balance || 0) + ')"><i class="fa fa-minus-circle text-danger"></i> 扣除金币</a></li>';
                    html += '<li class="divider"></li>';
                    html += '<li><a href="coin/log?user_id=' + userId + '" class="btn-dialog"><i class="fa fa-list-alt text-info"></i> 金币流水</a></li>';
                    html += '</ul></div>';
                    
                    // 风控操作下拉
                    html += '<div class="btn-group">';
                    html += '<button type="button" class="btn btn-danger btn-xs dropdown-toggle" data-toggle="dropdown" title="风控操作"><i class="fa fa-shield-alt"></i>风控</button>';
                    html += '<ul class="dropdown-menu dropdown-menu-right">';
                    html += '<li class="dropdown-header">风控操作</li>';
                    if (status === 'banned') {
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
                    html += '<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" title="更多操作"><i class="fa fa-ellipsis-h"></i>更多</button>';
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
            bindOperateEvents: function(table) {
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

                $(document).on('change', 'select[name="ban_type"]', function () {
                    if ($(this).val() === 'permanent') {
                        $('.ban-duration-group').hide();
                    } else {
                        $('.ban-duration-group').show();
                    }
                });

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

    // ========== 生成系统会员相关全局函数 ==========

    // 打开生成弹窗
    window.openGenerateModal = function() {
        $.ajax({
            url: 'member/user/getSystemMemberCount',
            type: 'GET',
            dataType: 'json',
            success: function(ret) {
                if (ret.code == 1) {
                    var data = ret.data;
                    $('#gen-sys-count').text(data.system_count);
                    $('#gen-real-count').text(data.real_count);
                    $('#gen-total-count').text(data.total_count);
                }
            }
        });

        $('#generate-form input[name="count"]').val(10);
        $('#generate-form input[name="password"]').val('qwe123');
        $('#generate-modal').modal('show');
    };

    // 设置生成数量
    window.setCount = function(num) {
        var $input = $('#generate-form input[name="count"]');
        $input.val(num);
        $input.css('border-color', '#f5576c');
        setTimeout(function() { $input.css('border-color', '#e5e7eb'); }, 300);
    };

    // 调整数量
    window.adjustCount = function(add) {
        var $input = $('#generate-form input[name="count"]');
        var current = parseInt($input.val()) || 0;
        var newVal = Math.max(1, Math.min(current + add, 500));
        $input.val(newVal);
    };

    // 确认生成
    window.confirmGenerate = function() {
        var count = parseInt($('#generate-form input[name="count"]').val()) || 0;
        var password = $('#generate-form input[name="password"]').val() || 'qwe123';

        if (count <= 0 || count > 500) {
            Toastr.error('生成数量需在1~500之间');
            return;
        }
        if (password.length < 4) {
            Toastr.error('密码长度不能少于4位');
            return;
        }

        Layer.confirm(
            '<div style="text-align:center;padding:12px 0;">' +
            '<div style="width:56px;height:56px;margin:0 auto 10px;background:linear-gradient(135deg,#f093fb,#f5576c);border-radius:14px;display:flex;align-items:center;justify-content:center;">' +
            '<i class="fa fa-robot" style="font-size:24px;color:#fff;"></i></div>' +
            '<div style="font-size:16px;font-weight:700;color:#1f2937;">即将生成 <span style="color:#f5576c;">' + count + '</span> 个系统会员</div>' +
            '<div style="font-size:12px;color:#9ca3af;margin-top:6px;">密码: <code style="background:#f3f4f6;padding:1px 6px;border-radius:4px;font-size:11px;">' + password + '</code> &middot; 头像从附件库随机分配</div>' +
            '</div>',
            {
                title: '<i class="fa fa-magic" style="color:#f5576c;margin-right:5px;"></i>确认生成',
                btn: ['<i class="fa fa-check"></i> 确认', '<i class="fa fa-times"></i> 取消'],
                btn1: function(index) {
                    var loadIndex = Layer.load(2, {shade: [0.25, '#000'], content: '<div style="padding-top:20px;color:#fff;font-size:13px;"><i class="fa fa-spinner fa-spin" style="font-size:20px;display:block;margin-bottom:8px;"></i>正在生成，请稍候...</div>'});

                    Fast.api.ajax({
                        url: 'member/user/generateSystemMembers',
                        data: { count: count, password: password }
                    }, function(ret) {
                        Layer.close(loadIndex);
                        Layer.close(index);
                        $('#generate-modal').modal('hide');

                        var data = ret.data || {};
                        var msg = '成功生成 ' + (data.success || 0) + ' 个系统会员';
                        if (data.failed > 0) {
                            msg += '，失败 ' + data.failed + ' 个';
                        }
                        if (data.has_avatar) {
                            msg += '（已随机分配头像）';
                        }
                        Toastr.success(msg);
                        $('#gen-sys-count').text(data.total_system_members || 0);
                        $("#table").bootstrapTable('refresh');

                        if (data.errors && data.errors.length > 0) {
                            console.warn('生成失败详情:', data.errors);
                        }
                    }, function() {
                        Layer.close(loadIndex);
                    });
                }
            }
        );
    };

    // 筛选全部会员
    window.filterAllMembers = function() {
        var table = $("#table");
        var options = table.bootstrapTable('getOptions');
        options.queryParams = function(params) {
            delete params.filter;
            return params;
        };
        table.bootstrapTable('refresh', {silent: true});
        Toastr.info('已重置：全部会员');
    };

    // 筛选系统会员
    window.filterSystemMembers = function() {
        var table = $("#table");
        var options = table.bootstrapTable('getOptions');
        options.queryParams = function(params) {
            params.filter = JSON.stringify({user_type: '1'});
            return params;
        };
        table.bootstrapTable('refresh', {silent: true});
        Toastr.info('已筛选：系统会员');
    };

    // 筛选真实会员
    window.filterRealMembers = function() {
        var table = $("#table");
        var options = table.bootstrapTable('getOptions');
        options.queryParams = function(params) {
            params.filter = JSON.stringify({user_type: '0'});
            return params;
        };
        table.bootstrapTable('refresh', {silent: true});
        Toastr.info('已筛选：真实会员');
    };

    return Controller;
});

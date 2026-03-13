define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/userrisk/index',
                    detail_url: 'risk/userrisk/detail',
                    del_url: 'risk/userrisk/del',
                    multi_url: 'risk/userrisk/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'total_score',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, visible: false},
                        {field: 'user_id', title: '用户ID', sortable: true, visible: false},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'total_score', title: '风险总分', sortable: true, operate: 'BETWEEN', formatter: Controller.api.formatter.score},
                        {field: 'video_score', title: '视频风险分', visible: false},
                        {field: 'task_score', title: '任务风险分', visible: false},
                        {field: 'withdraw_score', title: '提现风险分', visible: false},
                        {field: 'redpacket_score', title: '红包风险分', visible: false},
                        {field: 'invite_score', title: '邀请风险分', visible: false},
                        {field: 'global_score', title: '全局风险分', visible: false},
                        {field: 'risk_level', title: '风险等级', searchList: {
                            "safe": "安全",
                            "low": "低",
                            "medium": "中",
                            "high": "高",
                            "dangerous": "危险"
                        }, formatter: Controller.api.formatter.riskLevel},
                        {field: 'status', title: '状态', searchList: {
                            "normal": "正常",
                            "frozen": "冻结",
                            "banned": "封禁"
                        }, formatter: Controller.api.formatter.statusText},
                        {field: 'violation_count', title: '违规次数', sortable: true},
                        {field: 'last_violation_time', title: '最后违规时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'updatetime', title: '更新时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
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
                score: function (value, row, index) {
                    var score = parseFloat(value) || 0;
                    var color = 'success';
                    if (score >= 200) color = 'danger';
                    else if (score >= 100) color = 'warning';
                    else if (score >= 50) color = 'info';
                    return '<span class="badge bg-' + color + '">' + score.toFixed(0) + '</span>';
                },
                riskLevel: function (value, row, index) {
                    var colorMap = {
                        'safe': 'success',
                        'low': 'info',
                        'medium': 'warning',
                        'high': 'danger',
                        'dangerous': 'danger'
                    };
                    var textMap = {
                        'safe': '安全',
                        'low': '低',
                        'medium': '中',
                        'high': '高',
                        'dangerous': '危险'
                    };
                    return '<span class="label label-' + (colorMap[value] || 'default') + '">' + (textMap[value] || value) + '</span>';
                },
                statusText: function (value, row, index) {
                    var colorMap = {
                        'normal': 'success',
                        'frozen': 'warning',
                        'banned': 'danger'
                    };
                    var textMap = {
                        'normal': '正常',
                        'frozen': '冻结',
                        'banned': '封禁'
                    };
                    return '<span class="label label-' + (colorMap[value] || 'default') + '">' + (textMap[value] || value) + '</span>';
                },
                operate: function (value, row, index) {
                    var that = $.extend({}, this);
                    var table = $(that.table).clone(true);
                    
                    var buttons = [];
                    
                    // 判断是否已撤销（最近7天内有撤销记录）
                    if (row.is_revoked == 1) {
                        // 已撤销，显示已撤销标签
                        var revokeTime = row.last_revoke_time ? '撤销于 ' + (new Date(row.last_revoke_time * 1000).toLocaleString()) : '已撤销';
                        buttons.push('<span class="label label-default" title="' + revokeTime + '"><i class="fa fa-check"></i> 已撤销</span>');
                        
                        // 如果在白名单中，显示移出白名单按钮
                        buttons.push('<a href="javascript:;" class="btn btn-danger btn-xs btn-remove-whitelist" data-user-id="' + row.user_id + '"><i class="fa fa-times"></i> 移出白名单</a>');
                    } else {
                        // 未撤销，显示撤销风控按钮
                        buttons.push('<a href="javascript:;" class="btn btn-success btn-xs btn-revoke" data-user-id="' + row.user_id + '"><i class="fa fa-undo"></i> 撤销风控</a>');
                    }
                    
                    // 如果是封禁状态，显示解封按钮
                    if (row.status === 'banned' || row.status === 'frozen') {
                        buttons.push('<a href="javascript:;" class="btn btn-warning btn-xs btn-release" data-user-id="' + row.user_id + '"><i class="fa fa-unlock"></i> 解封</a>');
                    }
                    
                    return buttons.join(' ');
                }
            }
        }
    };

    // 撤销风控事件
    $(document).on('click', '.btn-revoke', function () {
        var userId = $(this).attr('data-user-id');
        if (!userId) {
            Layer.alert('无法获取用户ID', {icon: 2});
            return;
        }
        Controller.api.showRevokeModal(userId);
    });

    // 解封事件
    $(document).on('click', '.btn-release', function () {
        var userId = $(this).attr('data-user-id');
        if (!userId) {
            Layer.alert('无法获取用户ID', {icon: 2});
            return;
        }
        var that = this;
        Layer.confirm('确定要解封该用户吗？', {
            title: '解封确认',
            btn: ['确定', '取消']
        }, function (layerIndex) {
            $.ajax({
                url: 'risk/userrisk/release',
                type: 'POST',
                dataType: 'json',
                data: {user_id: userId, reason: '管理员手动解封'},
                success: function (ret) {
                    Layer.close(layerIndex);
                    if (ret.code === 1) {
                        Layer.alert('解封成功', {icon: 1});
                        $(that).closest('table').bootstrapTable('refresh');
                    } else {
                        Layer.alert(ret.msg || '解封失败', {icon: 2});
                    }
                },
                error: function () {
                    Layer.alert('请求失败', {icon: 2});
                }
            });
        });
    });

    // 移出白名单事件
    $(document).on('click', '.btn-remove-whitelist', function () {
        var userId = $(this).attr('data-user-id');
        if (!userId) {
            Layer.alert('无法获取用户ID', {icon: 2});
            return;
        }
        var that = this;
        Layer.confirm('确定要将该用户移出白名单吗？移出后将恢复风控检查。', {
            title: '移出白名单确认',
            btn: ['确定', '取消']
        }, function (layerIndex) {
            $.ajax({
                url: 'risk/userrisk/removeWhitelist',
                type: 'POST',
                dataType: 'json',
                data: {user_id: userId},
                success: function (ret) {
                    Layer.close(layerIndex);
                    if (ret.code === 1) {
                        Layer.alert('已移出白名单', {icon: 1});
                        $(that).closest('table').bootstrapTable('refresh');
                    } else {
                        Layer.alert(ret.msg || '操作失败', {icon: 2});
                    }
                },
                error: function () {
                    Layer.alert('请求失败', {icon: 2});
                }
            });
        });
    });

    // 撤销风控弹窗
    Controller.api.showRevokeModal = function (userId) {
        // 显示加载中
        var loadIndex = Layer.load(1);
        
        $.ajax({
            url: 'risk/userrisk/revokeInfo',
            type: 'GET',
            dataType: 'json',
            data: {user_id: userId},
            success: function (ret) {
                Layer.close(loadIndex);
                
                if (ret.code !== 1) {
                    Layer.alert(ret.msg || '获取用户信息失败', {icon: 2});
                    return;
                }
                
                var info = ret.data;
                
                // 构建弹窗内容
                var html = '<div class="revoke-modal-content" style="padding: 15px;">';
                
                // 用户基本信息
                html += '<div class="panel panel-default">';
                html += '<div class="panel-heading"><i class="fa fa-user"></i> 用户基本信息</div>';
                html += '<div class="panel-body">';
                html += '<div class="row">';
                html += '<div class="col-md-4"><p><strong>用户名：</strong>' + (info.user.username || '-') + '</p></div>';
                html += '<div class="col-md-4"><p><strong>昵称：</strong>' + (info.user.nickname || '-') + '</p></div>';
                html += '<div class="col-md-4"><p><strong>手机号：</strong>' + (info.user.mobile || '-') + '</p></div>';
                html += '</div>';
                html += '<div class="row">';
                html += '<div class="col-md-4"><p><strong>风险总分：</strong><span class="label label-danger">' + (info.risk_score.total_score || 0) + '</span></p></div>';
                html += '<div class="col-md-4"><p><strong>风险等级：</strong>' + Controller.api.formatter.riskLevel(info.risk_score.risk_level) + '</p></div>';
                html += '<div class="col-md-4"><p><strong>违规次数：</strong>' + (info.risk_score.violation_count || 0) + '</p></div>';
                html += '</div>';
                // 白名单状态
                if (info.in_whitelist == 1) {
                    html += '<div class="row"><div class="col-md-12"><p><span class="label label-success"><i class="fa fa-shield"></i> 当前在白名单中</span></p></div></div>';
                }
                html += '</div></div>';
                
                // 最近风控记录
                html += '<div class="panel panel-default" style="margin-top: 15px;">';
                html += '<div class="panel-heading"><i class="fa fa-list"></i> 最近风控记录</div>';
                html += '<div class="panel-body" style="max-height: 250px; overflow-y: auto;">';
                if (info.risk_logs && info.risk_logs.length > 0) {
                    html += '<table class="table table-striped table-condensed">';
                    html += '<thead><tr><th>时间</th><th>规则</th><th>类型</th><th>加分</th><th>动作</th></tr></thead>';
                    html += '<tbody>';
                    info.risk_logs.forEach(function(log) {
                        html += '<tr>';
                        html += '<td>' + (log.createtime_text || '-') + '</td>';
                        html += '<td>' + (log.rule_name || '-') + '</td>';
                        html += '<td>' + (log.rule_type || '-') + '</td>';
                        html += '<td><span class="text-danger">+' + (log.score_add || 0) + '</span></td>';
                        html += '<td>' + (log.action || '-') + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p class="text-muted text-center">暂无风控记录</p>';
                }
                html += '</div></div>';
                
                // 撤销选项
                html += '<div class="panel panel-default" style="margin-top: 15px;">';
                html += '<div class="panel-heading"><i class="fa fa-cog"></i> 撤销选项</div>';
                html += '<div class="panel-body">';
                html += '<form id="revoke-form">';
                html += '<div class="form-group">';
                html += '<label>撤销方式</label>';
                html += '<select name="revoke_type" class="form-control">';
                html += '<option value="reset">重置风险分（清零所有风险分）</option>';
                html += '<option value="reduce">降低风险分（减少指定分数）</option>';
                html += '<option value="whitelist">加入白名单（豁免风控检查）</option>';
                html += '</select>';
                html += '</div>';
                html += '<div class="form-group reduce-score-group" style="display:none;">';
                html += '<label>减少分数</label>';
                html += '<input type="number" name="reduce_score" class="form-control" value="50" min="1">';
                html += '</div>';
                html += '<div class="form-group whitelist-days-group" style="display:none;">';
                html += '<label>白名单有效期（天，0为永久）</label>';
                html += '<input type="number" name="whitelist_days" class="form-control" value="30" min="0">';
                html += '</div>';
                html += '<div class="form-group">';
                html += '<label>撤销原因</label>';
                html += '<textarea name="reason" class="form-control" rows="2" placeholder="请填写撤销原因"></textarea>';
                html += '</div>';
                html += '<input type="hidden" name="user_id" value="' + userId + '">';
                html += '</form>';
                html += '</div></div>';
                
                html += '</div>';
                
                // 显示弹窗
                Layer.open({
                    type: 1,
                    title: '撤销风控',
                    area: ['700px', 'auto'],
                    maxHeight: 500,
                    content: html,
                    btn: ['确认撤销', '取消'],
                    yes: function (layerIndex, layero) {
                        var form = $('#revoke-form');
                        var revokeType = form.find('select[name="revoke_type"]').val();
                        var reduceScore = form.find('input[name="reduce_score"]').val();
                        var whitelistDays = form.find('input[name="whitelist_days"]').val();
                        var reason = form.find('textarea[name="reason"]').val();
                        
                        $.ajax({
                            url: 'risk/userrisk/revoke',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                user_id: userId,
                                revoke_type: revokeType,
                                reduce_score: reduceScore,
                                whitelist_days: whitelistDays,
                                reason: reason
                            },
                            success: function (ret) {
                                if (ret.code === 1) {
                                    Layer.close(layerIndex);
                                    Layer.alert('撤销成功', {icon: 1});
                                    $('#table').bootstrapTable('refresh');
                                } else {
                                    Layer.alert(ret.msg || '撤销失败', {icon: 2});
                                }
                            },
                            error: function () {
                                Layer.alert('请求失败', {icon: 2});
                            }
                        });
                    }
                });
                
                // 撤销方式切换
                $('select[name="revoke_type"]').on('change', function() {
                    var val = $(this).val();
                    $('.reduce-score-group').toggle(val === 'reduce');
                    $('.whitelist-days-group').toggle(val === 'whitelist');
                });
            },
            error: function () {
                Layer.close(loadIndex);
                Layer.alert('获取用户信息失败', {icon: 2});
            }
        });
    };

    return Controller;
});

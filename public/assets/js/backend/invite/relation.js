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
                            field: 'level1_count',
                            title: '一级下级',
                            sortable: true,
                            width: '120px',
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
                            width: '120px',
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
                            width: '120px',
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
                            title: '下级提现总额',
                            sortable: true,
                            width: '120px',
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
                            width: '100px',
                            formatter: function(value, row, index) {
                                var val = parseFloat(value || 0);
                                if (val > 0) {
                                    return '<span style="color:#27ae60;">¥' + val.toFixed(2) + '</span>';
                                }
                                return '<span class="text-muted">-</span>';
                            }
                        },
                        {
                            field: 'pending_commission',
                            title: '待结算',
                            sortable: true,
                            width: '80px',
                            formatter: function(value, row, index) {
                                var val = parseFloat(value || 0);
                                if (val > 0) {
                                    return '<span style="color:#f39c12;">¥' + val.toFixed(2) + '</span>';
                                }
                                return '<span class="text-muted">-</span>';
                            }
                        },
                        {
                            field: 'operate',
                            title: '操作',
                            table: table,
                            width: '120px',
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
        
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 风险类型映射
            var riskTypeMap = {
                'ip_check': 'IP检测',
                'device_check': '设备检测',
                'frequency_check': '频率检测',
                'amount_check': '金额检测',
                'account_check': '账号检测',
                'risk_check': '风险检测',
                'score_check': '评分检测',
                'video_watch_speed': '视频观看速度',
                'video_watch_repeat': '视频重复观看',
                'video_daily_limit': '视频每日限额',
                'video_reward_speed': '视频奖励速度',
                'video_skip_ratio': '视频跳过比例',
                'task_complete_speed': '任务完成速度',
                'task_daily_limit': '任务每日限额',
                'task_repeat_submit': '任务重复提交',
                'task_fake_behavior': '任务虚假行为',
                'withdraw_frequency': '提现频率',
                'withdraw_amount_anomaly': '提现金额异常',
                'withdraw_new_account': '新账号提现',
                'redpacket_grab_speed': '红包抢夺速度',
                'redpacket_daily_limit': '红包每日限额',
                'invite_speed': '邀请速度',
                'invite_fake_account': '邀请虚假账号',
                'ip_multi_account': 'IP多账号',
                'device_multi_account': '设备多账号',
                'behavior_pattern': '行为模式',
                'video': '视频',
                'task': '任务',
                'withdraw': '提现',
                'redpacket': '红包',
                'invite': '邀请',
                'global': '全局'
            };

            // 风险等级映射
            var riskLevelMap = {
                0: '普通',
                1: '低风险',
                2: '中风险',
                3: '高风险'
            };

            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/risklog/index',
                    detail_url: 'withdraw/risklog/detail',
                    del_url: 'withdraw/risklog/del',
                    multi_url: 'withdraw/risklog/multi',
                    table: '',
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
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'risk_type', title: '风险类型', searchList: riskTypeMap, formatter: function(value, row, index) {
                            var text = riskTypeMap[value] || value;
                            var colorMap = {
                                'ip_check': 'label-info',
                                'device_check': 'label-primary',
                                'frequency_check': 'label-warning',
                                'amount_check': 'label-danger',
                                'account_check': 'label-danger',
                                'risk_check': 'label-danger'
                            };
                            var color = colorMap[value] || 'label-default';
                            return '<span class="label ' + color + '">' + text + '</span>';
                        }},
                        {field: 'risk_level', title: '风险等级', searchList: {
                            "1": "低风险",
                            "2": "中风险",
                            "3": "高风险"
                        }, formatter: function(value, row, index) {
                            var map = {
                                0: '<span class="label label-default">普通</span>',
                                1: '<span class="label label-success">低风险</span>',
                                2: '<span class="label label-warning">中风险</span>',
                                3: '<span class="label label-danger">高风险</span>'
                            };
                            return map[value] || '<span class="label label-default">未知</span>';
                        }},
                        {field: 'risk_score', title: '风险评分', sortable: true, formatter: function(value, row, index) {
                            if (value >= 50) {
                                return '<span class="text-danger"><strong>' + value + '</strong></span>';
                            } else if (value >= 30) {
                                return '<span class="text-warning"><strong>' + value + '</strong></span>';
                            }
                            return '<span class="text-success">' + value + '</span>';
                        }},
                        {field: 'handle_action', title: '处理状态', searchList: {
                            "pass": "通过",
                            "review": "人工审核",
                            "reject": "拒绝",
                            "freeze": "冻结"
                        }, formatter: function(value, row, index) {
                            var map = {
                                "pass": '<span class="label label-success">已通过</span>',
                                "review": '<span class="label label-info">审核中</span>',
                                "reject": '<span class="label label-warning">已拒绝</span>',
                                "freeze": '<span class="label label-danger">已冻结</span>'
                            };
                            return map[value] || '<span class="label label-default">待处理</span>';
                        }},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate',
                            title: '操作',
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '风控记录详情',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-list',
                                    url: 'withdraw/risklog/detail',
                                    extend: 'data-area=\'["90%","90%"]\''
                                },
                                {
                                    name: 'pass',
                                    text: '通过',
                                    title: '通过审核',
                                    classname: 'btn btn-xs btn-success',
                                    icon: 'fa fa-check',
                                    hidden: function(row) {
                                        return row.handle_action === 'pass' || row.handle_action === 'reject' || row.handle_action === 'freeze';
                                    },
                                    click: function(e, row) {
                                        showConfirmDialog(row, 'pass');
                                    }
                                },
                                {
                                    name: 'review',
                                    text: '审核',
                                    title: '人工审核',
                                    classname: 'btn btn-xs btn-warning',
                                    icon: 'fa fa-user',
                                    hidden: function(row) {
                                        return row.handle_action === 'pass' || row.handle_action === 'reject' || row.handle_action === 'freeze';
                                    },
                                    click: function(e, row) {
                                        showConfirmDialog(row, 'review');
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '拒绝',
                                    title: '拒绝处理',
                                    classname: 'btn btn-xs btn-danger',
                                    icon: 'fa fa-ban',
                                    hidden: function(row) {
                                        return row.handle_action === 'pass' || row.handle_action === 'reject' || row.handle_action === 'freeze';
                                    },
                                    click: function(e, row) {
                                        showConfirmDialog(row, 'reject');
                                    }
                                },
                                {
                                    name: 'freeze',
                                    text: '冻结',
                                    title: '冻结用户',
                                    classname: 'btn btn-xs btn-default',
                                    icon: 'fa fa-snowflake-o',
                                    hidden: function(row) {
                                        return row.handle_action === 'pass' || row.handle_action === 'reject' || row.handle_action === 'freeze';
                                    },
                                    click: function(e, row) {
                                        showConfirmDialog(row, 'freeze');
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 显示确认弹窗
            function showConfirmDialog(row, action) {
                var riskTypeText = riskTypeMap[row.risk_type] || row.risk_type;
                var riskLevelText = riskLevelMap[row.risk_level] || '未知';
                
                var content = '<div style="padding: 15px;">';
                
                // 基本信息
                content += '<div class="panel panel-default"><div class="panel-heading"><strong>记录基本信息</strong></div><div class="panel-body">';
                content += '<table class="table table-bordered" style="margin-bottom:0;">';
                content += '<tr><th width="100">记录ID</th><td>' + row.id + '</td><th width="100">用户ID</th><td>' + row.user_id + '</td></tr>';
                content += '<tr><th>用户名</th><td>' + (row.username || '-') + '</td><th>订单号</th><td>' + (row.order_no || '-') + '</td></tr>';
                content += '<tr><th>风险类型</th><td>' + riskTypeText + '</td><th>风险等级</th><td>' + riskLevelText + '</td></tr>';
                content += '<tr><th>风险评分</th><td colspan="3"><strong class="text-danger">' + row.risk_score + '</strong></td></tr>';
                content += '</table></div></div>';

                // 操作说明
                var actionInfo = {
                    'pass': {
                        'title': '通过审核',
                        'effect': '<div class="alert alert-success"><i class="fa fa-info-circle"></i> <strong>操作效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li>该风控记录将被标记为"已通过"</li>'
                            + '<li>用户的提现请求将正常继续处理</li>'
                            + '<li>用户账号状态不受影响</li>'
                            + '</ul></div>',
                        'next': '<div class="alert alert-info"><i class="fa fa-list-ol"></i> <strong>后续操作：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li>系统将继续处理用户的提现请求</li>'
                            + '<li>如需重新审核，可在详情页修改状态</li>'
                            + '</ul></div>',
                        'btn': 'btn-success',
                        'btnText': '确认通过'
                    },
                    'review': {
                        'title': '人工审核',
                        'effect': '<div class="alert alert-warning"><i class="fa fa-info-circle"></i> <strong>操作效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li>该风控记录将被标记为"人工审核中"</li>'
                            + '<li>提现请求将被暂停，等待人工进一步确认</li>'
                            + '<li>用户账号状态不受影响</li>'
                            + '</ul></div>',
                        'next': '<div class="alert alert-info"><i class="fa fa-list-ol"></i> <strong>后续操作：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li>需要高级管理员进行最终审核</li>'
                            + '<li>可选择通过、拒绝或冻结用户</li>'
                            + '<li>建议联系用户核实情况</li>'
                            + '</ul></div>',
                        'btn': 'btn-warning',
                        'btnText': '确认标记审核'
                    },
                    'reject': {
                        'title': '拒绝处理',
                        'effect': '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <strong>操作效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li>该风控记录将被标记为"已拒绝"</li>'
                            + '<li>用户的提现请求将被取消</li>'
                            + '<li>用户账号状态不受影响</li>'
                            + '</ul></div>',
                        'next': '<div class="alert alert-info"><i class="fa fa-list-ol"></i> <strong>后续操作：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li>如需退款，请在提现订单中处理</li>'
                            + '<li>系统将记录拒绝原因</li>'
                            + '<li>用户可重新发起提现申请</li>'
                            + '</ul></div>',
                        'btn': 'btn-danger',
                        'btnText': '确认拒绝',
                        'needReason': true,
                        'reasonOptions': ['异常提现行为', '疑似刷单', '风险用户', '频繁操作', '资料不完整', '其他']
                    },
                    'freeze': {
                        'title': '冻结用户',
                        'effect': '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <strong>操作效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li><span class="text-danger"><strong>该用户账号将被冻结，无法登录和操作</strong></span></li>'
                            + '<li>用户的所有提现请求将被暂停</li>'
                            + '<li>用户的金币将被冻结</li>'
                            + '</ul></div>',
                        'next': '<div class="alert alert-info"><i class="fa fa-list-ol"></i> <strong>后续操作：</strong><ul style="margin:5px 0 0 20px;padding:0;">'
                            + '<li>冻结后用户无法登录APP</li>'
                            + '<li>如需解冻，请在"用户管理"中操作</li>'
                            + '<li>建议记录详细的冻结原因</li>'
                            + '</ul></div>',
                        'btn': 'btn-danger',
                        'btnText': '确认冻结',
                        'needReason': true,
                        'reasonOptions': ['涉嫌欺诈', '频繁违规操作', '高风险用户', '异常账号', '多账号作弊', '其他']
                    }
                };

                var info = actionInfo[action];
                content += info.effect;
                content += info.next;

                // 如果需要填写原因
                if (info.needReason) {
                    content += '<div class="form-group" style="margin-top:15px;">';
                    content += '<label><span class="text-danger">*</span> 请选择/填写原因：</label>';
                    content += '<select class="form-control reason-select" style="margin-bottom:10px;">';
                    content += '<option value="">请选择原因</option>';
                    for (var i = 0; i < info.reasonOptions.length; i++) {
                        content += '<option value="' + info.reasonOptions[i] + '">' + info.reasonOptions[i] + '</option>';
                    }
                    content += '</select>';
                    content += '<textarea class="form-control reason-remark" rows="2" placeholder="详细说明（选填）"></textarea>';
                    content += '</div>';
                }

                content += '</div>';

                // 弹窗
                var layerIndex = Layer.open({
                    type: 1,
                    title: '<i class="fa fa-edit"></i> ' + info.title,
                    area: ['600px', 'auto'],
                    content: content,
                    btn: ['<i class="fa fa-check"></i> ' + info.btnText, '<i class="fa fa-times"></i> 取消'],
                    btn1: function(index) {
                        var remark = '';
                        
                        // 如果需要原因
                        if (info.needReason) {
                            var $layer = $('#layui-layer' + layerIndex);
                            remark = $layer.find('.reason-select').val();
                            var customRemark = $layer.find('.reason-remark').val();
                            
                            if (!remark) {
                                Toastr.error('请选择原因');
                                return;
                            }
                            if (customRemark) {
                                remark += '：' + customRemark;
                            }
                        }

                        // 执行操作
                        Fast.api.ajax({
                            url: 'withdraw/risklog/' + action,
                            data: {ids: row.id, remark: remark}
                        }, function(data, ret) {
                            Layer.close(layerIndex);
                            table.bootstrapTable('refresh');
                            Toastr.success(ret.msg);
                        });
                    },
                    btn2: function(index) {
                        Layer.close(index);
                    }
                });
            }

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 下拉菜单筛选功能
            $(document).on('click', '.dropdown-menu a[data-field]', function(e) {
                e.preventDefault();
                var field = $(this).data('field');
                var value = $(this).data('value');
                var queryParams = {};
                queryParams[field] = value;
                table.bootstrapTable('refresh', {
                    query: queryParams
                });
            });

            // 批量通过
            $(document).on('click', '.btn-batch-pass', function(e) {
                e.preventDefault();
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.warning('请选择要操作的记录');
                    return;
                }
                
                var content = '<div style="padding:15px;">';
                content += '<div class="alert alert-success"><i class="fa fa-info-circle"></i> <strong>批量通过效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">';
                content += '<li>选中 ' + ids.length + ' 条记录将被标记为"已通过"</li>';
                content += '<li>相关用户的提现请求将正常继续处理</li>';
                content += '<li>用户账号状态不受影响</li>';
                content += '</ul></div></div>';
                
                Layer.confirm(content, {
                    title: '<i class="fa fa-check"></i> 批量通过确认',
                    btn: ['<i class="fa fa-check"></i> 确认通过', '取消']
                }, function(index) {
                    Fast.api.ajax({
                        url: 'withdraw/risklog/multi',
                        data: {ids: ids.join(','), action: 'pass'}
                    }, function(data, ret) {
                        Layer.close(index);
                        table.bootstrapTable('refresh');
                        Toastr.success(ret.msg);
                    });
                });
            });

            // 批量人工审核
            $(document).on('click', '.btn-batch-review', function(e) {
                e.preventDefault();
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.warning('请选择要操作的记录');
                    return;
                }
                
                var content = '<div style="padding:15px;">';
                content += '<div class="alert alert-warning"><i class="fa fa-info-circle"></i> <strong>批量审核效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">';
                content += '<li>选中 ' + ids.length + ' 条记录将被标记为"人工审核中"</li>';
                content += '<li>相关提现请求将被暂停</li>';
                content += '<li>需要高级管理员进一步确认</li>';
                content += '</ul></div></div>';
                
                Layer.confirm(content, {
                    title: '<i class="fa fa-user"></i> 批量审核确认',
                    btn: ['<i class="fa fa-check"></i> 确认标记', '取消']
                }, function(index) {
                    Fast.api.ajax({
                        url: 'withdraw/risklog/multi',
                        data: {ids: ids.join(','), action: 'review'}
                    }, function(data, ret) {
                        Layer.close(index);
                        table.bootstrapTable('refresh');
                        Toastr.success(ret.msg);
                    });
                });
            });

            // 批量拒绝
            $(document).on('click', '.btn-batch-reject', function(e) {
                e.preventDefault();
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.warning('请选择要操作的记录');
                    return;
                }
                
                var content = '<div style="padding:15px;">';
                content += '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <strong>批量拒绝效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">';
                content += '<li>选中 ' + ids.length + ' 条记录将被标记为"已拒绝"</li>';
                content += '<li>相关用户的提现请求将被取消</li>';
                content += '<li>用户账号状态不受影响</li>';
                content += '</ul></div>';
                content += '<div class="form-group" style="margin-top:15px;">';
                content += '<label><span class="text-danger">*</span> 请选择/填写原因：</label>';
                content += '<select class="form-control batch-reason-select" style="margin-bottom:10px;">';
                content += '<option value="">请选择原因</option>';
                content += '<option value="异常提现行为">异常提现行为</option>';
                content += '<option value="疑似刷单">疑似刷单</option>';
                content += '<option value="风险用户">风险用户</option>';
                content += '<option value="频繁操作">频繁操作</option>';
                content += '<option value="其他">其他</option>';
                content += '</select>';
                content += '<textarea class="form-control batch-reason-remark" rows="2" placeholder="详细说明（选填）"></textarea>';
                content += '</div></div>';
                
                var layerIndex = Layer.open({
                    type: 1,
                    title: '<i class="fa fa-ban"></i> 批量拒绝确认',
                    area: ['500px', 'auto'],
                    content: content,
                    btn: ['<i class="fa fa-check"></i> 确认拒绝', '取消'],
                    btn1: function(index) {
                        var $layer = $('#layui-layer' + layerIndex);
                        var remark = $layer.find('.batch-reason-select').val();
                        var customRemark = $layer.find('.batch-reason-remark').val();
                        
                        if (!remark) {
                            Toastr.error('请选择原因');
                            return;
                        }
                        if (customRemark) {
                            remark += '：' + customRemark;
                        }
                        
                        Fast.api.ajax({
                            url: 'withdraw/risklog/multi',
                            data: {ids: ids.join(','), action: 'reject', remark: remark}
                        }, function(data, ret) {
                            Layer.close(layerIndex);
                            table.bootstrapTable('refresh');
                            Toastr.success(ret.msg);
                        });
                    }
                });
            });

            // 批量冻结
            $(document).on('click', '.btn-batch-freeze', function(e) {
                e.preventDefault();
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.warning('请选择要操作的记录');
                    return;
                }
                
                var content = '<div style="padding:15px;">';
                content += '<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> <strong>批量冻结效果：</strong><ul style="margin:5px 0 0 20px;padding:0;">';
                content += '<li><span class="text-danger"><strong>选中 ' + ids.length + ' 条记录对应的用户将被冻结</strong></span></li>';
                content += '<li>用户将无法登录APP和进行任何操作</li>';
                content += '<li>用户的金币将被冻结</li>';
                content += '</ul></div>';
                content += '<div class="form-group" style="margin-top:15px;">';
                content += '<label><span class="text-danger">*</span> 请选择/填写原因：</label>';
                content += '<select class="form-control batch-freeze-select" style="margin-bottom:10px;">';
                content += '<option value="">请选择原因</option>';
                content += '<option value="涉嫌欺诈">涉嫌欺诈</option>';
                content += '<option value="频繁违规操作">频繁违规操作</option>';
                content += '<option value="高风险用户">高风险用户</option>';
                content += '<option value="异常账号">异常账号</option>';
                content += '<option value="其他">其他</option>';
                content += '</select>';
                content += '<textarea class="form-control batch-freeze-remark" rows="2" placeholder="详细说明（选填）"></textarea>';
                content += '</div></div>';
                
                var layerIndex = Layer.open({
                    type: 1,
                    title: '<i class="fa fa-snowflake-o"></i> 批量冻结确认',
                    area: ['500px', 'auto'],
                    content: content,
                    btn: ['<i class="fa fa-check"></i> 确认冻结', '取消'],
                    btn1: function(index) {
                        var $layer = $('#layui-layer' + layerIndex);
                        var remark = $layer.find('.batch-freeze-select').val();
                        var customRemark = $layer.find('.batch-freeze-remark').val();
                        
                        if (!remark) {
                            Toastr.error('请选择原因');
                            return;
                        }
                        if (customRemark) {
                            remark += '：' + customRemark;
                        }
                        
                        Fast.api.ajax({
                            url: 'withdraw/risklog/multi',
                            data: {ids: ids.join(','), action: 'freeze', remark: remark}
                        }, function(data, ret) {
                            Layer.close(layerIndex);
                            table.bootstrapTable('refresh');
                            Toastr.success(ret.msg);
                        });
                    }
                });
            });
        },
        detail: function() {
            // 详情页面的JS逻辑已在内联脚本中实现
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

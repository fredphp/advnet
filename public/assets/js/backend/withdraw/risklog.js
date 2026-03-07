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
                // 旧类型兼容
                'video': '视频',
                'task': '任务',
                'withdraw': '提现',
                'redpacket': '红包',
                'invite': '邀请',
                'global': '全局'
            };

            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'withdraw/risklog/index',
                    detail_url: 'withdraw/risklog/detail',
                    del_url: 'withdraw/risklog/del',
                    multi_url: 'withdraw/risklog/multi',
                    pass_url: 'withdraw/risklog/pass',
                    review_url: 'withdraw/risklog/review',
                    reject_url: 'withdraw/risklog/reject',
                    freeze_url: 'withdraw/risklog/freeze',
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
                                'risk_check': 'label-danger',
                                'score_check': 'label-warning'
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
                                1: '<span class="label label-success">低风险</span>',
                                2: '<span class="label label-warning">中风险</span>',
                                3: '<span class="label label-danger">高风险</span>'
                            };
                            return map[value] || '<span class="label label-default">未知</span>';
                        }},
                        {field: 'risk_score', title: '风险评分', sortable: true, formatter: function(value, row, index) {
                            if (value >= 50) {
                                return '<span class="text-danger">' + value + '</span>';
                            } else if (value >= 30) {
                                return '<span class="text-warning">' + value + '</span>';
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
                                "pass": '<span class="label label-success">通过</span>',
                                "review": '<span class="label label-info">人工审核</span>',
                                "reject": '<span class="label label-warning">拒绝</span>',
                                "freeze": '<span class="label label-danger">冻结</span>'
                            };
                            return map[value] || '<span class="label label-default">' + (value || '待处理') + '</span>';
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
                                    title: '确认通过',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-check',
                                    url: 'withdraw/risklog/pass',
                                    confirm: '确认标记为通过？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'review',
                                    text: '审核',
                                    title: '人工审核',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-user',
                                    url: 'withdraw/risklog/review',
                                    confirm: '确认标记为需要人工审核？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'reject',
                                    text: '拒绝',
                                    title: '确认拒绝',
                                    classname: 'btn btn-xs btn-danger btn-ajax',
                                    icon: 'fa fa-ban',
                                    url: 'withdraw/risklog/reject',
                                    confirm: '确认拒绝该记录？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    }
                                },
                                {
                                    name: 'freeze',
                                    text: '冻结',
                                    title: '冻结用户',
                                    classname: 'btn btn-xs btn-default btn-ajax',
                                    icon: 'fa fa-snowflake-o',
                                    url: 'withdraw/risklog/freeze',
                                    confirm: '确认冻结该用户？冻结后用户将无法登录！',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 下拉菜单筛选功能
            $(document).on('click', '.dropdown-menu a[data-field]', function(e) {
                e.preventDefault();
                var field = $(this).data('field');
                var value = $(this).data('value');
                table.bootstrapTable('refresh', {
                    query: {
                        filter: JSON.stringify({}),
                        op: JSON.stringify({}),
                        offset: 0
                    },
                    silent: true
                });
                var options = table.bootstrapTable('getOptions');
                options.queryParams = function(params) {
                    params.filter = JSON.stringify({});
                    params.op = JSON.stringify({});
                    params[field] = value;
                    return params;
                };
                table.bootstrapTable('refresh');
            });

            // 批量通过
            $(document).on('click', '.btn-batch-pass', function(e) {
                e.preventDefault();
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.warning('请选择要操作的记录');
                    return;
                }
                Layer.confirm('确认批量通过选中的 ' + ids.length + ' 条记录？', function(index) {
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
                Layer.confirm('确认批量标记为人工审核？', function(index) {
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
                $('#reject-ids').val(ids.join(','));
                $('#reject-modal').modal('show');
            });

            // 确认拒绝
            $(document).on('click', '#btn-confirm-reject', function(e) {
                e.preventDefault();
                var ids = $('#reject-ids').val();

                Fast.api.ajax({
                    url: 'withdraw/risklog/multi',
                    data: {ids: ids, action: 'reject'}
                }, function(data, ret) {
                    $('#reject-modal').modal('hide');
                    table.bootstrapTable('refresh');
                    Toastr.success(ret.msg);
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
                $('#freeze-ids').val(ids.join(','));
                $('#freeze-modal').modal('show');
            });

            // 确认冻结
            $(document).on('click', '#btn-confirm-freeze', function(e) {
                e.preventDefault();
                var ids = $('#freeze-ids').val();

                Fast.api.ajax({
                    url: 'withdraw/risklog/multi',
                    data: {ids: ids, action: 'freeze'}
                }, function(data, ret) {
                    $('#freeze-modal').modal('hide');
                    table.bootstrapTable('refresh');
                    Toastr.success(ret.msg);
                });
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

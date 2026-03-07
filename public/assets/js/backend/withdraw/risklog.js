define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
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

            // 风险类型映射
            var riskTypeMap = {
                'video': '视频',
                'task': '任务',
                'withdraw': '提现',
                'redpacket': '红包',
                'invite': '邀请',
                'global': '全局'
            };

            // 处理动作映射
            var actionMap = {
                '': '待处理',
                'pass': '通过',
                'review': '人工审核',
                'reject': '拒绝',
                'freeze': '冻结'
            };

            // 风险等级映射
            var riskLevelMap = {
                0: '普通',
                1: '低风险',
                2: '中风险',
                3: '高风险'
            };

            // 风险类型格式化
            var riskTypeFormatter = function (value, row, index) {
                return riskTypeMap[value] || value;
            };

            // 处理动作格式化
            var actionFormatter = function (value, row, index) {
                var text = actionMap[value] || value || '待处理';
                var className = '';
                switch (value) {
                    case 'pass':
                        className = 'label label-success';
                        break;
                    case 'review':
                        className = 'label label-warning';
                        break;
                    case 'reject':
                        className = 'label label-danger';
                        break;
                    case 'freeze':
                        className = 'label label-info';
                        break;
                    default:
                        className = 'label label-default';
                }
                return '<span class="' + className + '">' + text + '</span>';
            };

            // 风险等级格式化
            var riskLevelFormatter = function (value, row, index) {
                var level = parseInt(value) || 0;
                var text = riskLevelMap[level] || '普通';
                var className = '';
                switch (level) {
                    case 1:
                        className = 'label label-info';
                        break;
                    case 2:
                        className = 'label label-warning';
                        break;
                    case 3:
                        className = 'label label-danger';
                        break;
                    default:
                        className = 'label label-default';
                }
                return '<span class="' + className + '">' + text + '</span>';
            };

            // 行样式（根据风险等级）
            var rowStyle = function (row, index) {
                var level = parseInt(row.risk_level) || 0;
                switch (level) {
                    case 1:
                        return { classes: 'info' }; // 蓝色
                    case 2:
                        return { classes: 'warning' }; // 橘色
                    case 3:
                        return { classes: 'danger' }; // 红色
                    default:
                        return {};
                }
            };

            // 操作按钮格式化
            var operateFormatter = function (value, row, index) {
                var html = [];
                var handleAction = row.handle_action || '';
                
                // 查看详情按钮
                html.push('<a href="javascript:;" class="btn btn-xs btn-primary btn-detail" data-id="' + row.id + '"><i class="fa fa-eye"></i> 详情</a> ');
                
                // 只有待处理状态才显示操作按钮
                if (!handleAction) {
                    html.push('<a href="javascript:;" class="btn btn-xs btn-success btn-pass" data-id="' + row.id + '"><i class="fa fa-check"></i> 通过</a> ');
                    html.push('<a href="javascript:;" class="btn btn-xs btn-warning btn-review" data-id="' + row.id + '"><i class="fa fa-user"></i> 审核</a> ');
                    html.push('<a href="javascript:;" class="btn btn-xs btn-danger btn-reject" data-id="' + row.id + '"><i class="fa fa-ban"></i> 拒绝</a> ');
                    html.push('<a href="javascript:;" class="btn btn-xs btn-default btn-freeze" data-id="' + row.id + '"><i class="fa fa-snowflake-o"></i> 冻结</a>');
                }
                
                return html.join('');
            };

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                rowStyle: rowStyle,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 80},
                        {field: 'user_id', title: '用户ID', sortable: true, width: 80},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'order_no', title: '订单号', operate: 'LIKE'},
                        {field: 'risk_type', title: '风险类型', searchList: riskTypeMap, formatter: riskTypeFormatter},
                        {field: 'risk_level', title: '风险等级', searchList: riskLevelMap, formatter: riskLevelFormatter},
                        {field: 'risk_score', title: '风险分', width: 80},
                        {field: 'ip', title: 'IP地址', operate: 'LIKE'},
                        {field: 'handle_action', title: '处理状态', searchList: actionMap, formatter: actionFormatter},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Controller.api.events, formatter: operateFormatter, width: 250}
                    ]
                ]
            });

            // 绑定事件
            Controller.api.bindEvents(table);

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            bindEvents: function (table) {
                // 通过按钮
                $(document).on('click', '.btn-pass', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var id = $(this).data('id');
                    if (!id) return;
                    
                    Layer.confirm('确认通过该记录？', { icon: 3, title: '提示' }, function (index) {
                        $.ajax({
                            url: 'withdraw/risklog/pass',
                            type: 'POST',
                            data: { ids: id },
                            dataType: 'json',
                            success: function (ret) {
                                Layer.close(index);
                                if (ret.code == 1) {
                                    table.bootstrapTable('refresh');
                                    Toastr.success(ret.msg);
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function () {
                                Layer.close(index);
                                Toastr.error('操作失败');
                            }
                        });
                    });
                });

                // 人工审核按钮
                $(document).on('click', '.btn-review', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var id = $(this).data('id');
                    if (!id) return;
                    
                    Layer.confirm('确认标记为人工审核？', { icon: 3, title: '提示' }, function (index) {
                        $.ajax({
                            url: 'withdraw/risklog/review',
                            type: 'POST',
                            data: { ids: id },
                            dataType: 'json',
                            success: function (ret) {
                                Layer.close(index);
                                if (ret.code == 1) {
                                    table.bootstrapTable('refresh');
                                    Toastr.success(ret.msg);
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function () {
                                Layer.close(index);
                                Toastr.error('操作失败');
                            }
                        });
                    });
                });

                // 拒绝按钮
                $(document).on('click', '.btn-reject', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var id = $(this).data('id');
                    if (!id) return;
                    
                    Layer.prompt({ title: '拒绝原因', formType: 0 }, function (value, index) {
                        $.ajax({
                            url: 'withdraw/risklog/reject',
                            type: 'POST',
                            data: { ids: id, reason: value },
                            dataType: 'json',
                            success: function (ret) {
                                Layer.close(index);
                                if (ret.code == 1) {
                                    table.bootstrapTable('refresh');
                                    Toastr.success(ret.msg);
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function () {
                                Layer.close(index);
                                Toastr.error('操作失败');
                            }
                        });
                    });
                });

                // 冻结按钮
                $(document).on('click', '.btn-freeze', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var id = $(this).data('id');
                    if (!id) return;
                    
                    Layer.confirm('确认冻结该用户？冻结后用户将无法登录！', { icon: 3, title: '警告' }, function (index) {
                        $.ajax({
                            url: 'withdraw/risklog/freeze',
                            type: 'POST',
                            data: { ids: id },
                            dataType: 'json',
                            success: function (ret) {
                                Layer.close(index);
                                if (ret.code == 1) {
                                    table.bootstrapTable('refresh');
                                    Toastr.success(ret.msg);
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function () {
                                Layer.close(index);
                                Toastr.error('操作失败');
                            }
                        });
                    });
                });

                // 详情按钮
                $(document).on('click', '.btn-detail', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var id = $(this).data('id');
                    if (!id) return;
                    Fast.api.open('withdraw/risklog/detail?ids=' + id, '风控记录详情');
                });

                // 批量通过
                $(document).on('click', '.btn-batch-pass', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var ids = Table.api.selectedids(table);
                    if (ids.length === 0) {
                        Toastr.warning('请先选择要操作的记录');
                        return;
                    }
                    
                    Layer.confirm('确认批量通过选中的 ' + ids.length + ' 条记录？', { icon: 3, title: '提示' }, function (index) {
                        $.ajax({
                            url: 'withdraw/risklog/pass',
                            type: 'POST',
                            data: { ids: ids.join(',') },
                            dataType: 'json',
                            success: function (ret) {
                                Layer.close(index);
                                if (ret.code == 1) {
                                    table.bootstrapTable('refresh');
                                    Toastr.success(ret.msg);
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function () {
                                Layer.close(index);
                                Toastr.error('操作失败');
                            }
                        });
                    });
                });

                // 批量标记人工审核
                $(document).on('click', '.btn-batch-review', function (e) {
                    e.stopPropagation();
                    e.preventDefault();
                    var ids = Table.api.selectedids(table);
                    if (ids.length === 0) {
                        Toastr.warning('请先选择要操作的记录');
                        return;
                    }
                    
                    Layer.confirm('确认批量标记 ' + ids.length + ' 条记录为人工审核？', { icon: 3, title: '提示' }, function (index) {
                        $.ajax({
                            url: 'withdraw/risklog/review',
                            type: 'POST',
                            data: { ids: ids.join(',') },
                            dataType: 'json',
                            success: function (ret) {
                                Layer.close(index);
                                if (ret.code == 1) {
                                    table.bootstrapTable('refresh');
                                    Toastr.success(ret.msg);
                                } else {
                                    Toastr.error(ret.msg);
                                }
                            },
                            error: function () {
                                Layer.close(index);
                                Toastr.error('操作失败');
                            }
                        });
                    });
                });

                // 工具栏下拉筛选
                $(document).on('click', '.dropdown-menu li a[data-field]', function () {
                    var field = $(this).data('field');
                    var value = $(this).data('value');
                    if (field && value !== undefined) {
                        var filter = {};
                        filter[field] = value;
                        table.bootstrapTable('refresh', { query: { filter: JSON.stringify(filter) } });
                    }
                });
            },
            events: {
                'click .btn-pass': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                },
                'click .btn-review': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                },
                'click .btn-reject': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                },
                'click .btn-freeze': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                },
                'click .btn-detail': function (e, value, row, index) {
                    e.stopPropagation();
                    e.preventDefault();
                }
            }
        }
    };
    return Controller;
});

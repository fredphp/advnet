define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/commissionlog/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'invite_commission_log',
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
                        {
                            field: 'user_nickname', 
                            title: '获益用户', 
                            operate: false,
                            formatter: function(value, row, index) {
                                if (!value) return '<span class="text-muted">-</span>';
                                var url = 'invite/relation/invitees?parent_id=' + row.user_id;
                                return '<a href="' + url + '" class="btn-dialog" title="查看用户下级">' + value + ' <i class="fa fa-external-link" style="font-size:12px;"></i></a>';
                            }
                        },
                        {
                            field: 'parent_nickname', 
                            title: '来源用户', 
                            operate: false,
                            formatter: function(value, row, index) {
                                if (!value) return '<span class="text-muted">-</span>';
                                var url = 'invite/relation/invitees?parent_id=' + row.parent_id;
                                return '<a href="' + url + '" class="btn-dialog" title="查看用户下级">' + value + ' <i class="fa fa-external-link" style="font-size:12px;"></i></a>';
                            }
                        },
                        {
                            field: 'source_type', 
                            title: '来源类型', 
                            searchList: {"withdraw":"提现"},
                            formatter: function(value, row, index) {
                                var types = {
                                    'withdraw': '<span class="label label-success">提现</span>',
                                    'video': '<span class="label label-info">视频观看</span>',
                                    'task': '<span class="label label-primary">任务</span>',
                                    'red_packet': '<span class="label label-warning">红包</span>',
                                    'game': '<span class="label label-danger">游戏</span>'
                                };
                                return types[value] || '<span class="label label-default">' + value + '</span>';
                            }
                        },
                        {field: 'source_id', title: '来源ID'},
                        {field: 'source_amount', title: '订单金额(元)', sortable: true},
                        {field: 'commission_rate', title: '佣金比例(%)'},
                        {field: 'commission_amount', title: '佣金金额(元)', sortable: true},
                        {field: 'coin_amount', title: '金币数量', sortable: true},
                        {
                            field: 'level', 
                            title: '层级', 
                            searchList: {"1":"一级","2":"二级","3":"三级"},
                            formatter: function(value, row, index) {
                                var labels = {
                                    1: '<span class="label label-primary">一级</span>',
                                    2: '<span class="label label-info">二级</span>',
                                    3: '<span class="label label-default">三级</span>'
                                };
                                return labels[value] || value;
                            }
                        },
                        {
                            field: 'status', 
                            title: '状态', 
                            searchList: {"0":"待结算","1":"已结算","2":"已取消","3":"已冻结"},
                            formatter: function(value, row, index) {
                                var status = {
                                    0: '<span class="label label-warning">待结算</span>',
                                    1: '<span class="label label-success">已结算</span>',
                                    2: '<span class="label label-default">已取消</span>',
                                    3: '<span class="label label-danger">已冻结</span>'
                                };
                                return status[value] || value;
                            }
                        },
                        {field: 'settle_time', title: '结算时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/commissionstat/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'user_commission_stat',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'total_commission',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {
                            field: 'user_id', 
                            title: '用户信息', 
                            operate: false,
                            formatter: function(value, row, index) {
                                var avatar = row.user_avatar || '/assets/img/avatar.png';
                                var nickname = row.user_nickname || '未知用户';
                                var html = '<div style="display: flex; align-items: center;">';
                                html += '<img src="' + avatar + '" style="width: 36px; height: 36px; border-radius: 50%; margin-right: 10px;">';
                                html += '<div>';
                                html += '<div style="font-weight: 500;">' + nickname + '</div>';
                                html += '<div style="color: #999; font-size: 12px;">ID: ' + value + '</div>';
                                html += '</div></div>';
                                return html;
                            }
                        },
                        {
                            field: 'total_invite_count', 
                            title: '总邀请', 
                            sortable: true,
                            width: 80,
                            formatter: function(value, row, index) {
                                return '<span style="color: #667eea; font-weight: bold;">' + (value || 0) + '</span>';
                            }
                        },
                        {
                            field: 'level1_count', 
                            title: '一级邀请', 
                            sortable: true,
                            width: 80,
                            formatter: function(value, row, index) {
                                return '<span style="color: #52c41a; font-weight: bold;">' + (value || 0) + '</span>';
                            }
                        },
                        {
                            field: 'level2_count', 
                            title: '二级邀请', 
                            sortable: true,
                            width: 80,
                            formatter: function(value, row, index) {
                                return '<span style="color: #faad14; font-weight: bold;">' + (value || 0) + '</span>';
                            }
                        },
                        {
                            field: 'total_commission', 
                            title: '累计佣金', 
                            sortable: true,
                            width: 100,
                            formatter: function(value, row, index) {
                                return '<span style="color: #f5222d; font-weight: bold;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'withdrawn_commission', 
                            title: '已提现', 
                            sortable: true,
                            width: 90,
                            formatter: function(value, row, index) {
                                return '<span style="color: #1890ff;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'pending_commission', 
                            title: '待结算', 
                            sortable: true,
                            width: 90,
                            formatter: function(value, row, index) {
                                return '<span style="color: #faad14;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'today_commission', 
                            title: '今日佣金', 
                            sortable: true,
                            width: 90,
                            formatter: function(value, row, index) {
                                return '<span style="color: #52c41a;">¥' + parseFloat(value || 0).toFixed(2) + '</span>';
                            }
                        },
                        {
                            field: 'createtime', 
                            title: '创建时间', 
                            formatter: Table.api.formatter.datetime, 
                            operate: 'RANGE', 
                            addclass: 'datetimerange', 
                            sortable: true,
                            width: 150
                        },
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            width: 120,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: __('查看详情'),
                                    title: __('查看详情'),
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'invite/commissionstat/detail',
                                    extend: 'data-area=\'["900px","90%"]\''
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function() {
            // 详情页面的初始化
            Form.api.bindevent($("form[role=form]"));
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/participation/index',
                    detail_url: 'redpacket/participation/detail',
                    del_url: 'redpacket/participation/del',
                    multi_url: 'redpacket/participation/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 加载统计数据
            Controller.api.loadStats();

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
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'task_id', title: '任务ID', sortable: true},
                        {field: 'task_name', title: '任务名称', operate: 'LIKE'},
                        {field: 'is_new_user', title: '用户类型', searchList: {
                            "0": "老用户",
                            "1": "新用户"
                        }, formatter: Table.api.formatter.status},
                        {field: 'base_amount', title: '基础金额', operate: 'BETWEEN', sortable: true,
                            formatter: function(value) {
                                return value ? value.toLocaleString() : '0';
                            }
                        },
                        {field: 'accumulate_amount', title: '累加金额', operate: 'BETWEEN', sortable: true,
                            formatter: function(value) {
                                return value ? value.toLocaleString() : '0';
                            }
                        },
                        {field: 'total_amount', title: '总金额', operate: 'BETWEEN', sortable: true,
                            formatter: function(value) {
                                return '<span class="text-success"><b>' + (value ? value.toLocaleString() : '0') + '</b></span>';
                            }
                        },
                        {field: 'click_count', title: '点击次数', operate: 'BETWEEN', sortable: true},
                        {field: 'is_collected', title: '领取状态', searchList: {
                            "0": "待领取",
                            "1": "已领取"
                        }, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'collect_time', title: '领取时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
            },
            // 加载统计数据
            loadStats: function() {
                $.ajax({
                    url: 'redpacket/participation/stat',
                    type: 'GET',
                    dataType: 'json',
                    success: function(ret) {
                        if (ret.code === 1 && ret.data) {
                            $('#stat-today-count').text(ret.data.today_count || 0);
                            $('#stat-today-amount').text(ret.data.today_amount || 0);
                            $('#stat-total-users').text(ret.data.total_users || 0);
                            $('#stat-total-amount').text(ret.data.total_amount || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

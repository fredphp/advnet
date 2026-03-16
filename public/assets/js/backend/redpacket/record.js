define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/record/index',
                    detail_url: 'redpacket/record/detail',
                    del_url: 'redpacket/record/del',
                    table: 'red_packet_record',
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
                        {field: 'user_id', title: '用户ID', sortable: true, width: 80},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'task_name', title: '任务名称', operate: 'LIKE'},
                        {field: 'amount', title: '红包金额', sortable: true, 
                            formatter: function(value, row, index) {
                                return '<span style="color:#e74a3b;font-weight:600;">' + value + '</span>';
                            }
                        },
                        {field: 'status', title: '状态', searchList: {"pending":"待处理","success":"成功","failed":"失败"},
                            formatter: function(value, row, index) {
                                var statusMap = {
                                    'pending': '<span class="status-badge pending"><i class="fa fa-clock"></i> 待处理</span>',
                                    'success': '<span class="status-badge success"><i class="fa fa-check"></i> 成功</span>',
                                    'failed': '<span class="status-badge failed"><i class="fa fa-times"></i> 失败</span>'
                                };
                                return statusMap[value] || value;
                            }
                        },
                        {field: 'createtime', title: '领取时间', operate: 'RANGE', addclass: 'datetimerange', 
                            formatter: Table.api.formatter.datetime, sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate, width: 100}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        detail: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

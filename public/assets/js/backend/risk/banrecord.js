define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/banrecord/index',
                    detail_url: 'risk/banrecord/detail',
                    del_url: 'risk/banrecord/del',
                    multi_url: 'risk/banrecord/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'createtime',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'mobile', title: '手机号', operate: 'LIKE'},
                        {field: 'reason', title: '封禁原因', operate: 'LIKE'},
                        {field: 'ban_type', title: '封禁类型', searchList: {
                            "temporary": "临时封禁",
                            "permanent": "永久封禁"
                        }},
                        {field: 'ban_source', title: '封禁来源', searchList: {
                            "auto": "自动封禁",
                            "manual": "手动封禁"
                        }},
                        {field: 'duration', title: '封禁时长(秒)'},
                        {field: 'expire_time', title: '解封时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'admin_id', title: '操作人ID'},
                        {field: 'admin_name', title: '操作人'},
                        {field: 'status', title: '状态', searchList: {
                            "active": "封禁中",
                            "released": "已解封",
                            "expired": "已过期"
                        }, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '封禁时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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

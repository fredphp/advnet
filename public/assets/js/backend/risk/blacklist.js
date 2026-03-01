define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'risk/blacklist/index',
                    add_url: 'risk/blacklist/add',
                    edit_url: 'risk/blacklist/edit',
                    del_url: 'risk/blacklist/del',
                    multi_url: 'risk/blacklist/multi',
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
                        {field: 'type', title: '类型', searchList: {
                            "user": "用户",
                            "ip": "IP",
                            "device": "设备ID",
                            "phone": "手机号"
                        }},
                        {field: 'value', title: '值', operate: 'LIKE'},
                        {field: 'reason', title: '原因', operate: 'LIKE'},
                        {field: 'source', title: '来源', searchList: {
                            "manual": "手动添加",
                            "auto": "自动添加",
                            "import": "导入"
                        }},
                        {field: 'expire_time', title: '过期时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'enabled', title: '状态', searchList: {"0": "禁用", "1": "启用"}, formatter: Table.api.formatter.status},
                        {field: 'admin_name', title: '操作人'},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'singlepage/category/index',
                    add_url: 'singlepage/category/add',
                    edit_url: 'singlepage/category/edit',
                    del_url: 'singlepage/category/del',
                    multi_url: 'singlepage/category/multi',
                    table: 'singlepage_category',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                sortOrder: 'desc',
                search: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: '60px'},
                        {field: 'name', title: '分类名称', operate: 'LIKE', align: 'left', formatter: Table.api.formatter.title},
                        {field: 'description', title: '分类描述', operate: false, align: 'left', formatter: function(value) {
                            if (!value) return '-';
                            return '<span class="text-muted" title="' + value + '">' + (value.length > 30 ? value.substring(0, 30) + '...' : value) + '</span>';
                        }},
                        {field: 'weigh', title: '权重', sortable: true, operate: false, width: '80px'},
                        {field: 'status', title: '状态', searchList: {"1":'启用',"0":'禁用'}, formatter: Table.api.formatter.status, width: '80px'},
                        {field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, width: '160px'},
                        {field: 'updatetime', title: '更新时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, width: '160px', visible: false},
                        {
                            field: 'operate',
                            title: '操作',
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            width: '120px'
                        }
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

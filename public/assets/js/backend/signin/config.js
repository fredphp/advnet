define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格
            Table.api.init({
                extend: {
                    index_url: 'signin/config/index',
                    add_url: 'signin/config/add',
                    edit_url: 'signin/config/edit',
                    del_url: 'signin/config/del',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'day',
                sortOrder: 'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {field: 'day', title: '周期天数', operate: false, align: 'center', width: 100,
                            formatter: function (value) {
                                return '<span class="label label-primary">第 ' + value + ' 天</span>';
                            }
                        },
                        {field: 'coins', title: '奖励金币', operate: false, align: 'center', width: 120,
                            formatter: function (value) {
                                return '<span class="text-success" style="font-size:16px;font-weight:bold;">+' + value + '</span> <small>金币</small>';
                            }
                        },
                        {field: 'description', title: '规则描述', operate: 'LIKE', align: 'left'},
                        {field: 'createtime', title: '创建时间', operate: false, align: 'center', width: 160,
                            formatter: Table.api.formatter.datetime
                        },
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, align: 'center', width: 120}
                    ]
                ]
            });

            // 绑定表格事件
            Table.api.bindevent(table);

            // 绑定配置表单事件
            Controller.api.bindevent();
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"), function (ret) {
                    // 成功后刷新页面
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                });
            }
        }
    };
    return Controller;
});

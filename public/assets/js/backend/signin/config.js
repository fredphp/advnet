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
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'day',
                sortOrder: 'asc',
                search: false,
                commonSearch: false,
                showColumns: false,
                showToggle: false,
                showExport: false,
                showRefresh: false,
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

            // 手动绑定工具栏按钮（确保嵌套panel结构下也能正常工作）
            // 添加规则按钮
            $('#toolbar').on('click', '.btn-add', function (e) {
                e.preventDefault();
                e.stopPropagation();
                Fast.api.open('signin/config/add', '添加规则', {
                    area: ['600px', '450px'],
                    callback: function () {
                        table.bootstrapTable('refresh');
                    }
                });
                return false;
            });

            // 删除规则按钮
            $('#toolbar').on('click', '.btn-del', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.info(__('Please select at least one row'));
                    return false;
                }
                Layer.confirm(
                    __('Are you sure you want to delete the selected %s rows?', ids.length),
                    function (index) {
                        Backend.api.ajax({
                            url: 'signin/config/del/ids/' + ids.join(','),
                            data: {}
                        }, function (data, ret) {
                            Layer.close(index);
                            table.bootstrapTable('refresh');
                            Toastr.success(__('Delete successful'));
                        });
                    }
                );
                return false;
            });

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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'video/author/index',
                    add_url: 'video/author/add',
                    edit_url: 'video/author/edit',
                    del_url: 'video/author/del',
                    multi_url: 'video/author/multi',
                    table: '',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: 60},
                        {field: 'avatar', title: __('头像'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image, width: 80},
                        {field: 'name', title: __('作者名称'), operate: 'LIKE', align: 'left'},
                        {field: 'nickname', title: __('昵称'), operate: 'LIKE', align: 'left', visible: false},
                        {field: 'video_count', title: __('视频数'), operate: false, align: 'center', width: 80},
                        {field: 'total_views', title: __('总播放'), operate: false, align: 'right', formatter: Table.api.formatter.number, width: 90},
                        {field: 'total_likes', title: __('总点赞'), operate: false, align: 'right', formatter: Table.api.formatter.number, width: 90},
                        {field: 'verify_status', title: __('认证状态'), searchList: {"0":"未认证","1":"已认证","2":"认证中"}, formatter: Table.api.formatter.status, align: 'center', width: 100},
                        {field: 'verify_type', title: __('认证类型'), operate: 'LIKE', align: 'center', width: 100, visible: false},
                        {field: 'region', title: __('地区'), operate: 'LIKE', align: 'left', width: 150, visible: false},
                        {field: 'ip', title: __('IP'), operate: 'LIKE', align: 'center', width: 120, visible: false},
                        {field: 'createtime', title: __('创建时间'), operate: 'RANGE', addclass: 'datetimerange', autocomplete: false, formatter: Table.api.formatter.datetime, width: 150, sortable: true},
                        {field: 'weigh', title: __('权重'), sortable: true, align: 'center', width: 60},
                        {field: 'status', title: __('状态'), searchList: {"normal":"正常","hidden":"隐藏"}, formatter: Table.api.formatter.status, align: 'center', width: 80},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, align: 'center', width: 100}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            // 更新统计数据按钮
            $(document).on('click', '.btn-update-stats', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要更新的记录');
                    return;
                }
                
                $.ajax({
                    url: 'video/author/updateStats',
                    type: 'POST',
                    data: {ids: ids.join(',')},
                    dataType: 'json',
                    success: function(ret) {
                        if (ret.code === 1) {
                            Toastr.success(ret.msg);
                            table.bootstrapTable('refresh');
                        } else {
                            Toastr.error(ret.msg);
                        }
                    }
                });
            });
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

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            Table.init({
                extend: {
                    index_url: 'adincome/redpacket',
                    detail_url: 'adincome/redpacket/detail',
                    del_url: 'adincome/redpacket/del',
                    multi_url: 'adincome/redpacket/multi',
                    table: 'table',
                },
                columns: [
                    [
                        {field: 'state', checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'user_id', title: '用户ID'},
                        {field: 'username', title: '用户名', operate: false},
                        {field: 'nickname', title: '昵称', operate: false},
                        {field: 'amount', title: '金额(金币)', sortable: true},
                        {field: 'source_text', title: '来源', operate: false},
                        {field: 'status_text', title: '状态', searchList: {0: '未领取', 1: '已领取', 2: '已过期'}},
                        {field: 'createtime_text', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'claim_time_text', title: '领取时间', operate: false},
                        {field: 'expire_time_text', title: '过期时间', operate: false},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate,
                            buttons: [
                                {name: 'detail', text: '详情', title: '详情', classname: 'btn btn-xs btn-info btn-dialog', icon: 'fa fa-list', url: 'adincome/redpacket/detail'}
                            ]
                        }
                    ]
                ]
            });
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

    // 全局函数
    window.expirePackets = function () {
        Layer.confirm('确定要执行过期处理吗？将所有已过期的未领取红包标记为已过期。', function (index) {
            $.ajax({
                url: 'adincome/redpacket/expire',
                type: 'POST',
                dataType: 'json',
                success: function (res) {
                    if (res.code === 1) {
                        Layer.close(index);
                        Table.api.refresh();
                        Toastr.success(res.msg);
                    } else {
                        Toastr.error(res.msg);
                    }
                }
            });
        });
    };

    return Controller;
});

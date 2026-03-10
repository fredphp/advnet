define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/relation/index',
                    add_url: '',
                    edit_url: '',
                    del_url: '',
                    multi_url: '',
                    table: 'invite_relation',
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
                        {field: 'parent_id', title: '邀请人ID', sortable: true},
                        {field: 'inviter_name', title: '邀请人用户名', operate: 'LIKE'},
                        {field: 'inviter_nickname', title: '邀请人昵称', operate: 'LIKE'},
                        {field: 'user_id', title: '被邀请人ID', sortable: true},
                        {field: 'invitee_name', title: '被邀请人用户名', operate: 'LIKE'},
                        {field: 'invitee_nickname', title: '被邀请人昵称', operate: 'LIKE'},
                        {field: 'grandparent_id', title: '二级上级ID', sortable: true},
                        {field: 'invite_code', title: '邀请码', operate: 'LIKE'},
                        {field: 'invite_channel', title: '邀请渠道', searchList: {"link":"链接","qrcode":"二维码","share":"分享"}},
                        {field: 'createtime', title: '邀请时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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

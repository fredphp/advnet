define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'invite/commissionconfig/index',
                    add_url: 'invite/commissionconfig/add',
                    edit_url: 'invite/commissionconfig/edit',
                    del_url: 'invite/commissionconfig/del',
                    multi_url: 'invite/commissionconfig/multi',
                    table: 'invite_commission_config',
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
                        {field: 'name', title: '配置名称', operate: 'LIKE'},
                        {field: 'level', title: '邀请等级', searchList: {"1":"一级","2":"二级","3":"三级"}},
                        {field: 'commission_type', title: '分佣类型', searchList: {"percent":"按比例","fixed":"固定金额"}},
                        {field: 'commission_value', title: '分佣值'},
                        {field: 'source', title: '来源场景', searchList: {"all":"全部","video":"视频观看","withdraw":"提现","task":"任务","red_packet":"红包","game":"游戏"}},
                        {field: 'min_amount', title: '最低金额门槛'},
                        {field: 'max_commission', title: '最高佣金'},
                        {field: 'enabled', title: '状态', searchList: {"0":"禁用","1":"启用"}, formatter: Table.api.formatter.status},
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

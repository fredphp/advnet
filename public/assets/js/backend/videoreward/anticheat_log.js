define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'videoreward/anticheat_log/index',
                    del_url: 'videoreward/anticheat_log/del',
                    table: 'anticheat_log',
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
                        {field: 'user_id', title: '用户ID', operate: '='},
                        {field: 'user.nickname', title: '用户昵称', operate: false},
                        {field: 'type', title: '作弊类型', searchList: {"speed":"加速播放","multi":"多开账号","emulator":"模拟器","proxy":"代理IP","device":"设备异常","behavior":"异常行为"}, formatter: Table.api.formatter.normal},
                        {field: 'data', title: '作弊详情', operate: false, formatter: function(value, row, index) {
                            if (value) {
                                try {
                                    var data = typeof value === 'string' ? JSON.parse(value) : value;
                                    return '<span title="' + JSON.stringify(data) + '">' + (data.reason || value).toString().substring(0, 50) + '</span>';
                                } catch(e) {
                                    return value.substring(0, 50);
                                }
                            }
                            return '-';
                        }},
                        {field: 'ip', title: 'IP地址', operate: 'LIKE'},
                        {field: 'device_id', title: '设备ID', operate: 'LIKE'},
                        {field: 'createtime', title: '时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '作弊详情',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'videoreward/anticheat_log/detail',
                                    extend: 'data-area=\'["800px","600px"]\''
                                }
                            ]
                        }
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

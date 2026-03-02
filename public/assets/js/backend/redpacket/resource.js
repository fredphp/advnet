define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/resource/index',
                    add_url: 'redpacket/resource/add',
                    edit_url: 'redpacket/resource/edit',
                    del_url: 'redpacket/resource/del',
                    multi_url: 'redpacket/resource/multi',
                    table: 'red_packet_resource',
                }
            });

            var table = $("#table");
            
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'sort',
                sortOrder: 'asc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'type', title: '资源类型', searchList: {
                            "app": "App下载",
                            "mini_program": "小程序",
                            "game": "游戏",
                            "video": "视频",
                            "link": "分享链接"
                        }, formatter: Table.api.formatter.normal},
                        {field: 'logo', title: '图标', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'name', title: '资源名称', operate: 'LIKE'},
                        {field: 'description', title: '描述', operate: false},
                        {field: 'url', title: '链接', operate: false, formatter: function(value) {
                            return value ? '<a href="' + value + '" target="_blank" title="' + value + '">查看</a>' : '-';
                        }},
                        {field: 'sort', title: '排序', sortable: true},
                        {field: 'status', title: '状态', searchList: {"0":"禁用","1":"启用"}, formatter: Table.api.formatter.status},
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

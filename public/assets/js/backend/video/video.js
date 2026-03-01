define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/video/index',
                    add_url: 'video/video/add',
                    edit_url: 'video/video/edit',
                    del_url: 'video/video/del',
                    multi_url: 'video/video/multi',
                    table: 'video',
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
                        {field: 'title', title: '视频标题', operate: 'LIKE'},
                        {field: 'cover', title: '封面', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'user_id', title: '发布者ID', operate: '='},
                        {field: 'duration', title: '时长(秒)', operate: 'BETWEEN', sortable: true},
                        {field: 'view_count', title: '播放量', operate: 'BETWEEN', sortable: true},
                        {field: 'reward_coin', title: '奖励金币', operate: 'BETWEEN', sortable: true},
                        {field: 'status', title: '状态', searchList: {"0":"待审核","1":"已发布","2":"已下架","3":"已封禁","4":"草稿"}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'stats',
                                    text: '统计',
                                    title: '视频统计',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-bar-chart',
                                    url: 'video/video/stats',
                                    extend: 'data-area=\'["90%","90%"]\''
                                },
                                {
                                    name: 'online',
                                    text: '上架',
                                    title: '上架视频',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-arrow-up',
                                    url: 'video/video/batchOnline',
                                    confirm: '确认上架？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function(data, ret) {
                                        Layer.alert(ret.msg);
                                    },
                                    visible: function(row) {
                                        return row.status != 1;
                                    }
                                },
                                {
                                    name: 'offline',
                                    text: '下架',
                                    title: '下架视频',
                                    classname: 'btn btn-xs btn-warning btn-ajax',
                                    icon: 'fa fa-arrow-down',
                                    url: 'video/video/batchOffline',
                                    confirm: '确认下架？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function(data, ret) {
                                        Layer.alert(ret.msg);
                                    },
                                    visible: function(row) {
                                        return row.status == 1;
                                    }
                                }
                            ]
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

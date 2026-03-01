define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'videoreward/video_watch_record/index',
                    del_url: 'videoreward/video_watch_record/del',
                    table: 'video_watch_record',
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
                        {field: 'video_id', title: '视频ID', operate: '='},
                        {field: 'video.title', title: '视频标题', operate: false},
                        {field: 'watch_duration', title: '观看时长(秒)', operate: 'BETWEEN', sortable: true},
                        {field: 'watch_progress', title: '观看进度(%)', operate: 'BETWEEN', sortable: true},
                        {field: 'is_completed', title: '是否完成', searchList: {"0":"否","1":"是"}, formatter: Table.api.formatter.normal},
                        {field: 'reward_status', title: '奖励状态', searchList: {"pending":"待发放","success":"已发放","failed":"发放失败"}, formatter: Table.api.formatter.normal},
                        {field: 'reward_coin', title: '奖励金币', operate: 'BETWEEN', sortable: true},
                        {field: 'createtime', title: '观看时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
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
                                    title: '观看详情',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-eye',
                                    url: 'videoreward/video_watch_record/detail',
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

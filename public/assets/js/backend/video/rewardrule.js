define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/rewardrule/index',
                    add_url: 'video/rewardrule/add',
                    edit_url: 'video/rewardrule/edit',
                    del_url: 'video/rewardrule/del',
                    multi_url: 'video/rewardrule/multi',
                    table: 'video_reward_rule',
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
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'title', title: '规则名称', operate: 'LIKE'},
                        {field: 'reward_type', title: '奖励类型', searchList: {"fixed":"固定奖励","random":"随机奖励"}, formatter: Table.api.formatter.normal},
                        {field: 'coin_reward', title: '奖励金币', operate: 'BETWEEN', sortable: true},
                        {field: 'coin_min', title: '最小金币', operate: false, visible: false},
                        {field: 'coin_max', title: '最大金币', operate: false, visible: false},
                        {field: 'watch_duration', title: '观看时长要求(秒)', operate: 'BETWEEN', sortable: true},
                        {field: 'daily_limit', title: '每日限制', operate: 'BETWEEN', sortable: true},
                        {field: 'status', title: '状态', searchList: {"0":"禁用","1":"启用"}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: '权重', sortable: true},
                        {field: 'start_time', title: '开始时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', visible: false},
                        {field: 'end_time', title: '结束时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', visible: false},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true, visible: false},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'toggle',
                                    text: '',
                                    title: '切换状态',
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-toggle-on',
                                    url: 'video/rewardrule/toggle',
                                    confirm: '确认切换状态？',
                                    success: function(data, ret) {
                                        table.bootstrapTable('refresh');
                                    },
                                    error: function(data, ret) {
                                        Layer.alert(ret.msg);
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

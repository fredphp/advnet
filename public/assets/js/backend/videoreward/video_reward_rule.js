define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'videoreward/video_reward_rule/index',
                    add_url: 'videoreward/video_reward_rule/add',
                    edit_url: 'videoreward/video_reward_rule/edit',
                    del_url: 'videoreward/video_reward_rule/del',
                    multi_url: 'videoreward/video_reward_rule/multi',
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
                        {field: 'name', title: '规则名称', operate: 'LIKE'},
                        {field: 'condition_type', title: '条件类型', searchList: {"duration":"时长领取","count":"集数领取","random":"随机领取"}, formatter: Table.api.formatter.normal},
                        {field: 'reward_type', title: '奖励类型', searchList: {"fixed":"固定","random":"随机"}, formatter: Table.api.formatter.normal},
                        {field: 'reward_amount', title: '奖励金额', operate: 'BETWEEN', sortable: true},
                        {field: 'reward_min', title: '最小金额', operate: false, visible: false},
                        {field: 'reward_max', title: '最大金额', operate: false, visible: false},
                        {field: 'watch_duration', title: '观看时长(秒)', operate: 'BETWEEN', sortable: true, visible: false},
                        {field: 'watch_count', title: '观看集数', operate: 'BETWEEN', sortable: true, visible: false},
                        {field: 'scope_type', title: '适用范围', searchList: {"all":"全部视频","video":"指定视频","collection":"指定合集"}, formatter: Table.api.formatter.normal},
                        {field: 'daily_limit', title: '每日限制', operate: 'BETWEEN', sortable: true, visible: false},
                        {field: 'status', title: '状态', searchList: {"0":"禁用","1":"启用"}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: '权重', sortable: true},
                        {field: 'start_time', title: '开始时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', visible: false},
                        {field: 'end_time', title: '结束时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', visible: false},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'copy',
                                    text: '复制',
                                    title: '复制规则',
                                    classname: 'btn btn-xs btn-info btn-ajax',
                                    icon: 'fa fa-copy',
                                    url: 'videoreward/video_reward_rule/copy',
                                    confirm: '确认复制此规则？',
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
                
                // 条件类型切换
                $('select[name="row[condition_type]"]').on('change', function() {
                    var type = $(this).val();
                    $('.condition-duration').toggle(type === 'duration');
                    $('.condition-count').toggle(type === 'count');
                }).trigger('change');
                
                // 奖励类型切换
                $('select[name="row[reward_type]"]').on('change', function() {
                    var type = $(this).val();
                    $('.reward-fixed').toggle(type === 'fixed');
                    $('.reward-random').toggle(type === 'random');
                }).trigger('change');
                
                // 适用范围切换
                $('select[name="row[scope_type]"]').on('change', function() {
                    var type = $(this).val();
                    $('.scope-video').toggle(type === 'video');
                    $('.scope-collection').toggle(type === 'collection');
                }).trigger('change');
            }
        }
    };
    return Controller;
});

define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'coin/log/index',
                    add_url: 'coin/log/add',
                    edit_url: 'coin/log/edit',
                    del_url: 'coin/log/del',
                    multi_url: 'coin/log/multi',
                    table: 'coin_log',
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
                        {field: 'user_id', title: '用户ID', sortable: true},
                        {field: 'username', title: '用户名', operate: 'LIKE'},
                        {field: 'nickname', title: '昵称', operate: 'LIKE'},
                        {field: 'type', title: '流水类型', searchList: {
                            "register_reward": "注册奖励",
                            "video_watch": "观看视频",
                            "video_share": "分享视频",
                            "task_reward": "任务奖励",
                            "sign_in": "签到奖励",
                            "invite_level1": "一级邀请奖励",
                            "invite_level2": "二级邀请奖励",
                            "commission_level1": "一级佣金",
                            "commission_level2": "二级佣金",
                            "red_packet": "红包奖励",
                            "game_reward": "游戏奖励",
                            "admin_add": "后台增加",
                            "admin_reduce": "后台扣减",
                            "withdraw": "提现",
                            "withdraw_fee": "提现手续费",
                            "withdraw_return": "提现退回"
                        }, formatter: Table.api.formatter.normal},
                        {field: 'amount', title: '金币数量', sortable: true, operate: 'BETWEEN', formatter: function(value, row) {
                            if (value > 0) {
                                return '<span class="text-success">+' + value + '</span>';
                            } else if (value < 0) {
                                return '<span class="text-danger">' + value + '</span>';
                            }
                            return value;
                        }},
                        {field: 'balance_before', title: '变动前余额', operate: 'BETWEEN'},
                        {field: 'balance_after', title: '变动后余额', operate: 'BETWEEN'},
                        {field: 'relation_type', title: '关联类型', searchList: {
                            "video": "视频",
                            "task": "任务",
                            "withdraw": "提现",
                            "invite": "邀请",
                            "red_packet": "红包",
                            "admin": "后台"
                        }},
                        {field: 'relation_id', title: '关联ID'},
                        {field: 'title', title: '标题', operate: 'LIKE'},
                        {field: 'description', title: '描述', operate: 'LIKE', visible: false},
                        {field: 'ip', title: '操作IP', formatter: Table.api.formatter.search},
                        {field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        statistics: function () {
            Controller.api.loadStatistics();
            $('#start_date, #end_date').on('change', function () {
                Controller.api.loadStatistics();
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
            },
            loadStatistics: function () {
                var startDate = $('#start_date').val();
                var endDate = $('#end_date').val();

                $.ajax({
                    url: 'coin/log/statistics',
                    type: 'GET',
                    data: {start_date: startDate, end_date: endDate},
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            $('#total-income').text(data.total_stats.total_income || 0);
                            $('#total-expense').text(data.total_stats.total_expense || 0);
                            $('#total-count').text(data.total_stats.total_count || 0);
                        }
                    }
                });
            }
        }
    };
    return Controller;
});

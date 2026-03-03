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
            
            // 类型列表（与模型定义一致）
            var typeList = {
                "download_app": "下载App",
                "mini_program": "跳转小程序",
                "play_game": "玩游戏时长",
                "watch_video": "观看视频",
                "share_link": "分享链接",
                "sign_in": "签到任务"
            };
            
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
                        {field: 'type', title: '资源类型', searchList: typeList, formatter: function(value, row) {
                            // 优先使用后端返回的type_text，否则使用本地映射
                            return row.type_text || typeList[value] || value;
                        }},
                        {field: 'logo', title: '图标', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
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
            Controller.api.initTypeChange();
        },
        edit: function () {
            Controller.api.bindevent();
            Controller.api.initTypeChange();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            initTypeChange: function() {
                // 根据资源类型显示/隐藏对应字段
                $('#c-type').on('change', function() {
                    var type = $(this).val();
                    $('.type-field').hide();
                    $('.type-' + type).show();
                });
                
                // 页面加载时触发一次
                $('#c-type').trigger('change');
            }
        }
    };
    return Controller;
});

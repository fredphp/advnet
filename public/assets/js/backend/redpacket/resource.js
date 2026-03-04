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

            // 类型列表
            var typeList = {
                "download": "下载App",
                "miniapp": "小程序游戏",
                "adv": "广告时长",
                "video": "观看视频"
            };

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
                        {field: 'type', title: '资源类型', searchList: typeList, formatter: function(value, row) {
                            return row.type_text || typeList[value] || value;
                        }},
                        {field: 'logo', title: '图标', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'name', title: '资源名称', operate: 'LIKE'},
                        {field: 'description', title: '描述', operate: false},
                        {field: 'status', title: '状态', searchList: {"normal":"正常","hidden":"隐藏"}, formatter: Table.api.formatter.status},
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
        select: function () {
            // 选择资源弹窗
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            initTypeChange: function() {
                // 根据资源类型显示/隐藏对应字段
                var $typeSelect = $('#c-type');

                // 使用 change 事件（selectpicker 使用 changed.bs.select）
                $typeSelect.on('change changed.bs.select', function() {
                    var type = $(this).val();
                    $('.type-config').hide();

                    if (type === 'miniapp') {
                        // 小程序游戏配置
                        $('.type-miniapp').show();
                    } else if (type === 'download') {
                        // 下载App配置
                        $('.type-download').show();
                    } else if (type === 'video') {
                        // 观看视频配置
                        $('.type-video').show();
                    } else if (type === 'adv') {
                        // 广告时长配置
                        $('.type-adv').show();
                    }
                });

                // 页面加载时触发一次
                setTimeout(function() {
                    $typeSelect.trigger('change');
                }, 100);
            }
        }
    };
    return Controller;
});

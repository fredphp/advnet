define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/collection/index',
                    add_url: 'video/collection/add',
                    edit_url: 'video/collection/edit',
                    del_url: 'video/collection/del',
                    multi_url: 'video/collection/multi',
                    table: 'video_collection',
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
                        {field: 'title', title: '合集标题', operate: 'LIKE'},
                        {field: 'cover', title: '封面', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'video_count', title: '视频数量', operate: false},
                        {field: 'status', title: '状态', searchList: {"0":"禁用","1":"启用"}, formatter: Table.api.formatter.status},
                        {field: 'weigh', title: '权重', sortable: true},
                        {field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'videos',
                                    text: __('管理视频'),
                                    title: __('管理合集视频'),
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-video-camera',
                                    url: 'video/collection/videos',
                                    callback: function(data) {
                                        table.bootstrapTable('refresh');
                                    }
                                }
                            ]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 工具栏管理视频按钮 - 选中一行后启用
            table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function () {
                var ids = Table.api.selectedids(table);
                var btn = $('.btn-dialog[data-url="video/collection/videos"]');
                if (ids.length === 1) {
                    btn.removeClass('btn-disabled disabled');
                    btn.data('url', 'video/collection/videos/ids/' + ids[0]);
                } else {
                    btn.addClass('btn-disabled disabled');
                }
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        videos: function () {
            // 管理视频页面
            var collectionId = Fast.api.query('ids');
            
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/collection/getVideos?id=' + collectionId,
                    table: 'video_collection_item',
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
                        {field: 'sort', title: '集数', sortable: true},
                        {field: 'cover', title: '封面', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'title', title: '视频标题'},
                        {field: 'duration', title: '时长(秒)'},
                        {field: 'reward_coin', title: '奖励金币'},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 添加视频按钮
            $(document).on('click', '.btn-add-video', function() {
                Fast.api.open('video/video/select?collection_id=' + collectionId, '选择视频');
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});

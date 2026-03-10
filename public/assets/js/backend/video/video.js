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
                        {field: 'id', title: 'ID', sortable: true, width: '60px'},
                        {field: 'cover_url', title: '封面', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false, width: '80px'},
                        {field: 'title', title: '视频标题', operate: 'LIKE', width: '200px', formatter: Table.api.formatter.title},
                        {field: 'duration', title: '时长', operate: false, sortable: true, width: '70px', formatter: function(value) {
                            if (!value) return '0秒';
                            var minutes = Math.floor(value / 60);
                            var seconds = value % 60;
                            if (minutes > 0) {
                                return minutes + '分' + seconds + '秒';
                            }
                            return seconds + '秒';
                        }},
                        {field: 'view_count', title: '播放', operate: 'BETWEEN', sortable: true, width: '70px', formatter: function(value) {
                            return '<span class="text-primary">' + (value || 0) + '</span>';
                        }},
                        {field: 'like_count', title: '点赞', operate: false, sortable: true, width: '70px', formatter: function(value) {
                            return '<span class="text-danger">' + (value || 0) + '</span>';
                        }},
                        {field: 'collect_count', title: '收藏', operate: false, sortable: true, width: '70px', formatter: function(value) {
                            return '<span class="text-warning">' + (value || 0) + '</span>';
                        }},
                        {field: 'comment_count', title: '评论', operate: false, sortable: true, width: '70px', formatter: function(value) {
                            return '<span class="text-info">' + (value || 0) + '</span>';
                        }},
                        {field: 'share_count', title: '转发', operate: false, sortable: true, width: '70px', formatter: function(value) {
                            return '<span class="text-success">' + (value || 0) + '</span>';
                        }},
                        // {field: 'reward_coin', title: '奖励金币', operate: 'BETWEEN', sortable: true, width: '80px'},
                        // {field: 'reward_count', title: '奖励人数', operate: 'BETWEEN', sortable: true, width: '80px'},
                        {field: 'status', title: '状态', searchList: {"0":"待审核","1":"已发布","2":"已下架","3":"已封禁","4":"草稿"}, formatter: Table.api.formatter.status, width: '80px'},
                        {field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, width: '150px'},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            width: '150px',
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
            
            // 视频URL切换逻辑
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr('href');
                if (target === '#tab-url') {
                    // 切换到链接输入时，清空上传输入
                    $('#c-video_url_upload').val('');
                } else {
                    // 切换到上传时，清空链接输入
                    $('#c-video_url_link').val('');
                }
            });
            
            // 视频上传按钮自定义处理
            $(document).on('click', '.faupload-video', function() {
                var input_id = $(this).data('input-id');
                var mimetype = $(this).data('mimetype');
                var maxsize = $(this).data('maxsize') || '100mb';
                
                // 打开上传对话框
                Fast.api.open('general/attachment/select?element_id=' + input_id + '&multiple=false&mimetype=' + encodeURIComponent(mimetype) + '&maxsize=' + maxsize, '选择视频', {
                    area: ['90%', '90%']
                });
            });
        },
        edit: function () {
            Controller.api.bindevent();
            
            // 视频URL切换逻辑
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var target = $(e.target).attr('href');
                if (target === '#tab-url') {
                    $('#c-video_url_upload').val('');
                } else {
                    $('#c-video_url_link').val('');
                }
            });
            
            // 视频上传按钮自定义处理
            $(document).on('click', '.faupload-video', function() {
                var input_id = $(this).data('input-id');
                var mimetype = $(this).data('mimetype');
                var maxsize = $(this).data('maxsize') || '100mb';
                
                Fast.api.open('general/attachment/select?element_id=' + input_id + '&multiple=false&mimetype=' + encodeURIComponent(mimetype) + '&maxsize=' + maxsize, '选择视频', {
                    area: ['90%', '90%']
                });
            });
        },
        select: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/video/select',
                }
            });

            var table = $("#table");
            var selectedIds = [];

            // 选中事件
            table.on('check.bs.table uncheck.bs.table check-all.bs.table uncheck-all.bs.table', function (e, row) {
                if (e.type == 'check' || e.type == 'uncheck') {
                    row = [row];
                } else if (e.type == 'check-all' || e.type == 'uncheck-all') {
                    selectedIds = [];
                }
                $.each(row, function (i, j) {
                    if (e.type.indexOf("uncheck") > -1) {
                        var index = selectedIds.indexOf(j.id);
                        if (index > -1) {
                            selectedIds.splice(index, 1);
                        }
                    } else {
                        if (selectedIds.indexOf(j.id) == -1) {
                            selectedIds.push(j.id);
                        }
                    }
                });
            });

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                sortOrder: 'desc',
                showToggle: false,
                showExport: false,
                maintainSelected: true,
                columns: [
                    [
                        {field: 'state', checkbox: true, visible: true, operate: false},
                        {field: 'id', title: 'ID'},
                        {field: 'title', title: '视频标题', operate: 'LIKE'},
                        {field: 'cover_url', title: '封面', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'duration', title: '时长(秒)', operate: false},
                        {field: 'reward_coin', title: '奖励金币', operate: false},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 选择按钮点击事件
            $(document).on('click', '.btn-choose-multi', function() {
                if (selectedIds.length === 0) {
                    Toastr.error('请至少选择一个视频');
                    return;
                }
                
                var collectionId = Fast.api.query('collection_id');
                
                // 调用添加视频接口
                Fast.api.ajax({
                    url: 'video/collection/addVideo',
                    data: {
                        collection_id: collectionId,
                        video_ids: selectedIds
                    }
                }, function(data, ret) {
                    // 关闭弹窗并刷新父页面
                    var index = parent.layer.getFrameIndex(window.name);
                    parent.$(".btn-refresh").trigger("click");
                    parent.layer.close(index);
                    Toastr.success(ret.msg);
                });
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

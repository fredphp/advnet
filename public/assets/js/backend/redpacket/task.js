define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    // 任务类型列表
    var typeList = {
        'chat': '普通聊天',
        'download': '下载App',
        'miniapp': '小程序游戏',
        'adv': '广告时长',
        'video': '观看视频'
    };

    // 当前选中的任务类型，用于selectpage动态参数
    var currentTaskType = '';

    var Controller = {
        index: function () {
            // 初始化表格配置
            Table.api.init({
                extend: {
                    index_url: 'redpacket/task/index',
                    add_url: 'redpacket/task/add',
                    edit_url: 'redpacket/task/edit',
                    del_url: 'redpacket/task/del',
                    multi_url: 'redpacket/task/multi',
                    table: '',
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
                        {field: 'name', title: '任务名称', operate: 'LIKE', formatter: Table.api.formatter.title},
                        {field: 'type', title: '任务类型', searchList: typeList, formatter: Table.api.formatter.normal},
                        {field: 'total_amount', title: '总金额(金币)', sortable: true},
                        {field: 'total_count', title: '总数量', sortable: true},
                        {field: 'status', title: '状态', searchList: {
                            "pending": "待发送",
                            "normal": "进行中",
                            "finished": "已抢完",
                            "expired": "已过期"
                        }, formatter: Table.api.formatter.status},
                        {field: 'push_status', title: '发送状态', searchList: {"0":"未发送","1":"已发送"}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'send',
                                    text: '发送',
                                    title: '发送任务到客户端',
                                    classname: 'btn btn-xs btn-warning btn-dialog',
                                    icon: 'fa fa-send',
                                    url: function(row) {
                                        return 'redpacket/task/send/ids/' + row.id + '?month=' + (row._month || '');
                                    },
                                    hidden: function(row) {
                                        return row.push_status == 1;
                                    }
                                },
                                {
                                    name: 'edit',
                                    text: '编辑',
                                    title: '编辑任务',
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-pencil',
                                    url: function(row) {
                                        return 'redpacket/task/edit/ids/' + row.id + '?month=' + (row._month || '');
                                    },
                                    hidden: function(row) {
                                        return row.push_status == 1;
                                    }
                                },
                                {
                                    name: 'detail',
                                    text: '详情',
                                    title: '任务详情',
                                    classname: 'btn btn-xs btn-info btn-dialog',
                                    icon: 'fa fa-list',
                                    url: function(row) {
                                        return 'redpacket/task/detail/ids/' + row.id + '?month=' + (row._month || '');
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
            // 编辑页面加载已选资源信息
            if (typeof __resourceData !== 'undefined' && __resourceData) {
                setTimeout(function() {
                    Controller.api.showResourceInfo(__resourceData);
                }, 500);
            }
        },
        send: function() {
            Controller.api.bindeventSend();
        },
        push: function() {
            Form.api.bindevent($("form[role=form]"));
        },
        detail: function() {
            // 详情页面
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                var $resourceInput = $('#c-resource_id');

                // 任务类型切换
                $('#c-type').on('change', function() {
                    var taskType = $(this).val();
                    var prevTaskType = currentTaskType;
                    currentTaskType = taskType;

                    if (taskType) {
                        var typeName = typeList[taskType] || '资源';
                        $('.resource-type-tip').text('请选择【' + typeName + '】类型的资源');

                        // 更新 selectpage 的 data-params 属性
                        $resourceInput.attr('data-params', '{"custom[type]":"' + taskType + '"}');
                        
                        // 如果类型变化了，清空当前选中值
                        if (prevTaskType && prevTaskType !== taskType) {
                            $resourceInput.val('');
                            Controller.api.hideResourceInfo();
                        }
                    } else {
                        $('.resource-type-tip').text('选择资源后，将使用资源中的图标、背景图、跳转链接等信息');
                        currentTaskType = '';
                    }
                });

                // 监听 selectpage 选择事件
                $(document).on('selectpage:select', '#c-resource_id', function(e, data) {
                    if (data && data.id) {
                        // 加载完整的资源信息
                        Controller.api.loadResourceInfo(data.id);
                    } else {
                        Controller.api.hideResourceInfo();
                    }
                });

                // 监听 selectpage 清除事件
                $(document).on('selectpage:clear', '#c-resource_id', function(e) {
                    Controller.api.hideResourceInfo();
                });

                // 初始化任务类型
                setTimeout(function() {
                    $('#c-type').trigger('change');
                }, 100);
            },
            // 发送页面绑定事件
            bindeventSend: function() {
                // 从URL获取month参数
                var urlParams = new URLSearchParams(window.location.search);
                var month = urlParams.get('month') || '';
                
                // 确认发送按钮
                $('#btn-confirm-send').on('click', function() {
                    var $btn = $(this);
                    var taskId = $btn.data('task-id');
                    
                    Layer.confirm('确定要发送该任务到客户端吗？发送后将无法修改。', {
                        title: '发送确认',
                        btn: ['确定发送', '取消']
                    }, function(index) {
                        Layer.close(index);
                        
                        // 显示发送中
                        $btn.prop('disabled', true).text('发送中...');
                        
                        // 发送请求
                        $.ajax({
                            url: Backend.api.fixurl('redpacket/task/doSend'),
                            type: 'POST',
                            data: { ids: taskId, month: month },
                            dataType: 'json',
                            success: function(ret) {
                                if (ret.code === 1) {
                                    Layer.alert('发送成功！', { icon: 1 }, function() {
                                        // 关闭弹窗并刷新列表
                                        var index2 = parent.layer.getFrameIndex(window.name);
                                        parent.$("#table").bootstrapTable('refresh');
                                        parent.layer.close(index2);
                                    });
                                } else {
                                    $btn.prop('disabled', false).text('确认发送');
                                    Layer.alert(ret.msg || '发送失败', { icon: 2 });
                                }
                            },
                            error: function() {
                                $btn.prop('disabled', false).text('确认发送');
                                Layer.alert('网络错误，请稍后重试', { icon: 2 });
                            }
                        });
                    });
                });
            },
            // 显示资源信息
            showResourceInfo: function(data) {
                if (!data || !data.id) {
                    Controller.api.hideResourceInfo();
                    return;
                }

                // 设置基本信息
                var logoUrl = data.logo || '/assets/img/avatar.png';
                $('#resource-logo').attr('src', logoUrl);
                $('#resource-name').text(data.name || '未命名资源');
                $('#resource-description').text(data.description || '暂无描述');

                // 根据类型显示详细信息
                var detailHtml = '';
                var typeText = typeList[data.type] || data.type_text || data.type || '未知类型';
                
                // 类型标签
                var badgeClass = 'label-primary';
                if (data.type === 'miniapp') badgeClass = 'label-success';
                else if (data.type === 'download') badgeClass = 'label-info';
                else if (data.type === 'adv') badgeClass = 'label-warning';
                else if (data.type === 'video') badgeClass = 'label-danger';
                
                detailHtml += '<p style="margin-bottom:10px;"><span class="label ' + badgeClass + '">' + typeText + '</span></p>';
                
                // 根据类型显示不同字段
                detailHtml += '<table class="table table-condensed table-bordered" style="margin-bottom:0;font-size:12px;">';
                
                switch (data.type) {
                    case 'download':
                        if (data.download_url) {
                            detailHtml += '<tr><td width="80" class="text-right"><strong>下载链接</strong></td><td>' + 
                                '<a href="' + data.download_url + '" target="_blank" title="' + data.download_url + '">' + 
                                (data.download_url.length > 40 ? data.download_url.substring(0, 40) + '...' : data.download_url) + '</a></td></tr>';
                        }
                        if (data.package_name) {
                            detailHtml += '<tr><td class="text-right"><strong>包名</strong></td><td>' + data.package_name + '</td></tr>';
                        }
                        if (data.download_type) {
                            var downloadTypeText = data.download_type === 'android' ? 'Android' : (data.download_type === 'ios' ? 'iOS' : data.download_type);
                            detailHtml += '<tr><td class="text-right"><strong>平台</strong></td><td>' + downloadTypeText + '</td></tr>';
                        }
                        break;
                    case 'miniapp':
                        if (data.miniapp_id) {
                            detailHtml += '<tr><td width="80" class="text-right"><strong>AppID</strong></td><td>' + data.miniapp_id + '</td></tr>';
                        }
                        if (data.miniapp_path) {
                            detailHtml += '<tr><td class="text-right"><strong>页面路径</strong></td><td>' + data.miniapp_path + '</td></tr>';
                        }
                        if (data.miniapp_type) {
                            var miniappTypeText = data.miniapp_type === 'release' ? '正式版' : (data.miniapp_type === 'trial' ? '体验版' : '开发版');
                            detailHtml += '<tr><td class="text-right"><strong>版本</strong></td><td>' + miniappTypeText + '</td></tr>';
                        }
                        break;
                    case 'chat':
                        if (data.chat_duration) {
                            detailHtml += '<tr><td width="80" class="text-right"><strong>聊天时长</strong></td><td>' + data.chat_duration + ' 秒</td></tr>';
                        }
                        if (data.chat_requirement) {
                            var reqText = data.chat_requirement.length > 50 ? data.chat_requirement.substring(0, 50) + '...' : data.chat_requirement;
                            detailHtml += '<tr><td class="text-right"><strong>聊天要求</strong></td><td>' + reqText + '</td></tr>';
                        }
                        break;
                    case 'video':
                        if (data.video_url) {
                            detailHtml += '<tr><td width="80" class="text-right"><strong>视频链接</strong></td><td>' + 
                                '<a href="' + data.video_url + '" target="_blank" title="' + data.video_url + '">' + 
                                (data.video_url.length > 40 ? data.video_url.substring(0, 40) + '...' : data.video_url) + '</a></td></tr>';
                        }
                        if (data.video_duration) {
                            detailHtml += '<tr><td class="text-right"><strong>视频时长</strong></td><td>' + data.video_duration + ' 秒</td></tr>';
                        }
                        break;
                    case 'adv':
                        if (data.adv_id) {
                            detailHtml += '<tr><td width="80" class="text-right"><strong>广告ID</strong></td><td>' + data.adv_id + '</td></tr>';
                        }
                        if (data.adv_platform) {
                            detailHtml += '<tr><td class="text-right"><strong>广告平台</strong></td><td>' + data.adv_platform + '</td></tr>';
                        }
                        if (data.adv_duration) {
                            detailHtml += '<tr><td class="text-right"><strong>广告时长</strong></td><td>' + data.adv_duration + ' 秒</td></tr>';
                        }
                        break;
                }
                
                // 通用字段：跳转URL
                if (data.url) {
                    detailHtml += '<tr><td width="80" class="text-right"><strong>跳转链接</strong></td><td>' + 
                        '<a href="' + data.url + '" target="_blank" title="' + data.url + '">' + 
                        (data.url.length > 40 ? data.url.substring(0, 40) + '...' : data.url) + '</a></td></tr>';
                }
                
                detailHtml += '</table>';
                
                $('#resource-detail-info').html(detailHtml);
                
                $('.resource-info-area').show();
                $('.no-resource-area').hide();
            },
            // 隐藏资源信息
            hideResourceInfo: function() {
                $('.resource-info-area').hide();
                $('.no-resource-area').show();
            },
            // 加载资源信息（通过AJAX）
            loadResourceInfo: function(resourceId) {
                if (!resourceId) {
                    Controller.api.hideResourceInfo();
                    return;
                }
                $.ajax({
                    url: Backend.api.fixurl('redpacket/resource/detail'),
                    type: 'GET',
                    data: { ids: resourceId },
                    dataType: 'json',
                    success: function(ret) {
                        if (ret.code === 1 && ret.data) {
                            Controller.api.showResourceInfo(ret.data);
                        } else {
                            Controller.api.hideResourceInfo();
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to load resource info:', error);
                        Controller.api.hideResourceInfo();
                    }
                });
            }
        }
    };
    return Controller;
});

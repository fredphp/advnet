define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'selectpage', 'template'], function ($, undefined, Backend, Table, Form, SelectPage, Template) {

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
                    push_url: 'redpacket/task/push',
                    send_url: 'redpacket/task/send',
                    sendmessage_url: 'redpacket/task/sendMessage',
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
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                            {
                                name: 'send',
                                text: '发送',
                                title: '发送任务到客户端',
                                classname: 'btn btn-xs btn-warning btn-dialog',
                                icon: 'fa fa-send',
                                url: 'redpacket/task/send',
                                hidden: function(row) {
                                    // 已发送的任务隐藏发送按钮
                                    return row.push_status == 1;
                                }
                            },
                            {
                                name: 'edit',
                                text: '编辑',
                                title: '编辑任务',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                icon: 'fa fa-pencil',
                                url: 'redpacket/task/edit',
                                hidden: function(row) {
                                    // 已发送的任务隐藏编辑按钮
                                    return row.push_status == 1;
                                }
                            },
                            {
                                name: 'detail',
                                text: '详情',
                                title: '任务详情',
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa fa-list',
                                url: 'redpacket/task/detail'
                            }
                        ]}
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
        send: function() {
            Controller.api.bindeventSend();
        },
        push: function() {
            Form.api.bindevent($("form[role=form]"));
        },
        detail: function() {
            // 详情页面
        },
        sendmessage: function() {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));

                // 任务类型切换
                $('#c-type').on('change', function() {
                    var taskType = $(this).val();
                    var $resourceId = $('#c-resource_id');
                    var initTaskType = $resourceId.data('init-task-type');

                    if (taskType) {
                        currentTaskType = taskType;

                        var typeName = typeList[taskType] || '资源';
                        $('.resource-type-tip').text('请选择【' + typeName + '】类型的资源');

                        // 任务类型变化时清空选中值并刷新
                        if (initTaskType && initTaskType !== taskType) {
                            if ($resourceId.data('selectPage')) {
                                $resourceId.selectPageClear();
                            }
                            $('.resource-info-area').hide();
                        }

                        // 刷新selectpage数据
                        if ($resourceId.data('selectPage')) {
                            $resourceId.selectPageRefresh();
                        }
                    } else {
                        $('.resource-info-area').hide();
                        currentTaskType = '';
                    }
                });

                // 初始化任务类型
                var initTaskType = $('#c-type').val();
                if (initTaskType) {
                    currentTaskType = initTaskType;
                    $('#c-resource_id').data('init-task-type', initTaskType);
                }

                // 初始化selectpage
                $('#c-resource_id').selectPage({
                    source: 'redpacket/resource/select',
                    showField: 'name',
                    keyField: 'id',
                    searchField: 'name',
                    pagination: true,
                    pageSize: 10,
                    params: function() {
                        return { type: currentTaskType };
                    },
                    eAjaxSuccess: function(data) {
                        data.list = typeof data.rows !== 'undefined' ? data.rows : (typeof data.list !== 'undefined' ? data.list : []);
                        data.totalRow = typeof data.total !== 'undefined' ? data.total : (typeof data.totalRow !== 'undefined' ? data.totalRow : data.list.length);
                        return data;
                    },
                    eListStyle: function(data) {
                        return '<div style="padding:5px 0;">' +
                            '<strong>' + data.name + '</strong>' +
                            (data.description ? '<br><small class="text-muted">' + data.description.substring(0, 50) + '</small>' : '') +
                            '</div>';
                    },
                    // 选择资源后的回调
                    eSelect: function(data) {
                        if (data && data.id) {
                            Controller.api.showResourceInfo(data);
                        } else {
                            $('.resource-info-area').hide();
                        }
                    }
                });

                // 延迟触发change和加载已选资源
                setTimeout(function() {
                    $('#c-type').trigger('change');
                    // 编辑页面如果有已选资源ID，加载资源信息
                    var existingResourceId = $('#c-resource_id').val();
                    if (existingResourceId && !isNaN(existingResourceId)) {
                        Controller.api.loadResourceInfo(existingResourceId);
                    }
                }, 300);
            },
            // 发送页面绑定事件
            bindeventSend: function() {
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
                            data: { ids: taskId },
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
            // 显示资源信息（从selectpage选择的数据）
            showResourceInfo: function(data) {
                if (!data || !data.id) {
                    $('.resource-info-area').hide();
                    return;
                }

                var logoUrl = data.logo || '/assets/img/avatar.png';
                $('#resource-logo').attr('src', logoUrl);
                $('#resource-name').text(data.name || '');
                $('#resource-description').text(data.description || '暂无描述');

                var extraInfo = [];
                if (data.type) {
                    extraInfo.push('类型: ' + (typeList[data.type] || data.type));
                }
                $('#resource-extra').html(extraInfo.join(' | '));

                $('.resource-info-area').show();
            },
            // 加载资源信息（通过AJAX）
            loadResourceInfo: function(resourceId) {
                if (!resourceId) {
                    $('.resource-info-area').hide();
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
                            $('.resource-info-area').hide();
                        }
                    },
                    error: function() {
                        $('.resource-info-area').hide();
                    }
                });
            }
        }
    };
    return Controller;
});

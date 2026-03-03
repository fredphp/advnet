define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'selectpage', 'template'], function ($, undefined, Backend, Table, Form, SelectPage, Template) {
    
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
                        {field: 'task_type', title: '任务类型', searchList: typeList, formatter: Table.api.formatter.normal},
                        {field: 'icon', title: '图标', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'total_amount', title: '总金额(金币)', sortable: true},
                        {field: 'remain_amount', title: '剩余金额', sortable: true},
                        {field: 'total_count', title: '总数量', sortable: true},
                        {field: 'remain_count', title: '剩余数量', sortable: true},
                        {field: 'status', title: '状态', searchList: {
                            "0": "禁用",
                            "1": "启用",
                            "2": "已结束",
                            "3": "已抢完"
                        }, formatter: Table.api.formatter.status},
                        {field: 'push_status', title: '推送状态', searchList: {"0":"未推送","1":"已推送"}, formatter: function(val, row) {
                            if (val == 1) {
                                return '<span class="label label-success">已推送</span>';
                            }
                            return '<span class="label label-default">未推送</span>';
                        }},
                        {field: 'start_time', title: '开始时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate, buttons: [
                            {
                                name: 'push',
                                text: '推送',
                                title: '推送任务到客户端',
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa fa-paper-plane',
                                url: 'redpacket/task/push',
                                hidden: function(row) {
                                    return row.status != 1 || row.push_status == 1;
                                },
                                success: function(data, ret) {
                                    table.bootstrapTable('refresh');
                                }
                            },
                            {
                                name: 'message',
                                text: '发消息',
                                title: '发送消息通知',
                                classname: 'btn btn-xs btn-warning btn-dialog',
                                icon: 'fa fa-envelope',
                                url: function(row) {
                                    return 'redpacket/task/sendMessage?ids=' + row.id;
                                },
                                hidden: function(row) {
                                    return row.status != 1;
                                }
                            },
                            {
                                name: 'detail',
                                text: '详情',
                                title: '任务详情',
                                classname: 'btn btn-xs btn-success btn-dialog',
                                icon: 'fa fa-list',
                                url: 'redpacket/task/detail'
                            }
                        ]}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            // 批量推送按钮
            $(document).on('click', '.btn-push', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要推送的任务');
                    return;
                }
                Layer.confirm('确定要推送选中的任务吗？', {icon: 3, title: '提示'}, function(index) {
                    var count = 0;
                    var success = 0;
                    var fail = 0;
                    
                    ids.forEach(function(id) {
                        $.ajax({
                            url: 'redpacket/task/push',
                            type: 'POST',
                            data: {ids: id},
                            async: false,
                            success: function(ret) {
                                if (ret.code === 1) {
                                    success++;
                                } else {
                                    fail++;
                                }
                            },
                            error: function() {
                                fail++;
                            },
                            complete: function() {
                                count++;
                            }
                        });
                    });
                    
                    Layer.close(index);
                    if (success > 0) {
                        Toastr.success('成功推送 ' + success + ' 个任务');
                        table.bootstrapTable('refresh');
                    }
                    if (fail > 0) {
                        Toastr.error(fail + ' 个任务推送失败');
                    }
                });
            });
            
            // 批量发送消息按钮
            $(document).on('click', '.btn-message', function() {
                var ids = Table.api.selectedids(table);
                if (ids.length === 0) {
                    Toastr.error('请选择要发送消息的任务');
                    return;
                }
                if (ids.length > 1) {
                    Toastr.error('一次只能为一个任务发送消息');
                    return;
                }
                
                Fast.api.open('redpacket/task/sendMessage?ids=' + ids[0], '发送消息通知');
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        push: function() {
            // 推送页面
            Form.api.bindevent($("form[role=form]"));
        },
        sendmessage: function() {
            // 发送消息页面
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                
                // 金额类型切换
                $('#c-amount_type').on('change', function() {
                    if ($(this).val() === 'fixed') {
                        $('.amount-fixed').show();
                        $('.amount-random').hide();
                    } else {
                        $('.amount-fixed').hide();
                        $('.amount-random').show();
                    }
                });
                
                // 任务类型切换
                $('#c-task_type').on('change', function() {
                    var taskType = $(this).val();
                    var $resourceId = $('#c-resource_id');
                    var $resourceArea = $('.resource-select-area');
                    var initTaskType = $resourceId.data('init-task-type');
                    
                    if (taskType && taskType !== 'sign_in') {
                        // 更新当前任务类型
                        currentTaskType = taskType;
                        
                        // 显示资源选择区域
                        $resourceArea.show();
                        
                        // 更新提示文字
                        var typeName = typeList[taskType] || '资源';
                        $('.resource-type-tip').text('请选择【' + typeName + '】类型的资源，支持搜索名称');
                        
                        // 任务类型变化时清空选中值并刷新
                        if (initTaskType && initTaskType !== taskType) {
                            $resourceId.selectPageClear();
                            $('.resource-info-area').hide();
                        }
                        
                        // 刷新selectpage数据
                        $resourceId.selectPageRefresh();
                    } else {
                        $resourceArea.hide();
                        $('.resource-info-area').hide();
                        currentTaskType = '';
                    }
                });
                
                // 资源选择变化
                $(document).on('change', '#c-resource_id', function() {
                    var resourceId = $(this).val();
                    if (resourceId) {
                        $.ajax({
                            url: Backend.api.fixurl('redpacket/resource/detail'),
                            type: 'GET',
                            data: { ids: resourceId },
                            dataType: 'json',
                            success: function(ret) {
                                if (ret.code === 1 && ret.data) {
                                    var data = ret.data;
                                    $('#resource-logo').attr('src', data.logo || '/assets/img/avatar.png');
                                    $('#resource-name').text(data.name || '');
                                    $('#resource-description').text(data.description || '暂无描述');
                                    
                                    var extra = '';
                                    if (data.package_name) extra += '包名: ' + data.package_name + ' ';
                                    if (data.app_id) extra += 'AppID: ' + data.app_id + ' ';
                                    if (data.url) extra += '链接: ' + data.url;
                                    $('#resource-extra').text(extra);
                                    
                                    $('.resource-info-area').show();
                                }
                            }
                        });
                    } else {
                        $('.resource-info-area').hide();
                    }
                });
                
                // 新用户限制切换
                $('input[name="row[new_user_only]"]').on('change', function() {
                    if ($(this).prop('checked')) {
                        $('#c-new_user_days').closest('.form-group').show();
                    } else {
                        $('#c-new_user_days').closest('.form-group').hide();
                    }
                });
                
                // 初始状态
                if ($('input[name="row[new_user_only]"]').prop('checked')) {
                    $('#c-new_user_days').closest('.form-group').show();
                }
                
                // 初始化任务类型
                var initTaskType = $('#c-task_type').val();
                if (initTaskType) {
                    currentTaskType = initTaskType;
                    $('#c-resource_id').data('init-task-type', initTaskType);
                }
                
                // 初始化selectpage，完整配置支持分页和搜索
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
                    }
                });
                
                // 延迟触发change
                setTimeout(function() {
                    $('#c-task_type').trigger('change');
                }, 100);
            }
        }
    };
    return Controller;
});

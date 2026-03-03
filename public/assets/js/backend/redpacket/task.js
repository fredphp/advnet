define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'selectpage'], function ($, undefined, Backend, Table, Form) {
    
    // 当前选中的任务类型，用于selectpage动态参数
    var currentTaskType = '';
    
    var Controller = {
        index: function () {
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
            
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true},
                        {field: 'name', title: '任务名称', operate: 'LIKE'},
                        {field: 'task_type', title: '任务类型', searchList: typeList, formatter: Table.api.formatter.normal},
                        {field: 'total_amount', title: '总金额(金币)'},
                        {field: 'single_amount', title: '单个金额(金币)'},
                        {field: 'total_count', title: '总数量'},
                        {field: 'remain_count', title: '剩余数量'},
                        {field: 'status', title: '状态', searchList: {
                            "0": "禁用",
                            "1": "启用",
                            "2": "已结束",
                            "3": "已抢完"
                        }, formatter: Table.api.formatter.status},
                        {field: 'start_time', title: '开始时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'end_time', title: '结束时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
                        {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: '操作', table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

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
                    // 数据源
                    source: 'redpacket/resource/select',
                    // 显示字段
                    showField: 'name',
                    // 主键字段
                    keyField: 'id',
                    // 搜索字段（后端会使用name字段搜索）
                    searchField: 'name',
                    // 启用分页
                    pagination: true,
                    // 每页显示数量
                    pageSize: 10,
                    // 动态参数函数
                    params: function() {
                        return { type: currentTaskType };
                    },
                    // 处理返回数据
                    eAjaxSuccess: function(data) {
                        data.list = typeof data.rows !== 'undefined' ? data.rows : (typeof data.list !== 'undefined' ? data.list : []);
                        data.totalRow = typeof data.total !== 'undefined' ? data.total : (typeof data.totalRow !== 'undefined' ? data.totalRow : data.list.length);
                        return data;
                    },
                    // 列表项格式化显示
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

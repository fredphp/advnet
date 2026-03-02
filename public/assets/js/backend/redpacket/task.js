define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    
    // 类型列表（从HTML页面传递的全局变量）
    // var typeList 在HTML模板中定义
    
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
                
                // 任务类型切换 - 核心逻辑
                $('#c-task_type').on('change', function() {
                    var taskType = $(this).val();
                    var $resourceArea = $('.resource-select-area');
                    var $resourceId = $('#c-resource_id');
                    var initTaskType = $resourceId.attr('data-init-task-type');
                    
                    // 签到任务不需要选择资源
                    if (taskType && taskType !== 'sign_in') {
                        var typeName = typeList[taskType] || '资源';
                        
                        // 显示资源选择区域
                        $resourceArea.show();
                        
                        // 更新提示文字
                        $('.resource-type-tip').text('请选择【' + typeName + '】类型的资源（可在资源管理中添加）');
                        
                        // 判断是否需要清空值：如果任务类型发生变化，则清空
                        if (initTaskType && initTaskType !== taskType) {
                            $resourceId.val('');
                            $resourceId.attr('data-init-value', '');
                            $('.resource-info-area').hide();
                        }
                        
                        // 重新初始化selectpage，使用新的type参数
                        Controller.api.reinitSelectPage($resourceId, taskType);
                    } else {
                        $resourceArea.hide();
                        $('.resource-info-area').hide();
                    }
                });
                
                // 资源选择变化时显示资源信息
                $(document).on('change', '#c-resource_id', function() {
                    var resourceId = $(this).val();
                    if (resourceId) {
                        // 获取资源详情
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
                
                // 触发初始状态
                if ($('input[name="row[new_user_only]"]').prop('checked')) {
                    $('#c-new_user_days').closest('.form-group').show();
                } else {
                    $('#c-new_user_days').closest('.form-group').hide();
                }
                
                // 页面加载时如果已有任务类型，触发change事件
                var initTaskType = $('#c-task_type').val();
                if (initTaskType) {
                    // 保存初始任务类型到 data 属性
                    $('#c-resource_id').attr('data-init-task-type', initTaskType);
                    
                    // 保存初始值
                    var initValue = $('#c-resource_id_init').val() || $('#c-resource_id').val();
                    if (initValue) {
                        $('#c-resource_id').attr('data-init-value', initValue);
                    }
                    
                    // 初始化selectpage
                    Controller.api.reinitSelectPage($('#c-resource_id'), initTaskType);
                    
                    // 延迟触发change事件
                    setTimeout(function() {
                        $('#c-task_type').trigger('change');
                    }, 100);
                }
            },
            
            /**
             * 重新初始化selectpage
             * 关键：销毁现有实例，更新data-source URL，然后重新初始化
             */
            reinitSelectPage: function($element, resourceType) {
                // 保存当前值
                var currentValue = $element.val();
                
                // 获取隐藏字段的值
                var $hidden = $element.next('.sp_hidden');
                var currentHiddenValue = $hidden.length ? $hidden.val() : '';
                
                // 销毁现有的selectpage实例
                var selectPageObj = $element.data('selectPageObject');
                if (selectPageObj) {
                    // 移除selectpage相关的DOM元素
                    var $container = $element.closest('.sp_container');
                    $container.find('.sp_result_area').remove();
                    $container.find('.sp_element_box').remove();
                    $container.find('.sp_clear_btn').remove();
                    $container.find('.sp_hidden').remove();
                    $element.unwrap('.sp_container');
                    $element.removeData('selectPageObject');
                    $element.removeClass('sp_input');
                    $element.show();
                }
                
                // 方法1: 更新data-params属性
                $element.attr('data-params', JSON.stringify({type: resourceType}));
                
                // 方法2: 同时更新data-source URL添加type参数（双重保障）
                var baseUrl = 'redpacket/resource/select';
                $element.attr('data-source', baseUrl);
                
                // 重新初始化selectpage
                require(['selectpage'], function() {
                    $element.selectPage({
                        // 重要：使用params函数动态返回type参数
                        params: function() {
                            return { type: resourceType };
                        },
                        // 同时设置data参数
                        data: { type: resourceType },
                        // 设置custom参数
                        custom: { type: resourceType },
                        eAjaxSuccess: function(data) {
                            data.list = typeof data.rows !== 'undefined' ? data.rows : (typeof data.list !== 'undefined' ? data.list : []);
                            data.totalRow = typeof data.total !== 'undefined' ? data.total : (typeof data.totalRow !== 'undefined' ? data.totalRow : data.list.length);
                            return data;
                        }
                    });
                    
                    // 如果有初始值且任务类型没变，恢复选中状态
                    var initTaskType = $element.attr('data-init-task-type');
                    var initValue = $element.attr('data-init-value');
                    
                    if (initValue && initTaskType === resourceType) {
                        // 使用定时器确保selectpage初始化完成
                        setTimeout(function() {
                            // 通过AJAX获取选中项的数据
                            $.ajax({
                                url: Backend.api.fixurl('redpacket/resource/select'),
                                type: 'GET',
                                data: {
                                    keyField: 'id',
                                    showField: 'name',
                                    keyValue: initValue
                                },
                                dataType: 'json',
                                success: function(ret) {
                                    if (ret && ret.list && ret.list.length > 0) {
                                        var item = ret.list[0];
                                        $element.selectPageData(item);
                                    }
                                }
                            });
                        }, 100);
                    }
                });
            }
        }
    };
    return Controller;
});

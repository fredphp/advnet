define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    
    // 任务类型与资源类型映射
    var taskTypeMap = {
        'download_app': 'app',
        'mini_program': 'mini_program',
        'play_game': 'game',
        'watch_video': 'video',
        'share_link': 'link',
        'sign_in': 'link'
    };
    
    // 资源类型名称
    var resourceTypeNames = {
        'app': 'App',
        'mini_program': '小程序',
        'game': '游戏',
        'video': '视频',
        'link': '分享链接'
    };
    
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
                        {field: 'task_type', title: '任务类型', searchList: {
                            "download_app": "下载App",
                            "mini_program": "跳转小程序",
                            "play_game": "玩游戏时长",
                            "watch_video": "观看视频",
                            "share_link": "分享链接",
                            "sign_in": "签到任务"
                        }, formatter: Table.api.formatter.normal},
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
            Controller.api.initResourceSelector();
        },
        edit: function () {
            Controller.api.bindevent();
            Controller.api.initResourceSelector();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            
            initResourceSelector: function() {
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
                    var $resourceInfoArea = $('.resource-info-area');
                    var $resourceId = $('#c-resource_id');
                    
                    if (taskType && taskType !== 'sign_in') {
                        var resourceType = taskTypeMap[taskType];
                        if (!resourceType) {
                            resourceType = 'link'; // 默认
                        }
                        var typeName = resourceTypeNames[resourceType] || '资源';
                        
                        // 显示资源选择区域
                        $resourceArea.show();
                        
                        // 更新提示文字
                        $('.resource-type-tip').text('请选择' + typeName + '资源');
                        
                        // 清空当前值
                        $resourceId.val('');
                        $resourceInfoArea.hide();
                        
                        // 获取selectpage对象并更新参数
                        var selectPage = $resourceId.data('selectPageObject');
                        if (selectPage) {
                            // 更新查询参数
                            selectPage.option.params = function() {
                                return {
                                    custom: { type: resourceType }
                                };
                            };
                            // 重新加载数据
                            selectPage.clear();
                        } else {
                            // 如果selectpage还没初始化，更新data属性
                            $resourceId.attr('data-params', JSON.stringify({custom: {type: resourceType}}));
                        }
                        
                    } else {
                        $resourceArea.hide();
                        $resourceInfoArea.hide();
                    }
                });
                
                // 资源选择变化时显示资源信息
                $('#c-resource_id').on('change', function() {
                    var resourceId = $(this).val();
                    if (resourceId) {
                        // 获取资源详情
                        $.ajax({
                            url: 'redpacket/resource/detail',
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
                
                // 初始化selectpage的自定义参数
                $(document).on('selectpage:open', '#c-resource_id', function(e, obj) {
                    var taskType = $('#c-task_type').val();
                    var resourceType = taskTypeMap[taskType];
                    if (resourceType) {
                        obj.option.params = function() {
                            return { custom: { type: resourceType } };
                        };
                    }
                });
            }
        }
    };
    return Controller;
});

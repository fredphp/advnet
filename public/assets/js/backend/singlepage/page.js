define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var ue = null; // UEditor 实例

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'singlepage/page/index',
                    add_url: 'singlepage/page/add',
                    edit_url: 'singlepage/page/edit',
                    del_url: 'singlepage/page/del',
                    multi_url: 'singlepage/page/multi',
                    table: 'singlepage',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                sortOrder: 'desc',
                search: false,
                pagination: true,
                pageSize: 15,
                pageList: [10, 15, 25, 50, 'All'],
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'ID', sortable: true, width: '60px'},
                        {field: 'category_id', title: '所属分类', operate: '=', searchList: Controller.api.getCategoryList(), formatter: function(value, row) {
                            return row.category ? row.category.name : '<span class="text-muted">未分类</span>';
                        }, width: '120px'},
                        {field: 'title', title: '页面标题', operate: 'LIKE', align: 'left', formatter: Table.api.formatter.title},
                        {field: 'image', title: '封面', events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false, width: '80px'},
                        {field: 'weigh', title: '权重', sortable: true, operate: false, width: '80px'},
                        {field: 'status', title: '状态', searchList: {"1":'启用',"0":'禁用'}, formatter: Table.api.formatter.status, width: '80px'},
                        {field: 'createtime', title: '创建时间', operate: 'RANGE', addclass: 'datetimerange', formatter: Table.api.formatter.datetime, sortable: true, width: '160px'},
                        {
                            field: 'operate',
                            title: '操作',
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            width: '120px'
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
            Controller.api.initUeditor();
        },
        edit: function () {
            Controller.api.bindevent();
            Controller.api.initUeditor();
        },
        api: {
            // 获取分类列表
            getCategoryList: function() {
                var list = {};
                if (Config && Config.categoryList) {
                    list = Config.categoryList;
                }
                return list;
            },
            // 初始化UEditor编辑器
            initUeditor: function () {
                // 在加载UEditor前设置 UEDITOR_HOME_URL（使用相对路径确保dialog等资源正确加载）
                window.UEDITOR_HOME_URL = '/assets/libs/ueditor/';

                require(['ueditor'], function () {
                    // 等待DOM渲染完成后初始化编辑器
                    setTimeout(function () {
                        var editorContainer = document.getElementById('c-content');
                        if (!editorContainer) return;

                        // 先保存textarea中已有的内容
                        var existingContent = $('#c-content').val();

                        // 获取服务器地址用于UEditor配置
                        var serverUrl = Config.moduleurl + '/singlepage/page/ueditor';

                        // 确保UE对象可用
                        if (typeof UE === 'undefined') {
                            console.error('UEditor 未正确加载');
                            return;
                        }

                        ue = UE.getEditor('c-content', {
                            serverUrl: serverUrl,
                            toolbars: [[
                                'fullscreen', 'source', '|', 'undo', 'redo', '|',
                                'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', 'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|', 'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',
                                'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
                                'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',
                                'directionalityltr', 'directionalityrtl', 'indent', '|',
                                'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'touppercase', 'tolowercase', '|',
                                'link', 'unlink', 'anchor', '|', 'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
                                'simpleupload', 'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'gmap', 'insertframe', 'insertcode', 'webapp', 'pagebreak', 'template', 'background', '|',
                                'horizontal', 'date', 'time', 'spechars', 'snapscreen', 'wordimage', '|',
                                'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', 'charts', '|', 'print', 'preview', 'searchreplace', 'help'
                            ]],
                            autoFloatEnabled: false,
                            autoHeightEnabled: true,
                            autoFloatOffsetTop: 0,
                            initialFrameWidth: '100%',
                            initialFrameHeight: 450,
                            enableAutoSave: false,
                            saveInterval: 0,
                            zIndex: 99999
                        });

                        // 编辑器就绪后设置内容
                        ue.ready(function () {
                            if (existingContent) {
                                ue.setContent(existingContent);
                            }
                        });
                    }, 500);
                }, function (e) {
                    console.error('UEditor 加载失败:', e);
                });
            },
            bindevent: function () {
                // 绑定表单事件
                Form.api.bindevent($("form[role=form]"));

                // 在表单提交之前，将UEditor内容同步到textarea
                $("form[role=form]").on('submit', function () {
                    if (ue && ue.isReady) {
                        ue.sync();
                    }
                });

                // 同时在validator的valid回调前同步（兼容FastAdmin的表单验证提交流程）
                var form = $("form[role=form]");
                if (form.length > 0) {
                    var validator = form.data('validator');
                    if (validator) {
                        var originalValid = validator.options.valid;
                        validator.options.valid = function (ret) {
                            // 在表单验证通过后、提交前同步UEditor内容
                            if (ue && ue.isReady) {
                                ue.sync();
                            }
                            // 调用原始valid方法
                            return originalValid.call(this, ret);
                        };
                    }
                }
            }
        }
    };
    return Controller;
});

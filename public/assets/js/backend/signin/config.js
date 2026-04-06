define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'layer'], function ($, undefined, Backend, Table, Form, Layer) {

    var Controller = {

        index: function () {
            console.log('[signin/config] Controller.index() 开始执行');

            // ========== 加载规则列表 ==========
            function loadRuleList() {
                console.log('[signin/config] loadRuleList() 请求开始');
                $.ajax({
                    url: 'signin/config/getRuleList',
                    type: 'GET',
                    dataType: 'json',
                    success: function (ret) {
                        console.log('[signin/config] getRuleList 返回:', ret);
                        var html = '';
                        if (ret.code === 1 && ret.data && ret.data.rows && ret.data.rows.length > 0) {
                            for (var i = 0; i < ret.data.rows.length; i++) {
                                var row = ret.data.rows[i];
                                var timeStr = row.createtime > 0 ? new Date(row.createtime * 1000).toLocaleString('zh-CN') : '-';
                                html += '<tr>';
                                html += '<td>' + row.id + '</td>';
                                html += '<td align="center"><span class="label label-primary">第 ' + row.day + ' 天</span></td>';
                                html += '<td align="center"><span class="text-success" style="font-size:16px;font-weight:bold;">+' + row.coins + '</span> <small>金币</small></td>';
                                html += '<td>' + (row.description || '-') + '</td>';
                                html += '<td align="center">' + timeStr + '</td>';
                                html += '<td align="center">';
                                html += '<a href="javascript:;" class="btn btn-xs btn-success btn-edit-rule" data-id="' + row.id + '"><i class="fa fa-pencil"></i> 编辑</a> ';
                                html += '<a href="javascript:;" class="btn btn-xs btn-danger btn-del-rule" data-id="' + row.id + '"><i class="fa fa-trash"></i> 删除</a>';
                                html += '</td>';
                                html += '</tr>';
                            }
                        } else {
                            html = '<tr><td colspan="6" class="text-center" style="padding:30px;color:#999;">暂无规则，请点击"添加规则"按钮</td></tr>';
                        }
                        $('#rule-list-body').html(html);
                    },
                    error: function (xhr, status, error) {
                        console.log('[signin/config] getRuleList 请求失败:', status, error);
                        $('#rule-list-body').html('<tr><td colspan="6" class="text-center text-danger">加载失败(' + status + ')，请刷新页面重试</td></tr>');
                    }
                });
            }

            // 初始化加载
            loadRuleList();

            // ========== 添加规则按钮 ==========
            $('#btn-add-rule').on('click', function (e) {
                e.preventDefault();
                console.log('[signin/config] 点击添加规则');
                Backend.api.open('signin/config/add', '添加规则', {
                    area: ['600px', '450px']
                });
            });

            // ========== 编辑规则（事件委托） ==========
            $(document).on('click', '.btn-edit-rule', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                console.log('[signin/config] 编辑规则 id=', id);
                Backend.api.open('signin/config/edit/ids/' + id, '编辑规则', {
                    area: ['600px', '450px']
                });
            });

            // ========== 删除规则（事件委托） ==========
            $(document).on('click', '.btn-del-rule', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                console.log('[signin/config] 删除规则 id=', id);
                Layer.confirm('确定要删除该规则吗？', function (index) {
                    Backend.api.ajax({
                        url: 'signin/config/del/ids/' + id
                    }, function () {
                        Layer.close(index);
                        Toastr.success('删除成功');
                        loadRuleList();
                    });
                });
            });

            // ========== 弹窗关闭后刷新列表 ==========
            $(document).on('click', '.btn-refresh', function () {
                console.log('[signin/config] btn-refresh 触发');
                loadRuleList();
            });

            // ========== 保存基础配置 ==========
            $('#btn-save-config').on('click', function () {
                console.log('[signin/config] 保存配置');
                var formData = $('#config-form').serialize();
                Backend.api.ajax({
                    url: 'signin/config/save',
                    data: formData
                }, function () {
                    Toastr.success('保存成功');
                });
            });
        },

        add: function () {
            Form.api.bindevent($("form[role=form]"));
        },

        edit: function () {
            Form.api.bindevent($("form[role=form]"));
        },

        api: {}
    };

    return Controller;
});

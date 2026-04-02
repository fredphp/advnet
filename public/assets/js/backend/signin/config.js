define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {

        index: function () {

            // ========== 加载规则列表 ==========
            function loadRuleList() {
                $.ajax({
                    url: 'signin/config/index',
                    type: 'GET',
                    dataType: 'json',
                    success: function (ret) {
                        var html = '';
                        if (ret.rows && ret.rows.length > 0) {
                            for (var i = 0; i < ret.rows.length; i++) {
                                var row = ret.rows[i];
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
                    error: function () {
                        $('#rule-list-body').html('<tr><td colspan="6" class="text-center text-danger">加载失败，请刷新页面重试</td></tr>');
                    }
                });
            }

            // 初始化加载
            loadRuleList();

            // ========== 添加规则按钮 ==========
            $(document).on('click', '#btn-add-rule', function (e) {
                e.preventDefault();
                Fast.api.open('signin/config/add', '添加规则', {
                    area: ['600px', '450px']
                });
            });

            // ========== 编辑规则（事件委托） ==========
            $(document).on('click', '.btn-edit-rule', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
                Fast.api.open('signin/config/edit/ids/' + id, '编辑规则', {
                    area: ['600px', '450px']
                });
            });

            // ========== 删除规则（事件委托） ==========
            $(document).on('click', '.btn-del-rule', function (e) {
                e.preventDefault();
                var id = $(this).data('id');
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
            // Form.api.bindevent 成功后会触发 parent.$(".btn-refresh").trigger("click")
            $(document).on('click', '.btn-refresh', function () {
                loadRuleList();
            });

            // ========== 保存基础配置 ==========
            $('#btn-save-config').on('click', function () {
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
            // 添加规则弹窗中的表单绑定
            Form.api.bindevent($("form[role=form]"));
        },

        edit: function () {
            // 编辑规则弹窗中的表单绑定
            Form.api.bindevent($("form[role=form]"));
        },

        api: {}
    };

    return Controller;
});

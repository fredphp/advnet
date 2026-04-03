define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化日期选择器
            if ($('.datetimepicker').length > 0) {
                require(['bootstrap-datetimepicker'], function () {
                    $('.datetimepicker').datetimepicker({
                        format: 'YYYY-MM-DD',
                        locale: 'zh-cn',
                        useCurrent: false
                    });
                });
            }

            // 默认加载今日统计
            loadStat('today');

            // 绑定按钮事件
            $('#btn-today').on('click', function () { loadStat('today'); });
            $('#btn-yesterday').on('click', function () { loadStat('yesterday'); });
            $('#btn-week').on('click', function () { loadStat('week'); });
            $('#btn-month').on('click', function () { loadStat('month'); });
            $('#btn-custom').on('click', function () { toggleCustomRange(); });
            $('#btn-search').on('click', function () { loadStat('custom'); });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };

    // 全局函数 - 加载统计数据
    function loadStat(type) {
        // 切换按钮高亮
        $('#btn-today, #btn-yesterday, #btn-week, #btn-month, #btn-custom').removeClass('btn-primary active').addClass('btn-default');
        var btnId = '#btn-' + type;
        $(btnId).removeClass('btn-default').addClass('btn-primary active');

        var params = {type: type};
        if (type === 'custom') {
            params.start_date = $('#start_date').val();
            params.end_date = $('#end_date').val();
        }

        $.ajax({
            url: 'adincome/stat/index',
            data: params,
            dataType: 'json',
            type: 'GET',
            success: function (res) {
                if (res.code === 1 && res.data) {
                    var data = res.data;

                    // 更新概览卡片
                    var overview = data.overview || {};
                    $('#stat-records').text(overview.total_records || 0);
                    $('#stat-users').text(overview.user_count || 0);
                    $('#stat-user-coin').text(parseInt(overview.user_coin || 0).toLocaleString());
                    $('#stat-platform-coin').text(parseInt(overview.platform_coin || 0).toLocaleString());

                    // 用户排行
                    var rankingHtml = '';
                    var userRanking = data.user_ranking || [];
                    if (userRanking.length > 0) {
                        for (var i = 0; i < userRanking.length; i++) {
                            var item = userRanking[i];
                            var name = item.nickname || item.username || ('ID:' + item.user_id);
                            rankingHtml += '<tr><td>' + (i + 1) + '</td><td>' + name + '</td><td>' + item.count + '</td><td class="text-success">' + parseInt(item.total_coin || 0).toLocaleString() + '</td></tr>';
                        }
                    } else {
                        rankingHtml = '<tr><td colspan="4" class="text-center text-muted">暂无数据</td></tr>';
                    }
                    $('#user-ranking').html(rankingHtml);

                    // 类型统计
                    var typeHtml = '';
                    var typeStats = data.type_stats || [];
                    if (typeStats.length > 0) {
                        for (var j = 0; j < typeStats.length; j++) {
                            var row = typeStats[j];
                            var typeLabel = row.ad_type === 'feed' ? '信息流广告' : (row.ad_type === 'reward' ? '激励视频' : row.ad_type);
                            typeHtml += '<tr><td>' + typeLabel + '</td><td>' + row.count + '</td><td class="text-success">' + parseInt(row.user_coin || 0).toLocaleString() + '</td></tr>';
                        }
                    } else {
                        typeHtml = '<tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr>';
                    }
                    $('#type-stats').html(typeHtml);
                } else {
                    // 接口返回错误
                    var errorMsg = res.msg || '未知错误';
                    $('#user-ranking').html('<tr><td colspan="4" class="text-center text-danger">' + errorMsg + '</td></tr>');
                    $('#type-stats').html('<tr><td colspan="3" class="text-center text-danger">' + errorMsg + '</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                $('#user-ranking').html('<tr><td colspan="4" class="text-center text-danger">请求失败: ' + (error || status) + '</td></tr>');
                $('#type-stats').html('<tr><td colspan="3" class="text-center text-danger">请求失败: ' + (error || status) + '</td></tr>');
            }
        });
    }

    // 全局函数 - 切换自定义日期范围
    function toggleCustomRange() {
        $('#custom-range').toggle();
        $('#btn-today, #btn-yesterday, #btn-week, #btn-month').removeClass('btn-primary active').addClass('btn-default');
        $('#btn-custom').removeClass('btn-default').addClass('btn-primary active');
    }

    return Controller;
});

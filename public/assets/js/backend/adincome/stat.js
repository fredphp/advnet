define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化日期选择器
            Controller.api.initDatepicker();

            // 默认加载今日统计
            Controller.api.loadStat('today');

            // 绑定按钮事件
            Controller.api.bindEvents();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },

            initDatepicker: function () {
                if ($('.datetimepicker').length > 0) {
                    require(['bootstrap-datetimepicker'], function () {
                        $('.datetimepicker').datetimepicker({
                            format: 'YYYY-MM-DD',
                            locale: 'zh-cn',
                            useCurrent: false
                        });
                    });
                }
            },

            bindEvents: function () {
                $('#btn-today').on('click', function () { Controller.api.loadStat('today'); });
                $('#btn-yesterday').on('click', function () { Controller.api.loadStat('yesterday'); });
                $('#btn-week').on('click', function () { Controller.api.loadStat('week'); });
                $('#btn-month').on('click', function () { Controller.api.loadStat('month'); });
                $('#btn-custom').on('click', function () { Controller.api.toggleCustomRange(); });
                $('#btn-search').on('click', function () { Controller.api.loadStat('custom'); });
            },

            loadStat: function (type) {
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
                    success: function (res) {
                        if (res.code === 1) {
                            var data = res.data;

                            // 更新概览卡片
                            $('#stat-records').text(data.overview.total_records || 0);
                            $('#stat-users').text(data.overview.user_count || 0);
                            $('#stat-user-coin').text(parseInt(data.overview.user_coin || 0).toLocaleString());
                            $('#stat-platform-coin').text(parseInt(data.overview.platform_coin || 0).toLocaleString());

                            // 用户排行
                            var rankingHtml = '';
                            if (data.user_ranking && data.user_ranking.length > 0) {
                                $.each(data.user_ranking, function (i, item) {
                                    rankingHtml += '<tr><td>' + (i + 1) + '</td><td>' + (item.nickname || item.username || 'ID:' + item.user_id) + '</td><td>' + item.count + '</td><td class="text-success">' + parseInt(item.total_coin || 0).toLocaleString() + '</td></tr>';
                                });
                            } else {
                                rankingHtml = '<tr><td colspan="4" class="text-center text-muted">暂无数据</td></tr>';
                            }
                            $('#user-ranking').html(rankingHtml);

                            // 类型统计
                            var typeHtml = '';
                            if (data.type_stats && data.type_stats.length > 0) {
                                $.each(data.type_stats, function (i, item) {
                                    var typeLabel = item.ad_type === 'feed' ? '信息流广告' : (item.ad_type === 'reward' ? '激励视频' : item.ad_type);
                                    typeHtml += '<tr><td>' + typeLabel + '</td><td>' + item.count + '</td><td class="text-success">' + parseInt(item.user_coin || 0).toLocaleString() + '</td></tr>';
                                });
                            } else {
                                typeHtml = '<tr><td colspan="3" class="text-center text-muted">暂无数据</td></tr>';
                            }
                            $('#type-stats').html(typeHtml);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('广告统计请求失败:', error);
                    }
                });
            },

            toggleCustomRange: function () {
                $('#custom-range').toggle();
                // 切换按钮状态
                $('#btn-today, #btn-yesterday, #btn-week, #btn-month').removeClass('btn-primary active').addClass('btn-default');
                $('#btn-custom').removeClass('btn-default').addClass('btn-primary active');
            }
        }
    };

    return Controller;
});

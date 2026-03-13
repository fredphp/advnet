define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form, Echarts) {
    var Controller = {
        index: function () {
            // 加载仪表盘数据
            Controller.api.loadDashboard();
            // 绑定刷新事件
            $(document).on('click', '.btn-refresh', function() {
                Controller.api.loadDashboard();
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            // 风险等级颜色映射
            riskLevelMap: {
                'safe': { label: '安全', class: 'safe', color: '#10ac84' },
                'low': { label: '低风险', class: 'low', color: '#54a0ff' },
                'medium': { label: '中风险', class: 'medium', color: '#feca57' },
                'high': { label: '高风险', class: 'high', color: '#ff9f43' },
                'dangerous': { label: '危险', class: 'dangerous', color: '#ee5a5a' }
            },
            // 状态映射
            statusMap: {
                'normal': { label: '正常', color: '#10ac84' },
                'frozen': { label: '冻结', color: '#feca57' },
                'banned': { label: '封禁', color: '#ee5a5a' }
            },
            // 封禁类型映射
            banTypeMap: {
                'temporary': { label: '临时封禁', class: 'label-warning' },
                'permanent': { label: '永久封禁', class: 'label-danger' }
            },
            // 图表实例
            chartInstances: {},
            
            loadDashboard: function () {
                // 显示加载状态
                $('.stat-number').text('-');
                
                $.ajax({
                    url: 'risk/dashboard/index',
                    type: 'GET',
                    dataType: 'json',
                    success: function (ret) {
                        if (ret.code == 1) {
                            var data = ret.data;
                            
                            // 1. 更新统计卡片
                            Controller.api.updateStats(data);
                            
                            // 2. 更新图表
                            Controller.api.updateCharts(data);
                            
                            // 3. 更新表格
                            Controller.api.updateTables(data);
                            
                            // 4. 更新预警
                            Controller.api.updateAlerts(data);
                        } else {
                            console.error('加载数据失败:', ret.msg);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('请求失败:', error);
                    }
                });
            },
            
            // 更新统计卡片
            updateStats: function(data) {
                // 今日违规次数
                var todayViolations = 0;
                if (data.hourly_violations && data.hourly_violations.length > 0) {
                    data.hourly_violations.forEach(function(item) {
                        todayViolations += parseInt(item.count) || 0;
                    });
                }
                $('#stat-violations').text(todayViolations);
                
                // 今日封禁人数
                var todayBans = 0;
                var autoBans = 0;
                var manualBans = 0;
                if (data.recent_bans && data.recent_bans.length > 0) {
                    todayBans = data.recent_bans.length;
                    data.recent_bans.forEach(function(item) {
                        if (item.ban_source === 'auto') {
                            autoBans++;
                        } else {
                            manualBans++;
                        }
                    });
                }
                $('#stat-bans').text(todayBans);
                $('#auto-bans').text(autoBans);
                $('#manual-bans').text(manualBans);
                
                // 高风险用户
                var highRiskCount = 0;
                var dangerousCount = 0;
                if (data.risk_user_stats && data.risk_user_stats.length > 0) {
                    data.risk_user_stats.forEach(function(item) {
                        if (item.risk_level === 'high') {
                            highRiskCount = parseInt(item.count) || 0;
                        } else if (item.risk_level === 'dangerous') {
                            dangerousCount = parseInt(item.count) || 0;
                        }
                    });
                }
                $('#stat-highrisk').text(highRiskCount + dangerousCount);
                $('#dangerous-users').text(dangerousCount);
                
                // 黑名单数量
                if (data.today_stats) {
                    $('#stat-blacklist').text(data.today_stats.blacklist_count || 0);
                    $('#ip-blacklist').text(data.today_stats.ip_blacklist || 0);
                    $('#device-blacklist').text(data.today_stats.device_blacklist || 0);
                }
            },
            
            // 更新图表
            updateCharts: function(data) {
                // 风险等级分布饼图
                Controller.api.renderRiskLevelChart(data.risk_user_stats || []);
                
                // 用户状态分布饼图
                Controller.api.renderUserStatusChart(data.user_status_stats || []);
                
                // 24小时违规趋势
                Controller.api.renderHourlyChart(data.hourly_violations || []);
            },
            
            // 渲染风险等级饼图
            renderRiskLevelChart: function(data) {
                var chartDom = document.getElementById('risk-level-chart');
                if (!chartDom) return;
                
                var chartData = [];
                var legendHtml = '<div class="row">';
                
                if (data.length > 0) {
                    data.forEach(function(item) {
                        var level = Controller.api.riskLevelMap[item.risk_level] || { label: item.risk_level, color: '#ccc' };
                        chartData.push({
                            name: level.label,
                            value: parseInt(item.count) || 0,
                            itemStyle: { color: level.color }
                        });
                        
                        legendHtml += '<div class="col-xs-6" style="margin-bottom: 8px;">' +
                            '<span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:' + level.color + ';margin-right:5px;"></span>' +
                            '<small>' + level.label + ': <strong>' + (parseInt(item.count) || 0) + '</strong></small>' +
                            '</div>';
                    });
                } else {
                    chartData.push({ name: '无数据', value: 1, itemStyle: { color: '#e9ecef' } });
                }
                
                legendHtml += '</div>';
                $('#risk-level-legend').html(legendHtml);
                
                // 销毁旧图表
                if (Controller.api.chartInstances.riskLevel) {
                    Controller.api.chartInstances.riskLevel.dispose();
                }
                
                var chart = Echarts.init(chartDom);
                Controller.api.chartInstances.riskLevel = chart;
                
                var option = {
                    tooltip: {
                        trigger: 'item',
                        formatter: '{b}: {c} ({d}%)'
                    },
                    series: [{
                        type: 'pie',
                        radius: ['50%', '70%'],
                        center: ['50%', '50%'],
                        label: { show: false },
                        data: chartData
                    }]
                };
                
                chart.setOption(option);
            },
            
            // 渲染用户状态饼图
            renderUserStatusChart: function(data) {
                var chartDom = document.getElementById('user-status-chart');
                if (!chartDom) return;
                
                var chartData = [];
                var legendHtml = '<div class="row">';
                
                if (data.length > 0) {
                    data.forEach(function(item) {
                        var status = Controller.api.statusMap[item.status] || { label: item.status, color: '#ccc' };
                        chartData.push({
                            name: status.label,
                            value: parseInt(item.count) || 0,
                            itemStyle: { color: status.color }
                        });
                        
                        legendHtml += '<div class="col-xs-6" style="margin-bottom: 8px;">' +
                            '<span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:' + status.color + ';margin-right:5px;"></span>' +
                            '<small>' + status.label + ': <strong>' + (parseInt(item.count) || 0) + '</strong></small>' +
                            '</div>';
                    });
                } else {
                    chartData.push({ name: '无数据', value: 1, itemStyle: { color: '#e9ecef' } });
                }
                
                legendHtml += '</div>';
                $('#user-status-legend').html(legendHtml);
                
                // 销毁旧图表
                if (Controller.api.chartInstances.userStatus) {
                    Controller.api.chartInstances.userStatus.dispose();
                }
                
                var chart = Echarts.init(chartDom);
                Controller.api.chartInstances.userStatus = chart;
                
                var option = {
                    tooltip: {
                        trigger: 'item',
                        formatter: '{b}: {c} ({d}%)'
                    },
                    series: [{
                        type: 'pie',
                        radius: ['50%', '70%'],
                        center: ['50%', '50%'],
                        label: { show: false },
                        data: chartData
                    }]
                };
                
                chart.setOption(option);
            },
            
            // 渲染24小时违规趋势图
            renderHourlyChart: function(data) {
                var chartDom = document.getElementById('hourly-chart');
                if (!chartDom) return;
                
                var labels = [];
                var values = [];
                var maxVal = 0;
                
                // 生成24小时标签
                var now = new Date();
                for (var i = 23; i >= 0; i--) {
                    var hour = new Date(now - i * 3600000);
                    var hourStr = hour.getHours().toString().padStart(2, '0') + ':00';
                    labels.push(hourStr);
                    
                    // 查找对应数据
                    var found = false;
                    if (data.length > 0) {
                        for (var j = 0; j < data.length; j++) {
                            if (data[j].hour && data[j].hour.indexOf(hourStr.replace(':00', '')) !== -1) {
                                var val = parseInt(data[j].count) || 0;
                                values.push(val);
                                if (val > maxVal) maxVal = val;
                                found = true;
                                break;
                            }
                        }
                    }
                    if (!found) {
                        values.push(0);
                    }
                }
                
                // 销毁旧图表
                if (Controller.api.chartInstances.hourly) {
                    Controller.api.chartInstances.hourly.dispose();
                }
                
                var chart = Echarts.init(chartDom);
                Controller.api.chartInstances.hourly = chart;
                
                var option = {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' }
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        top: '3%',
                        containLabel: true
                    },
                    xAxis: {
                        type: 'category',
                        data: labels,
                        axisLine: { lineStyle: { color: '#ddd' } },
                        axisLabel: { color: '#666' }
                    },
                    yAxis: {
                        type: 'value',
                        minInterval: 1,
                        splitLine: { lineStyle: { color: '#eee' } },
                        axisLabel: { color: '#666' }
                    },
                    series: [{
                        type: 'bar',
                        data: values,
                        itemStyle: {
                            color: '#ee5a5a',
                            borderRadius: [4, 4, 0, 0]
                        },
                        barWidth: '60%'
                    }]
                };
                
                chart.setOption(option);
            },
            
            // 更新表格
            updateTables: function(data) {
                // 最近封禁记录
                var banHtml = '';
                if (data.recent_bans && data.recent_bans.length > 0) {
                    data.recent_bans.forEach(function(item) {
                        var banType = Controller.api.banTypeMap[item.ban_type] || { label: item.ban_type, class: 'label-default' };
                        var sourceLabel = item.ban_source === 'auto' ? 
                            '<span class="label label-info">自动</span>' : 
                            '<span class="label label-primary">手动</span>';
                        
                        banHtml += '<tr>' +
                            '<td><a href="member/user/detail/ids/' + item.user_id + '?ref=addtabs">' + (item.username || item.nickname || 'ID:' + item.user_id) + '</a></td>' +
                            '<td><span class="label ' + banType.class + '">' + banType.label + '</span></td>' +
                            '<td title="' + (item.ban_reason || '-') + '">' + Controller.api.truncate(item.ban_reason || '-', 15) + '</td>' +
                            '<td>' + sourceLabel + '</td>' +
                            '<td><small>' + Controller.api.formatTime(item.createtime) + '</small></td>' +
                            '</tr>';
                    });
                } else {
                    banHtml = '<tr><td colspan="5" class="text-center text-muted" style="padding: 30px;">暂无封禁记录</td></tr>';
                }
                $('#recent-bans-table').html(banHtml);
                
                // 规则触发排行
                var ruleHtml = '';
                var totalTriggers = 0;
                if (data.rule_trigger_stats && data.rule_trigger_stats.length > 0) {
                    data.rule_trigger_stats.forEach(function(item) {
                        totalTriggers += parseInt(item.trigger_count) || 0;
                    });
                    
                    data.rule_trigger_stats.forEach(function(item) {
                        var count = parseInt(item.trigger_count) || 0;
                        var percent = totalTriggers > 0 ? Math.round(count / totalTriggers * 100) : 0;
                        
                        ruleHtml += '<tr>' +
                            '<td>' + (item.rule_name || item.rule_code || '-') + '</td>' +
                            '<td><small>' + (item.rule_type || '-') + '</small></td>' +
                            '<td><strong>' + count + '</strong></td>' +
                            '<td style="width: 120px;">' +
                                '<div class="progress" style="margin-bottom: 0;">' +
                                    '<div class="progress-bar progress-bar-warning" style="width: ' + percent + '%"></div>' +
                                '</div>' +
                            '</td>' +
                            '</tr>';
                    });
                } else {
                    ruleHtml = '<tr><td colspan="4" class="text-center text-muted" style="padding: 30px;">暂无触发记录</td></tr>';
                }
                $('#rule-trigger-table').html(ruleHtml);
            },
            
            // 更新预警区域
            updateAlerts: function(data) {
                // 高风险用户预警
                var highRiskHtml = '';
                if (data.alerts && data.alerts.high_risk && data.alerts.high_risk.length > 0) {
                    data.alerts.high_risk.forEach(function(item) {
                        var currentStatus = item.current_status || 'normal';
                        var actionButtons = '';
                        
                        // 只有正常状态才显示冻结和封禁按钮
                        if (currentStatus === 'normal') {
                            actionButtons = '<div class="action-buttons">' +
                                '<button class="btn btn-freeze btn-freeze-user" data-user-id="' + item.user_id + '"><i class="fa fa-snowflake"></i> 冻结</button>' +
                                '<button class="btn btn-ban btn-ban-user" data-user-id="' + item.user_id + '"><i class="fa fa-ban"></i> 封禁</button>' +
                                '</div>';
                        } else if (currentStatus === 'frozen') {
                            actionButtons = '<div style="margin-top: 12px;"><span class="status-badge status-frozen"><i class="fa fa-snowflake"></i> 已冻结</span></div>';
                        } else {
                            actionButtons = '<div style="margin-top: 12px;"><span class="status-badge status-banned"><i class="fa fa-ban"></i> 已封禁</span></div>';
                        }
                        
                        highRiskHtml += '<div class="alert-item alert-warning">' +
                            '<div class="user-info">' +
                                '<div class="user-avatar"><i class="fa fa-user"></i></div>' +
                                '<div class="user-details">' +
                                    '<div class="user-name"><a href="javascript:;" onclick="Backend.api.addtabs(\'risk/userrisk\', \'用户风险\')">用户 ID: ' + item.user_id + '</a></div>' +
                                    '<div class="user-meta">' +
                                        '<span>风险分: <strong class="text-danger">' + (item.total_score || 0) + '</strong></span>' +
                                        '<span>违规: <strong>' + (item.violation_count || 0) + '</strong> 次</span>' +
                                    '</div>' +
                                    actionButtons +
                                '</div>' +
                                '<span class="risk-level-badge high">高风险</span>' +
                            '</div>' +
                            '</div>';
                    });
                } else {
                    highRiskHtml = '<div class="text-center text-muted" style="padding: 40px;"><i class="fa fa-check-circle" style="font-size: 48px; color: #10ac84; margin-bottom: 15px;"></i><br>暂无高风险用户</div>';
                }
                $('#high-risk-alerts').html(highRiskHtml);
                
                // 危险用户预警
                var dangerousHtml = '';
                if (data.alerts && data.alerts.dangerous && data.alerts.dangerous.length > 0) {
                    dangerousHtml = '<div style="margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f8d7da;"><i class="fa fa-skull-crossbones text-danger"></i> <strong class="text-danger">危险用户</strong></div>';
                    data.alerts.dangerous.forEach(function(item) {
                        var currentStatus = item.current_status || 'normal';
                        var actionButtons = '';
                        
                        // 只有正常状态才显示冻结和封禁按钮
                        if (currentStatus === 'normal') {
                            actionButtons = '<div class="action-buttons">' +
                                '<button class="btn btn-freeze btn-freeze-user" data-user-id="' + item.user_id + '"><i class="fa fa-snowflake"></i> 冻结</button>' +
                                '<button class="btn btn-ban btn-ban-user" data-user-id="' + item.user_id + '"><i class="fa fa-ban"></i> 封禁</button>' +
                                '</div>';
                        } else if (currentStatus === 'frozen') {
                            actionButtons = '<div style="margin-top: 12px;"><span class="status-badge status-frozen"><i class="fa fa-snowflake"></i> 已冻结</span></div>';
                        } else {
                            actionButtons = '<div style="margin-top: 12px;"><span class="status-badge status-banned"><i class="fa fa-ban"></i> 已封禁</span></div>';
                        }
                        
                        dangerousHtml += '<div class="alert-item alert-danger">' +
                            '<div class="user-info">' +
                                '<div class="user-avatar"><i class="fa fa-exclamation-triangle"></i></div>' +
                                '<div class="user-details">' +
                                    '<div class="user-name"><a href="javascript:;" onclick="Backend.api.addtabs(\'risk/userrisk\', \'用户风险\')">用户 ID: ' + item.user_id + '</a></div>' +
                                    '<div class="user-meta">' +
                                        '<span>风险分: <strong class="text-danger">' + (item.total_score || 0) + '</strong></span>' +
                                        '<span>违规: <strong>' + (item.violation_count || 0) + '</strong> 次</span>' +
                                    '</div>' +
                                    actionButtons +
                                '</div>' +
                                '<span class="risk-level-badge dangerous">危险</span>' +
                            '</div>' +
                            '</div>';
                    });
                    $('#high-risk-alerts').prepend(dangerousHtml);
                }
                
                // 频繁违规用户
                var frequentHtml = '';
                if (data.alerts && data.alerts.recent_violators && data.alerts.recent_violators.length > 0) {
                    data.alerts.recent_violators.forEach(function(item) {
                        frequentHtml += '<div class="alert-item alert-info">' +
                            '<div class="user-info">' +
                                '<div class="user-avatar"><i class="fa fa-clock"></i></div>' +
                                '<div class="user-details">' +
                                    '<div class="user-name"><a href="javascript:;" onclick="Backend.api.addtabs(\'risk/userrisk\', \'用户风险\')">用户 ID: ' + item.user_id + '</a></div>' +
                                    '<div class="user-meta">' +
                                        '<span>24h违规: <strong class="text-warning">' + (item.violation_count || 0) + '</strong> 次</span>' +
                                    '</div>' +
                                '</div>' +
                                '<span class="risk-level-badge medium">频繁</span>' +
                            '</div>' +
                            '</div>';
                    });
                } else {
                    frequentHtml = '<div class="text-center text-muted" style="padding: 40px;"><i class="fa fa-check-circle" style="font-size: 48px; color: #10ac84; margin-bottom: 15px;"></i><br>暂无频繁违规用户</div>';
                }
                $('#frequent-violators').html(frequentHtml);
                
                // 绑定冻结按钮事件
                $(document).off('click', '.btn-freeze-user').on('click', '.btn-freeze-user', function() {
                    var userId = $(this).data('user-id');
                    Controller.api.showActionModal(userId, 'freeze');
                });
                
                // 绑定封禁按钮事件
                $(document).off('click', '.btn-ban-user').on('click', '.btn-ban-user', function() {
                    var userId = $(this).data('user-id');
                    Controller.api.showActionModal(userId, 'ban');
                });
            },
            
            // 显示操作弹窗
            showActionModal: function(userId, actionType) {
                var loadIndex = Layer.load(1);
                
                $.ajax({
                    url: 'risk/dashboard/getUserInfo',
                    type: 'GET',
                    dataType: 'json',
                    data: { user_id: userId },
                    success: function(ret) {
                        Layer.close(loadIndex);
                        
                        if (ret.code !== 1) {
                            Layer.alert(ret.msg || '获取用户信息失败', { icon: 2 });
                            return;
                        }
                        
                        var info = ret.data;
                        var currentStatus = info.risk_score.status || 'normal';
                        
                        // 如果已冻结或已封禁，直接显示状态
                        if (currentStatus === 'frozen') {
                            Layer.alert('该用户已被冻结，无需重复操作', { icon: 0 });
                            return;
                        }
                        if (currentStatus === 'banned') {
                            Layer.alert('该用户已被封禁', { icon: 0 });
                            return;
                        }
                        
                        // 构建弹窗内容
                        var statusClass = actionType === 'freeze' ? 'alert-warning' : 'alert-danger';
                        var statusIcon = actionType === 'freeze' ? 'fa-snowflake' : 'fa-ban';
                        var statusTitle = actionType === 'freeze' ? '冻结用户' : '封禁用户';
                        var statusColor = actionType === 'freeze' ? '#ff9f43' : '#ee5a5a';
                        
                        var html = '<div style="padding: 15px;">';
                        
                        // 用户信息
                        html += '<div class="alert-item ' + statusClass + '" style="margin-bottom: 15px; border-left: 4px solid ' + statusColor + ';">';
                        html += '<div class="user-info">';
                        html += '<div class="user-avatar" style="background: linear-gradient(135deg, ' + statusColor + ', ' + statusColor + ');"><i class="fa fa-user"></i></div>';
                        html += '<div class="user-details">';
                        html += '<div class="user-name">用户 ID: ' + info.user.id + '</div>';
                        html += '<div class="user-meta">';
                        html += '<span>用户名: <strong>' + (info.user.username || '-') + '</strong></span>';
                        html += '<span>手机号: <strong>' + (info.user.mobile || '-') + '</strong></span>';
                        html += '</div>';
                        html += '<div class="user-meta">';
                        html += '<span>风险分: <strong class="text-danger">' + (info.risk_score.total_score || 0) + '</strong></span>';
                        html += '<span>违规次数: <strong>' + (info.risk_score.violation_count || 0) + '</strong></span>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        
                        // 违规记录
                        html += '<div style="margin-bottom: 15px;"><strong><i class="fa fa-history"></i> 近期违规记录</strong></div>';
                        if (info.risk_logs && info.risk_logs.length > 0) {
                            html += '<table class="table table-striped table-condensed" style="font-size: 12px;">';
                            html += '<thead><tr><th>时间</th><th>规则</th><th>加分</th><th>动作</th></tr></thead>';
                            html += '<tbody>';
                            info.risk_logs.forEach(function(log) {
                                html += '<tr>';
                                html += '<td>' + (log.createtime_text || '-') + '</td>';
                                html += '<td>' + (log.rule_name || '-') + '</td>';
                                html += '<td><span class="text-danger">+' + (log.score_add || 0) + '</span></td>';
                                html += '<td>' + (log.action || '-') + '</td>';
                                html += '</tr>';
                            });
                            html += '</tbody></table>';
                        } else {
                            html += '<p class="text-muted text-center" style="padding: 20px;">暂无违规记录</p>';
                        }
                        
                        // 操作表单
                        html += '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e9ecef;">';
                        html += '<form id="action-form">';
                        if (actionType === 'freeze') {
                            html += '<div class="form-group">';
                            html += '<label>冻结时长（天）</label>';
                            html += '<select name="duration" class="form-control">';
                            html += '<option value="1">1天</option>';
                            html += '<option value="3">3天</option>';
                            html += '<option value="7" selected>7天</option>';
                            html += '<option value="15">15天</option>';
                            html += '<option value="30">30天</option>';
                            html += '</select>';
                            html += '</div>';
                        }
                        html += '<div class="form-group">';
                        html += '<label>原因</label>';
                        html += '<textarea name="reason" class="form-control" rows="2" placeholder="请填写' + statusTitle + '原因">' + (actionType === 'freeze' ? '仪表盘手动冻结' : '仪表盘手动封禁') + '</textarea>';
                        html += '</div>';
                        html += '<input type="hidden" name="user_id" value="' + userId + '">';
                        html += '</form>';
                        html += '</div>';
                        
                        html += '</div>';
                        
                        // 显示弹窗
                        Layer.open({
                            type: 1,
                            title: '<i class="fa ' + statusIcon + '" style="color: ' + statusColor + ';"></i> ' + statusTitle,
                            area: ['550px', 'auto'],
                            maxHeight: 500,
                            content: html,
                            btn: ['确认' + statusTitle, '取消'],
                            yes: function(layerIndex, layero) {
                                var form = $('#action-form');
                                var data = {
                                    user_id: userId,
                                    duration: form.find('select[name="duration"]').val() || 7,
                                    reason: form.find('textarea[name="reason"]').val()
                                };
                                
                                $.ajax({
                                    url: 'risk/dashboard/' + actionType,
                                    type: 'POST',
                                    dataType: 'json',
                                    data: data,
                                    success: function(ret) {
                                        if (ret.code === 1) {
                                            Layer.close(layerIndex);
                                            Layer.alert((actionType === 'freeze' ? '冻结' : '封禁') + '成功', { icon: 1 });
                                            Controller.api.loadDashboard();
                                        } else {
                                            Layer.alert(ret.msg || '操作失败', { icon: 2 });
                                        }
                                    },
                                    error: function() {
                                        Layer.alert('请求失败', { icon: 2 });
                                    }
                                });
                            }
                        });
                    },
                    error: function() {
                        Layer.close(loadIndex);
                        Layer.alert('获取用户信息失败', { icon: 2 });
                    }
                });
            },
            
            // 截断文本
            truncate: function(str, len) {
                if (!str) return '-';
                if (str.length <= len) return str;
                return str.substring(0, len) + '...';
            },
            
            // 格式化时间
            formatTime: function(timestamp) {
                if (!timestamp) return '-';
                var date = new Date(parseInt(timestamp) * 1000);
                var now = new Date();
                var diff = (now - date) / 1000;
                
                if (diff < 60) return '刚刚';
                if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
                if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
                if (diff < 604800) return Math.floor(diff / 86400) + '天前';
                
                return date.getFullYear() + '-' + 
                       (date.getMonth() + 1).toString().padStart(2, '0') + '-' + 
                       date.getDate().toString().padStart(2, '0');
            }
        }
    };
    return Controller;
});

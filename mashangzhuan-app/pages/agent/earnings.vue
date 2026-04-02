<template>
        <view class="earnings-page">
                <!-- 顶部导航栏 -->
                <fa-navbar title="收益明细" :border-bottom="false"></fa-navbar>

                <!-- 收益概览 -->
                <view class="earnings-overview">
                        <view class="overview-item">
                                <text class="overview-label">累计收益</text>
                                <text class="overview-value">¥{{ userInfo.total_income || '0.00' }}</text>
                        </view>
                        <view class="overview-divider"></view>
                        <view class="overview-item">
                                <text class="overview-label">本月收益</text>
                                <text class="overview-value">¥{{ userInfo.month_reward || '0.00' }}</text>
                        </view>
                        <view class="overview-divider"></view>
                        <view class="overview-item">
                                <text class="overview-label">待结算</text>
                                <text class="overview-value">¥{{ pendingMoney }}</text>
                        </view>
                </view>

                <!-- 来源类型筛选（暂时屏蔽） -->
                <!-- <view class="filter-section">
                        <view class="filter-tabs">
                                <view class="tab-item" :class="{ active: activeSource === '' }" @click="handleSourceChange('')">
                                        <text>全部</text>
                                </view>
                                <view class="tab-item" :class="{ active: activeSource === 'withdraw' }" @click="handleSourceChange('withdraw')">
                                        <text>提现分佣</text>
                                </view>
                                <view class="tab-item" :class="{ active: activeSource === 'video' }" @click="handleSourceChange('video')">
                                        <text>视频分佣</text>
                                </view>
                                <view class="tab-item" :class="{ active: activeSource === 'red_packet' }" @click="handleSourceChange('red_packet')">
                                        <text>红包分佣</text>
                                </view>
                                <view class="tab-item" :class="{ active: activeSource === 'game' }" @click="handleSourceChange('game')">
                                        <text>游戏分佣</text>
                                </view>
                        </view>
                </view> -->

                <!-- 日期筛选 -->
                <view class="search-section">
                        <view class="time-picker">
                                <picker mode="date" :value="startDate" @change="bindStartDateChange" fields="month">
                                        <view class="picker">{{ startDate || '开始月份' }}</view>
                                </picker>
                                <text class="separator">至</text>
                                <picker mode="date" :value="endDate" @change="bindEndDateChange" fields="month">
                                        <view class="picker">{{ endDate || '结束月份' }}</view>
                                </picker>
                        </view>
                        <view class="search-actions">
                                <view class="reset-btn" @click="handleReset" v-if="startDate || endDate">
                                        <text>重置</text>
                                </view>
                                <view class="search-btn" @click="handleSearch">
                                        <text>搜索</text>
                                </view>
                        </view>
                </view>

                <!-- 收益明细列表 -->
                <view class="earnings-list">
                        <view class="list-header">
                                <text class="header-title">收益明细</text>
                                <text class="header-count" v-if="total > 0">共{{ total }}条</text>
                        </view>

                        <!-- 列表项 -->
                        <view class="earning-item" v-for="(item, index) in earnings" :key="index">
                                <view class="item-left">
                                        <u-avatar :src="item.user_info && item.user_info.avatar ? item.user_info.avatar : '/static/image/avatar.png'" size="80" mode="circle"></u-avatar>
                                        <view class="item-detail">
                                                <view class="item-title">{{ item.goods ? item.goods.title : '佣金收益' }}</view>
                                                <view class="item-meta">
                                                        <text class="item-time">{{ item.createtime }}</text>
                                                        <text class="item-status" :class="'status-' + item.status">{{ item.status_text }}</text>
                                                </view>
                                                <view class="item-sub" v-if="item.user_info">
                                                        <text class="item-nickname">来自: {{ item.user_info.nickname }}</text>
                                                        <text class="item-level" v-if="item.level">· {{ item.level == 1 ? '一级' : '二级' }}</text>
                                                </view>
                                        </view>
                                </view>
                                <view class="item-right">
                                        <text class="item-amount" :class="{'amount-positive': item.status === 'completed'}">
                                                {{ item.status === 'completed' ? '+' : '' }}¥{{ Number(item.reward_money || 0).toFixed(2) }}
                                        </text>
                                </view>
                        </view>

                        <!-- 加载中 -->
                        <view class="load-more" v-if="loading && page > 1">
                                <view class="loading-spinner"></view>
                                <text class="load-text">加载中...</text>
                        </view>

                        <!-- 首次加载中 -->
                        <view class="first-loading" v-if="loading && page === 1 && earnings.length === 0">
                                <view class="loading-spinner"></view>
                                <text class="load-text">加载中...</text>
                        </view>

                        <!-- 没有更多 -->
                        <view class="load-more" v-else-if="!hasMore && earnings.length > 0">
                                <text class="load-text">— 没有更多了 —</text>
                        </view>

                        <!-- 空状态 -->
                        <view class="no-data" v-if="!loading && earnings.length === 0">
                                <u-empty mode="list" text="暂无收益记录"></u-empty>
                        </view>
                </view>

                <!-- 底部安全区域 -->
                <view class="safe-bottom"></view>
        </view>
</template>

<script>
        import uAvatar from '@/uview-ui/components/u-avatar/u-avatar.vue';

        export default {
                components: {
                        uAvatar
                },
                data() {
                        return {
                                // 用户概览信息
                                userInfo: {},
                                // 待结算金额
                                pendingMoney: '0.00',

                                // 分佣来源类型筛选
                                activeSource: '',

                                // 日期筛选
                                startDate: '',
                                endDate: '',

                                // 收益列表
                                earnings: [],

                                // 分页
                                page: 1,
                                pageSize: 20,
                                total: 0,
                                loading: false,
                                hasMore: false,

                                // 来源类型名称映射
                                sourceTypeNames: {
                                        'withdraw': '提现分佣',
                                        'video': '视频分佣',
                                        'red_packet': '红包分佣',
                                        'game': '游戏分佣',
                                        'sign': '签到分佣',
                                        'other': '其他分佣',
                                },
                        };
                },
                onLoad() {
                        // 默认查询当月
                        const now = new Date();
                        const year = now.getFullYear();
                        const month = String(now.getMonth() + 1).padStart(2, '0');
                        const currentMonth = year + '-' + month;
                        this.startDate = currentMonth;
                        this.endDate = currentMonth;
                        // 加载数据
                        this.loadOverview();
                        this.loadEarnings(true);
                },
                onPullDownRefresh() {
                        // 下拉刷新
                        this.resetAndLoad();
                },
                onReachBottom() {
                        // 上拉加载更多
                        this.loadMore();
                },
                methods: {
                        // 加载概览数据
                        loadOverview() {
                                this.$api.inviteOverview().then(res => {
                                        if (res && res.code == 1) {
                                                this.userInfo = {
                                                        ...res.data,
                                                        total_income: res.data.total_income || '0.00',
                                                        month_reward: res.data.month_reward || '0.00',
                                                };
                                                // 待结算 = 冻结金币 / 汇率
                                                const coinFrozen = parseFloat(res.data.coin_frozen || 0);
                                                const exchangeRate = parseFloat(res.data.exchange_rate || 10000);
                                                if (exchangeRate > 0) {
                                                        this.pendingMoney = (coinFrozen / exchangeRate).toFixed(2);
                                                }
                                        }
                                }).catch(err => {
                                        console.error('[Earnings] overview接口异常:', err);
                                });
                        },

                        // 加载收益列表（reset=true时重置列表）
                        loadEarnings(reset) {
                                if (this.loading) return;
                                if (reset) {
                                        this.page = 1;
                                        this.earnings = [];
                                        this.hasMore = false;
                                }
                                this.loading = true;

                                const params = {
                                        page: this.page,
                                        limit: this.pageSize,
                                };
                                if (this.activeSource) {
                                        params.source_type = this.activeSource;
                                }
                                if (this.startDate) {
                                        params.start_time = this.startDate + '-01';
                                }
                                if (this.endDate) {
                                        // 获取结束月份的最后一天
                                        const endDateParts = this.endDate.split('-');
                                        const year = parseInt(endDateParts[0]);
                                        const month = parseInt(endDateParts[1]);
                                        const lastDay = new Date(year, month, 0).getDate();
                                        params.end_time = this.endDate + '-' + String(lastDay).padStart(2, '0');
                                }

                                this.$api.inviteCommissionList(params).then(res => {
                                        if (res && res.code == 1) {
                                                const newList = res.data.list || [];
                                                this.total = res.data.total || 0;

                                                if (reset) {
                                                        this.earnings = newList;
                                                } else {
                                                        this.earnings = this.earnings.concat(newList);
                                                }
                                                this.hasMore = this.earnings.length < this.total;
                                        }
                                }).catch(err => {
                                        console.error('[Earnings] commissionList接口异常:', err);
                                        uni.showToast({ title: '加载失败', icon: 'none' });
                                }).finally(() => {
                                        this.loading = false;
                                        uni.stopPullDownRefresh();
                                });
                        },

                        // 重置并加载
                        resetAndLoad() {
                                this.loadOverview();
                                this.loadEarnings(true);
                        },

                        // 上拉加载更多
                        loadMore() {
                                if (this.loading || !this.hasMore) return;
                                this.page++;
                                this.loadEarnings(false);
                        },

                        // 来源类型切换
                        handleSourceChange(sourceType) {
                                if (this.activeSource === sourceType) return;
                                this.activeSource = sourceType;
                                this.loadEarnings(true);
                        },

                        // 开始日期选择
                        bindStartDateChange(e) {
                                this.startDate = e.detail.value;
                        },

                        // 结束日期选择
                        bindEndDateChange(e) {
                                this.endDate = e.detail.value;
                        },

                        // 搜索
                        handleSearch() {
                                this.loadEarnings(true);
                        },

                        // 重置筛选
                        handleReset() {
                                this.startDate = '';
                                this.endDate = '';
                                this.loadEarnings(true);
                        },
                }
        };
</script>

<style scoped lang="scss">
        .earnings-page {
                background-color: #F5F7FA;
                min-height: 100vh;
                font-size: 28rpx;
                color: #333;
        }

        /* 收益概览 */
        .earnings-overview {
                margin: 24rpx 24rpx 0;
                border-radius: 20rpx;
                display: flex;
                padding: 36rpx 0;
                color: #fff;
                background: linear-gradient(135deg, #FF8D3B 0%, #E62129 100%);
                box-shadow: 0 8rpx 24rpx rgba(230, 33, 41, 0.25);

                .overview-item {
                        flex: 1;
                        text-align: center;

                        .overview-label {
                                font-size: 22rpx;
                                opacity: 0.85;
                        }

                        .overview-value {
                                font-size: 34rpx;
                                font-weight: bold;
                                margin-top: 10rpx;
                                display: block;
                        }
                }

                .overview-divider {
                        width: 2rpx;
                        height: 60rpx;
                        background: rgba(255, 255, 255, 0.3);
                }
        }

        /* 来源类型筛选 */
        .filter-section {
                margin: 20rpx 24rpx 0;
                background: #fff;
                border-radius: 16rpx;
                padding: 6rpx;
                box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);
                overflow-x: auto;
                white-space: nowrap;

                .filter-tabs {
                        display: flex;
                        -webkit-overflow-scrolling: touch;
                }

                .tab-item {
                        flex-shrink: 0;
                        text-align: center;
                        padding: 18rpx 20rpx;
                        font-size: 26rpx;
                        color: #666;
                        border-radius: 12rpx;
                        transition: all 0.25s ease;

                        &:active {
                                opacity: 0.7;
                        }

                        &.active {
                                color: #E62129;
                                font-weight: 600;
                                background: rgba(230, 33, 41, 0.08);
                        }
                }
        }

        /* 日期筛选 */
        .search-section {
                display: flex;
                align-items: center;
                background-color: #fff;
                border-radius: 16rpx;
                padding: 16rpx 20rpx;
                margin: 20rpx 24rpx 0;
                box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

                .time-picker {
                        flex: 1;
                        display: flex;
                        align-items: center;

                        .picker {
                                padding: 14rpx 20rpx;
                                background-color: #F5F7FA;
                                border-radius: 10rpx;
                                font-size: 26rpx;
                                color: #333;
                                min-width: 160rpx;
                                text-align: center;
                        }

                        .separator {
                                margin: 0 16rpx;
                                font-size: 26rpx;
                                color: #999;
                                flex-shrink: 0;
                        }
                }

                .search-actions {
                        display: flex;
                        align-items: center;
                        flex-shrink: 0;
                        margin-left: 16rpx;

                        .reset-btn {
                                padding: 14rpx 24rpx;
                                background-color: #F5F7FA;
                                border-radius: 10rpx;
                                font-size: 26rpx;
                                color: #666;
                                margin-right: 12rpx;

                                &:active {
                                        opacity: 0.7;
                                }
                        }

                        .search-btn {
                                padding: 14rpx 32rpx;
                                background-color: #E62129;
                                color: #fff;
                                font-size: 26rpx;
                                border-radius: 10rpx;

                                &:active {
                                        opacity: 0.8;
                                }
                        }
                }
        }

        /* 收益明细列表 */
        .earnings-list {
                margin: 20rpx 24rpx 0;
                background-color: #fff;
                border-radius: 20rpx;
                overflow: hidden;
                box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

                .list-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 28rpx 30rpx 20rpx;
                        border-bottom: 1rpx solid #F5F5F5;

                        .header-title {
                                font-size: 32rpx;
                                color: #111;
                                font-weight: 600;
                        }

                        .header-count {
                                font-size: 24rpx;
                                color: #C0C4CC;
                        }
                }

                .earning-item {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 24rpx 30rpx;
                        border-bottom: 1rpx solid #F7F8FA;

                        &:last-child {
                                border-bottom: none;
                        }

                        &:active {
                                background-color: #FAFAFA;
                        }

                        .item-left {
                                display: flex;
                                align-items: center;
                                flex: 1;
                                min-width: 0;

                                .item-detail {
                                        margin-left: 20rpx;
                                        flex: 1;
                                        min-width: 0;

                                        .item-title {
                                                font-size: 28rpx;
                                                color: #1D2129;
                                                font-weight: 500;
                                                overflow: hidden;
                                                text-overflow: ellipsis;
                                                white-space: nowrap;
                                        }

                                        .item-meta {
                                                display: flex;
                                                align-items: center;
                                                margin-top: 6rpx;

                                                .item-time {
                                                        font-size: 22rpx;
                                                        color: #C0C4CC;
                                                }

                                                .item-status {
                                                        font-size: 20rpx;
                                                        margin-left: 16rpx;
                                                        padding: 2rpx 12rpx;
                                                        border-radius: 6rpx;

                                                        &.status-completed {
                                                                color: #00B42A;
                                                                background: rgba(0, 180, 42, 0.08);
                                                        }

                                                        &.status-pending {
                                                                color: #FF7D00;
                                                                background: rgba(255, 125, 0, 0.08);
                                                        }
                                                }
                                        }

                                        .item-sub {
                                                margin-top: 6rpx;
                                                display: flex;
                                                align-items: center;

                                                .item-nickname {
                                                        font-size: 22rpx;
                                                        color: #86909C;
                                                        overflow: hidden;
                                                        text-overflow: ellipsis;
                                                        white-space: nowrap;
                                                        max-width: 240rpx;
                                                }

                                                .item-level {
                                                        font-size: 22rpx;
                                                        color: #C0C4CC;
                                                        margin-left: 8rpx;
                                                        flex-shrink: 0;
                                                }
                                        }
                                }
                        }

                        .item-right {
                                flex-shrink: 0;
                                margin-left: 16rpx;

                                .item-amount {
                                        font-size: 32rpx;
                                        font-weight: 600;

                                        &.amount-positive {
                                                color: #E62129;
                                        }
                                }
                        }
                }

                .first-loading {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        padding: 100rpx 0;
                        color: #C0C4CC;
                }

                .no-data {
                        padding: 100rpx 0;
                        text-align: center;
                }

                .load-more {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        padding: 36rpx 0;
                        color: #C0C4CC;

                        .load-text {
                                margin-left: 12rpx;
                                font-size: 24rpx;
                        }
                }
        }

        /* 加载动画 */
        .loading-spinner {
                width: 32rpx;
                height: 32rpx;
                border: 4rpx solid #E0E0E0;
                border-top-color: #E62129;
                border-radius: 50%;
                animation: spin 0.7s linear infinite;
        }

        @keyframes spin {
                to {
                        transform: rotate(360deg);
                }
        }

        /* 底部安全区域 */
        .safe-bottom {
                height: calc(env(safe-area-inset-bottom) + 40rpx);
        }
</style>

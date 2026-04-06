<template>
        <view class="fui-wrap recharge-detail-container">
                <fa-navbar title="提现日志" :border-bottom="false"></fa-navbar>

                <!-- 筛选区域 -->
                <view class="search-section">
                        <view class="time-picker">
                                <view style="width:40%">
                                        <picker mode="date" :value="startDate" @change="bindStartDateChange">
                                                <view class="picker">{{ startDate || '开始日期' }}</view>
                                        </picker>
                                </view>
                                <text class="separator">至</text>
                                <view style="width:40%">
                                        <picker mode="date" :value="endDate" @change="bindEndDateChange">
                                                <view class="picker">{{ endDate || '结束日期' }}</view>
                                        </picker>
                                </view>
                        </view>
                        <button class="search-btn" @click="handleSearch">搜索</button>
                </view>

                <!-- 列表区域 -->
                <scroll-view scroll-y class="list-container" @scrolltolower="loadMore" refresher-enabled
                        :refresher-triggered="refreshing" @refresherrefresh="onRefresh">

                        <u-empty text="暂无提现记录" mode="data" v-if="list.length === 0 && !loading"></u-empty>

                        <!-- 提现记录列表 -->
                        <view class="list-item" v-for="(item, index) in list" :key="index">
                                <view class="item-left">
                                        <view class="item-title">{{ item.withdraw_type_text || '提现' }}</view>
                                        <view class="item-sub" v-if="item.order_no">
                                                <text>单号: {{ item.order_no }}</text>
                                        </view>
                                        <view class="item-time">{{ item.create_time_text }}</view>
                                </view>
                                <view class="item-right">
                                        <view class="item-amount">{{ item.cash_amount }}元</view>
                                        <view class="item-status" :class="getStatusClass(item.status)">
                                                {{ item.status_text }}
                                        </view>
                                </view>
                        </view>

                        <!-- 加载更多提示 -->
                        <view class="load-more" v-if="loading">
                                <text class="load-text">加载中...</text>
                        </view>
                        <view class="load-more" v-else-if="!hasMore && list.length > 0">
                                <text class="load-text">— 没有更多了 —</text>
                        </view>
                </scroll-view>
        </view>
</template>

<script>
        import uEmpty from '@/uview-ui/components/u-empty/u-empty.vue'

        export default {
                components: {
                        uEmpty
                },
                data() {
                        return {
                                startDate: '',
                                endDate: '',
                                list: [],
                                page: 1,
                                pageSize: 20,
                                total: 0,
                                loading: false,
                                refreshing: false,
                                hasMore: false,
                                filterStatus: '' // 状态筛选
                        }
                },
                onLoad() {
                        const now = new Date();
                        const startDate = this.formatDate(new Date(now.getFullYear(), now.getMonth(), 1));
                        const endDate = this.formatDate(now);
                        this.startDate = startDate;
                        this.endDate = endDate;
                        this.getList();
                },
                methods: {
                        formatDate(date) {
                                const year = date.getFullYear();
                                const month = (date.getMonth() + 1).toString().padStart(2, '0');
                                const day = date.getDate().toString().padStart(2, '0');
                                return `${year}-${month}-${day}`;
                        },

                        bindStartDateChange(e) {
                                this.startDate = e.detail.value;
                        },

                        bindEndDateChange(e) {
                                this.endDate = e.detail.value;
                        },

                        handleSearch() {
                                if (!this.startDate || !this.endDate) {
                                        uni.showToast({ title: '请选择时间范围', icon: 'none' });
                                        return;
                                }
                                if (new Date(this.startDate) > new Date(this.endDate)) {
                                        uni.showToast({ title: '开始日期不能大于结束日期', icon: 'none' });
                                        return;
                                }
                                this.page = 1;
                                this.list = [];
                                this.getList();
                        },

                        getList() {
                                if (this.loading) return;
                                this.loading = true;

                                this.$api.withdrawList({
                                        page: this.page,
                                        limit: this.pageSize,
                                        status: this.filterStatus || undefined,
                                        start_date: this.startDate || undefined,
                                        end_date: this.endDate || undefined
                                }).then(res => {
                                        if (res && res.code == 1) {
                                                const newList = res.data.list || [];
                                                this.list = this.page === 1
                                                        ? newList
                                                        : this.list.concat(newList);
                                                this.total = res.data.total || 0;
                                                this.hasMore = this.list.length < this.total;
                                        }
                                }).catch(err => {
                                        console.error('[WithdrawLog] withdrawList异常:', err);
                                }).finally(() => {
                                        this.loading = false;
                                        this.refreshing = false;
                                });
                        },

                        getStatusClass(status) {
                                const map = {
                                        0: 'processing',  // 待审核
                                        1: 'processing',  // 审核通过
                                        2: 'processing',  // 打款中
                                        3: 'success',     // 提现成功
                                        4: 'failed',      // 审核拒绝
                                        5: 'failed',      // 打款失败
                                        6: 'failed',      // 已取消
                                };
                                return map[status] || '';
                        },

                        onRefresh() {
                                if (this.loading) return;
                                this.refreshing = true;
                                this.page = 1;
                                this.getList();
                        },

                        loadMore() {
                                if (this.loading || !this.hasMore) return;
                                this.page += 1;
                                this.getList();
                        }
                }
        }
</script>

<style lang="scss">
        .recharge-detail-container {
                padding: 20rpx;
                min-height: 100vh;
        }

        .search-section {
                display: flex;
                align-items: center;
                background-color: #fff;
                border-radius: 16rpx;
                padding: 20rpx;
                margin-bottom: 20rpx;
                box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);

                .time-picker {
                        flex: 1;
                        display: flex;
                        align-items: center;

                        .picker {
                                padding: 10rpx 20rpx;
                                background-color: #f9f9f9;
                                border-radius: 8rpx;
                                font-size: 28rpx;
                                color: #333;
                        }

                        .separator {
                                margin: 0 20rpx;
                                font-size: 28rpx;
                                color: #999;
                        }
                }

                .search-btn {
                        margin-left: 20rpx;
                        background-color: #d30010;
                        color: #fff;
                        font-size: 26rpx;
                        height: 50rpx;
                        line-height: 50rpx;
                        border-radius: 8rpx;
                        padding: 0 30rpx;
                }
        }

        .list-container {
                height: calc(100vh - 160rpx);

                .list-item {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        background-color: #fff;
                        border-radius: 16rpx;
                        padding: 30rpx;
                        margin-bottom: 20rpx;
                        box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);

                        .item-left {
                                flex: 1;
                                min-width: 0;

                                .item-title {
                                        font-size: 30rpx;
                                        color: #333;
                                        font-weight: 500;
                                        margin-bottom: 8rpx;
                                }

                                .item-sub {
                                        font-size: 22rpx;
                                        color: #999;
                                        margin-bottom: 6rpx;
                                        overflow: hidden;
                                        text-overflow: ellipsis;
                                        white-space: nowrap;
                                }

                                .item-time {
                                        font-size: 24rpx;
                                        color: #c0c4cc;
                                }
                        }

                        .item-right {
                                text-align: right;
                                flex-shrink: 0;
                                margin-left: 24rpx;

                                .item-amount {
                                        font-size: 34rpx;
                                        font-weight: 600;
                                        color: #ff6a00;
                                        margin-bottom: 8rpx;
                                }

                                .item-status {
                                        font-size: 24rpx;

                                        &.success {
                                                color: #67c23a;
                                        }

                                        &.failed {
                                                color: #f56c6c;
                                        }

                                        &.processing {
                                                color: #e6a23c;
                                        }
                                }
                        }
                }

                .load-more {
                        padding: 30rpx 0;
                        text-align: center;
                        font-size: 26rpx;
                        color: #c0c4cc;
                }
        }
</style>

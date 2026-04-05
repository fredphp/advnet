<template>
        <view class="ad-feed-message">
                <!-- 广告卡片（全宽平铺） -->
                <view class="ad-card" @click="handleWatchAd">
                        <!-- 顶部标识栏 -->
                        <view class="ad-card-header">
                                <view class="ad-badge">
                                        <text class="badge-text">信息流</text>
                                </view>
                                <text class="ad-card-title">浏览信息流赚金币</text>
                                <text class="ad-reward-tag">+{{ displayRewardCoin }} 金币</text>
                        </view>

                        <!-- 广告内容区 -->
                        <view class="ad-container">
                                <view class="ad-preview">
                                        <view class="ad-preview-content">
                                                <text class="ad-preview-icon">📱</text>
                                                <text class="ad-preview-title">精选推荐内容</text>
                                                <text class="ad-preview-desc">点击跳转浏览{{ watchSeconds }}秒获得奖励</text>
                                        </view>
                                        <view class="ad-preview-arrow">
                                                <text class="arrow-text">›</text>
                                        </view>
                                </view>
                        </view>

                        <!-- ★ 浏览进度条 -->
                        <view class="progress-section" v-if="threshold > 0 && !rewarded">
                                <view class="progress-bar-track">
                                        <view class="progress-bar-fill" :style="{ width: progressPercent + '%' }"></view>
                                </view>
                                <text class="progress-text">已浏览 {{ viewCount }}/{{ threshold }} 次，再浏览 {{ remaining }} 次获得奖励</text>
                        </view>

                        <!-- 已领取奖励提示 -->
                        <view class="ad-reward-tip" v-if="rewarded">
                                <text class="reward-tip-text">✅ 已获得 +{{ rewardAmount }} 金币</text>
                        </view>
                </view>
        </view>
</template>

<script>
export default {
        name: 'AdFeedMessage',
        props: {
                message: {
                        type: Object,
                        default: () => ({})
                },
                isMe: {
                        type: Boolean,
                        default: false
                },
                // ★ 从父组件传入的浏览进度
                feedProgress: {
                        type: Object,
                        default: () => null
                }
        },

        data() {
                return {
                        adpid: '',
                        rewardCoin: 50,
                        rewardAmount: 0,
                        rewarded: false,
                        watchSeconds: 30,
                        // ★ 进度数据（优先使用 props 传入的，否则默认值）
                        viewCount: 0,
                        threshold: 0,
                        remaining: 0,
                        progressPercent: 0,
                };
        },

        created() {
                const taskData = this.message.taskData || {};
                const resource = taskData.resource || {};
                this.adpid = resource.adpid || taskData.adpid || '';
                if (taskData.reward_coin) this.rewardCoin = taskData.reward_coin;
                if (taskData.watch_seconds) this.watchSeconds = taskData.watch_seconds;

                // ★ 初始化进度数据
                this.updateProgress();

                // 监听广告观看页返回的结果
                this._watchResultHandler = (data) => {
                        if (data.msgId !== this.message.id) return;
                        if (data.adType !== 'feed') return;
                        if (data.success) {
                                this.rewarded = true;
                                this.rewardAmount = data.amount || this.rewardCoin;
                                this.$emit('ad-rewarded', {
                                        message: this.message,
                                        amount: this.rewardAmount,
                                        adType: 'feed'
                                });
                        } else if (data.progress) {
                                // ★ 未达阈值 → 更新进度显示
                                this.viewCount = data.progress.view_count || 0;
                                this.threshold = data.progress.threshold || this.threshold;
                                this.remaining = this.threshold > 0 ? Math.max(0, this.threshold - this.viewCount) : 0;
                                this.progressPercent = this.threshold > 0 ? Math.min(100, Math.round((this.viewCount / this.threshold) * 100)) : 0;
                        }
                };
                uni.$on('ad-watch-result', this._watchResultHandler);
        },

        watch: {
                // ★ 监听父组件传入的进度变化
                feedProgress: {
                        handler(val) {
                                if (val) this.updateProgress();
                        },
                        deep: true,
                        immediate: true,
                }
        },

        beforeDestroy() {
                if (this._watchResultHandler) {
                        uni.$off('ad-watch-result', this._watchResultHandler);
                }
        },

        computed: {
                displayRewardCoin() {
                        return this.threshold > 0 ? this.rewardCoin : this.rewardCoin;
                }
        },

        methods: {
                /**
                 * 更新进度数据（从 props 或 message 获取）
                 */
                updateProgress() {
                        if (this.feedProgress) {
                                this.viewCount = this.feedProgress.view_count || 0;
                                this.threshold = this.feedProgress.threshold || 0;
                                this.remaining = this.feedProgress.remaining || 0;
                                this.progressPercent = this.feedProgress.progress_percent || 0;
                        }
                },

                /**
                 * 点击 → 跳转到广告观看页面
                 */
                handleWatchAd() {
                        if (this.rewarded) {
                                uni.showToast({ title: '已获得奖励', icon: 'none' });
                                return;
                        }

                        const params = {
                                type: 'feed',
                                adpid: this.adpid,
                                rewardCoin: this.rewardCoin,
                                watchSeconds: this.watchSeconds,
                                msgId: this.message.id,
                        };
                        const query = Object.keys(params).map(k => k + '=' + params[k]).join('&');
                        uni.navigateTo({
                                url: '/pages/ad/watch?' + query,
                                fail: (err) => {
                                        console.error('[AdFeed] 跳转失败:', err);
                                        uni.showToast({ title: '页面跳转失败', icon: 'none' });
                                }
                        });
                }
        }
}
</script>

<style lang="scss" scoped>
.ad-feed-message {
        width: auto;
        margin-left: -20rpx;
        margin-right: -20rpx;
        margin-top: 0;
        margin-bottom: 0;
        padding: 0;
}

.ad-card {
        width: 100%;
        background-color: #fff;
        overflow: hidden;
        box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.04);
}

.ad-card-header {
        display: flex;
        align-items: center;
        padding: 16rpx 24rpx;
        background: linear-gradient(135deg, #fff5eb, #fff9f3);
        border-bottom: 1rpx solid #f5e6d3;
}

.ad-badge {
        background: linear-gradient(135deg, #ff9500, #ff6b00);
        padding: 4rpx 14rpx;
        border-radius: 6rpx;
        margin-right: 12rpx;
        flex-shrink: 0;
}

.badge-text {
        font-size: 20rpx;
        color: #fff;
        font-weight: bold;
}

.ad-card-title {
        font-size: 26rpx;
        color: #333;
        font-weight: 500;
        flex: 1;
}

.ad-reward-tag {
        font-size: 24rpx;
        color: #ff6b00;
        font-weight: bold;
        flex-shrink: 0;
}

/* 广告容器 - 点击跳转预览 */
.ad-container {
        width: 100%;
}

.ad-preview {
        display: flex;
        align-items: center;
        padding: 30rpx 24rpx;
}

.ad-preview-content {
        flex: 1;
        display: flex;
        flex-direction: column;
}

.ad-preview-icon {
        font-size: 56rpx;
        margin-bottom: 12rpx;
}

.ad-preview-title {
        font-size: 28rpx;
        color: #333;
        font-weight: 600;
        margin-bottom: 8rpx;
}

.ad-preview-desc {
        font-size: 22rpx;
        color: #999;
}

.ad-preview-arrow {
        width: 48rpx;
        height: 48rpx;
        border-radius: 50%;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        margin-left: 16rpx;
}

.arrow-text {
        font-size: 28rpx;
        color: #ccc;
        font-weight: bold;
}

/* ★ 浏览进度条 */
.progress-section {
        padding: 16rpx 24rpx;
        background: #fffbf5;
        border-top: 1rpx solid #fff0e0;
}

.progress-bar-track {
        width: 100%;
        height: 12rpx;
        background: #ffe8cc;
        border-radius: 6rpx;
        overflow: hidden;
        margin-bottom: 10rpx;
}

.progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff9500, #ff6b00);
        border-radius: 6rpx;
        transition: width 0.5s ease;
}

.progress-text {
        font-size: 22rpx;
        color: #ff9500;
        text-align: center;
        display: block;
}

/* 奖励提示条 */
.ad-reward-tip {
        padding: 14rpx 24rpx;
        text-align: center;
        background-color: #f0fff4;
        border-top: 1rpx solid #c6f6d5;
}

.reward-tip-text {
        font-size: 24rpx;
        color: #38a169;
}
</style>

<template>
        <view class="ad-feed-message">
                <!-- 广告卡片（全宽平铺） -->
                <view class="ad-card" :class="{ 'video-card': isVideo }">
                        <!-- 顶部标识栏 -->
                        <view class="ad-card-header" :class="{ 'video-header': isVideo }">
                                <view class="ad-badge" :class="{ 'video-badge': isVideo }">
                                        <text class="badge-text">{{ isVideo ? '视频' : '广告' }}</text>
                                </view>
                                <text class="ad-card-title">{{ isVideo ? '观看视频赚金币' : '浏览推荐赚金币' }}</text>
                                <text class="ad-reward-tag" :class="{ 'video-reward-tag': isVideo }">+{{ rewardCoin }} 金币</text>
                        </view>

                        <!-- uni-ad 广告组件 -->
                        <view class="ad-container" v-if="adpid">
                                <!-- #ifdef APP-PLUS || MP-WEIXIN || MP -->
                                <ad :adpid="adpid" unit-id="adunit" @load="onAdLoad" @error="onAdError" @close="onAdClose"
                                        style="width: 100%; min-height: 120px;"></ad>
                                <!-- #endif -->

                                <!-- #ifdef H5 -->
                                <view class="ad-placeholder" :class="{ 'video-placeholder': isVideo }" @click="handleAdClick">
                                        <view class="ad-placeholder-content">
                                                <view class="ad-icon-wrapper">
                                                        <text class="ad-icon">{{ isVideo ? '🎬' : '📰' }}</text>
                                                </view>
                                                <text class="ad-text">{{ isVideo ? '观看视频广告赚更多金币' : '浏览推荐内容赚金币' }}</text>
                                                <view class="ad-reward-badge">
                                                        <text class="ad-reward-text">+{{ rewardCoin }}</text>
                                                        <text class="ad-reward-unit">金币</text>
                                                </view>
                                                <text class="ad-hint-text">{{ isVideo ? '完整观看可获得奖励' : '浏览即可获得奖励' }}</text>
                                        </view>
                                </view>
                                <!-- #endif -->
                        </view>

                        <!-- 无广告位ID时的提示 -->
                        <view class="ad-container ad-no-config" v-else>
                                <view class="ad-placeholder ad-placeholder-disabled">
                                        <view class="ad-placeholder-content">
                                                <text class="ad-icon">📡</text>
                                                <text class="ad-text">广告位未配置</text>
                                                <text class="ad-hint">请联系管理员配置广告位ID</text>
                                        </view>
                                </view>
                        </view>

                        <!-- 广告奖励提示 -->
                        <view class="ad-reward-tip" v-if="rewarded">
                                <text class="reward-tip-text">✅ 已获得 +{{ rewardAmount }} 金币</text>
                        </view>
                        <view class="ad-reward-tip loading-tip" v-else-if="loading">
                                <view class="loading-content">
                                        <text class="loading-spinner" v-if="isVideo">⏳</text>
                                        <text class="reward-tip-text">{{ isVideo ? '视频播放中，请耐心观看...' : '加载中...' }}</text>
                                </view>
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
                }
        },

        data() {
                return {
                        adpid: '',
                        loading: false,
                        rewarded: false,
                        rewardAmount: 0,
                        rewardCoin: 50,
                        hasReported: false,
                        adType: 'feed', // feed=信息流, reward=激励视频
                };
        },

        computed: {
                isVideo() {
                        return this.adType === 'reward';
                }
        },

        created() {
                const taskData = this.message.taskData || {};
                const resource = taskData.resource || {};

                this.adpid = resource.adpid || taskData.adpid || '';

                // ★ 广告类型：从 taskData 读取，默认 feed
                this.adType = taskData.ad_type || 'feed';

                if (taskData.reward_coin) {
                        this.rewardCoin = taskData.reward_coin;
                }
        },

        methods: {
                onAdLoad(e) {
                        console.log('[' + (this.isVideo ? 'AdVideo' : 'AdFeed') + '] 广告加载成功:', e);
                        this.loading = false;
                },

                onAdError(e) {
                        console.warn('[' + (this.isVideo ? 'AdVideo' : 'AdFeed') + '] 广告加载失败:', e);
                        this.loading = false;
                },

                onAdClose(e) {
                        console.log('[' + (this.isVideo ? 'AdVideo' : 'AdFeed') + '] 广告关闭:', e);
                        this.reportAdReward();
                },

                handleAdClick() {
                        if (this.rewarded) {
                                uni.showToast({ title: '已获得奖励', icon: 'none' });
                                return;
                        }

                        this.loading = true;

                        // 信息流：模拟2秒，激励视频：模拟5秒
                        const duration = this.isVideo ? 5000 : 2000;
                        setTimeout(() => {
                                this.loading = false;
                                this.reportAdReward();
                        }, duration);
                },

                async reportAdReward() {
                        if (this.hasReported) return;
                        this.hasReported = true;

                        try {
                                const transactionId = 'ad_' + this.adType + '_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

                                const res = await this.$api.adCallback({
                                        ad_type: this.adType,
                                        adpid: this.adpid,
                                        ad_provider: 'uniad',
                                        ad_source: 'redbag_page',
                                        transaction_id: transactionId,
                                });

                                if (res && res.code === 1 && res.data) {
                                        this.rewarded = true;
                                        this.rewardAmount = res.data.user_amount_coin || 0;

                                        const adLabel = this.isVideo ? '激励视频广告' : '信息流广告';
                                        console.log('========== 💰 ' + adLabel + '金币分配明细 ==========');
                                        console.log('📢 广告位ID:', this.adpid);
                                        console.log('💰 广告平台给出总金币:', res.data.total_reward_coin || 0);
                                        console.log('📊 平台抽成比例:', (res.data.platform_rate !== undefined ? (res.data.platform_rate * 100).toFixed(0) + '%' : '未返回'));
                                        console.log('🏦 平台抽成金币:', res.data.platform_amount_coin || 0);
                                        console.log('👤 用户实际获得金币:', res.data.user_amount_coin || 0);
                                        console.log('🆔 收益记录ID:', res.data.log_id);
                                        console.log('================================================');

                                        if (this.rewardAmount > 0) {
                                                uni.showToast({
                                                        title: '获得 +' + this.rewardAmount + ' 金币',
                                                        icon: 'none',
                                                        duration: 2000
                                                });
                                        }

                                        this.$emit('ad-rewarded', {
                                                message: this.message,
                                                amount: this.rewardAmount,
                                                adType: this.adType,
                                                totalRewardCoin: res.data.total_reward_coin || 0,
                                                platformRate: res.data.platform_rate,
                                                platformCoin: res.data.platform_amount_coin || 0,
                                                userCoin: res.data.user_amount_coin || 0,
                                                logId: res.data.log_id,
                                        });
                                } else {
                                        this.hasReported = false;
                                        const msg = (res && res.msg) || '奖励获取失败';
                                        if (msg !== '重复回调') {
                                                uni.showToast({ title: msg, icon: 'none' });
                                        } else {
                                                this.rewarded = true;
                                        }
                                }
                        } catch (e) {
                                this.hasReported = false;
                                console.error('[' + (this.isVideo ? 'AdVideo' : 'AdFeed') + '] 上报广告奖励失败:', e);
                        }
                },

                formatTime(timestamp) {
                        if (!timestamp) return '';
                        const date = new Date(timestamp);
                        const hours = date.getHours().toString().padStart(2, '0');
                        const minutes = date.getMinutes().toString().padStart(2, '0');
                        return `${hours}:${minutes}`;
                }
        }
}
</script>

<style lang="scss" scoped>
/* 全宽平铺 */
.ad-feed-message {
        width: auto;
        margin-left: -20rpx;
        margin-right: -20rpx;
        margin-top: 0;
        margin-bottom: 0;
        padding: 0;
}

/* 广告卡片 */
.ad-card {
        width: 100%;
        background-color: #fff;
        overflow: hidden;
        box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.04);
}

/* ==================== 信息流广告样式（橙色系） ==================== */

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

/* ==================== 激励视频广告样式（蓝紫色系） ==================== */

.video-card {
        box-shadow: 0 2rpx 8rpx rgba(99, 102, 241, 0.12);
}

.video-header {
        background: linear-gradient(135deg, #eef2ff, #f0f0ff);
        border-bottom: 1rpx solid #e0e0ff;
}

.video-badge {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
}

.video-reward-tag {
        color: #6366f1;
}

/* ==================== 广告容器 ==================== */

.ad-container {
        width: 100%;
        min-height: 120px;
}

.ad-no-config {
        background-color: #f5f5f5;
}

.ad-placeholder-disabled {
        background: linear-gradient(135deg, #ccc, #999);
}

.ad-hint {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 4rpx;
}

/* 信息流广告占位 - 橙色 */
.ad-placeholder {
        width: 100%;
        padding: 30rpx 20rpx;
        background: linear-gradient(135deg, #ff9500, #ff6b00);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
}

/* 激励视频广告占位 - 紫色渐变 + 视频图标 */
.video-placeholder {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        min-height: 160px;
}

.ad-placeholder-content {
        display: flex;
        flex-direction: column;
        align-items: center;
}

.ad-icon-wrapper {
        width: 80rpx;
        height: 80rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12rpx;
}

.video-placeholder .ad-icon-wrapper {
        width: 100rpx;
        height: 100rpx;
        background: rgba(255, 255, 255, 0.25);
        margin-bottom: 16rpx;
}

.ad-icon {
        font-size: 40rpx;
}

.video-placeholder .ad-icon {
        font-size: 52rpx;
}

.ad-text {
        font-size: 28rpx;
        color: #fff;
        margin-bottom: 8rpx;
        font-weight: 500;
}

.ad-reward-badge {
        display: flex;
        align-items: baseline;
        background: rgba(255, 255, 255, 0.2);
        padding: 6rpx 24rpx;
        border-radius: 30rpx;
        margin-bottom: 10rpx;
}

.ad-reward-text {
        font-size: 36rpx;
        color: #ffd700;
        font-weight: bold;
}

.ad-reward-unit {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.85);
        margin-left: 4rpx;
}

.ad-hint-text {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.65);
}

/* ==================== 奖励提示条 ==================== */

.ad-reward-tip {
        padding: 14rpx 24rpx;
        text-align: center;
        background-color: #f0fff4;
        border-top: 1rpx solid #c6f6d5;

        &.loading-tip {
                background-color: #fffbeb;
                border-top-color: #fde68a;
        }
}

.loading-content {
        display: flex;
        align-items: center;
        justify-content: center;
}

.loading-spinner {
        margin-right: 8rpx;
        animation: spin 1s linear infinite;
}

@keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
}

.reward-tip-text {
        font-size: 24rpx;
        color: #38a169;
}
</style>

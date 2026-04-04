<template>
        <view class="ad-feed-message">
                <!-- 广告卡片（全宽平铺） -->
                <view class="ad-card">
                        <!-- 顶部标识栏 -->
                        <view class="ad-card-header">
                                <view class="ad-badge">
                                        <text class="badge-text">广告</text>
                                </view>
                                <text class="ad-card-title">观看广告赚金币</text>
                                <text class="ad-reward-tag">+{{ rewardCoin }} 金币</text>
                        </view>

                        <!-- uni-ad 信息流广告组件 -->
                        <view class="ad-container" v-if="adpid">
                                <!-- #ifdef APP-PLUS || MP-WEIXIN || MP -->
                                <ad :adpid="adpid" unit-id="adunit" @load="onAdLoad" @error="onAdError" @close="onAdClose"
                                        style="width: 100%; min-height: 120px;"></ad>
                                <!-- #endif -->

                                <!-- #ifdef H5 -->
                                <!-- H5 环境下使用模拟广告或第三方广告SDK -->
                                <view class="ad-placeholder" @click="handleAdClick">
                                        <view class="ad-placeholder-content">
                                                <text class="ad-icon">🎁</text>
                                                <text class="ad-text">点击观看广告赚金币</text>
                                                <text class="ad-reward">+{{ rewardCoin }} 金币</text>
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
                                                <text class="ad-hint">请联系管理员配置信息流广告位ID</text>
                                        </view>
                                </view>
                        </view>

                        <!-- 广告奖励提示 -->
                        <view class="ad-reward-tip" v-if="rewarded">
                                <text class="reward-tip-text">✅ 已获得 +{{ rewardAmount }} 金币</text>
                        </view>
                        <view class="ad-reward-tip loading-tip" v-else-if="loading">
                                <text class="reward-tip-text">⏳ 广告加载中...</text>
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
                        adpid: '',       // uni-ad 广告位ID
                        loading: false,  // 广告加载中
                        rewarded: false, // 是否已获得奖励
                        rewardAmount: 0, // 获得的奖励金额
                        rewardCoin: 50,  // 预期奖励金币数
                        hasReported: false, // 是否已回调
                };
        },

        created() {
                // 从消息数据中获取广告位ID
                const taskData = this.message.taskData || {};
                const resource = taskData.resource || {};

                // 广告位ID优先级：resource.adpid > taskData.adpid > 配置默认值
                this.adpid = resource.adpid || taskData.adpid || '';

                // 奖励金币数
                if (taskData.reward_coin) {
                        this.rewardCoin = taskData.reward_coin;
                }
        },

        methods: {
                /**
                 * 广告加载成功
                 */
                onAdLoad(e) {
                        console.log('[AdFeed] 广告加载成功:', e);
                        this.loading = false;
                },

                /**
                 * 广告加载失败
                 */
                onAdError(e) {
                        console.warn('[AdFeed] 广告加载失败:', e);
                        this.loading = false;
                },

                /**
                 * 广告关闭回调
                 * uni-ad 激励广告：用户完整观看后关闭才触发奖励
                 * uni-ad 信息流广告：展示即可触发
                 */
                onAdClose(e) {
                        console.log('[AdFeed] 广告关闭:', e);
                        this.reportAdReward();
                },

                /**
                 * H5 环境下点击模拟广告
                 */
                handleAdClick() {
                        if (this.rewarded) {
                                uni.showToast({ title: '已获得奖励', icon: 'none' });
                                return;
                        }

                        this.loading = true;

                        // 模拟观看广告 2 秒
                        setTimeout(() => {
                                this.loading = false;
                                this.reportAdReward();
                        }, 2000);
                },

                /**
                 * 上报广告奖励到后端
                 */
                async reportAdReward() {
                        if (this.hasReported) return;
                        this.hasReported = true;

                        try {
                                // 生成唯一 transaction_id（防止重复回调）
                                const transactionId = 'ad_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

                                const res = await this.$api.adCallback({
                                        ad_type: 'feed',
                                        adpid: this.adpid,
                                        ad_provider: 'uniad',
                                        ad_source: 'redbag_page',
                                        transaction_id: transactionId,
                                });

                                if (res && res.code === 1 && res.data) {
                                        this.rewarded = true;
                                        this.rewardAmount = res.data.user_amount_coin || 0;

                                        // ★ 打印金币分配明细到控制台
                                        console.log('========== 💰 信息流广告金币分配明细 ==========');
                                        console.log('📢 广告位ID:', this.adpid);
                                        console.log('💰 广告平台给出总金币:', res.data.total_reward_coin || 0);
                                        console.log('📊 平台抽成比例:', (res.data.platform_rate !== undefined ? (res.data.platform_rate * 100).toFixed(0) + '%' : '未返回'));
                                        console.log('🏦 平台抽成金币:', res.data.platform_amount_coin || 0);
                                        console.log('👤 用户实际获得金币:', res.data.user_amount_coin || 0);
                                        console.log('🆔 收益记录ID:', res.data.log_id);
                                        console.log('📦 完整回调数据:', JSON.stringify(res.data, null, 2));
                                        console.log('================================================');

                                        if (this.rewardAmount > 0) {
                                                uni.showToast({
                                                        title: '获得 +' + this.rewardAmount + ' 金币',
                                                        icon: 'none',
                                                        duration: 2000
                                                });
                                        }

                                        // 通知父组件更新（传递完整分配数据）
                                        this.$emit('ad-rewarded', {
                                                message: this.message,
                                                amount: this.rewardAmount,
                                                totalRewardCoin: res.data.total_reward_coin || 0,
                                                platformRate: res.data.platform_rate,
                                                platformCoin: res.data.platform_amount_coin || 0,
                                                userCoin: res.data.user_amount_coin || 0,
                                                logId: res.data.log_id,
                                        });
                                } else {
                                        this.hasReported = false; // 失败后允许重试
                                        const msg = (res && res.msg) || '奖励获取失败';
                                        if (msg !== '重复回调') {
                                                uni.showToast({ title: msg, icon: 'none' });
                                        } else {
                                                this.rewarded = true;
                                        }
                                }
                        } catch (e) {
                                this.hasReported = false;
                                console.error('[AdFeed] 上报广告奖励失败:', e);
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
/* 全宽平铺，用负边距抵消父容器的 padding */
.ad-feed-message {
        width: auto;
        margin-left: -20rpx;
        margin-right: -20rpx;
        margin-top: 0;
        margin-bottom: 0;
        padding: 0;
}

/* 广告卡片 - 全宽撑满 */
.ad-card {
        width: 100%;
        background-color: #fff;
        overflow: hidden;
        box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.04);
}

/* 顶部标识栏 */
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

/* 广告容器 - 全宽 */
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

.ad-placeholder {
        width: 100%;
        padding: 30rpx 20rpx;
        background: linear-gradient(135deg, #ff9500, #ff6b00);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
}

.ad-placeholder-content {
        display: flex;
        flex-direction: column;
        align-items: center;
}

.ad-icon {
        font-size: 48rpx;
        margin-bottom: 8rpx;
}

.ad-text {
        font-size: 28rpx;
        color: #fff;
        margin-bottom: 8rpx;
}

.ad-reward {
        font-size: 32rpx;
        color: #ffd700;
        font-weight: bold;
}

/* 奖励提示条 */
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

.reward-tip-text {
        font-size: 24rpx;
        color: #38a169;
}
</style>

<template>
        <view class="rewarded-video-message">
                <!-- 顶部：发送者信息条 -->
                <view class="msg-header">
                        <image class="avatar" :src="message.user ? message.user.avatar : '/static/image/avatar.png'" mode="aspectFill"></image>
                        <text class="nickname">{{ message.user ? message.user.nickname : '限时福利' }}</text>
                        <view class="header-tag">
                                <text class="tag-text">激励视频</text>
                        </view>
                        <view class="header-time">
                                <text class="time-text">{{ formatTime(message.time) }}</text>
                        </view>
                </view>

                <!-- ★ 激励视频卡片 -->
                <view class="video-card" @click="handleWatchVideo">
                        <view class="card-body">
                                <!-- 未观看 -->
                                <view class="video-prompt" v-if="!rewarded">
                                        <view class="prompt-left">
                                                <view class="play-icon-wrap">
                                                        <text class="play-icon">▶</text>
                                                </view>
                                                <view class="prompt-info">
                                                        <text class="prompt-title">完整观看视频赚大额金币</text>
                                                        <text class="prompt-desc">观看{{ watchSeconds }}秒视频即可获得奖励</text>
                                                </view>
                                        </view>
                                        <view class="prompt-right">
                                                <view class="reward-chip">
                                                        <text class="chip-text">+{{ rewardCoin }} 金币</text>
                                                </view>
                                                <view class="watch-arrow">
                                                        <text class="arrow-text">›</text>
                                                </view>
                                        </view>
                                </view>

                                <!-- 已观看 -->
                                <view class="video-state state-done" v-else>
                                        <text class="state-icon">✓</text>
                                        <text class="state-text">已获得 +{{ rewardAmount }} 金币</text>
                                        <text class="state-hint" v-if="cooldownText">{{ cooldownText }}</text>
                                </view>
                        </view>


                </view>
        </view>
</template>

<script>
export default {
        name: 'RewardedVideoMessage',
        props: {
                message: { type: Object, default: () => ({}) },
                isMe: { type: Boolean, default: false },
                // ★ 从父组件传入的浏览进度
                rewardProgress: {
                        type: Object,
                        default: () => null
                }
        },

        data() {
                return {
                        adpid: '',
                        rewardCoin: 200,
                        rewardAmount: 0,
                        rewarded: false,
                        hasReported: false,
                        isCooldown: false,
                        cooldownRemaining: 0,
                        cooldownTimer: null,
                        cooldownSeconds: 120,
                        watchSeconds: 30,
                        // ★ 进度数据
                        viewCount: 0,
                        threshold: 0,
                        remaining: 0,
                        progressPercent: 0,
                };
        },

        computed: {
                cooldownText() {
                        if (this.isCooldown && this.cooldownRemaining > 0) {
                                return this.cooldownRemaining + '秒后可再次观看';
                        }
                        return '';
                }
        },

        created() {
                const taskData = this.message.taskData || {};
                const resource = taskData.resource || {};
                this.adpid = resource.adpid || taskData.adpid || '';
                if (taskData.reward_coin) this.rewardCoin = taskData.reward_coin;
                if (taskData.cooldown) this.cooldownSeconds = taskData.cooldown;
                if (taskData.watch_seconds) this.watchSeconds = taskData.watch_seconds;

                // ★ 初始化进度数据
                this.updateProgress();

                // 监听广告观看页返回的结果
                this._watchResultHandler = (data) => {
                        if (data.msgId !== this.message.id) return;
                        if (data.adType !== 'reward') return;
                        if (data.success) {
                                this.rewarded = true;
                                this.rewardAmount = data.amount || this.rewardCoin;
                                this.$emit('ad-rewarded', {
                                        message: this.message,
                                        amount: this.rewardAmount,
                                        adType: 'reward'
                                });
                                this.startCooldown();
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
                rewardProgress: {
                        handler(val) {
                                if (val) this.updateProgress();
                        },
                        deep: true,
                        immediate: true,
                }
        },

        beforeDestroy() {
                this.clearCooldownTimer();
                if (this._watchResultHandler) {
                        uni.$off('ad-watch-result', this._watchResultHandler);
                }
        },

        methods: {
                /**
                 * 更新进度数据（从 props 获取）
                 */
                updateProgress() {
                        if (this.rewardProgress) {
                                this.viewCount = this.rewardProgress.view_count || 0;
                                this.threshold = this.rewardProgress.threshold || 0;
                                this.remaining = this.rewardProgress.remaining || 0;
                                this.progressPercent = this.rewardProgress.progress_percent || 0;
                        }
                },

                /**
                 * 点击 → 跳转到广告观看页面
                 */
                handleWatchVideo() {
                        if (this.rewarded && this.isCooldown) {
                                uni.showToast({ title: '冷却中，请稍后再试', icon: 'none' });
                                return;
                        }
                        if (this.rewarded) return;

                        const params = {
                                type: 'reward',
                                adpid: this.adpid,
                                rewardCoin: this.rewardCoin,
                                watchSeconds: this.watchSeconds,
                                msgId: this.message.id,
                        };
                        const query = Object.keys(params).map(k => k + '=' + params[k]).join('&');
                        uni.navigateTo({
                                url: '/pages/ad/watch?' + query,
                                fail: (err) => {
                                        console.error('[RewardedVideo] 跳转失败:', err);
                                        uni.showToast({ title: '页面跳转失败', icon: 'none' });
                                }
                        });
                },

                // ==================== 冷却倒计时 ====================

                startCooldown() {
                        this.isCooldown = true;
                        this.cooldownRemaining = this.cooldownSeconds;
                        this.clearCooldownTimer();

                        this.cooldownTimer = setInterval(() => {
                                this.cooldownRemaining--;
                                if (this.cooldownRemaining <= 0) {
                                        this.isCooldown = false;
                                        this.cooldownRemaining = 0;
                                        this.rewarded = false;
                                        this.hasReported = false;
                                        this.clearCooldownTimer();
                                }
                        }, 1000);
                },

                clearCooldownTimer() {
                        if (this.cooldownTimer) {
                                clearInterval(this.cooldownTimer);
                                this.cooldownTimer = null;
                        }
                },

                formatTime(timestamp) {
                        if (!timestamp) return '';
                        const date = new Date(timestamp);
                        return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
                }
        }
}
</script>

<style lang="scss" scoped>
.rewarded-video-message {
        width: 100%;
        padding: 100rpx 0;
        margin: 0;
}

.msg-header {
        display: flex;
        align-items: center;
        padding: 16rpx 24rpx 10rpx;
}

.avatar {
        width: 52rpx;
        height: 52rpx;
        border-radius: 50%;
        margin-right: 12rpx;
        flex-shrink: 0;
}

.nickname {
        font-size: 24rpx;
        color: #999;
        font-weight: 400;
}

.header-tag {
        margin-left: 12rpx;
        background: linear-gradient(135deg, #ff6b35, #ff3838);
        padding: 2rpx 12rpx;
        border-radius: 6rpx;
}

.tag-text {
        font-size: 20rpx;
        color: #fff;
        font-weight: 600;
}

.header-time {
        margin-left: auto;
}

.time-text {
        font-size: 22rpx;
        color: #ccc;
}

.video-card {
        margin: 0 16rpx;
        border-radius: 16rpx;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2rpx 12rpx rgba(255, 56, 56, 0.08);
        cursor: pointer;
}

.card-body {
        padding: 28rpx 24rpx;
}

.video-prompt {
        display: flex;
        align-items: center;
        justify-content: space-between;
}

.prompt-left {
        display: flex;
        align-items: center;
        flex: 1;
}

.play-icon-wrap {
        width: 80rpx;
        height: 80rpx;
        border-radius: 20rpx;
        background: linear-gradient(135deg, #ff6b35, #ff3838);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20rpx;
        flex-shrink: 0;
        box-shadow: 0 4rpx 12rpx rgba(255, 56, 56, 0.25);
}

.play-icon {
        font-size: 32rpx;
        color: #fff;
        margin-left: 4rpx;
}

.prompt-info {
        display: flex;
        flex-direction: column;
}

.prompt-title {
        font-size: 28rpx;
        color: #333;
        font-weight: 600;
        margin-bottom: 6rpx;
}

.prompt-desc {
        font-size: 22rpx;
        color: #999;
}

.prompt-right {
        display: flex;
        align-items: center;
        flex-shrink: 0;
        margin-left: 16rpx;
}

.reward-chip {
        background: linear-gradient(135deg, #ff6b35, #ff3838);
        padding: 14rpx 24rpx;
        border-radius: 32rpx;
        box-shadow: 0 4rpx 12rpx rgba(255, 56, 56, 0.3);
}

.chip-text {
        font-size: 26rpx;
        color: #fff;
        font-weight: 700;
        white-space: nowrap;
}

.watch-arrow {
        margin-left: 12rpx;
        width: 40rpx;
        height: 40rpx;
        border-radius: 50%;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
}

.arrow-text {
        font-size: 28rpx;
        color: #ccc;
        font-weight: bold;
}

.video-state {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24rpx 0;
}

.state-done {
        flex-direction: column;
        padding: 20rpx 0;
}

.state-icon {
        font-size: 44rpx;
        color: #52c41a;
        font-weight: bold;
        margin-bottom: 8rpx;
}

.state-text {
        font-size: 26rpx;
        color: #666;
        font-weight: 500;
}

.state-hint {
        font-size: 22rpx;
        color: #bbb;
        margin-top: 4rpx;
}

/* ★ 浏览进度条 */
.video-progress-section {
        padding: 16rpx 24rpx;
        background: #fff5f5;
        border-top: 1rpx solid #ffe0e0;
}

.video-progress-track {
        width: 100%;
        height: 12rpx;
        background: #ffe0e0;
        border-radius: 6rpx;
        overflow: hidden;
        margin-bottom: 10rpx;
}

.video-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #ff6b35, #ff3838);
        border-radius: 6rpx;
        transition: width 0.5s ease;
}

.video-progress-text {
        font-size: 22rpx;
        color: #ff3838;
        text-align: center;
        display: block;
}

</style>

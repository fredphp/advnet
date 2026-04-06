<template>
        <view class="rewarded-video-message">
                <!-- 顶部：发送者信息条（与普通消息风格一致） -->
                <view class="msg-header">
                        <image class="avatar" :src="message.user ? message.user.avatar : '/static/image/avatar.png'" mode="aspectFit"></image>
                        <text class="nickname">{{ message.user ? message.user.nickname : '限时福利' }}</text>
                </view>

                <!-- ★ 激励视频卡片 -->
                <view class="video-card" @click="handleWatchVideo">
                        <!-- 未观看：视频预览 + 播放按钮 + 奖励信息 -->
                        <view class="card-preview" v-if="!rewarded">
                                <!-- 视频封面区域 -->
                                <view class="video-cover">
                                        <view class="cover-gradient">
                                                <text class="cover-icon">🎬</text>
                                        </view>
                                        <!-- 播放按钮 -->
                                        <view class="play-btn">
                                                <text class="play-btn-icon">▶</text>
                                        </view>
                                        <!-- 时长标签 -->
                                        <view class="duration-tag">
                                                <text class="duration-text">{{ watchSeconds }}s</text>
                                        </view>
                                </view>
                                <!-- 底部信息条 -->
                                <view class="card-info">
                                        <view class="info-left">
                                                <text class="info-title">观看视频赚金币</text>
                                                <text class="info-desc">完整观看即可获得奖励</text>
                                        </view>
                                        <view class="info-right">
                                                <view class="reward-badge">
                                                        <text class="reward-badge-text">+{{ rewardCoin }}</text>
                                                        <text class="reward-badge-unit">金币</text>
                                                </view>
                                        </view>
                                </view>
                        </view>

                        <!-- 已观看：完成状态 -->
                        <view class="card-done" v-else>
                                <view class="done-icon-wrap">
                                        <text class="done-icon">✓</text>
                                </view>
                                <view class="done-info">
                                        <text class="done-title">已获得 +{{ rewardAmount }} 金币</text>
                                        <text class="done-desc" v-if="cooldownText">{{ cooldownText }}</text>
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
                                this.viewCount = data.progress.view_count || 0;
                                this.threshold = data.progress.threshold || this.threshold;
                                this.remaining = this.threshold > 0 ? Math.max(0, this.threshold - this.viewCount) : 0;
                                this.progressPercent = this.threshold > 0 ? Math.min(100, Math.round((this.viewCount / this.threshold) * 100)) : 0;
                        }
                };
                uni.$on('ad-watch-result', this._watchResultHandler);
        },

        watch: {
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
                updateProgress() {
                        if (this.rewardProgress) {
                                this.viewCount = this.rewardProgress.view_count || 0;
                                this.threshold = this.rewardProgress.threshold || 0;
                                this.remaining = this.rewardProgress.remaining || 0;
                                this.progressPercent = this.rewardProgress.progress_percent || 0;
                        }
                },

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
        padding: 0;
        margin-bottom: 24rpx;
}

/* 发送者信息条（与 chatMessage 风格一致） */
.msg-header {
        display: flex;
        align-items: center;
        margin-bottom: 8rpx;
        padding: 0 24rpx;
}

.avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: 50%;
        margin-right: 20rpx;
        flex-shrink: 0;
}

.nickname {
        font-size: 24rpx;
        color: #999;
        font-weight: 400;
}

/* ★ 激励视频卡片 */
.video-card {
        margin: 0 24rpx 0 120rpx; /* 对齐文字区域：头像80+间距20+昵称区~20 */
        border-radius: 16rpx;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
        cursor: pointer;
}

/* === 未观看状态 === */
.card-preview {
        width: 100%;
}

/* 视频封面区域 */
.video-cover {
        width: 100%;
        height: 260rpx;
        position: relative;
        background: linear-gradient(145deg, #1a1a2e, #16213e);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
}

.cover-gradient {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: radial-gradient(circle at center, rgba(255, 107, 53, 0.15) 0%, transparent 70%);
}

.cover-icon {
        font-size: 80rpx;
        opacity: 0.3;
}

/* 播放按钮 */
.play-btn {
        position: absolute;
        width: 96rpx;
        height: 96rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.3);
}

.play-btn-icon {
        font-size: 32rpx;
        color: #ff3838;
        margin-left: 6rpx;
}

/* 时长标签 */
.duration-tag {
        position: absolute;
        right: 16rpx;
        bottom: 16rpx;
        background: rgba(0, 0, 0, 0.6);
        padding: 4rpx 14rpx;
        border-radius: 8rpx;
}

.duration-text {
        font-size: 22rpx;
        color: #fff;
        font-weight: 500;
}

/* 底部信息条 */
.card-info {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20rpx 24rpx;
        border-top: 1rpx solid #f5f5f5;
}

.info-left {
        display: flex;
        flex-direction: column;
        flex: 1;
        min-width: 0;
}

.info-title {
        font-size: 28rpx;
        color: #333;
        font-weight: 600;
        line-height: 1.4;
}

.info-desc {
        font-size: 22rpx;
        color: #999;
        margin-top: 4rpx;
}

.info-right {
        flex-shrink: 0;
        margin-left: 20rpx;
}

.reward-badge {
        display: flex;
        align-items: baseline;
        background: linear-gradient(135deg, #ff6b35, #ff3838);
        padding: 8rpx 20rpx;
        border-radius: 12rpx;
        box-shadow: 0 4rpx 12rpx rgba(255, 56, 56, 0.25);
}

.reward-badge-text {
        font-size: 32rpx;
        color: #fff;
        font-weight: 800;
}

.reward-badge-unit {
        font-size: 20rpx;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 500;
        margin-left: 4rpx;
}

/* === 已观看完成状态 === */
.card-done {
        display: flex;
        align-items: center;
        padding: 32rpx 24rpx;
        background: #f8fdf5;
        border: 2rpx solid #d4edda;
}

.done-icon-wrap {
        width: 64rpx;
        height: 64rpx;
        border-radius: 50%;
        background: linear-gradient(135deg, #52c41a, #389e0d);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20rpx;
        flex-shrink: 0;
}

.done-icon {
        font-size: 32rpx;
        color: #fff;
        font-weight: bold;
}

.done-info {
        display: flex;
        flex-direction: column;
        flex: 1;
}

.done-title {
        font-size: 28rpx;
        color: #52c41a;
        font-weight: 600;
}

.done-desc {
        font-size: 22rpx;
        color: #999;
        margin-top: 4rpx;
}
</style>

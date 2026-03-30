<template>
        <view :class="['message', isMe ? 'me' : 'other']">
                <!-- 用户头像 -->
                <image v-if="!isMe" class="avatar" :src="message.user.avatar" mode="aspectFit"></image>
                <image v-if="isMe" class="avatar" :src="vuex_user.avatar || '/static/image/avatar.png'" mode="aspectFit"></image>

                <view class="content-wrapper">
                        <!-- 用户昵称 -->
                        <text v-if="!isMe" class="nickname">{{ message.user.nickname }}</text>

                        <!-- 红包消息卡片 -->
                        <view :class="['redbag-card', cardStatusClass]" @click="handleClick">
                                <view class="redbag-content">
                                        <view class="redbag-icon-wrapper">
                                                <image class="redbag-icon" :src="message.backgroundImage || '/static/image/redbag-icon.png'" mode="aspectFit"></image>
                                        </view>
                                        <view class="redbag-info">
                                                <text class="redbag-text">{{ message.content || '恭喜发财' }}</text>
                                                <!-- 未领取：显示点击提示 -->
                                                <text v-if="message.status === 'unopened'" class="redbag-hint">点击拆红包</text>
                                                <!-- 已拆开：显示金额 -->
                                                <text v-else-if="message.status === 'opened'" class="redbag-amount-final">
                                                        {{ message.claimedAmount || message.currentAmount || message.amount || 0 }} 金币
                                                </text>
                                                <!-- 已领取 -->
                                                <text v-else-if="message.status === 'claimed'" class="redbag-claimed">已领取</text>
                                                <!-- 已过期 -->
                                                <text v-else-if="message.status === 'expired'" class="redbag-expired">已过期</text>
                                        </view>
                                </view>
                                <view class="redbag-footer">
                                        <text class="footer-text">{{ footerText }}</text>
                                </view>
                        </view>
                </view>
        </view>
</template>

<script>
export default {
        name: 'RedbagMessage',
        props: {
                message: {
                        type: Object,
                        required: true
                },
                isMe: {
                        type: Boolean,
                        default: false
                }
        },
        computed: {
                vuex_user() {
                        return this.$store ? this.$store.state.vuex_user : {};
                },
                cardStatusClass() {
                        const s = this.message.status;
                        if (s === 'claimed' || s === 'expired') return 'claimed';
                        if (s === 'opened') return 'opened';
                        return '';
                },
                footerText() {
                        const taskData = this.message.taskData || {};
                        if (this.message.status === 'expired') return '已领取';
                        if (this.message.status === 'claimed') return '已完成';
                        if (this.message.status === 'opened') return '已拆开 · 待领取';
                        const resourceName = taskData.resource && taskData.resource.name ? taskData.resource.name : '小程序红包';
                        return resourceName;
                }
        },
        methods: {
                handleClick() {
                        // 已过期或已领取，不触发事件
                        if (this.message.status === 'expired') {
                                uni.showToast({ title: '已领取', icon: 'none' });
                                return;
                        }
                        if (this.message.status === 'claimed') {
                                uni.showToast({ title: '已领取', icon: 'none' });
                                return;
                        }
                        this.$emit('open-redbag', this.message);
                }
        }
}
</script>

<style lang="scss" scoped>
.message {
        display: flex;
        margin-bottom: 30rpx;
        padding: 0 20rpx;

        &.me {
                flex-direction: row-reverse;

                .content-wrapper {
                        align-items: flex-end;
                }
        }
}

.avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: 50%;
        margin: 0 20rpx;
        flex-shrink: 0;
}

.content-wrapper {
        display: flex;
        flex-direction: column;
        max-width: 70%;
}

.nickname {
        font-size: 24rpx;
        color: #999;
        margin-bottom: 8rpx;
        margin-left: 10rpx;
}

.redbag-card {
        background: linear-gradient(135deg, #e74c3c 0%, #c0392b 50%, #a93226 100%);
        border-radius: 16rpx;
        overflow: hidden;
        min-width: 400rpx;
        box-shadow: 0 4rpx 16rpx rgba(231, 76, 60, 0.3);
        position: relative;

        &::before {
                content: '';
                position: absolute;
                top: -40rpx;
                right: -40rpx;
                width: 120rpx;
                height: 120rpx;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.08);
        }

        &::after {
                content: '';
                position: absolute;
                bottom: -20rpx;
                left: -20rpx;
                width: 80rpx;
                height: 80rpx;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.05);
        }

        &.opened {
                background: linear-gradient(135deg, #f5b7b1 0%, #f1948a 50%, #ec7063 100%);
                box-shadow: 0 4rpx 12rpx rgba(236, 112, 99, 0.2);
        }

        &.claimed {
                background: linear-gradient(135deg, #f0d9d5 0%, #e8c4be 100%);
                box-shadow: 0 2rpx 8rpx rgba(232, 196, 190, 0.3);

                .redbag-icon-wrapper {
                        opacity: 0.5;
                }
        }
}

.redbag-content {
        display: flex;
        align-items: center;
        padding: 28rpx 24rpx;
}

.redbag-icon-wrapper {
        width: 90rpx;
        height: 90rpx;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 16rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20rpx;
        flex-shrink: 0;
}

.redbag-icon {
        width: 64rpx;
        height: 64rpx;
        border-radius: 8rpx;
}

.redbag-info {
        display: flex;
        flex-direction: column;
        flex: 1;
}

.redbag-text {
        font-size: 30rpx;
        color: #fff;
        font-weight: 500;
        margin-bottom: 8rpx;
}

.redbag-hint {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.7);
        animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
        0%, 100% { opacity: 0.7; }
        50% { opacity: 1; }
}

.redbag-amount-final {
        font-size: 32rpx;
        font-weight: 600;
        color: #ffe082;
}

.redbag-claimed {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.6);
}

.redbag-expired {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.5);
}

.redbag-footer {
        background: rgba(255, 255, 255, 0.1);
        padding: 14rpx 24rpx;
        border-top: 1rpx solid rgba(255, 255, 255, 0.08);
}

.footer-text {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.8);
}
</style>

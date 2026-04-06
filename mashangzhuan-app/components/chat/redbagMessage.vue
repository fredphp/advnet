<template>
        <view :class="['message', isMe ? 'me' : 'other']">
                <!-- 用户头像 -->
                <image v-if="!isMe" class="avatar" :src="(message.user && message.user.avatar) || '/static/image/avatar.png'" mode="aspectFill"></image>
                <image v-if="isMe" class="avatar" :src="vuex_user.avatar || '/static/image/avatar.png'" mode="aspectFill"></image>

                <view class="content-wrapper">
                        <!-- 用户昵称 -->
                        <text v-if="!isMe" class="nickname">{{ (message.user && message.user.nickname) || '系统' }}</text>

                        <!-- 微信红包样式：左侧图标 + 右侧文字，整体圆角矩形 -->
                        <view :class="['redbag-card', statusClass]" @click="handleClick">
                                <!-- 左侧红包图标 -->
                                <view class="redbag-icon-wrap">
                                        <view class="redbag-icon-inner">
                                                <text class="redbag-emoji">🧧</text>
                                        </view>
                                </view>
                                <!-- 右侧文字 -->
                                <view class="redbag-text-wrap">
                                        <text class="redbag-title">{{ mainTitle }}</text>
                                        <text class="redbag-desc">{{ mainDesc }}</text>
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
                statusClass() {
                        const s = this.message.status;
                        if (s === 'expired') return 'expired';
                        if (s === 'claimed') return 'claimed';
                        return '';
                },
                mainTitle() {
                        if (this.message.status === 'expired') return '已领完';
                        if (this.message.status === 'claimed') return '已领取';
                        return '恭喜发财，大吉大利';
                },
                mainDesc() {
                        if (this.message.status === 'expired') return '红包已过期';
                        if (this.message.status === 'claimed') return '领取红包';
                        return '领取红包';
                }
        },
        methods: {
                handleClick() {
                        if (this.message.status === 'expired') {
                                uni.showToast({ title: '红包已过期', icon: 'none' });
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
        flex-direction: row;
        align-items: flex-start;
        margin-bottom: 28rpx;
        padding: 0 20rpx;

        &.me {
                flex-direction: row-reverse;
        }
}

/* 头像（与 chatMessage 保持一致） */
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
        max-width: 65%;
        flex: 1;
}

.nickname {
        font-size: 24rpx;
        color: #999;
        margin-bottom: 8rpx;
        margin-left: 10rpx;
}

/* ==================== 微信红包卡片 ==================== */
.redbag-card {
        display: flex;
        flex-direction: row;
        align-items: center;
        height: auto;
        border-radius: 12rpx;
        overflow: hidden;
        // 微信红包橙色
        background: #FA9D3B;
        min-width: 360rpx;
        max-width: 480rpx;
        box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.1);

        /* 已过期：淡红色 */
        &.expired {
                background: #F0B8A8;
        }

        /* 已领取：颜色变淡 */
        &.claimed {
                background: #E8C4BE;
        }
}

/* 左侧红包图标区域 */
.redbag-icon-wrap {
        flex-shrink: 0;
        width: 110rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.06);
}

.redbag-icon-inner {
        width: 76rpx;
        height: 76rpx;
        display: flex;
        align-items: center;
        justify-content: center;
}

.redbag-emoji {
        font-size: 54rpx;
        line-height: 1;
}

/* 右侧文字区域 */
.redbag-text-wrap {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 28rpx 28rpx;
}

.redbag-title {
        font-size: 28rpx;
        color: #FFFFFF;
        font-weight: 500;
        line-height: 1.5;
}

.redbag-desc {
        font-size: 22rpx;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.5;
        margin-top: 2rpx;
}

/* 过期/领取状态文字颜色调整 */
.expired .redbag-title,
.claimed .redbag-title {
        color: #FFFFFF;
}

.expired .redbag-desc {
        color: rgba(255, 255, 255, 0.5);
}

.claimed .redbag-desc {
        color: rgba(255, 255, 255, 0.55);
}
</style>

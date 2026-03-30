<template>
        <view :class="['message', isMe ? 'me' : 'other']">
                <!-- 用户头像 -->
                <image v-if="!isMe" class="avatar" :src="message.user.avatar" mode="aspectFit"></image>
                <image v-if="isMe" class="avatar" :src="vuex_user.avatar || '/static/image/avatar.png'" mode="aspectFit"></image>

                <view class="content-wrapper">
                        <!-- 用户昵称 -->
                        <text v-if="!isMe" class="nickname">{{ message.user.nickname }}</text>

                        <!-- 普通聊天 → 简单文本气泡，只显示 description -->
                        <view v-if="msgType === 'chat'" class="chat-bubble" @click="handleCardClick">
                                <text class="bubble-text">{{ message.content }}</text>
                        </view>

                        <!-- 下载App卡片 -->
                        <view v-else-if="msgType === 'download'" class="task-card download-card" @click="handleCardClick">
                                <view class="card-header">
                                        <view class="app-icon-wrapper">
                                                <image class="app-icon" :src="message.backgroundImage || '/static/image/icon-download.png'" mode="aspectFill"></image>
                                        </view>
                                        <view class="card-header-info">
                                                <text class="card-title">{{ message.displayTitle || message.content }}</text>
                                                <text class="card-subtitle" v-if="message.displayDesc">{{ message.displayDesc }}</text>
                                        </view>
                                </view>
                                <view class="card-body" v-if="message.resource && message.resource.name">
                                        <text class="app-name">{{ message.resource.name }}</text>
                                </view>
                                <view class="card-footer">
                                        <view class="download-btn">
                                                <text class="btn-text">立即下载</text>
                                        </view>
                                        <text class="footer-label">下载App</text>
                                </view>
                        </view>

                        <!-- 广告时长卡片 -->
                        <view v-else-if="msgType === 'adv'" class="task-card adv-card" @click="handleCardClick">
                                <view class="card-header">
                                        <image class="card-icon adv-icon" src="/static/image/icon-ad.png" mode="aspectFit"></image>
                                        <view class="card-header-info">
                                                <text class="card-title">{{ message.displayTitle || message.content }}</text>
                                                <text class="card-subtitle" v-if="message.displayDesc">{{ message.displayDesc }}</text>
                                        </view>
                                </view>
                                <view class="card-body" v-if="message.resource">
                                        <view class="adv-preview" v-if="message.backgroundImage">
                                                <image class="adv-image" :src="message.backgroundImage" mode="aspectFill"></image>
                                        </view>
                                </view>
                                <view class="card-footer">
                                        <view class="adv-duration" v-if="message.advDuration">
                                                <text class="duration-text">观看{{ message.advDuration }}秒</text>
                                        </view>
                                        <text class="footer-label">广告</text>
                                </view>
                        </view>

                        <!-- 视频任务卡片 -->
                        <view v-else-if="msgType === 'video'" class="task-card video-card" @click="handleCardClick">
                                <view class="card-header">
                                        <image class="card-icon video-icon" src="/static/image/icon-ad.png" mode="aspectFit"></image>
                                        <view class="card-header-info">
                                                <text class="card-title">{{ message.displayTitle || message.content }}</text>
                                                <text class="card-subtitle" v-if="message.displayDesc">{{ message.displayDesc }}</text>
                                        </view>
                                </view>
                                <view class="card-body">
                                        <view class="video-preview" v-if="message.resource && message.resource.video_url">
                                                <view class="play-btn">
                                                        <text class="play-icon">&#9654;</text>
                                                </view>
                                        </view>
                                </view>
                                <view class="card-footer">
                                        <view class="adv-duration" v-if="message.videoDuration">
                                                <text class="duration-text">{{ message.videoDuration }}s</text>
                                        </view>
                                        <text class="footer-label">观看视频</text>
                                </view>
                        </view>

                        <!-- 普通文本兜底 -->
                        <view v-else class="text-bubble" @click="handleCardClick">
                                <text>{{ message.content }}</text>
                        </view>
                </view>
        </view>
</template>

<script>
export default {
        name: 'TaskMessage',
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
                // 从 taskData 中判断消息类型
                msgType() {
                        const taskData = this.message.taskData || {};
                        const type = taskData.taskType || this.message.taskType || '';
                        if (type === 'chat') return 'chat';
                        if (type === 'download' || type === 'download_app') return 'download';
                        if (type === 'adv') return 'adv';
                        if (type === 'video' || type === 'watch_video') return 'video';
                        return 'text';
                },
                // 聊天内容
                chatContent() {
                        const taskData = this.message.taskData || {};
                        return taskData.resource && taskData.resource.chat_requirement 
                                ? taskData.resource.chat_requirement 
                                : (taskData.description || '');
                },
                chatDuration() {
                        const taskData = this.message.taskData || {};
                        return taskData.resource ? taskData.resource.chat_duration : 0;
                },
                advDuration() {
                        const taskData = this.message.taskData || {};
                        return taskData.resource ? taskData.resource.adv_duration : 0;
                },
                videoDuration() {
                        const taskData = this.message.taskData || {};
                        return taskData.resource ? taskData.resource.video_duration : 0;
                }
        },
        methods: {
                handleCardClick() {
                        const taskData = this.message.taskData || {};
                        this.$emit('task-click', {
                                type: this.msgType,
                                message: this.message,
                                taskData: taskData
                        });
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

/* ===== 通用任务卡片 ===== */
.task-card {
        background-color: #fff;
        border-radius: 16rpx;
        overflow: hidden;
        min-width: 420rpx;
        box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.card-header {
        display: flex;
        align-items: center;
        padding: 24rpx 24rpx 0;
}

.card-icon {
        width: 64rpx;
        height: 64rpx;
        border-radius: 12rpx;
        margin-right: 20rpx;
        flex-shrink: 0;
}

.card-header-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
}

.card-title {
        font-size: 30rpx;
        font-weight: 600;
        color: #333;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
}

.card-subtitle {
        font-size: 24rpx;
        color: #999;
        margin-top: 6rpx;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
}

.card-body {
        padding: 16rpx 24rpx;
}

.card-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16rpx 24rpx;
        border-top: 1rpx solid #f5f5f5;
}

.footer-label {
        font-size: 20rpx;
        color: #bbb;
}

/* ===== 普通聊天文本气泡 ===== */
.chat-bubble {
        background-color: #fff;
        color: #333;
        padding: 20rpx 24rpx;
        border-radius: 16rpx;
        word-break: break-word;
        font-size: 30rpx;
        line-height: 1.5;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);

        .bubble-text {
                font-size: 30rpx;
                color: #333;
                line-height: 1.6;
        }
}

.adv-duration {
        background-color: #fff3e0;
        border-radius: 20rpx;
        padding: 6rpx 16rpx;

        .duration-text {
                font-size: 22rpx;
                color: #ff9800;
                font-weight: 500;
        }
}

/* ===== 下载App卡片 ===== */
.download-card {
        .app-icon-wrapper {
                width: 64rpx;
                height: 64rpx;
                border-radius: 14rpx;
                overflow: hidden;
                flex-shrink: 0;
                margin-right: 20rpx;
                background: #f0f0f0;
        }

        .app-icon {
                width: 100%;
                height: 100%;
        }

        .app-name {
                font-size: 26rpx;
                color: #666;
        }

        .download-btn {
                background: linear-gradient(135deg, #4CAF50, #43A047);
                border-radius: 24rpx;
                padding: 10rpx 28rpx;

                .btn-text {
                        font-size: 24rpx;
                        color: #fff;
                        font-weight: 500;
                }
        }
}

/* ===== 广告卡片 ===== */
.adv-card, .video-card {
        .adv-preview {
                border-radius: 12rpx;
                overflow: hidden;
                height: 200rpx;
        }

        .adv-image {
                width: 100%;
                height: 200rpx;
        }

        .video-preview {
                border-radius: 12rpx;
                overflow: hidden;
                height: 200rpx;
                background: #1a1a1a;
                display: flex;
                align-items: center;
                justify-content: center;
        }

        .play-btn {
                width: 80rpx;
                height: 80rpx;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
        }

        .play-icon {
                font-size: 32rpx;
                color: #ff6b6b;
                margin-left: 4rpx;
        }
}

.adv-icon, .video-icon {
        border-radius: 50%;
}

/* ===== 普通文本兜底 ===== */
.text-bubble {
        background-color: #fff;
        color: #333;
        padding: 20rpx 24rpx;
        border-radius: 16rpx;
        word-break: break-word;
        font-size: 30rpx;
        line-height: 1.5;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}
</style>

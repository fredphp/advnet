<template>
        <view class="ad-feed-message">
                <!-- ★ 顶部：发送者信息条（与普通消息风格一致） -->
                <view class="msg-header">
                        <image class="msg-avatar" :src="message.user ? message.user.avatar : '/static/image/avatar.png'" mode="aspectFit"></image>
                        <text class="msg-nickname">{{ message.user ? message.user.nickname : '广告推荐' }}</text>
                </view>

                <!-- ★ 信息流广告卡片 -->
                <view class="ad-card" @click="handleCardClick">
                        <!-- 左侧：广告图片区 -->
                        <view class="ad-img-wrap">
                                <view class="ad-img-placeholder">
                                        <text class="ad-img-icon">📱</text>
                                </view>
                                <!-- 广告标记 -->
                                <view class="ad-badge">
                                        <text class="ad-badge-text">广告</text>
                                </view>
                        </view>
                        <!-- 右侧：广告文字区 -->
                        <view class="ad-info">
                                <text class="ad-title">{{ adTitle }}</text>
                                <text class="ad-desc">浏览即可获得金币奖励</text>
                                <view class="ad-bottom">
                                        <view class="ad-reward-tag">
                                                <text class="reward-tag-text">+{{ rewardCoin }}金币</text>
                                        </view>
                                        <view class="ad-cta">
                                                <text class="ad-cta-text">查看详情 ›</text>
                                        </view>
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
                        rewarded: false,
                        hasReported: false,
                        viewCount: 0,
                        threshold: 0,
                        // ★ 随机广告标题列表
                        adTitles: [
                                '精选好物推荐',
                                '热门活动限时参与',
                                '新品首发特惠',
                                '品质生活精选',
                                '省钱攻略必看',
                                '热门好物低至1折',
                                '今日爆款推荐',
                                '限时福利活动',
                        ],
                        adTitle: '精选好物推荐',
                };
        },

        created() {
                const taskData = this.message.taskData || {};
                const resource = taskData.resource || {};
                this.adpid = resource.adpid || taskData.adpid || '';
                if (taskData.reward_coin) this.rewardCoin = taskData.reward_coin;

                // 随机选择一个广告标题
                this.adTitle = this.adTitles[Math.floor(Math.random() * this.adTitles.length)];

                // 初始化进度数据
                this.updateProgress();

                // 展示即上报
                this.$nextTick(() => {
                        this.silentReportView();
                });
        },

        computed: {
                displayRewardCoin() {
                        return this.rewardCoin;
                }
        },

        methods: {
                handleCardClick() {
                        // 信息流广告点击可跳转观看页（展示即计费，点击非必须）
                        // 目前不做跳转，仅展示
                },

                formatTime(timestamp) {
                        if (!timestamp) return '';
                        const date = new Date(timestamp);
                        return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
                },

                updateProgress() {
                        if (this.feedProgress) {
                                this.viewCount = this.feedProgress.view_count || 0;
                                this.threshold = this.feedProgress.threshold || 0;
                        }
                },

                /**
                 * ★ 静默上报广告浏览（展示即计费）
                 */
                async silentReportView() {
                        if (this.hasReported) return;
                        this.hasReported = true;

                        try {
                                const transactionId = 'af_auto_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                                console.log('[AdFeed] ★ 展示即上报, adpid=' + (this.adpid || '(未配置)') + ', msgId=' + this.message.id);

                                const res = await this.$api.adRecordView({
                                        ad_type: 'feed',
                                        adpid: this.adpid,
                                        ad_provider: 'uniad',
                                        ad_source: 'redbag_page',
                                        transaction_id: transactionId,
                                });

                                console.log('[AdFeed] recordView返回:', JSON.stringify(res));
                                if (res && res.code === 1 && res.data) {
                                        if (res.data.reward_given) {
                                                this.rewarded = true;
                                                this.$emit('ad-rewarded', {
                                                        message: this.message,
                                                        amount: res.data.amount || this.rewardCoin,
                                                        adType: 'feed',
                                                        silent: true
                                                });
                                        } else {
                                                console.log('[AdFeed] 浏览已记录, view_count=' + (res.data.view_count || 0) + '/' + (res.data.threshold || '?'));
                                        }
                                }
                        } catch (e) {
                                console.warn('[AdFeed] 静默上报失败:', e.message || e);
                        }
                }
        }
}
</script>

<style lang="scss" scoped>
.ad-feed-message {
        width: 100%;
        padding: 0 0;
        margin-bottom: 24rpx;
}

/* ★ 发送者信息条（与 chatMessage 风格一致） */
.msg-header {
        display: flex;
        align-items: center;
        margin-bottom: 8rpx;
        padding: 0 24rpx;
}

.msg-avatar {
        width: 80rpx;
        height: 80rpx;
        border-radius: 50%;
        margin-right: 20rpx;
        flex-shrink: 0;
}

.msg-nickname {
        font-size: 24rpx;
        color: #999;
        font-weight: 400;
}

/* ★ 信息流广告卡片 */
.ad-card {
        margin: 0 24rpx 0 120rpx; /* 对齐文字区域：头像80+间距20+昵称区~20 */
        background: #fff;
        border-radius: 16rpx;
        overflow: hidden;
        display: flex;
        flex-direction: row;
        box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.06);
}

/* 左侧图片区 */
.ad-img-wrap {
        width: 200rpx;
        height: 200rpx;
        flex-shrink: 0;
        position: relative;
        background: linear-gradient(145deg, #f0f0f0, #e4e4e4);
}

.ad-img-placeholder {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
}

.ad-img-icon {
        font-size: 64rpx;
        opacity: 0.5;
}

/* 广告标记（左上角） */
.ad-badge {
        position: absolute;
        top: 0;
        left: 0;
        background: rgba(0, 0, 0, 0.45);
        padding: 2rpx 10rpx;
        border-radius: 0 0 10rpx 0;
}

.ad-badge-text {
        font-size: 18rpx;
        color: #fff;
}

/* 右侧文字区 */
.ad-info {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 16rpx 20rpx;
        min-width: 0;
}

.ad-title {
        font-size: 28rpx;
        color: #333;
        font-weight: 600;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
        overflow: hidden;
        text-overflow: ellipsis;
}

.ad-desc {
        font-size: 22rpx;
        color: #999;
        line-height: 1.3;
        margin-top: 4rpx;
}

/* 底部：奖励标签 + CTA */
.ad-bottom {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12rpx;
}

.ad-reward-tag {
        background: linear-gradient(135deg, #ff9500, #ff6b00);
        padding: 4rpx 16rpx;
        border-radius: 20rpx;
}

.reward-tag-text {
        font-size: 20rpx;
        color: #fff;
        font-weight: 700;
}

.ad-cta {
        flex-shrink: 0;
}

.ad-cta-text {
        font-size: 22rpx;
        color: #999;
        font-weight: 400;
        white-space: nowrap;
}
</style>

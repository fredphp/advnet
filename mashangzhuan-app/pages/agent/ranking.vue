<template>
        <view class="ranking-page">
                <!-- 顶部横幅 -->
                <view class="rank-banner">
                        <view class="banner-bg"></view>
                        <view class="banner-content">
                                <text class="banner-title">邀请排行榜</text>
                                <text class="banner-sub">TOP INVITERS</text>
                                <view class="banner-stars">
                                        <text class="star star-1">✦</text>
                                        <text class="star star-2">✦</text>
                                        <text class="star star-3">✦</text>
                                        <text class="star star-4">✧</text>
                                        <text class="star star-5">✧</text>
                                </view>
                        </view>
                </view>

                <!-- 前三名展示 -->
                <view class="top-three" v-if="rankingList.length > 0">
                        <view class="second-place" v-if="secondPlace">
                                <view class="rank-item rank-2">
                                        <view class="crown-wrap crown-silver">🥈</view>
                                        <u-avatar :src="secondPlace.avatar || '/static/image/avatar.png'" size="72"></u-avatar>
                                        <view class="avatar-ring ring-silver"></view>
                                        <text class="rank-name">{{ secondPlace.nickname }}</text>
                                        <view class="rank-amount-wrap">
                                                <text class="rank-amount">{{ secondPlace.total_income }}</text>
                                                <text class="rank-amount-unit">人</text>
                                        </view>
                                        <view class="rank-podium podium-2"></view>
                                </view>
                        </view>

                        <view class="first-place" v-if="firstPlace">
                                <view class="rank-item rank-1">
                                        <view class="crown-wrap crown-gold">👑</view>
                                        <view class="avatar-glow"></view>
                                        <u-avatar :src="firstPlace.avatar || '/static/image/avatar.png'" size="96"></u-avatar>
                                        <view class="avatar-ring ring-gold"></view>
                                        <text class="rank-name rank-name-1">{{ firstPlace.nickname }}</text>
                                        <view class="rank-amount-wrap">
                                                <text class="rank-amount amount-gold">{{ firstPlace.total_income }}</text>
                                                <text class="rank-amount-unit">人</text>
                                        </view>
                                        <view class="rank-podium podium-1"></view>
                                </view>
                        </view>

                        <view class="third-place" v-if="thirdPlace">
                                <view class="rank-item rank-3">
                                        <view class="crown-wrap crown-bronze">🥉</view>
                                        <u-avatar :src="thirdPlace.avatar || '/static/image/avatar.png'" size="72"></u-avatar>
                                        <view class="avatar-ring ring-bronze"></view>
                                        <text class="rank-name">{{ thirdPlace.nickname }}</text>
                                        <view class="rank-amount-wrap">
                                                <text class="rank-amount">{{ thirdPlace.total_income }}</text>
                                                <text class="rank-amount-unit">人</text>
                                        </view>
                                        <view class="rank-podium podium-3"></view>
                                </view>
                        </view>
                </view>

                <!-- 排行榜列表 -->
                <view class="ranking-list" v-if="rankingList.length > 0">
                        <view class="list-header">
                                <text class="header-rank">排名</text>
                                <text class="header-name">分销商</text>
                                <text class="header-amount">邀请人数</text>
                        </view>

                        <view class="rank-item" v-for="(item, index) in rankingList" :key="index"
                                :class="{'current-user': item.isCurrentUser}" v-if="index >= 3">
                                <view class="item-rank-wrap">
                                        <view class="medal-icon" v-if="index < 6">
                                                <text class="medal-num">{{ index + 1 }}</text>
                                        </view>
                                        <text class="item-rank" v-else>{{ index + 1 }}</text>
                                </view>
                                <view class="item-user">
                                        <u-avatar :src="item.avatar || '/static/image/avatar.png'" size="60"></u-avatar>
                                        <text class="item-name">{{ item.nickname }}</text>
                                        <u-tag text="我" size="mini" mode="dark" shape="circle" bg-color="#D4380D" color="#FFFFFF"
                                                :custom-style="{marginLeft: '8rpx'}" v-if="item.isCurrentUser"></u-tag>
                                </view>
                                <view class="item-amount-wrap">
                                        <text class="item-amount">{{ item.total_income }}</text>
                                        <text class="item-amount-unit">人</text>
                                </view>
                        </view>
                </view>

                <!-- 加载中 -->
                <view class="loading-wrap" v-if="loading">
                        <view class="loading-spinner"></view>
                        <text class="loading-text">加载中...</text>
                </view>

                <!-- 空状态 -->
                <view class="empty-wrap" v-if="!loading && rankingList.length === 0">
                        <u-empty mode="list" text="暂无排行数据"></u-empty>
                </view>

                <!-- 我的排名 -->
                <view class="my-rank" v-if="myRank > 0">
                        <view class="my-rank-left">
                                <view class="my-rank-icon">🏆</view>
                                <view class="my-rank-info">
                                        <text class="rank-text">我的排名</text>
                                        <text class="rank-count">邀请 {{ myAmount }} 人</text>
                                </view>
                        </view>
                        <view class="my-rank-value">
                                <text class="my-rank-num">第{{ myRank }}</text>
                                <text class="my-rank-label">名</text>
                        </view>
                </view>
        </view>
</template>

<script>
        import uAvatar from '@/uview-ui/components/u-avatar/u-avatar.vue';
        import uTag from '@/uview-ui/components/u-tag/u-tag.vue';

        export default {
                components: {
                        uAvatar,
                        uTag
                },
                data() {
                        return {
                                firstPlace: null,
                                secondPlace: null,
                                thirdPlace: null,
                                rankingList: [],
                                myRank: 0,
                                myAmount: 0,
                                loading: false,
                        };
                },
                onLoad() {
                        this.loadRankingData();
                },
                onPullDownRefresh() {
                        this.loadRankingData();
                },
                methods: {
                        loadRankingData() {
                                if (this.loading) return;
                                this.loading = true;

                                this.$api.inviteRanking({ type: 'invite', limit: 20 }).then(res => {
                                        if (res && res.code == 1) {
                                                this.firstPlace = res.data.firstPlace || null;
                                                this.secondPlace = res.data.secondPlace || null;
                                                this.thirdPlace = res.data.thirdPlace || null;
                                                this.rankingList = res.data.list || [];
                                                this.myRank = res.data.myRank || 0;
                                                this.myAmount = res.data.myAmount || 0;
                                        }
                                }).catch(err => {
                                        console.error('[Ranking] 接口异常:', err);
                                        uni.showToast({ title: '加载失败', icon: 'none' });
                                }).finally(() => {
                                        this.loading = false;
                                        uni.stopPullDownRefresh();
                                });
                        },
                }
        };
</script>

<style scoped lang="scss">
        .ranking-page {
                background: linear-gradient(180deg, #FFF8F0 0%, #F5F7FA 30%, #F5F7FA 100%);
                min-height: 100vh;
                padding-bottom: calc(env(safe-area-inset-bottom) + 140rpx);
        }

        /* ========== 顶部横幅 ========== */
        .rank-banner {
                position: relative;
                height: 260rpx;
                overflow: hidden;
        }

        .banner-bg {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(135deg, #C41A1A 0%, #E62129 40%, #FF6B35 100%);
        }

        .banner-bg::after {
                content: '';
                position: absolute;
                bottom: -2rpx;
                left: 0;
                right: 0;
                height: 60rpx;
                background: #F5F7FA;
                border-radius: 40rpx 40rpx 0 0;
        }

        .banner-content {
                position: relative;
                z-index: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                padding-top: 30rpx;
        }

        .banner-title {
                font-size: 40rpx;
                font-weight: bold;
                color: #FFFFFF;
                letter-spacing: 4rpx;
                text-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.15);
        }

        .banner-sub {
                font-size: 22rpx;
                color: rgba(255, 255, 255, 0.7);
                letter-spacing: 8rpx;
                margin-top: 6rpx;
                font-weight: 300;
        }

        .banner-stars {
                display: flex;
                gap: 16rpx;
                margin-top: 16rpx;
        }

        .star {
                color: rgba(255, 215, 0, 0.7);
                font-size: 20rpx;
                animation: twinkle 2s ease-in-out infinite;
        }

        .star-1 { animation-delay: 0s; font-size: 28rpx; }
        .star-2 { animation-delay: 0.4s; }
        .star-3 { animation-delay: 0.8s; font-size: 24rpx; }
        .star-4 { animation-delay: 1.2s; font-size: 18rpx; }
        .star-5 { animation-delay: 1.6s; font-size: 16rpx; }

        @keyframes twinkle {
                0%, 100% { opacity: 0.5; transform: scale(1); }
                50% { opacity: 1; transform: scale(1.2); }
        }

        /* ========== 前三名展示 ========== */
        .top-three {
                display: flex;
                justify-content: center;
                align-items: flex-end;
                padding: 40rpx 24rpx 30rpx;
                gap: 20rpx;
                margin: -30rpx 24rpx 0;
                position: relative;
                z-index: 2;

                .first-place,
                .second-place,
                .third-place {
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        position: relative;
                }
        }

        .rank-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                position: relative;
                padding: 0 8rpx;
        }

        /* 冠军区域 */
        .first-place {
                order: 2;

                .rank-item {
                        padding-top: 60rpx;
                }
        }

        /* 亚军区域 */
        .second-place {
                order: 1;

                .rank-item {
                        padding-top: 20rpx;
                }
        }

        /* 季军区域 */
        .third-place {
                order: 3;

                .rank-item {
                        padding-top: 20rpx;
                }
        }

        /* 奖牌/皇冠装饰 */
        .crown-wrap {
                font-size: 40rpx;
                line-height: 1;
                margin-bottom: 8rpx;
                filter: drop-shadow(0 2rpx 4rpx rgba(0, 0, 0, 0.1));
        }

        .crown-gold {
                font-size: 52rpx;
                margin-bottom: 4rpx;
                animation: crownFloat 3s ease-in-out infinite;
        }

        @keyframes crownFloat {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-6rpx); }
        }

        .crown-silver {
                font-size: 36rpx;
        }

        .crown-bronze {
                font-size: 36rpx;
        }

        /* 头像光晕 */
        .avatar-glow {
                position: absolute;
                top: 104rpx;
                width: 130rpx;
                height: 130rpx;
                border-radius: 50%;
                background: radial-gradient(circle, rgba(255, 215, 0, 0.25) 0%, rgba(255, 215, 0, 0) 70%);
                z-index: 0;
        }

        /* 头像边框 */
        .avatar-ring {
                position: absolute;
                border-radius: 50%;
                border: 3rpx solid transparent;
                z-index: 1;
                pointer-events: none;
        }

        .ring-gold {
                width: 116rpx;
                height: 116rpx;
                top: 110rpx;
                border-color: rgba(255, 215, 0, 0.6);
                box-shadow: 0 0 16rpx rgba(255, 215, 0, 0.3), inset 0 0 8rpx rgba(255, 215, 0, 0.1);
        }

        .ring-silver {
                width: 96rpx;
                height: 96rpx;
                top: 58rpx;
                border-color: rgba(192, 192, 192, 0.5);
                box-shadow: 0 0 12rpx rgba(192, 192, 192, 0.2);
        }

        .ring-bronze {
                width: 96rpx;
                height: 96rpx;
                top: 58rpx;
                border-color: rgba(205, 127, 50, 0.5);
                box-shadow: 0 0 12rpx rgba(205, 127, 50, 0.2);
        }

        /* 名字 */
        .rank-name {
                font-size: 24rpx;
                font-weight: 500;
                margin-top: 10rpx;
                color: #555;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                max-width: 160rpx;
                text-align: center;
        }

        .rank-name-1 {
                font-size: 28rpx;
                font-weight: 600;
                color: #8B6914;
                max-width: 180rpx;
        }

        /* 邀请人数 */
        .rank-amount-wrap {
                display: flex;
                align-items: baseline;
                margin-top: 6rpx;
        }

        .rank-amount {
                font-size: 30rpx;
                color: #D4380D;
                font-weight: 700;
        }

        .amount-gold {
                color: #B8860B;
                font-size: 36rpx;
        }

        .rank-amount-unit {
                font-size: 20rpx;
                color: #999;
                margin-left: 2rpx;
        }

        /* 领奖台 */
        .rank-podium {
                width: 100%;
                border-radius: 16rpx 16rpx 0 0;
                margin-top: 16rpx;
                min-height: 8rpx;
        }

        .podium-1 {
                height: 24rpx;
                background: linear-gradient(180deg, #FBD568 0%, #F5C342 100%);
                box-shadow: 0 -4rpx 16rpx rgba(251, 213, 104, 0.4);
        }

        .podium-2 {
                height: 16rpx;
                background: linear-gradient(180deg, #E0E4E8 0%, #D0D5DA 100%);
                box-shadow: 0 -2rpx 12rpx rgba(208, 213, 218, 0.4);
        }

        .podium-3 {
                height: 16rpx;
                background: linear-gradient(180deg, #EDCB9E 0%, #E0B88A 100%);
                box-shadow: 0 -2rpx 12rpx rgba(224, 184, 138, 0.4);
        }

        /* ========== 排行榜列表 ========== */
        .ranking-list {
                background-color: #FFFFFF;
                border-radius: 24rpx;
                margin: 0 24rpx;
                box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.04);

                .list-header {
                        display: flex;
                        padding: 24rpx 30rpx;
                        border-bottom: 1rpx solid #F2F3F5;
                        font-weight: 600;
                        color: #969BA3;
                        font-size: 22rpx;
                        letter-spacing: 1rpx;

                        .header-rank { width: 15%; }
                        .header-name { width: 55%; }
                        .header-amount { width: 30%; text-align: right; }
                }

                .rank-item {
                        display: flex;
                        align-items: center;
                        padding: 20rpx 30rpx;
                        border-bottom: 1rpx solid #F7F8FA;
                        transition: background-color 0.2s;

                        &:last-child { border-bottom: none; }

                        &.current-user {
                                background: linear-gradient(90deg, rgba(230, 33, 41, 0.04) 0%, rgba(255, 215, 0, 0.06) 100%);
                                border-left: 4rpx solid #E62129;
                                padding-left: 26rpx;
                        }

                        .item-rank-wrap {
                                width: 15%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                        }

                        .medal-icon {
                                width: 42rpx;
                                height: 42rpx;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background: linear-gradient(135deg, #FFF8E8, #FFF0D0);
                                border: 1rpx solid rgba(255, 215, 0, 0.3);

                                .medal-num {
                                        font-size: 22rpx;
                                        font-weight: 700;
                                        color: #B8860B;
                                }
                        }

                        .item-rank {
                                color: #B0B5BD;
                                font-weight: 600;
                                font-size: 26rpx;
                        }

                        .item-user {
                                width: 55%;
                                display: flex;
                                align-items: center;

                                .item-name {
                                        margin-left: 14rpx;
                                        font-size: 26rpx;
                                        color: #333;
                                        overflow: hidden;
                                        text-overflow: ellipsis;
                                        white-space: nowrap;
                                        max-width: 200rpx;
                                }
                        }

                        .item-amount-wrap {
                                width: 30%;
                                display: flex;
                                align-items: baseline;
                                justify-content: flex-end;

                                .item-amount {
                                        font-weight: 700;
                                        color: #D4380D;
                                        font-size: 28rpx;
                                }

                                .item-amount-unit {
                                        font-size: 20rpx;
                                        color: #999;
                                        margin-left: 2rpx;
                                }
                        }
                }
        }

        /* ========== 加载中 ========== */
        .loading-wrap {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 200rpx 0;
        }

        .loading-spinner {
                width: 40rpx;
                height: 40rpx;
                border: 4rpx solid #E0E0E0;
                border-top-color: #E62129;
                border-radius: 50%;
                animation: spin 0.7s linear infinite;
        }

        .loading-text {
                margin-top: 16rpx;
                font-size: 26rpx;
                color: #C0C4CC;
        }

        @keyframes spin {
                to { transform: rotate(360deg); }
        }

        /* ========== 空状态 ========== */
        .empty-wrap {
                padding: 200rpx 0;
        }

        /* ========== 我的排名 ========== */
        .my-rank {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #FFFFFF 0%, #FFFAF5 100%);
                padding: 24rpx 36rpx;
                padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
                display: flex;
                align-items: center;
                justify-content: space-between;
                box-shadow: 0 -4rpx 24rpx rgba(0, 0, 0, 0.06);
                border-top: 2rpx solid rgba(255, 215, 0, 0.2);

                .my-rank-left {
                        display: flex;
                        align-items: center;
                }

                .my-rank-icon {
                        font-size: 40rpx;
                        margin-right: 16rpx;
                }

                .my-rank-info {
                        display: flex;
                        flex-direction: column;
                }

                .rank-text {
                        font-size: 24rpx;
                        color: #999;
                }

                .rank-count {
                        font-size: 22rpx;
                        color: #666;
                        margin-top: 4rpx;
                }

                .my-rank-value {
                        display: flex;
                        align-items: baseline;
                        background: linear-gradient(135deg, #E62129, #FF6B35);
                        padding: 8rpx 28rpx;
                        border-radius: 40rpx;

                        .my-rank-num {
                                color: #FFFFFF;
                                font-weight: 800;
                                font-size: 32rpx;
                        }

                        .my-rank-label {
                                color: rgba(255, 255, 255, 0.85);
                                font-size: 22rpx;
                                margin-left: 4rpx;
                        }
                }
        }
</style>

<template>
        <view class="distribution-center">
                <view class="ag-header">
                        <u-navbar title="分销" title-color="#fff" :is-back="false" back-icon-color="#FFF" :border-bottom="false"
                                :background="scrollTop>60?navBackground1:navBackground2"></u-navbar>

                        <!-- 头部用户信息区域 -->
                        <view class="user-info-section">
                                <view class="user-info">
                                        <u-avatar :src="vuex_user.avatar" size="96">
                                        </u-avatar>
                                        <view class="user-detail">
                                                <view class="user-name-level">
                                                        <text class="user-name">{{ vuex_user.nickname }}</text>
                                                        <u-tag :text="userInfo.level_name" size="mini" mode="dark" shape="circle" bg-color="#D69848"
                                                                color="#FFFFFF" :custom-style="{marginLeft: '16rpx'}"></u-tag>
                                                </view>
                                                <text class="user-id">ID: {{ userInfo.id }}</text>
                                        </view>
                                        <u-button type="primary" size="mini" shape="circle" hover-class="none"
                                                :custom-style="{backgroundColor: 'rgba(255, 255, 255, 0.2)',fontSize:'24rpx', color: '#FFFFFF', border: 'none'}"
                                                @click="goToTeam">我的团队</u-button>
                                </view>

                                <!-- 上级用户信息 -->
                                <view class="parent-info">

                                        <text class="parent-label">上级：</text>
                                        <text class="parent-name">{{ userInfo.parent_name || '无' }}</text>
                                        <text class="parent-id" v-if="userInfo.parent_user_id">ID: {{ parentInfo.id }}</text>
                                </view>

                        </view>
                </view>
                <!-- 顶部导航栏 -->




                <!-- 业绩概览 -->
                <view class="performance-section">
                        <view class="performance-overview">
                                <view class="performance-item">
                                        <text class="performance-value">{{userInfo.month_reward}}</text>
                                        <text class="performance-label">本月收益</text>
                                </view>
                                <view class="performance-item">
                                        <text class="performance-value">{{userInfo.total_income}}</text>
                                        <text class="performance-label">累计收益</text>
                                </view>
                                <view class="performance-item">
                                        <text class="performance-value">{{userInfo.order_nums}}</text>
                                        <text class="performance-label">推广订单</text>
                                </view>
                                <view class="performance-item">
                                        <text class="performance-value">{{userInfo.team_nums}}</text>
                                        <text class="performance-label">团队人数</text>
                                </view>
                        </view>
                </view>


                <!-- 钱包模块 -->
                <view class="wallet-section">
                        <image class="wallet-bg-img" :src="$IMG_URL+'/images/ag-wallet-bg.png'" mode="aspectFill"></image>
                        <view class="wallet-card">
                                <view class="wallet-header">
                                        <text class="wallet-title">我的钱包</text>
                                        <text class="wallet-record" @click="goToWithdrawRecord">提现记录 ></text>
                                </view>

                                <view class="wallet-balance">
                                        <text class="balance-label">可提现：</text>
                                        <text class="balance-amount">¥{{ userInfo.income_money }}</text>
                                        <u-button type="error" hover-class="none" shape="circle" size="mini"
                                                :custom-style="{backgroundColor: '#fff',fontSize:'26rpx', color: '#FF6512', border: 'none'}"
                                                @click="goToWithdraw">去提现</u-button>
                                </view>

                                <view class="wallet-info">
                                        <view class="wallet-item">
                                                <text class="info-label">冻结中</text>
                                                <text class="info-value">¥{{ userInfo.frozen_money }}</text>
                                        </view>
                                        <view class="wallet-item">
                                                <text class="info-label">已提现</text>
                                                <text class="info-value">¥{{ userInfo.total_withdraw }}</text>
                                        </view>
                                        <view class="wallet-item">
                                                <text class="info-label">提现次数</text>
                                                <text class="info-value">{{ userInfo.withdraw_count || 0 }}次</text>
                                        </view>
                                </view>
                        </view>
                </view>

                <!-- 推广区域 -->
                <view class="promotion-section">
                        <view class="section-title">
                                <text class="title-text">推广工具</text>
                                <!-- <text class="more-text" @click="goToAllPromotion">查看全部</text> -->
                        </view>

                        <!-- 推广卡片 -->
                        <view class="promotion-cards">
                                <coco-grid-simple :gridList="gridList" @itemclick="handleGridClick"></coco-grid-simple>
                        </view>
                </view>

                <!-- 最近订单 -->
                <view class="recent-orders">
                        <view class="section-title">
                                <text class="title-text">最近佣金</text>
                                <text class="more-text" @click="goToAllOrders">查看全部</text>
                        </view>

                        <view class="order-list">
                                <view class="order-item" v-for="(order, index) in recentOrders" :key="index" v-if="order.goods">
                                        <view class="order-product">
                                                <image :src="order.goods.image" mode="widthFix" class="product-image"></image>
                                                <view class="product-info">
                                                        <text class="product-name">{{ order.goods.title }}</text>
                                                        <text class="product-spec">{{ order.goods.attrdata }}</text>
                                                </view>

                                                <view class="order-status-commission">
                                                        <view class="u-m-b-20">
                                                                <text class="order-status"
                                                                        :class="{'status-pending': order.status === 'pending', 'status-completed': order.status === 'completed'}">
                                                                        {{ order.status_text }}
                                                                </text>
                                                        </view>
                                                        <view class="">
                                                                <text class="order-commission">+¥{{ order.reward_money }}</text>
                                                        </view>
                                                </view>
                                        </view>

                                </view>

                                <view class="no-orders" v-if="recentOrders.length === 0">
                                        <u-empty mode="order" text="暂无推广订单"></u-empty>
                                </view>
                        </view>
                </view>



                <!-- 分享邀请弹窗 -->
                <view class="share-popup-mask" v-if="showSharePopup" @click="closeSharePopup">
                        <view class="share-popup-mask-inner"></view>
                </view>
                <view class="share-popup" :class="{ 'share-popup-show': showSharePopup }" @click.stop>
                        <!-- 手柄条 -->
                        <view class="share-popup-bar">
                                <view class="bar-inner"></view>
                        </view>
                        <!-- 标题 -->
                        <view class="share-popup-header">
                                <text class="share-popup-title">邀请好友一起赚</text>
                                <view class="share-popup-close" @click="closeSharePopup">
                                        <text class="close-icon">✕</text>
                                </view>
                        </view>

                        <!-- 邀请卡片 -->
                        <view class="invite-card">
                                <view class="invite-card-bg"></view>
                                <view class="invite-card-content">
                                        <!-- 用户信息 -->
                                        <view class="invite-user-row">
                                                <u-avatar :src="vuex_user.avatar" size="80"></u-avatar>
                                                <view class="invite-user-info">
                                                        <text class="invite-user-name">{{ vuex_user.nickname }}</text>
                                                        <text class="invite-user-level">{{ userInfo.level_name }}</text>
                                                </view>
                                        </view>

                                        <!-- 邀请码 -->
                                        <view class="invite-code-section">
                                                <text class="invite-code-title">我的邀请码</text>
                                                <view class="invite-code-box" @click="copyInviteCode" @longpress="copyInviteCode">
                                                        <text class="invite-code-value">{{ userInfo.invite_code || '--' }}</text>
                                                        <view class="invite-code-copy">
                                                                <text class="copy-btn-text">复制</text>
                                                        </view>
                                                </view>
                                        </view>

                                        <!-- 提示文字 -->
                                        <text class="invite-card-desc">扫码或输入邀请码，加入我的团队一起赚</text>
                                </view>
                        </view>

                        <!-- 分享按钮区域 -->
                        <view class="share-popup-actions">
                                <view class="share-action-item" @click="shareToWechat">
                                        <view class="share-icon share-icon-wechat">
                                                <text class="icon-svg">
                                                </text>
                                        </view>
                                        <text class="share-action-label">微信好友</text>
                                </view>
                                <view class="share-action-item" @click="shareToMoments">
                                        <view class="share-icon share-icon-moments">
                                                <text class="icon-svg">
                                                </text>
                                        </view>
                                </view>
                                <view class="share-action-item" @click="copyInviteLink">
                                        <view class="share-icon share-icon-link">
                                                <text class="icon-text-link">链</text>
                                        </view>
                                        <text class="share-action-label">复制链接</text>
                                </view>
                                <view class="share-action-item" @click="shareMore">
                                        <view class="share-icon share-icon-more">
                                                <text class="icon-dots">•••</text>
                                        </view>
                                        <text class="share-action-label">更多</text>
                                </view>
                        </view>

                        <!-- 取消按钮 -->
                        <view class="share-popup-cancel" @click="closeSharePopup">
                                <text class="cancel-text">取消</text>
                        </view>
                </view>

                <!-- 提示组件 -->
                <u-toast ref="uToast" />
                <fa-tabbar></fa-tabbar>
        </view>
</template>

<script>
        import cocoGridSimple from '@/components/coco/coco-grid-simple.vue';
        export default {
                components: {
                        cocoGridSimple
                },
                data() {
                        return {
                                scrollTop: 0,
                                gridList: [{
                                                name: '分享邀请',
                                                image: '/images/agic-inv.png',
                                                path: 'wxshare',
                                        },
                                        {
                                                name: '分销规则',
                                                image: '/images/agic-rule.png',
                                                path: '/pages/page/page?id=3',
                                        },
                                        {
                                                name: '业绩排行',
                                                image: '/images/agic-rank.png',
                                                path: '/pages/agent/ranking',
                                        },
                                        {
                                                name: '签到',
                                                image: '/images/mic-sign.png',
                                                path: '/pages/signin/signin',
                                        }
                                ],
                                navBackground1: {
                                        backgroundColor: '#E62129',
                                },
                                navBackground2: {
                                        backgroundColor: 'transparent',
                                },
                                // 用户信息（由API填充）
                                userInfo: {
                                        avatar: '',
                                        name: '',
                                        id: 0,
                                        level: 1,
                                        level_name: '普通会员',
                                        month_reward: '0.00',
                                        total_income: '0.00',
                                        order_nums: 0,
                                        team_nums: 0,
                                        income_money: '0.00',
                                        frozen_money: '0.00',
                                        nosettle_money: '0.00',
                                        settle_money: '0.00',
                                        total_withdraw: '0.00',
                                        withdraw_count: 0,
                                        parent_name: '无',
                                        parent_user_id: 0,
                                        parent_avatar: '',
                                        invite_code: '',
                                        invite_link: '',
                                },

                                // 上级用户信息
                                parentInfo: {
                                        name: '无',
                                        id: 0
                                },

                                // 业绩数据
                                performanceData: [{
                                                value: '¥3,280',
                                                label: '本月收益'
                                        },
                                        {
                                                value: '¥12,560',
                                                label: '累计收益'
                                        },
                                        {
                                                value: '156',
                                                label: '推广订单'
                                        },
                                        {
                                                value: '156',
                                                label: '团队人数'
                                        }
                                ],

                                // 统计数据
                                totalEarnings: '12,560.00',
                                totalOrders: 86,
                                teamMembers: 156,

                                // 最近订单
                                recentOrders: [],

                                // 钱包数据
                                availableBalance: '5,680.00',
                                pendingBalance: '1,250.00',
                                totalWithdrawn: '7,880.00',

                                // 分享弹窗
                                showSharePopup: false,
                                posterBase64: '',
                                posterLoading: false,
                        };
                },
                onLoad() {
                        this.loadDistributionData();
                        this.getAgentInfo();
                        this.getWithdrawStat();
                },
                methods: {
                        getWithdrawStat() {
                                this.$api.withdrawStat().then(res => {
                                        if (res && res.code == 1) {
                                                const d = res.data;
                                                this.userInfo = {
                                                        ...this.userInfo,
                                                        total_withdraw: parseFloat(d.total_withdraw_amount || 0).toFixed(2),
                                                        withdraw_count: d.success_count || 0,
                                                };
                                        }
                                }).catch(err => {
                                        console.error('[Agent] withdrawStat接口异常:', err);
                                });
                        },
                        getAgentInfo() {
                                this.$api.inviteOverview().then(res => {
                                        if (res && res.code == 1) {
                                                const d = res.data;
                                                const rate = parseFloat(d.exchange_rate) || 10000;
                                                const coinBalance = parseFloat(d.coin_balance) || 0;
                                                const coinFrozen = parseFloat(d.coin_frozen) || 0;
                                                this.userInfo = {
                                                        ...this.userInfo,
                                                        ...d,
                                                        month_reward: d.month_reward || '0.00',
                                                        total_income: d.total_income || '0.00',
                                                        order_nums: d.order_nums || 0,
                                                        team_nums: d.team_nums || 0,
                                                        income_money: (coinBalance / rate).toFixed(2),
                                                        frozen_money: (coinFrozen / rate).toFixed(2),
                                                        level_name: d.level_name || '普通会员',
                                                        parent_name: d.parent_name || '无',
                                                        parent_user_id: d.parent_user_id || 0,
                                                };
                                                this.parentInfo = {
                                                        name: d.parent_name || '无',
                                                        id: d.parent_user_id || 0,
                                                };
                                                if (d.invite_link) {
                                                        this.inviteLinkUrl = d.invite_link;
                                                }
                                        }
                                }).catch(err => {
                                        console.error('[Agent] overview接口异常:', err);
                                });
                        },
                        getAgentOrder() {
                                this.$api.inviteCommissionList({ limit: 5 }).then(res => {
                                        if (res && res.code == 1) {
                                                this.recentOrders = res.data.list || [];
                                        }
                                }).catch(err => {
                                        console.error('[Agent] commissionList接口异常:', err);
                                });
                        },
                        // 加载分销数据
                        loadDistributionData() {
                                // 模拟API请求
                                setTimeout(() => {
                                        console.log('分销数据加载完成');
                                }, 500);
                        },

                        // 页面跳转
                        goToTeam() {
                                uni.navigateTo({
                                        url: '/pages/agent/teams'
                                });
                        },

                        goToEarnings() {
                                uni.navigateTo({
                                        url: '/pages/distribution/earnings'
                                });
                        },

                        goToOrders() {
                                uni.navigateTo({
                                        url: '/pages/distribution/orders'
                                });
                        },

                        goToTeamMembers() {
                                uni.navigateTo({
                                        url: '/pages/distribution/team-members'
                                });
                        },

                        goToAllPromotion() {
                                uni.navigateTo({
                                        url: '/pages/distribution/all-promotion'
                                });
                        },

                        goToAllOrders() {
                                uni.navigateTo({
                                        url: '/pages/agent/earnings'
                                });
                        },

                        goToRules() {
                                uni.navigateTo({
                                        url: '/pages/distribution/rules'
                                });
                        },

                        goToWithdraw() {
                                uni.navigateTo({
                                        url: '/pages/my/withdraw/index'
                                });
                        },

                        goToWithdrawRecord() {
                                uni.navigateTo({
                                        url: '/pages/my/withdraw/log'
                                });
                        },

                        goToRanking() {
                                uni.navigateTo({
                                        url: '/pages/agent/ranking'
                                });
                        },

                        goToHelp() {
                                uni.navigateTo({
                                        url: '/pages/distribution/help'
                                });
                        },

                        // ==================== 分享邀请 ====================

                        // 处理推广工具点击
                        handleGridClick(item) {
                                if (item.path === 'wxshare') {
                                        this.showSharePopup = true;
                                } else {
                                        uni.navigateTo({ url: item.path });
                                }
                        },

                        closeSharePopup() {
                                this.showSharePopup = false;
                        },

                        // 复制邀请码
                        copyInviteCode() {
                                const code = this.userInfo.invite_code;
                                if (!code) {
                                        uni.showToast({ title: '邀请码不存在', icon: 'none' });
                                        return;
                                }
                                uni.setClipboardData({
                                        data: code,
                                        success: () => {
                                                uni.showToast({ title: '邀请码已复制', icon: 'success' });
                                        }
                                });
                        },

                        // 复制邀请链接
                        copyInviteLink() {
                                const link = this.userInfo.invite_link;
                                if (!link) {
                                        uni.showToast({ title: '邀请链接不存在', icon: 'none' });
                                        return;
                                }
                                const shareText = '我正在使用马上赚APP，邀请你一起赚钱！';
                                uni.setClipboardData({
                                        data: shareText + '\n' + link,
                                        success: () => {
                                                uni.showToast({ title: '邀请链接已复制', icon: 'success' });
                                        }
                                });
                        },

                        // APP端分享 - 微信好友
                        shareToWechat() {
                                this.doAppShare('weixin');
                        },

                        // APP端分享 - 朋友圈
                        shareToMoments() {
                                this.doAppShare('weixin_moments');
                        },

                        // 更多分享
                        shareMore() {
                                this.closeSharePopup();
                                const href = this.userInfo.invite_link || '';
                                const shareText = '我正在使用马上赚APP，邀请你一起赚钱！快来看看吧';

                                // #ifdef APP-PLUS
                                plus.share.sendWithSystem({
                                        type: 'text',
                                        content: shareText + '\n' + href,
                                }, () => {
                                        console.log('系统分享成功');
                                }, (err) => {
                                        console.log('系统分享失败:', JSON.stringify(err));
                                        this.copyInviteLink();
                                });
                                // #endif

                                // #ifdef H5
                                this.copyInviteLink();
                                // #endif
                        },

                        // 统一分享方法
                        doAppShare(provider) {
                                const shareText = '我正在使用马上赚APP，邀请你一起赚钱！快来看看吧';
                                const href = this.userInfo.invite_link || '';

                                // #ifdef APP-PLUS
                                plus.share.getServices((services) => {
                                        let targetService = null;
                                        for (let i = 0; i < services.length; i++) {
                                                if (services[i].id === provider) {
                                                        targetService = services[i];
                                                        break;
                                                }
                                        }

                                        if (!targetService) {
                                                uni.showToast({
                                                        title: '未安装' + (provider === 'weixin' ? '微信' : '相关应用'),
                                                        icon: 'none'
                                                });
                                                return;
                                        }

                                        if (targetService.authenticated) {
                                                this._doSendShare(targetService, shareText, href);
                                        } else {
                                                targetService.authorize(() => {
                                                        this._doSendShare(targetService, shareText, href);
                                                }, (err) => {
                                                        uni.showToast({ title: '授权失败，请稍后重试', icon: 'none' });
                                                });
                                        }
                                }, (err) => {
                                        uni.showToast({ title: '获取分享服务失败', icon: 'none' });
                                });
                                // #endif

                                // #ifdef H5
                                this.copyInviteLink();
                                // #endif
                        },

                        // 执行分享
                        _doSendShare(service, shareText, href) {
                                service.send({
                                        type: 0,
                                        title: '马上赚 - 邀请你一起赚钱',
                                        summary: shareText,
                                        href: href,
                                        imageUrl: '',
                                }, () => {
                                        this.closeSharePopup();
                                        uni.showToast({ title: '分享成功', icon: 'success' });
                                }, (err) => {
                                        if (err.code !== -2) {
                                                uni.showToast({ title: '分享失败', icon: 'none' });
                                        }
                                });
                        }
                },
                onPageScroll(e) {
                        this.scrollTop = e.scrollTop;
                },
        };
</script>

<style scoped lang="scss">
        .distribution-center {
                background-color: #F7F8FA;
                min-height: 100vh;
                font-size: 28rpx;
                color: #333;
                padding-bottom: 32rpx;
        }

        .ag-header {
                border-radius: 0 0 40rpx 40rpx;
                background: linear-gradient(to right, #FF8D3B 0%, #E62129 100%);
        }

        // 用户信息区域
        .user-info-section {
                padding: 20rpx 40rpx 80rpx;

                color: #FFFFFF;


                .user-info {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        margin-bottom: 20rpx;

                        .user-detail {
                                margin-left: 20rpx;
                                flex: 1;

                                .user-name-level {
                                        display: flex;
                                        align-items: center;

                                        .user-name {
                                                font-size: 40rpx;
                                                font-weight: bold;
                                        }
                                }

                                .user-id {
                                        font-size: 24rpx;
                                        color: rgba(255, 255, 255, 0.8);
                                        margin-top: 8rpx;
                                        display: inline-block;
                                }
                        }
                }

                // 上级用户信息
                .parent-info {
                        display: flex;
                        align-items: center;
                        padding-bottom: 20rpx;
                        font-size: 26rpx;
                        color: rgba(255, 255, 255, 0.9);

                        .parent-label {
                                margin-left: 10rpx;
                        }

                        .parent-name {
                                font-weight: 500;
                        }

                        .parent-id {
                                margin-left: 10rpx;
                                color: rgba(255, 255, 255, 0.7);
                                font-size: 24rpx;
                        }
                }

        }

        .performance-section {
                background-color: #fff;
                border-radius: 20rpx;
                margin: -80rpx 32rpx 32rpx 32rpx;
        }

        .performance-overview {
                padding: 36rpx 0;
                display: flex;
                justify-content: space-around;
                text-align: center;

                .performance-item {
                        flex: 1;

                        .performance-value {
                                font-size: 32rpx;
                                font-weight: bold;
                                display: block;
                                margin-bottom: 8rpx;
                        }

                        .performance-label {
                                font-size: 24rpx;
                                color: #333;
                                opacity: 0.8;
                        }
                }
        }

        // 统计卡片
        .stats-cards {
                display: flex;
                padding: 0 30rpx;
                margin-top: -30rpx;

                .stat-card {
                        flex: 1;
                        background-color: #FFFFFF;
                        border-radius: 16rpx;
                        padding: 25rpx 20rpx;
                        margin: 0 10rpx;
                        box-shadow: 0 4rpx 10rpx rgba(0, 0, 0, 0.05);
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        transition: transform 0.3s ease;

                        &:active {
                                transform: scale(0.98);
                        }

                        .card-icon {
                                width: 60rpx;
                                height: 60rpx;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                background-color: rgba(222, 0, 17, 0.1);
                        }

                        .card-info {
                                margin-left: 15rpx;
                                flex: 1;

                                .card-value {
                                        font-size: 32rpx;
                                        font-weight: bold;
                                        color: #1D2129;
                                }

                                .card-label {
                                        font-size: 24rpx;
                                        color: #86909C;
                                        margin-top: 5rpx;
                                        display: inline-block;
                                }
                        }
                }

                .stat-card:nth-child(2) .card-icon {
                        background-color: rgba(255, 125, 0, 0.1);
                }

                .stat-card:nth-child(3) .card-icon {
                        background-color: rgba(0, 180, 42, 0.1);
                }
        }

        // 通用区域样式
        .section-title {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 24rpx 32rpx 4rpx 32rpx;

                .title-text {
                        font-size: 32rpx;
                        font-weight: bold;
                        color: #333;
                }

                .more-text {
                        font-size: 26rpx;
                        color: #de0011;
                }
        }

        // 推广区域
        .promotion-section {

                margin: 32rpx;
                border-radius: 16rpx;
                background-color: #FFFFFF;

                .promotion-cards {
                        // display: flex;
                        padding: 20rpx 0;

                        .promotion-card {
                                flex: 1;

                                margin: 0 10rpx;
                                padding: 30rpx 0;
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                justify-content: center;

                                transition: all 0.3s ease;

                                &:active {
                                        transform: scale(0.98);
                                        box-shadow: 0 2rpx 5rpx rgba(0, 0, 0, 0.03);
                                }

                                .promotion-icon {

                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        margin-bottom: 16rpx;

                                }

                                .promotion-text {
                                        font-size: 28rpx;
                                        color: #333;
                                }
                        }

                        .promotion-card:nth-child(2) .promotion-icon {
                                background-color: rgba(255, 125, 0, 0.1);
                        }

                        .promotion-card:nth-child(3) .promotion-icon {
                                background-color: rgba(15, 198, 194, 0.1);
                        }

                        .promotion-card:nth-child(4) .promotion-icon {
                                background-color: rgba(222, 0, 17, 0.1);
                        }
                }
        }

        // 最近订单
        .recent-orders {
                margin: 32rpx;

                background-color: #FFFFFF;
                border-radius: 20rpx;

                .order-list {
                        .order-item {

                                padding: 24rpx 32rpx;

                                display: flex;
                                flex-direction: column;

                                .order-product {
                                        display: flex;
                                        align-items: center;

                                        .product-image {
                                                width: 128rpx;
                                                height: 128rpx;
                                                border-radius: 8rpx;
                                                object-fit: cover;
                                        }

                                        .product-info {
                                                margin-left: 20rpx;
                                                flex: 1;
                                                display: flex;
                                                flex-direction: column;
                                                justify-content: center;

                                                .product-name {
                                                        font-size: 26rpx;
                                                        color: #333;
                                                        display: -webkit-box;
                                                        -webkit-line-clamp: 2;
                                                        -webkit-box-orient: vertical;
                                                        overflow: hidden;
                                                        line-height: 1.4;
                                                }

                                                .product-spec {
                                                        font-size: 24rpx;
                                                        color: #999999;
                                                        margin-top: 8rpx;
                                                }
                                        }
                                }

                                .order-status-commission {
                                        margin-left: 20rpx;
                                        display: flex;
                                        flex-direction: column;
                                        align-items: flex-end;
                                        justify-content: flex-end;

                                        .order-status {
                                                display: inline-flex;
                                                align-items: center;
                                                vertical-align: middle;
                                                justify-content: center;
                                                padding: 0 16rpx;
                                                font-size: 24rpx;
                                                height: 48rpx;
                                                border-radius: 4rpx 4rpx 4rpx 4rpx;
                                        }

                                        .status-pending {
                                                color: #FF7D00;
                                                background: rgba(255, 141, 40, 0.1);
                                        }

                                        .status-completed {
                                                color: #34C759;
                                                background: rgba(52, 199, 89, 0.1);
                                        }

                                        .order-commission {
                                                font-size: 32rpx;
                                                font-weight: bold;
                                                color: #E62129;
                                        }
                                }
                        }

                        .no-orders {
                                background-color: #FFFFFF;
                                border-radius: 16rpx;
                                padding: 60rpx 0;
                                text-align: center;
                        }
                }
        }

        // 钱包模块
        .wallet-section {
                background-color: #FFFFFF;
                margin: 32rpx;
                border-radius: 20rpx;
                padding: 28rpx;
                position: relative;

                .wallet-bg-img {
                        position: absolute;
                        left: 0;
                        top: 0;
                        width: 100%;
                        height: 100%;
                        z-index: 1;
                        border-radius: 20rpx;
                }

                .wallet-card {
                        position: relative;
                        z-index: 5;
                        color: #fff;
                }

                .wallet-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 32rpx;

                        .wallet-title {
                                font-size: 30rpx;
                                font-weight: bold;

                        }

                        .wallet-record {
                                font-size: 26rpx;

                        }
                }

                .wallet-balance {
                        display: flex;
                        align-items: center;
                        margin-bottom: 24rpx;
                        padding-bottom: 24rpx;


                        .balance-label {
                                font-size: 28rpx;
                                color: #fff;
                        }

                        .balance-amount {
                                font-size: 32rpx;
                                font-weight: bold;
                                margin-left: 15rpx;
                                flex: 1;
                        }
                }

                .wallet-info {
                        display: flex;
                        justify-content: space-between;

                        .wallet-item {
                                flex: 1;

                                .info-label {
                                        font-size: 28rpx;
                                        color: #FFD9D8;
                                        display: block;
                                        margin-bottom: 10rpx;
                                }

                                .info-value {
                                        font-size: 28rpx;
                                        font-weight: 500;
                                }
                        }
                }
        }

        // ==================== 分享邀请弹窗 ====================
        .share-popup-mask {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 998;
                background: rgba(0, 0, 0, 0);

                .share-popup-mask-inner {
                        width: 100%;
                        height: 100%;
                        background: rgba(0, 0, 0, 0.55);
                        transition: opacity 0.3s ease;
                }
        }

        .share-popup {
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                background: #F7F8FA;
                border-radius: 36rpx 36rpx 0 0;
                z-index: 999;
                transform: translateY(100%);
                transition: transform 0.35s cubic-bezier(0.32, 0.72, 0, 1);
                padding-bottom: env(safe-area-inset-bottom);
                max-height: 85vh;
                overflow-y: auto;
        }

        .share-popup-show {
                transform: translateY(0);
        }

        // 顶部手柄条
        .share-popup-bar {
                display: flex;
                justify-content: center;
                padding: 20rpx 0 8rpx;

                .bar-inner {
                        width: 80rpx;
                        height: 8rpx;
                        border-radius: 4rpx;
                        background: #DDD;
                }
        }

        .share-popup-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 16rpx 40rpx 24rpx;

                .share-popup-title {
                        font-size: 34rpx;
                        font-weight: bold;
                        color: #1A1A1A;
                }

                .share-popup-close {
                        width: 56rpx;
                        height: 56rpx;
                        border-radius: 50%;
                        background: #EEE;
                        display: flex;
                        align-items: center;
                        justify-content: center;

                        .close-icon {
                                font-size: 28rpx;
                                color: #888;
                                line-height: 1;
                        }
                }
        }

        // 邀请卡片
        .invite-card {
                margin: 0 32rpx 28rpx;
                border-radius: 24rpx;
                position: relative;
                overflow: hidden;

                .invite-card-bg {
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: linear-gradient(145deg, #E62129 0%, #FF6B35 50%, #FF9A56 100%);
                }

                .invite-card-content {
                        position: relative;
                        z-index: 2;
                        padding: 36rpx 32rpx;
                }
        }

        .invite-user-row {
                display: flex;
                align-items: center;
                margin-bottom: 28rpx;

                .invite-user-info {
                        margin-left: 20rpx;
                        flex: 1;

                        .invite-user-name {
                                font-size: 32rpx;
                                font-weight: bold;
                                color: #FFF;
                                display: block;
                                margin-bottom: 6rpx;
                        }

                        .invite-user-level {
                                font-size: 22rpx;
                                color: rgba(255, 255, 255, 0.85);
                                background: rgba(255, 255, 255, 0.2);
                                padding: 4rpx 16rpx;
                                border-radius: 20rpx;
                        }
                }
        }

        .invite-code-section {
                margin-bottom: 24rpx;

                .invite-code-title {
                        font-size: 24rpx;
                        color: rgba(255, 255, 255, 0.8);
                        display: block;
                        margin-bottom: 12rpx;
                }

                .invite-code-box {
                        display: flex;
                        align-items: center;
                        background: rgba(255, 255, 255, 0.2);
                        border-radius: 16rpx;
                        padding: 16rpx 20rpx;
                        backdrop-filter: blur(10px);

                        .invite-code-value {
                                flex: 1;
                                font-size: 36rpx;
                                font-weight: 800;
                                color: #FFF;
                                letter-spacing: 4rpx;
                                font-family: 'Courier New', monospace;
                        }

                        .invite-code-copy {
                                background: #FFF;
                                border-radius: 24rpx;
                                padding: 8rpx 28rpx;

                                .copy-btn-text {
                                        color: #E62129;
                                        font-size: 24rpx;
                                        font-weight: 600;
                                }
                        }
                }
        }

        .invite-card-desc {
                font-size: 24rpx;
                color: rgba(255, 255, 255, 0.7);
                text-align: center;
                display: block;
        }

        // 分享按钮区域
        .share-popup-actions {
                display: flex;
                justify-content: space-around;
                padding: 8rpx 40rpx 20rpx;
        }

        .share-action-item {
                display: flex;
                flex-direction: column;
                align-items: center;

                .share-icon {
                        width: 100rpx;
                        height: 100rpx;
                        border-radius: 24rpx;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-bottom: 14rpx;
                        transition: transform 0.2s ease;

                        &:active {
                                transform: scale(0.92);
                        }

                        .icon-svg {
                                font-size: 1rpx;
                        }

                        .icon-text-link {
                                font-size: 32rpx;
                                font-weight: 700;
                                color: #FFF;
                        }

                        .icon-dots {
                                font-size: 28rpx;
                                font-weight: 700;
                                color: #FFF;
                                letter-spacing: 4rpx;
                        }
                }

                .share-action-label {
                        font-size: 24rpx;
                        color: #555;
                }
        }

        .share-icon-wechat {
                background: linear-gradient(145deg, #07C160, #06AD56);
        }

        .share-icon-moments {
                background: linear-gradient(145deg, #FA9D3B, #F07C23);
        }

        .share-icon-link {
                background: linear-gradient(145deg, #E62129, #C41A21);
        }

        .share-icon-more {
                background: linear-gradient(145deg, #8C8C8C, #6B6B6B);
        }

        // 取消按钮
        .share-popup-cancel {
                margin: 12rpx 32rpx 24rpx;
                padding: 20rpx 0;
                text-align: center;
                background: #FFFFFF;
                border-radius: 16rpx;

                .cancel-text {
                        font-size: 30rpx;
                        color: #666;
                }
        }
</style>
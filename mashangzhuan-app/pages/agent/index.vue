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
                                                <text class="info-label">待结算金额</text>
                                                <text class="info-value">¥{{ userInfo.nosettle_money }}</text>
                                        </view>
                                        <view class="wallet-item">
                                                <text class="info-label">已结算金额</text>
                                                <text class="info-value">¥{{ userInfo.settle_money }}</text>
                                        </view>
                                        <view class="wallet-item">
                                                <text class="info-label">累计提现</text>
                                                <text class="info-value">¥{{ userInfo.total_withdraw }}</text>
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
                                <coco-grid-simple :gridList="gridList"></coco-grid-simple>
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



                <!-- 邀请选项底部弹窗 -->
                <u-action-sheet v-model="showActionSheet" :list="actionSheetList" @click="handleActionClick"
                        title="选择邀请方式"></u-action-sheet>

                <!-- 邀请海报弹窗 -->
                <u-modal v-model="showInviteModal" title="邀请好友" :show-confirm-button="false">
                        <view class="invite-modal-content">
                                <view class="invite-qrcode">
                                        <image :src="inviteQrcode" mode="widthFix" class="qrcode-image"></image>
                                </view>
                                <text class="invite-desc">扫码邀请好友加入分销</text>
                                <u-button text="保存图片" @click="saveQrcode"
                                        :custom-style="{width: '100%', marginTop: '20rpx', backgroundColor: '#de0011', borderColor: '#de0011'}"></u-button>
                        </view>
                </u-modal>

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
                                        nosettle_money: '0.00',
                                        settle_money: '0.00',
                                        total_withdraw: '0.00',
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

                                // 邀请相关
                                inviteQrcode: 'https://picsum.photos/300/300?random=10',
                                showInviteModal: false,
                                showActionSheet: false,
                                actionSheetList: [{
                                                text: '邀请链接',
                                                icon: 'link'
                                        },
                                        {
                                                text: '邀请海报',
                                                icon: 'qrcode'
                                        }
                                ]
                        };
                },
                onLoad() {
                        // 页面加载时可以调用接口获取真实数据
                        this.loadDistributionData();
                        this.getAgentInfo();
                },
                methods: {
                        getAgentInfo() {
                                this.$api.inviteOverview().then(res => {
                                        if (res && res.code == 1) {
                                                const d = res.data;
                                                this.userInfo = {
                                                        ...d,
                                                        month_reward: d.month_reward || '0.00',
                                                        total_income: d.total_income || '0.00',
                                                        order_nums: d.order_nums || 0,
                                                        team_nums: d.team_nums || 0,
                                                        income_money: d.income_money || '0.00',
                                                        nosettle_money: d.nosettle_money || '0.00',
                                                        settle_money: d.settle_money || '0.00',
                                                        total_withdraw: d.total_withdraw || '0.00',
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

                        // 推广相关操作
                        shareGoods() {
                                // 分享商品逻辑
                                this.$refs.uToast.show({
                                        title: '正在打开分享面板',
                                        type: 'info'
                                });
                        },

                        // 显示邀请选项
                        showInviteOptions() {
                                this.showActionSheet = true;
                        },

                        // 处理邀请选项点击
                        handleActionClick(index) {
                                this.showActionSheet = false;
                                if (index === 0) {
                                        // 邀请链接
                                        this.copyInviteLink();
                                } else if (index === 1) {
                                        // 邀请海报
                                        this.showInviteModal = true;
                                }
                        },

                        // 复制邀请链接
                        copyInviteLink() {
                                const link = this.userInfo.invite_link || 'https://example.com/invite';
                                uni.setClipboardData({
                                        data: link,
                                        success: () => {
                                                this.$refs.uToast.show({
                                                        title: '邀请链接已复制',
                                                        type: 'success'
                                                });
                                        }
                                });
                        },

                        // 保存二维码
                        saveQrcode() {
                                uni.saveImageToPhotosAlbum({
                                        filePath: this.inviteQrcode,
                                        success: () => {
                                                this.$refs.uToast.show({
                                                        title: '二维码已保存到相册',
                                                        type: 'success'
                                                });
                                                this.showInviteModal = false;
                                        },
                                        fail: (err) => {
                                                console.log('保存失败', err);
                                                this.$refs.uToast.show({
                                                        title: '保存失败，请重试',
                                                        type: 'error'
                                                });
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

        // 邀请弹窗
        .invite-modal-content {
                display: flex;
                flex-direction: column;
                align-items: center;

                .invite-qrcode {
                        padding: 20rpx;
                        background-color: #FFFFFF;
                        border-radius: 8rpx;
                        margin-top: 10rpx;

                        .qrcode-image {
                                width: 300rpx;
                                height: 300rpx;
                        }
                }

                .invite-desc {
                        font-size: 28rpx;
                        color: #1D2129;
                        margin-top: 25rpx;
                        margin-bottom: 10rpx;
                }
        }
</style>
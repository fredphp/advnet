<template>
        <view class="mine-container">
                <!-- 顶部导航栏 -->
                <!-- 顶部导航 -->
                <!-- <view class="ag-header"> -->
                        
                <!-- 1. 用户基本信息区 -->
                <view class="user-info-container">
                        <u-navbar title="我的" title-color="#fff" :is-back="false" back-icon-color="#FFF" :border-bottom="false"
                                :background="scrollTop>60?navBackground1:navBackground2"></u-navbar>
                        <view class="user-info-wrapper">
                                <!-- 头像 + 昵称 + 等级 -->
                                <view class="user-main-info">
                                        <u-avatar :src="vuex_user.avatar" size="96" shape="circle" class="user-avatar"></u-avatar>
                                        <view class="user-text-info">
                                                <view class="nickname-level">
                                                        <text class="user-nickname">{{ vuex_user.nickname }}</text>
                                                        <u-tag :text="`${vuex_user.level}级会员`" bg-color="#E62129" color="#fff" mode="dark"
                                                                shape="circle" size="mini" class="user-level-tag"></u-tag>
                                                </view>
                                                <view class="user-id" @click="copyText(vuex_user.invite_code)">
                                                        <text>邀请码: {{ vuex_user.invite_code || '--' }}</text>
                                                        <text class="copy-icon">复制</text>
                                                </view>
                                        </view>
                                </view>
                                <!-- 修改基本信息图标 -->
                                <view class="us-after">
                                        <u-icon name="edit-pen" size="48" color="#FFF" class="edit-info-icon"
                                                @click="goPage('/pages/my/profile')"></u-icon>
                                </view>
                        </view>
                        <view style="position: absolute;z-index:1;top:0">
                                <u-image :src="$IMG_URL+'/images/user-bg.png'" style="width: 750rpx;height: 300rpx;"></u-image>
                        </view>
                        
                </view>
                <!-- 3. 核心功能入口区（2列网格） -->

                <!-- 钱包模块 -->
                <view class="wallet-section">
                        <image class="wallet-bg-img" :src="$IMG_URL+'/images/ag-wallet-bg.png'" mode="aspectFill"></image>
                        <view class="wallet-card">
                                <view class="wallet-header">
                                        <text class="wallet-title">我的钱包</text>
                                        <text class="wallet-record" @click="goPage('/pages/my/withdraw/log')">提现记录 ></text>
                                </view>

                                <view class="wallet-balance-row">
                                        <view class="wallet-balance-item">
                                                <text class="balance-label">我的金币</text>
                                                <view class="balance-value-row">
                                                        <text class="balance-amount">{{ coinBalance }}</text>
                                                        <text class="balance-unit">金币</text>
                                                </view>
                                        </view>
                                        <view class="wallet-balance-divider"></view>
                                        <view class="wallet-balance-item">
                                                <text class="balance-label">可提现金额</text>
                                                <view class="balance-value-row">
                                                        <text class="balance-amount cash-amount">{{ cashAmount }}</text>
                                                        <text class="balance-unit">元</text>
                                                </view>
                                        </view>
                                </view>

                                <view class="wallet-exchange-tip">
                                        <text>兑换比例：{{ coinRate }}金币 = 1.00元</text>
                                </view>

                                <view class="wallet-action-row">
                                        <u-button type="error" hover-class="none" shape="circle" size="mini"
                                                :custom-style="{backgroundColor: '#fff',fontSize:'26rpx', color: '#FF6512', border: 'none', padding: '0 40rpx', height: '60rpx'}"
                                                @click="goPage('/pages/my/withdraw/index')">去提现</u-button>
                                </view>

                        </view>
                </view>

                <!-- 4. 系统功能列表区 -->
                <view class="system-functions-container">
                        <view class="group-lead">其他信息</view>
                        <view class="system-function-item" v-for="(item, index) in systemFunctions" :key="index"
                                @click="handleSystemFunction(item.type)">
                                <u-icon :name="$IMG_URL+item.icon" size="40" color="#666666"></u-icon>
                                <text class="system-function-name">{{ item.name }}</text>
                                <u-icon name="arrow-right" size="32" color="#cccccc"></u-icon>
                        </view>
                </view>

                <!-- 5. 退出登录按钮 -->
                <view class="logout-container">
                        <button @click="showLogoutConfirm = true" class="u-reset-button logout-btn">退出登录</button>
                </view>

                <!-- 修改基本信息弹窗 -->
                <u-popup v-model="showEditInfoModal" mode="center" width="600" border-radius="16">
                        <view class="edit-info-modal">
                                <view class="modal-header">
                                        <text class="modal-title">修改基本信息</text>
                                        <u-icon name="close" size="32" color="#666666" @click="showEditInfoModal = false"></u-icon>
                                </view>
                                <view class="modal-body">
                                        <!-- 头像上传 -->
                                        <view class="edit-item">
                                                <text class="edit-label">头像</text>
                                                <view class="avatar-upload">
                                                        <u-avatar :src="tempUserInfo.avatar" size="100" shape="circle"></u-avatar>
                                                        <u-button size="mini" @click="chooseAvatar">更换</u-button>
                                                </view>
                                        </view>
                                        <!-- 昵称修改 -->
                                        <view class="edit-item">
                                                <text class="edit-label">昵称</text>
                                                <u-input v-model="tempUserInfo.nickname" placeholder="请输入昵称" :border="false"
                                                        class="edit-input"></u-input>
                                        </view>
                                </view>
                                <view class="modal-footer">
                                        <u-button shape="circle" @click="showEditInfoModal = false">取消</u-button>
                                        <u-button type="error" shape="circle" @click="saveUserInfo">保存</u-button>
                                </view>
                        </view>
                </u-popup>

                <!-- 退出登录确认弹窗 -->
                <u-modal v-model="showLogoutConfirm" title="确认退出" content="退出后需要重新登录，是否继续？" confirm-text="退出" cancel-text="取消"
                        confirm-color="#de0011" @confirm="handleLogout" @cancel="showLogoutConfirm = false"></u-modal>
                <!-- 底部导航 -->
                <fa-tabbar></fa-tabbar>
                <!-- 加载提示 -->
                <u-loading-page v-if="loading" :loading-text="'加载中...'"></u-loading-page>
        </view>
</template>

<script>
        export default {
                data() {
                        return {
                                scrollTop: 0,
                                // 加载状态
                                loading: true,
                                coinBalance: '0',
                                cashAmount: '0.00',
                                coinRate: 10000,
                                // 用户基本信息
                                userInfo: {
                                        avatar: "https://picsum.photos/200/200?random=100", // 默认头像
                                        nickname: "酒友_12345",
                                        level: 3, // 用户等级
                                        userId: "88661234"
                                },
                                // 临时用户信息（修改时使用）
                                tempUserInfo: {},
                                // 弹窗控制
                                showEditInfoModal: false,
                                showLogoutConfirm: false,
                                navBackground1: {
                                        backgroundColor: '#E62129',
                                },
                                navBackground2: {
                                        backgroundColor: 'transparent',
                                },
                                // 订单状态列表
                                orderStatusList: [{
                                                icon: "/images/mic-dfk.png",
                                                name: "待付款",
                                                type: "1"
                                        },
                                        {
                                                icon: "/images/mic-dfh.png",
                                                name: "待发货",
                                                type: "2"
                                        },
                                        {
                                                icon: "/images/mic-dsh.png",
                                                name: "待收货",
                                                type: "3"
                                        },
                                        {
                                                icon: "/images/mic-dpj.png",
                                                name: "待评价",
                                                type: "4"
                                        },
                                        {
                                                icon: "/images/mic-tksh.png",
                                                name: "已完成",
                                                type: "5"
                                        }
                                ],
                                // 核心功能入口（优惠券、积分等）
                                // 系统功能列表（帮助中心、设置等）
                                systemFunctions: [{
                                                icon: "/images/mic-yaoqing.png",
                                                name: "邀请好友",
                                                type: "agent"
                                        },
                                        {
                                                icon: "/images/mic-help.png",
                                                name: "帮助中心",
                                                type: "help"
                                        },
                                        {
                                                icon: "/images/mic-kefu.png",
                                                name: "联系客服",
                                                type: "customer_service"
                                        },
                                        {
                                                icon: "/images/mic-set.png",
                                                name: "系统设置",
                                                type: "setting"
                                        },
                                        {
                                                icon: "/images/mic-policy.png",
                                                name: "用户协议",
                                                type: "user_agreement"
                                        },
                                        {
                                                icon: "/images/mic-priv.png",
                                                name: "隐私协议",
                                                type: "privacy_policy"
                                        },
                                        {
                                                icon: "/images/mic-policy.png",
                                                name: "分销规则",
                                                type: "distribution_rules"
                                        }
                                ]
                        };
                },

                onLoad() {
                        // 页面加载时初始化数据
                        this.initPageData();
                },
                onShow() {
                        if (this.vuex_token) {
                                this.getMyInfo();
                        } else {
                                this.$u.vuex('vuex_user', {});
                                this.$u.vuex('vuex_agent_user', {});
                                uni.redirectTo({
                                        url: "/pages/login/wxlogin"
                                })
                        }
                },
                methods: {
                        getMyInfo: async function() {
                                let res = await this.$api.getMyIndex();
                                uni.stopPullDownRefresh();
                                if (res.code == 1) {
                                        const info = res.data.userInfo || {};
                                        this.$u.vuex('vuex_user', info);
                                        this.coinBalance = (info.coin_balance || 0).toString();
                                        this.cashAmount = parseFloat(info.cash_amount || 0).toFixed(2);
                                        this.coinRate = info.coin_rate || 10000;
                                } else {
                                        this.$u.toast(res.msg);
                                        return;
                                }
                                this.loading = false;
                        },
                        copyText(text) {
                                if (!text) {
                                        uni.showToast({ title: '内容为空', icon: 'none' });
                                        return;
                                }
                                uni.setClipboardData({
                                        data: text,
                                        success: () => {
                                                uni.showToast({ title: '已复制', icon: 'success' });
                                        }
                                });
                        },
                        // 初始化页面数据
                        initPageData() {
                                // 模拟加载延迟
                                setTimeout(() => {
                                        // 初始化临时用户信息（用于修改）
                                        this.tempUserInfo = {
                                                ...this.userInfo
                                        };
                                        this.loading = false;
                                }, 800);
                        },

                        // 选择头像（模拟）
                        chooseAvatar() {
                                // 实际项目中使用uni.chooseImage选择本地图片
                                uni.showToast({
                                        title: "已选择新头像",
                                        icon: "success"
                                });
                                // 模拟更换头像
                                this.tempUserInfo.avatar = `https://picsum.photos/200/200?random=${Math.floor(Math.random() * 1000)}`;
                        },

                        // 保存用户信息
                        saveUserInfo() {
                                if (!this.tempUserInfo.nickname.trim()) {
                                        uni.showToast({
                                                title: "昵称不能为空",
                                                icon: "none"
                                        });
                                        return;
                                }
                                // 更新用户信息
                                this.userInfo = {
                                        ...this.tempUserInfo
                                };
                                this.showEditInfoModal = false;
                                uni.showToast({
                                        title: "信息修改成功",
                                        icon: "success"
                                });
                        },

                        // 处理核心功能点击（优惠券、积分等）
                        handleCoreFunction(type) {
                                switch (type) {
                                        case "coupon":
                                                uni.navigateTo({
                                                        url: "/pages/coupon/user"
                                                });
                                                break;
                                        case "points":
                                                uni.navigateTo({
                                                        url: "/pages/score/score"
                                                });
                                                break;
                                        case "address":
                                                uni.navigateTo({
                                                        url: "/pages/address/address"
                                                });
                                                break;
                                        case "collection":
                                                uni.navigateTo({
                                                        url: "/pages/my/collect"
                                                });
                                                break;
                                        default:
                                                uni.showToast({
                                                        title: "功能开发中",
                                                        icon: "none"
                                                });
                                }
                        },

                        // 处理系统功能点击（帮助中心、设置等）
                        handleSystemFunction(type) {
                                switch (type) {
                                        case "help":
                                                uni.navigateTo({
                                                        url: "/pages/help/index"
                                                });
                                                break;
                                        case "customer_service":
                                                // 模拟联系客服（实际项目可对接IM或拨打电话）
                                                uni.showModal({
                                                        title: "联系客服",
                                                        content: "客服电话：400-123-4567",
                                                        showCancel: false
                                                });
                                                break;
                                        case "setting":
                                                uni.navigateTo({
                                                        url: "/pages/my/profile"
                                                });
                                                break;
                                        case "agent":
                                                uni.navigateTo({
                                                        url: "/pages/agent/index"
                                                });
                                                break;
                                        case "user_agreement":
                                                uni.navigateTo({
                                                        url: "/pages/page/page?tpl=user-agreement"
                                                });
                                                break;
                                        case "privacy_policy":
                                                uni.navigateTo({
                                                        url: "/pages/page/page?tpl=privacy-policy"
                                                });
                                                break;
                                        case "distribution_rules":
                                                uni.navigateTo({
                                                        url: "/pages/page/page?tpl=distribution-rules"
                                                });
                                                break;
                                        default:
                                                uni.showToast({
                                                        title: "功能开发中",
                                                        icon: "none"
                                                });
                                }
                        },

                        // 跳转订单列表（按状态）
                        goToOrderList(orderType) {
                                uni.navigateTo({
                                        url: `/pages/order/list?status=${orderType}`
                                });
                        },

                        // 跳转全部订单
                        goToAllOrders() {
                                this.goToOrderList("all");
                        },

                        // 退出登录
                        handleLogout() {
                                this.$u.vuex('vuex_token', '');
                                this.$u.vuex('vuex_user', {});
                                this.$u.vuex('vuex_openid', '');
                                // 模拟退出登录逻辑（清除token、跳转登录页）
                                // uni.removeStorageSync("token");
                                // uni.removeStorageSync("userInfo");
                                this.showLogoutConfirm = false;
                                // 跳转登录页（实际项目根据路由配置调整）
                                uni.redirectTo({
                                        url: "/pages/login/wxlogin"
                                });
                        }
                }
        };
</script>

<style scoped lang="scss">
        .mine-container {
                min-height: 100vh;
                background-color: #F7F8FA;
        }

        /* 1. 用户基本信息区 */
        .user-info-container {
                
                position: relative;
                // background: linear-gradient(to right, #FF8D3B 0%, #E62129 100%);
                // padding: 40rpx 64rpx;
                margin-bottom: 50rpx;
                height: 300rpx;

                .user-info-wrapper {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        position: absolute;
                        z-index:2;
                        padding: 40rpx 64rpx;
                        width:750rpx;
                }

                .user-main-info {
                        flex: 1;
                        display: flex;
                        align-items: center;
                }

                .us-after {}

                .user-avatar {}

                .user-text-info {
                        margin-left: 20rpx;
                        flex: 1;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                }

                .nickname-level {
                        display: flex;
                        align-items: center;
                        margin-bottom: 8rpx;
                }

                .user-nickname {
                        font-size: 40rpx;
                        font-weight: 500;
                        color: #FFF;
                        margin-right: 15rpx;
                }

                .user-level-tag {}

                .user-id {
                        font-size: 24rpx;
                        color: #FFF;
                        display: flex;
                        align-items: center;

                        .copy-icon {
                                margin-left: 12rpx;
                                color: rgba(255, 255, 255, 0.5);
                                font-size: 22rpx;
                                border: 1rpx solid rgba(255, 255, 255, 0.4);
                                border-radius: 6rpx;
                                padding: 2rpx 10rpx;
                        }
                }

                .edit-info-icon {
                        cursor: pointer;
                }
        }

        /* 2. 我的订单区 */
        .my-orders-container {
                background-color: #ffffff;
                padding: 24rpx 32rpx;
                border-radius: 16rpx;
                margin: 0 32rpx 24rpx;

                .section-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 30rpx;
                        padding-top: 0rpx;
                        padding-bottom: 28rpx;
                        border-bottom: 1px solid #eee;
                }

                .section-title {
                        font-size: 28rpx;
                        font-weight: 500;
                        color: #333333;
                }

                .view-all {
                        font-size: 24rpx;
                        color: #86909C;
                        line-height: 32rpx;
                        display: flex;
                        align-items: center;
                }

                .order-status-list {
                        display: flex;
                        justify-content: space-between;
                }

                .order-status-item {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        width: 20%;

                        .status-text {
                                font-size: 24rpx;
                                color: #1D2129;
                                margin-top: 16rpx;
                        }
                }
        }

        /* 3. 核心功能入口区（2列网格） */
        .core-functions-container {
                background-color: #ffffff;
                padding: 16rpx 32rpx;
                margin: 0 32rpx 24rpx;
                border-radius: 16rpx;

                .group-lead {
                        margin-bottom: 16rpx;
                        padding-top: 12rpx;
                        padding-bottom: 28rpx;
                        border-bottom: 1px solid #eee;
                }

                .function-items {

                        display: grid;
                        grid-template-columns: repeat(4, 1fr);
                }

                .function-item {
                        padding: 16rpx 0;
                        display: flex;
                        align-items: center;
                        flex-direction: column;
                        justify-content: center;

                        .function-icon-wrapper {
                                position: relative;
                                margin-bottom: 16rpx;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                        }

                        .function-badge {
                                position: absolute;
                                top: -10rpx;
                                right: -10rpx;
                        }

                        .function-name {
                                font-size: 26rpx;
                                color: #333333;
                                line-height: 32rpx;
                        }
                }
        }

        /* 4. 系统功能列表区 */
        .system-functions-container {
                background: linear-gradient(to bottom, rgba(255, 255, 255, 0.3) 0%, #FFFFFF 59%, rgba(255, 255, 255, 0.3) 100%);
                margin: 0 32rpx 0 32rpx;
                border-radius: 12rpx;

                .group-lead {
                        padding: 24rpx 30rpx 0 30rpx;
                        color: #333;
                        font-size: 30rpx;
                }

                .system-function-item {
                        display: flex;
                        align-items: center;
                        padding: 30rpx;
                        border-bottom: 1px solid #f8f8f8;

                        &:last-child {
                                border-bottom: none;
                        }

                        .system-function-name {
                                font-size: 28rpx;
                                color: #111;
                                flex: 1;
                                margin: 0 20rpx;
                        }
                }
        }

        /* 5. 退出登录按钮 */
        .logout-container {
                padding: 40rpx 40rpx;


                .u-button {
                        width: 100%;
                        height: 80rpx;
                        font-size: 28rpx;
                }

                .logout-btn {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        border-radius: 12rpx 12rpx 12rpx 12rpx;
                        height: 80rpx;
                        font-size: 34rpx;
                        color: #fff;
                        background: linear-gradient(90deg, #FF8D3B 0%, #E62129 100%);
                }
        }

        /* 修改基本信息弹窗 */
        .edit-info-modal {
                padding: 30rpx;

                .modal-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        margin-bottom: 30rpx;
                }

                .modal-title {
                        font-size: 30rpx;
                        font-weight: 500;
                        color: #333333;
                }

                .modal-body {
                        margin-bottom: 40rpx;
                }

                .edit-item {
                        display: flex;
                        align-items: center;
                        margin-bottom: 35rpx;

                        &:last-child {
                                margin-bottom: 0;
                        }
                }

                .edit-label {
                        font-size: 26rpx;
                        color: #333333;
                        width: 160rpx;
                }

                .avatar-upload {
                        display: flex;
                        align-items: center;
                }

                .avatar-upload .u-avatar {
                        margin-right: 20rpx;
                }

                .edit-input {
                        font-size: 26rpx;
                        flex: 1;
                }

                .modal-footer {
                        display: flex;
                        align-items: center;
                        gap: 20rpx;
                }

                .modal-footer .u-button {
                        flex: 1;
                        height: 70rpx;
                        font-size: 26rpx;
                        display: flex;

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

                .wallet-balance-row {
                        display: flex;
                        align-items: stretch;
                        margin-bottom: 24rpx;

                        .wallet-balance-item {
                                flex: 1;
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                        }

                        .wallet-balance-divider {
                                width: 2rpx;
                                background: rgba(255, 255, 255, 0.3);
                                margin: 10rpx 30rpx;
                        }
                }

                .balance-label {
                        font-size: 24rpx;
                        color: rgba(255, 255, 255, 0.8);
                        margin-bottom: 10rpx;
                        display: block;
                }

                .balance-value-row {
                        display: flex;
                        align-items: baseline;
                }

                .balance-amount {
                        font-size: 44rpx;
                        font-weight: bold;
                        margin-right: 8rpx;
                }

                .balance-unit {
                        font-size: 24rpx;
                        color: rgba(255, 255, 255, 0.8);
                }

                .cash-amount {
                        color: #FFE4B5;
                }

                .wallet-exchange-tip {
                        font-size: 22rpx;
                        color: rgba(255, 255, 255, 0.6);
                        margin-bottom: 24rpx;
                        padding-bottom: 20rpx;
                        border-bottom: 1rpx solid rgba(255, 255, 255, 0.15);
                }

                .wallet-action-row {
                        display: flex;
                        justify-content: flex-end;
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
</style>
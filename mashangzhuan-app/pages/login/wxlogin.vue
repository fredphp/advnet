<template>
        <view class="login-page">
                <fa-navbar title="登录" :border-bottom="false"></fa-navbar>
                <!-- 顶部背景图 -->
                <view class="login-header">
                        <!-- <image src="/static/login-bg.png" mode="widthFix" class="bg-image"></image> -->
                        <view class="logo-container">
                                <image src="/static/logo.png" mode="widthFix" class="logo"></image>
                                <text class="app-name">马上赚</text>
                        </view>
                </view>

                <!-- 主要登录区域 - 微信登录 -->
                <view class="main-login-area">
                        <view class="wechat-desc">
                                <text>使用微信账号快捷登录</text>
                        </view>
                        <button class="wechat-btn" @click="goThreeLogin">
                                <u-icon name="weixin-fill" color="#fff" :size="50"></u-icon>
                                <text class="wechat-btn-text">微信快捷登录</text>
                        </button>
                        <view class="agreement">
                                <view class="u-p-t-30 u-text-center u-flex">
                                        <u-checkbox :active-color="theme.bgColor" v-model="agreeChecked" name="agree">阅读并同意</u-checkbox>
                                        <text class="u-font-30 agree" @click="goPage('/pages/page/page?id=2')" :style="[{ color: theme.bgColor }]">《用户协议》</text>
                                </view>
                                <!-- <text>登录即表示同意</text>
                                <text class="agreement-link">《用户服务协议》</text>
                                <text>和</text>
                                <text class="agreement-link">《隐私政策》</text> -->
                        </view>
                </view>

                <!-- 其他登录方式 - 底部区域（已屏蔽，仅支持微信授权登录） -->
                <view class="other-login-area" style="display: none;">
                        <view class="other-login-title">
                                <text>其他登录方式</text>
                        </view>
                        <view class="login-links">
                                <view class="login-link-item" @click="navigateToAccountLogin">
                                        <text class="link-text">用户名登录</text>
                                </view>
                                <view class="divider">|</view>
                                <view class="login-link-item" @click="navigateToPhoneLogin">
                                        <text class="link-text">手机快捷登录</text>
                                </view>
                        </view>
                        <view class="additional-links" v-if="false">
                                <view class="link-item" @click="handleForgetPwd">
                                        <text>忘记密码？</text>
                                </view>
                                <view class="link-item" @click="handleRegister">
                                        <text>立即注册</text>
                                </view>
                        </view>
                </view>
        </view>
</template>

<script>
        import {
                loginfunc
        } from '@/common/fa.mixin.js';
        export default {
                mixins: [loginfunc],
                data() {
                        return {
                                isThreeLogin: false,
                                agreeChecked:false,
                                // 无需登录类型切换，默认展示微信登录
                        };
                },
                onLoad() {
                        // #ifdef MP-WEIXIN || APP-PLUS
                        this.isThreeLogin = true;
                        // #endif

                        // #ifdef H5
                        if (this.$util.isWeiXinBrowser()) {
                                this.isThreeLogin = true;
                        }
                        // #endif
                        // 页面加载完成
                },
                methods: {
                        goThreeLogin: async function() {
                                if(!this.agreeChecked){
                                        uni.showModal({
                                                title:"系统提醒",
                                                content:"请阅读并同意会员协议后再操作",
                                                showCancel:false
                                        })
                                        return false;
                                }
                                // #ifdef MP-WEIXIN
                                this.goMpLogin();
                                // #endif

                                // #ifdef H5
                                this.goAuth();
                                // #endif

                                // #ifdef APP-PLUS
                                this.goAppLogin();
                                // #endif
                        },
                        // 微信登录处理
                        handleWechatLogin(userInfo) {
                                if (userInfo.detail.userInfo) {
                                        this.$u.toast('正在微信登录中...');
                                        // 实际项目中调用微信登录接口
                                        setTimeout(() => {
                                                this.$u.toast('登录成功');
                                                // 登录成功后跳转到首页
                                                uni.switchTab({
                                                        url: '/pages/index/index'
                                                });
                                        }, 1500);
                                } else {
                                        this.$u.toast('请授权微信登录');
                                }
                        },

                        // 跳转到账号密码登录页
                        navigateToAccountLogin() {
                                uni.navigateTo({
                                        url: '/pages/login/login'
                                });
                        },

                        // 跳转到手机验证码登录页
                        navigateToPhoneLogin() {
                                uni.navigateTo({
                                        url: '/pages/login/mobilelogin'
                                });
                        },

                        // 忘记密码
                        handleForgetPwd() {
                                uni.navigateTo({
                                        url: '/pages/login/forget-pwd'
                                });
                        },

                        // 注册账号
                        handleRegister() {
                                uni.navigateTo({
                                        url: '/pages/login/register'
                                });
                        }
                }
        };
</script>

<style scoped>
        .login-page {
                display: flex;
                flex-direction: column;
                min-height: 100vh;
                background-color: #ffffff;
        }

        /* 顶部背景 */
        .login-header {
                position: relative;
                width: 100%;
                margin-top:180rpx;
        }

        .bg-image {
                width: 100%;
                height: auto;
        }

        .logo-container {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                display: flex;
                flex-direction: column;
                align-items: center;
        }

        .logo {
                width: 160rpx;
                height: 160rpx;
                margin-bottom: 24rpx;
        }

        .app-name {
                font-size: 40rpx;
                color: #ffffff;
                font-weight: bold;
                /* text-shadow: 0 2rpx 4rpx rgba(249, 49, 16, 0.3); */
        }

        /* 主要登录区域 */
        .main-login-area {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 0 40rpx;
                margin-top: -80rpx;
                /* 向上调整位置，贴近顶部区域 */
        }

        .wechat-desc {
                margin-bottom: 50rpx;
        }

        .wechat-desc text {
                font-size: 32rpx;
                color: #333333;
        }

        .wechat-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                max-width: 500rpx;
                height: 100rpx;
                line-height: 100rpx;
                background-color: #07C160;
                color: #ffffff;
                border-radius: 50rpx;
                font-size: 34rpx;
                margin-bottom: 30rpx;
        }

        .wechat-icon {
                width: 44rpx;
                height: 44rpx;
                margin-right: 15rpx;
                vertical-align: middle;
        }

        .agreement {
                font-size: 24rpx;
                color: #999999;
                text-align: center;
                margin-bottom: 40rpx;
        }

        .agreement-link {
                color: #FF7D00;
                margin: 0 5rpx;
        }

        /* 其他登录方式区域 - 底部 */
        .other-login-area {
                padding: 30rpx 40rpx 60rpx;
                border-top: 1px solid #f0f0f0;
                margin-top: auto;
                /* 推到页面底部 */
        }

        .other-login-title {
                text-align: center;
                margin-bottom: 30rpx;
        }

        .other-login-title text {
                font-size: 26rpx;
                color: #999999;
                padding: 0 20rpx;
                background-color: #ffffff;
                position: relative;
                z-index: 1;
        }

        .other-login-title::before {
                content: '';
                position: absolute;
                left: 40rpx;
                right: 40rpx;
                height: 1px;
                /* background-color: #f0f0f0; */
                top: 50%;
                z-index: 0;
        }

        .login-links {
                display: flex;
                justify-content: center;
                align-items: center;
                margin-bottom: 30rpx;
        }

        .login-link-item {
                padding: 0 20rpx;
        }

        .link-text {
                font-size: 28rpx;
                color: #333333;
        }

        .divider {
                color: #ddd;
                font-size: 28rpx;
        }

        .additional-links {
                display: flex;
                justify-content: center;
                gap: 40rpx;
        }

        .additional-links .link-item text {
                font-size: 26rpx;
                color: #FF7D00;
        }
</style>
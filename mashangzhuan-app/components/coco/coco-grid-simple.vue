<template>
        <view class="grid-container">
                <block v-for="(item, index) in gridList" :key="index">
                        <!-- 小程序端：微信分享按钮用 open-type="share" -->
                        <!-- #ifdef MP-WEIXIN -->
                        <view v-if="item.path === 'wxshare'" class="grid-item">
                                <button class="share-button" open-type="share">
                                        <image class="grid-icon" :src="$IMG_URL+item.image" mode="aspectFit"></image>
                                        <text class="grid-text">{{item.name}}</text>
                                </button>
                        </view>
                        <!-- #endif -->

                        <!-- #ifdef APP-PLUS -->
                        <view v-if="item.path === 'wxshare'" class="grid-item" @click="handleClick(item)">
                                <image class="grid-icon" :src="$IMG_URL+item.image" mode="aspectFit"></image>
                                <text class="grid-text">{{item.name}}</text>
                        </view>
                        <!-- #endif -->

                        <!-- H5端：分享按钮走普通点击 -->
                        <!-- #ifdef H5 -->
                        <view v-if="item.path === 'wxshare'" class="grid-item" @click="handleClick(item)">
                                <image class="grid-icon" :src="$IMG_URL+item.image" mode="aspectFit"></image>
                                <text class="grid-text">{{item.name}}</text>
                        </view>
                        <!-- #endif -->

                        <!-- 普通跳转按钮 -->
                        <view v-if="item.path !== 'wxshare'" class="grid-item" @click="handleClick(item)">
                                <image class="grid-icon" :src="$IMG_URL+item.image" mode="aspectFit"></image>
                                <text class="grid-text">{{item.name}}</text>
                        </view>
                </block>
        </view>
</template>

<script>
        export default {
                name: 'CocoGridSimple',
                props: {
                        gridList: {
                                type: Array,
                                required: true,
                                default: () => []
                        }
                },
                data() {
                        return {
                                
                        }
                },
                methods: {
                        handleClick(item) {
                                // 通知父组件处理点击
                                this.$emit('itemclick', item);
                        }
                },
                // #ifdef MP-WEIXIN
                onShareAppMessage() {
                        return {
                                title: '邀请您一起加入',
                                path: '/pages/index/index',
                                imageUrl: '/static/image/share.jpg'
                        }
                }
                // #endif
        }
</script>

<style>
        .grid-container {
                display: flex;
                flex-wrap: wrap;
                padding: 10rpx;
                justify-content: space-around;
        }

        .grid-item {
                width: 25%;
                display: flex;
                flex-direction: column;
                align-items: center;
                /* margin-bottom: 20rpx; */
        }

        .grid-icon {
                width: 64rpx;
                height: 64rpx;
                margin-bottom: 10rpx;
        }

        .grid-text {
                font-size: 24rpx;
                color: #333;
                text-align: center;
        }

        /* 微信小程序分享按钮样式 */
        .share-button {
                background: none;
                padding: 0;
                margin: 0;
                line-height: normal;
                border: none;
                display: flex;
                flex-direction: column;
                align-items: center;
        }

        .share-button::after {
                border: none;
        }
</style>

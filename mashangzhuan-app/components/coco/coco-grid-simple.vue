<template>
	<view class="grid-container">
		<block v-for="(item, index) in gridList" :key="index">
			<!-- 判断是否为微信分享按钮 -->
			<view v-if="item.path === 'wxshare'" class="grid-item">
				<button class="share-button" open-type="share">
					<image class="grid-icon" :src="$IMG_URL+item.image" mode="aspectFit"></image>
					<text class="grid-text">{{item.name}}</text>
				</button>
			</view>

			<!-- 普通跳转按钮 -->
			<view v-else class="grid-item" @click="handleClick(item)">
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
		computed: {
			// 过滤掉第一个分享按钮，用于其他按钮的渲染
			otherGridItems() {
				return this.gridList.slice(1)
			}
		},
		methods: {
			handleClick(item) {
				uni.navigateTo({
					url: item.path
				})
			}
		},
		onLoad() {
			// 设置分享配置
			// #ifdef MP-WEIXIN
			wx.showShareMenu({
				withShareTicket: true,
				menus: ['shareAppMessage', 'shareTimeline']
			})
			// #endif
		},
		onShareAppMessage() {
			return {
				title: '邀请您一起加入',
				path: '/pages/index/index',
				imageUrl: '/static/image/share.jpg'
			}
		}
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

	/* 微信分享按钮样式 */
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
<template>
	<view class="ad-banner" @click="handleClick">
		<image class="ad-image" src="/static/image/ad-banner.png" mode="aspectFill"></image>
		<view class="ad-content">
			<view class="ad-badge">广告</view>
			<view class="ad-text">
				<view class="ad-title">助力领现金红包</view>
				<view class="ad-subtitle">拼多多</view>
			</view>
			<view class="ad-action">
				<text class="action-text">摇手机 了解更多</text>
			</view>
		</view>
		<!-- 装饰元素 -->
		<view class="decoration decoration-1"></view>
		<view class="decoration decoration-2"></view>
	</view>
</template>

<script>
	export default {
		name: 'AdBanner',
		methods: {
			handleClick() {
				// 广告点击事件
				uni.showToast({
					title: '广告点击',
					icon: 'none'
				});
			}
		}
	}
</script>

<style lang="scss" scoped>
	.ad-banner {
		position: relative;
		width: 100%;
		height: 200rpx;
		border-radius: 16rpx;
		overflow: hidden;
		background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 50%, #ffb3b3 100%);
	}

	.ad-image {
		width: 100%;
		height: 100%;
		position: absolute;
		top: 0;
		left: 0;
	}

	.ad-content {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		padding: 20rpx;
		box-sizing: border-box;
	}

	.ad-badge {
		position: absolute;
		bottom: 10rpx;
		right: 10rpx;
		background-color: rgba(0, 0, 0, 0.4);
		color: #fff;
		font-size: 18rpx;
		padding: 4rpx 10rpx;
		border-radius: 4rpx;
	}

	.ad-text {
		flex: 1;
		display: flex;
		flex-direction: column;
		justify-content: center;
	}

	.ad-title {
		font-size: 48rpx;
		font-weight: bold;
		color: #ffeb3b;
		text-shadow: 2rpx 2rpx 4rpx rgba(0, 0, 0, 0.3);
		margin-bottom: 10rpx;
	}

	.ad-subtitle {
		font-size: 24rpx;
		color: #fff;
		opacity: 0.9;
	}

	.ad-action {
		position: absolute;
		top: 20rpx;
		right: 20rpx;
		background-color: rgba(255, 255, 255, 0.9);
		padding: 8rpx 16rpx;
		border-radius: 20rpx;
	}

	.action-text {
		font-size: 20rpx;
		color: #ff6b6b;
	}

	// 装饰元素
	.decoration {
		position: absolute;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.1);
	}

	.decoration-1 {
		width: 100rpx;
		height: 100rpx;
		top: -30rpx;
		right: 100rpx;
	}

	.decoration-2 {
		width: 60rpx;
		height: 60rpx;
		bottom: 20rpx;
		left: 30rpx;
	}
</style>

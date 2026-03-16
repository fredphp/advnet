<template>
	<view :class="['message', isMe ? 'me' : 'other']">
		<!-- 用户头像 -->
		<image v-if="!isMe" class="avatar" :src="message.user.avatar" mode="aspectFit"></image>
		<image v-if="isMe" class="avatar" :src="vuex_user.avatar || '/static/image/avatar.png'" mode="aspectFit"></image>

		<view class="content-wrapper">
			<!-- 用户昵称 -->
			<text v-if="!isMe" class="nickname">{{ message.user.nickname }}</text>

			<!-- 红包消息卡片 -->
			<view :class="['redbag-card', message.status === 'opened' ? 'opened' : '']" @click="handleClick">
				<view class="redbag-content">
					<view class="redbag-icon-wrapper">
						<image class="redbag-icon" src="/static/image/redbag-icon.png" mode="aspectFit"></image>
					</view>
					<view class="redbag-info">
						<text class="redbag-text">{{ message.content }}</text>
						<text class="redbag-status">{{ message.status === 'opened' ? '已领取' : '领取红包' }}</text>
					</view>
				</view>
				<view class="redbag-footer">
					<text class="footer-text">视频红包</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		name: 'RedbagMessage',
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
			}
		},
		methods: {
			handleClick() {
				this.$emit('open-redbag', this.message);
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
		max-width: 60%;
	}

	.nickname {
		font-size: 24rpx;
		color: #999;
		margin-bottom: 8rpx;
		margin-left: 10rpx;
	}

	.redbag-card {
		background: linear-gradient(135deg, #ff9a7a 0%, #ff7e67 100%);
		border-radius: 16rpx;
		overflow: hidden;
		min-width: 400rpx;
		box-shadow: 0 4rpx 12rpx rgba(255, 126, 103, 0.3);

		&.opened {
			background: linear-gradient(135deg, #f5d0c5 0%, #e8b4a8 100%);
			box-shadow: 0 4rpx 12rpx rgba(232, 180, 168, 0.3);

			.redbag-icon-wrapper {
				opacity: 0.7;
			}

			.redbag-text {
				color: rgba(255, 255, 255, 0.8);
			}

			.redbag-status {
				color: rgba(255, 255, 255, 0.6);
			}

			.footer-text {
				color: rgba(255, 255, 255, 0.6);
			}
		}
	}

	.redbag-content {
		display: flex;
		align-items: center;
		padding: 30rpx;
	}

	.redbag-icon-wrapper {
		width: 80rpx;
		height: 80rpx;
		background-color: rgba(255, 255, 255, 0.2);
		border-radius: 12rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		margin-right: 20rpx;
	}

	.redbag-icon {
		width: 60rpx;
		height: 60rpx;
	}

	.redbag-info {
		display: flex;
		flex-direction: column;
		flex: 1;
	}

	.redbag-text {
		font-size: 32rpx;
		color: #fff;
		font-weight: 500;
		margin-bottom: 8rpx;
	}

	.redbag-status {
		font-size: 24rpx;
		color: rgba(255, 255, 255, 0.8);
	}

	.redbag-footer {
		background-color: rgba(255, 255, 255, 0.15);
		padding: 12rpx 30rpx;
		border-top: 1rpx solid rgba(255, 255, 255, 0.1);
	}

	.footer-text {
		font-size: 22rpx;
		color: rgba(255, 255, 255, 0.9);
	}
</style>

<template>
	<view :class="['message', isMe ? 'me' : 'other']">
		<!-- 用户头像 -->
		<image v-if="!isMe" class="avatar" :src="(message.user && message.user.avatar) || '/static/image/avatar.png'" mode="aspectFill"></image>

		<view class="content-wrapper">
			<!-- 用户昵称 -->
			<text v-if="!isMe" class="nickname">{{ message.user.nickname }}</text>

			<!-- 微信红包气泡 -->
			<view :class="['redbag-bubble', statusClass]" @click="handleClick">
				<!-- 左侧红包图标 -->
				<view class="redbag-icon-box">
					<text class="redbag-emoji">🧧</text>
				</view>
				<!-- 右侧文字 -->
				<view class="redbag-text-box">
					<text class="redbag-title">{{ isExpired ? '已领完' : '恭喜，大吉大利' }}</text>
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
		},
		isExpired() {
			return this.message.status === 'expired';
		},
		statusClass() {
			if (this.isExpired) return 'expired';
			if (this.message.status === 'claimed') return 'claimed';
			if (this.message.status === 'opened') return 'opened';
			return '';
		}
	},
	methods: {
		handleClick() {
			if (this.message.status === 'expired') {
				uni.showToast({ title: '红包已领完', icon: 'none' });
				return;
			}
			this.$emit('open-redbag', this.message);
		}
	}
}
</script>

<style lang="scss" scoped>
.message {
	display: flex;
	flex-direction: row;
	margin-bottom: 30rpx;
	padding: 0 20rpx;
	align-items: flex-start;

	&.me {
		flex-direction: row-reverse;

		.avatar {
			margin: 0 0 0 16rpx;
		}
	}
}

.avatar {
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	margin: 0 16rpx 0 0;
	flex-shrink: 0;
	background: #f5f5f5;
}

.content-wrapper {
	display: flex;
	flex-direction: column;
	max-width: 75%;
	flex: 1;
}

.nickname {
	font-size: 24rpx;
	color: #999;
	margin-bottom: 8rpx;
	margin-left: 4rpx;
}

/* ==================== 红包气泡 ==================== */
.redbag-bubble {
	display: flex;
	flex-direction: row;
	align-items: center;
	width: 480rpx;
	height: 96rpx;
	border-radius: 12rpx;
	overflow: hidden;
	background: linear-gradient(135deg, #FA9D3B 0%, #E8611A 50%, #D04B18 100%);
	box-shadow: 0 4rpx 16rpx rgba(208, 75, 24, 0.2);

	/* 已领取：颜色变淡 */
	&.claimed {
		background: linear-gradient(135deg, #F0D0B0 0%, #E8BFA0 50%, #D8B09A 100%);
		box-shadow: 0 2rpx 8rpx rgba(200, 170, 150, 0.2);
	}

	/* 已拆开：颜色变淡 */
	&.opened {
		background: linear-gradient(135deg, #F5C8A0 0%, #E8B48A 50%, #D8A478 100%);
		box-shadow: 0 2rpx 8rpx rgba(216, 164, 120, 0.2);
	}

	/* 已过期（已领完）：颜色明显变淡，灰色调 */
	&.expired {
		background: linear-gradient(135deg, #D5C4B8 0%, #C8B8AC 50%, #B8A89C 100%);
		box-shadow: 0 2rpx 8rpx rgba(180, 160, 140, 0.15);

		.redbag-icon-box {
			opacity: 0.5;
		}
	}
}

/* 左侧红包图标 */
.redbag-icon-box {
	width: 96rpx;
	height: 96rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	flex-shrink: 0;
	background: rgba(0, 0, 0, 0.08);
}

.redbag-emoji {
	font-size: 44rpx;
	line-height: 1;
}

/* 右侧文字 */
.redbag-text-box {
	flex: 1;
	display: flex;
	align-items: center;
	padding: 0 24rpx;
}

.redbag-title {
	font-size: 30rpx;
	color: #FFFFFF;
	font-weight: 600;
	line-height: 1.3;
}

/* 过期状态文字颜色 */
.expired .redbag-title {
	color: rgba(255, 255, 255, 0.6);
}
</style>

<template>
	<view :class="['message', isMe ? 'me' : 'other']">
		<!-- 用户头像（左侧，带红包主题边框） -->
		<view class="avatar-wrapper">
			<image class="avatar" :src="(message.user && message.user.avatar) || '/static/image/avatar.png'" mode="aspectFill"></image>
		</view>

		<view class="content-wrapper">
			<!-- 用户昵称 -->
			<text v-if="!isMe" class="nickname">{{ message.user.nickname }}</text>

			<!-- 微信红包气泡 -->
			<view :class="['redbag-bubble', cardStatusClass]" @click="handleClick">
				<!-- 左侧红包图标区域 -->
				<view class="redbag-left">
					<view class="redbag-icon-area">
						<text class="redbag-icon-text">🧧</text>
					</view>
				</view>
				<!-- 右侧文字区域 -->
				<view class="redbag-right">
					<text class="redbag-title">{{ displayTitle }}</text>
					<text class="redbag-subtitle">{{ displaySubtitle }}</text>
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
		cardStatusClass() {
			const s = this.message.status;
			if (s === 'expired') return 'status-expired';
			if (s === 'claimed') return 'status-claimed';
			if (s === 'opened') return 'status-opened';
			return 'status-unopened';
		},
		// 主标题
		displayTitle() {
			const s = this.message.status;
			if (s === 'expired') return '已过期的红包';
			if (s === 'claimed') return (this.message.claimedAmount || this.message.currentAmount || this.message.amount || 0) + '金币 · 已领取';
			if (s === 'opened') return '已拆开的红包';
			// 未领取：优先使用 displayTitle，否则用祝福语
			return this.message.displayTitle || '恭喜发财，大吉大利';
		},
		// 副标题
		displaySubtitle() {
			const s = this.message.status;
			if (s === 'expired') return '红包已过期';
			if (s === 'claimed') return '领取红包';
			if (s === 'opened') return '查看详情';
			// 未领取
			const taskData = this.message.taskData || {};
			if (taskData.isAdRedPacket && this.message.amount > 0) {
				return '待释放金币 ' + this.message.amount + ' 个';
			}
			return '领取红包';
		}
	},
	methods: {
		handleClick() {
			if (this.message.status === 'expired') {
				uni.showToast({ title: '红包已过期', icon: 'none' });
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
	}
}

/* 头像：圆形 + 红色边框，模拟微信红包发送者 */
.avatar-wrapper {
	flex-shrink: 0;
	margin: 0 16rpx 0 0;
}

.avatar {
	width: 80rpx;
	height: 80rpx;
	border-radius: 50%;
	border: 2rpx solid #e74c3c;
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

/* ==================== 微信红包气泡 ==================== */
.redbag-bubble {
	display: flex;
	flex-direction: row;
	align-items: center;
	min-width: 420rpx;
	max-width: 520rpx;
	border-radius: 12rpx;
	overflow: hidden;
	// 微信红包经典橙色
	background: linear-gradient(135deg, #FA9D3B 0%, #E8611A 50%, #D04B18 100%);
	box-shadow: 0 4rpx 20rpx rgba(224, 75, 24, 0.25);
	position: relative;

	&::before {
		content: '';
		position: absolute;
		top: -60rpx;
		right: -60rpx;
		width: 160rpx;
		height: 160rpx;
		border-radius: 50%;
		background: rgba(255, 255, 255, 0.06);
	}

	/* ---- 未领取状态 ---- */
	&.status-unopened {
		background: linear-gradient(135deg, #FA9D3B 0%, #E8611A 50%, #D04B18 100%);
		box-shadow: 0 4rpx 20rpx rgba(224, 75, 24, 0.25);
	}

	/* ---- 已拆开状态 ---- */
	&.status-opened {
		background: linear-gradient(135deg, #F5C088 0%, #E8986A 50%, #D98A5C 100%);
		box-shadow: 0 2rpx 12rpx rgba(217, 138, 92, 0.2);
	}

	/* ---- 已领取状态 ---- */
	&.status-claimed {
		background: linear-gradient(135deg, #F0D9D5 0%, #E8C4BE 100%);
		box-shadow: 0 2rpx 8rpx rgba(200, 180, 170, 0.3);
	}

	/* ---- 已过期状态 ---- */
	&.status-expired {
		background: linear-gradient(135deg, #D5D5D5 0%, #BFBFBF 50%, #ABABAB 100%);
		box-shadow: 0 2rpx 8rpx rgba(170, 170, 170, 0.2);
	}
}

/* 左侧红包图标区域 */
.redbag-left {
	flex-shrink: 0;
	width: 120rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 24rpx 0;
	position: relative;

	// 右边分隔线
	&::after {
		content: '';
		position: absolute;
		right: 0;
		top: 20%;
		height: 60%;
		width: 1rpx;
		background: rgba(255, 255, 255, 0.15);
	}
}

.redbag-icon-area {
	width: 72rpx;
	height: 72rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.redbag-icon-text {
	font-size: 52rpx;
	line-height: 1;
}

/* 右侧文字区域 */
.redbag-right {
	flex: 1;
	display: flex;
	flex-direction: column;
	justify-content: center;
	padding: 24rpx 28rpx;
}

.redbag-title {
	font-size: 30rpx;
	color: #FFFFFF;
	font-weight: 600;
	line-height: 1.4;
	margin-bottom: 6rpx;
	word-break: break-all;
}

.redbag-subtitle {
	font-size: 24rpx;
	color: rgba(255, 255, 255, 0.75);
	line-height: 1.4;
}

/* 已领取/过期时文字颜色调整 */
.status-claimed .redbag-title {
	color: #8B4513;
}

.status-claimed .redbag-subtitle {
	color: rgba(139, 69, 19, 0.6);
}

.status-expired .redbag-title {
	color: #FFFFFF;
}

.status-expired .redbag-subtitle {
	color: rgba(255, 255, 255, 0.5);
}
</style>

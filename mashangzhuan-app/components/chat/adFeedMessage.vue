<template>
	<view class="ad-feed-message">
		<!-- 发送者头像和名称 -->
		<view class="msg-header">
			<image class="avatar" :src="message.user ? message.user.avatar : '/static/image/avatar.png'" mode="aspectFill"></image>
			<text class="nickname">{{ message.user ? message.user.nickname : '系统' }}</text>
			<text class="time-text">{{ formatTime(message.time) }}</text>
		</view>

		<!-- 广告卡片 -->
		<view class="ad-card">
			<view class="ad-card-header">
				<view class="ad-badge">
					<text class="badge-text">广告</text>
				</view>
				<text class="ad-card-title">观看广告赚金币</text>
			</view>

			<!-- uni-ad 信息流广告组件 -->
			<view class="ad-container" v-if="adpid">
				<!-- #ifdef APP-PLUS || MP-WEIXIN || MP -->
				<ad :adpid="adpid" unit-id="adunit" @load="onAdLoad" @error="onAdError" @close="onAdClose"
					style="width: 100%; min-height: 120px;"></ad>
				<!-- #endif -->

				<!-- #ifdef H5 -->
				<!-- H5 环境下使用模拟广告或第三方广告SDK -->
				<view class="ad-placeholder" @click="handleAdClick">
					<view class="ad-placeholder-content">
						<text class="ad-icon">🎁</text>
						<text class="ad-text">点击观看广告赚金币</text>
						<text class="ad-reward">+{{ rewardCoin }} 金币</text>
					</view>
				</view>
				<!-- #endif -->
			</view>

			<!-- 广告奖励提示 -->
			<view class="ad-reward-tip" v-if="rewarded">
				<text class="reward-tip-text">✅ 已获得 +{{ rewardAmount }} 金币</text>
			</view>
			<view class="ad-reward-tip loading-tip" v-else-if="loading">
				<text class="reward-tip-text">⏳ 广告加载中...</text>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	name: 'AdFeedMessage',
	props: {
		message: {
			type: Object,
			default: () => ({})
		},
		isMe: {
			type: Boolean,
			default: false
		}
	},

	data() {
		return {
			adpid: '',       // uni-ad 广告位ID
			loading: false,  // 广告加载中
			rewarded: false, // 是否已获得奖励
			rewardAmount: 0, // 获得的奖励金额
			rewardCoin: 50,  // 预期奖励金币数
			hasReported: false, // 是否已回调
		};
	},

	created() {
		// 从消息数据中获取广告位ID
		const taskData = this.message.taskData || {};
		const resource = taskData.resource || {};

		// 广告位ID优先级：resource.adpid > taskData.adpid > 配置默认值
		this.adpid = resource.adpid || taskData.adpid || '';

		// 奖励金币数
		if (taskData.reward_coin) {
			this.rewardCoin = taskData.reward_coin;
		}
	},

	methods: {
		/**
		 * 广告加载成功
		 */
		onAdLoad(e) {
			console.log('[AdFeed] 广告加载成功:', e);
			this.loading = false;
		},

		/**
		 * 广告加载失败
		 */
		onAdError(e) {
			console.warn('[AdFeed] 广告加载失败:', e);
			this.loading = false;
		},

		/**
		 * 广告关闭回调
		 * uni-ad 激励广告：用户完整观看后关闭才触发奖励
		 * uni-ad 信息流广告：展示即可触发
		 */
		onAdClose(e) {
			console.log('[AdFeed] 广告关闭:', e);
			this.reportAdReward();
		},

		/**
		 * H5 环境下点击模拟广告
		 */
		handleAdClick() {
			if (this.rewarded) {
				uni.showToast({ title: '已获得奖励', icon: 'none' });
				return;
			}

			this.loading = true;

			// 模拟观看广告 2 秒
			setTimeout(() => {
				this.loading = false;
				this.reportAdReward();
			}, 2000);
		},

		/**
		 * 上报广告奖励到后端
		 */
		async reportAdReward() {
			if (this.hasReported) return;
			this.hasReported = true;

			try {
				// 生成唯一 transaction_id（防止重复回调）
				const transactionId = 'ad_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

				const res = await this.$api.adCallback({
					ad_type: 'feed',
					adpid: this.adpid,
					ad_provider: 'uniad',
					ad_source: 'redbag_page',
					transaction_id: transactionId,
				});

				if (res && res.code === 1 && res.data) {
					this.rewarded = true;
					this.rewardAmount = res.data.user_amount_coin || 0;

					if (this.rewardAmount > 0) {
						uni.showToast({
							title: '获得 +' + this.rewardAmount + ' 金币',
							icon: 'none',
							duration: 2000
						});
					}

					// 通知父组件更新
					this.$emit('ad-rewarded', {
						message: this.message,
						amount: this.rewardAmount,
						logId: res.data.log_id,
					});
				} else {
					this.hasReported = false; // 失败后允许重试
					const msg = (res && res.msg) || '奖励获取失败';
					if (msg !== '重复回调') {
						uni.showToast({ title: msg, icon: 'none' });
					} else {
						this.rewarded = true;
					}
				}
			} catch (e) {
				this.hasReported = false;
				console.error('[AdFeed] 上报广告奖励失败:', e);
			}
		},

		formatTime(timestamp) {
			if (!timestamp) return '';
			const date = new Date(timestamp);
			const hours = date.getHours().toString().padStart(2, '0');
			const minutes = date.getMinutes().toString().padStart(2, '0');
			return `${hours}:${minutes}`;
		}
	}
}
</script>

<style lang="scss" scoped>
.ad-feed-message {
	margin: 16rpx 0;
	max-width: 85%;
}

.msg-header {
	display: flex;
	align-items: center;
	margin-bottom: 12rpx;
}

.avatar {
	width: 64rpx;
	height: 64rpx;
	border-radius: 12rpx;
	margin-right: 16rpx;
}

.nickname {
	font-size: 26rpx;
	color: #666;
	font-weight: 500;
	margin-right: 12rpx;
}

.time-text {
	font-size: 22rpx;
	color: #999;
}

.ad-card {
	background-color: #fff;
	border-radius: 16rpx;
	overflow: hidden;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.ad-card-header {
	display: flex;
	align-items: center;
	padding: 16rpx 20rpx;
	background-color: #fff8f0;
	border-bottom: 1rpx solid #f0e6d8;
}

.ad-badge {
	background: linear-gradient(135deg, #ff9500, #ff6b00);
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
	margin-right: 12rpx;
}

.badge-text {
	font-size: 20rpx;
	color: #fff;
	font-weight: bold;
}

.ad-card-title {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
}

.ad-container {
	width: 100%;
	min-height: 120px;
}

.ad-placeholder {
	width: 100%;
	padding: 30rpx 20rpx;
	background: linear-gradient(135deg, #667eea, #764ba2);
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
}

.ad-placeholder-content {
	display: flex;
	flex-direction: column;
	align-items: center;
}

.ad-icon {
	font-size: 48rpx;
	margin-bottom: 8rpx;
}

.ad-text {
	font-size: 28rpx;
	color: #fff;
	margin-bottom: 8rpx;
}

.ad-reward {
	font-size: 32rpx;
	color: #ffd700;
	font-weight: bold;
}

.ad-reward-tip {
	padding: 16rpx 20rpx;
	text-align: center;
	background-color: #f0fff4;
	border-top: 1rpx solid #c6f6d5;

	&.loading-tip {
		background-color: #fffbeb;
		border-top-color: #fde68a;
	}
}

.reward-tip-text {
	font-size: 24rpx;
	color: #38a169;
}
</style>

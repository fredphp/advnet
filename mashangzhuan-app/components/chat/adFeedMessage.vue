<template>
	<view class="ad-feed-message">
		<!-- ★ 顶部：发送者信息条（与 rewardedVideoMessage 保持一致风格） -->
		<view class="msg-header">
			<image class="msg-avatar" :src="message.user ? message.user.avatar : '/static/image/avatar.png'" mode="aspectFill"></image>
			<text class="msg-nickname">{{ message.user ? message.user.nickname : '广告推荐' }}</text>
			<view class="header-tag">
				<text class="tag-text">信息流</text>
			</view>
			<view class="header-time">
				<text class="time-text">{{ formatTime(message.time) }}</text>
			</view>
		</view>

		<!-- 广告卡片（展示即计费，无需点击） -->
		<view class="ad-card">

			<!-- 广告内容区 -->
			<view class="ad-container">
				<view class="ad-preview">
					<view class="ad-preview-content">
						<text class="ad-preview-icon">📱</text>
						<text class="ad-preview-title">精选推荐内容</text>
						<text class="ad-preview-desc">浏览即可获得金币奖励</text>
					</view>
				</view>
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
		},
		// ★ 从父组件传入的浏览进度
		feedProgress: {
			type: Object,
			default: () => null
		}
	},

	data() {
		return {
			adpid: '',
			rewardCoin: 50,
			rewarded: false,
			hasReported: false,
			// ★ 内部进度追踪（不展示给用户）
			viewCount: 0,
			threshold: 0,
		};
	},

	created() {
		const taskData = this.message.taskData || {};
		const resource = taskData.resource || {};
		this.adpid = resource.adpid || taskData.adpid || '';
		if (taskData.reward_coin) this.rewardCoin = taskData.reward_coin;

		// ★ 初始化进度数据
		this.updateProgress();

		// ★ 展示即上报：组件创建时自动调用 recordView API
		// 无需用户点击，只要信息流广告卡片出现在页面上就计费
		this.$nextTick(() => {
			this.silentReportView();
		});
	},

	computed: {
		displayRewardCoin() {
			return this.rewardCoin;
		}
	},

	methods: {
		/**
		 * 格式化时间
		 */
		formatTime(timestamp) {
			if (!timestamp) return '';
			const date = new Date(timestamp);
			return date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
		},

		/**
		 * 更新进度数据（从 props 获取，内部使用不展示）
		 */
		updateProgress() {
			if (this.feedProgress) {
				this.viewCount = this.feedProgress.view_count || 0;
				this.threshold = this.feedProgress.threshold || 0;
			}
		},

		/**
		 * ★ 静默上报广告浏览
		 * 展示即计费：组件创建时自动调用，无需用户点击
		 * 调用 recordView API → 后端按阈值累加到 ad_freeze_balance
		 */
		async silentReportView() {
			if (this.hasReported) return;
			this.hasReported = true;

			try {
				const transactionId = 'af_auto_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
				console.log('[AdFeed] ★ 展示即上报, adpid=' + (this.adpid || '(未配置)') + ', msgId=' + this.message.id);

				const res = await this.$api.adRecordView({
					ad_type: 'feed',
					adpid: this.adpid,
					ad_provider: 'uniad',
					ad_source: 'redbag_page',
					transaction_id: transactionId,
				});

				console.log('[AdFeed] recordView返回:', JSON.stringify(res));
				if (res && res.code === 1 && res.data) {
					if (res.data.reward_given) {
						// 达到阈值，奖励已写入 ad_freeze_balance
						this.rewarded = true;
						// ★ 通知父组件刷新数据（但不显示系统消息，因为用户不应看到金币）
						this.$emit('ad-rewarded', {
							message: this.message,
							amount: res.data.amount || this.rewardCoin,
							adType: 'feed',
							silent: true  // ★ 标记为静默模式，父组件不显示系统消息
						});
					} else {
						// 未达阈值，浏览已记录
						console.log('[AdFeed] 浏览已记录, view_count=' + (res.data.view_count || 0) + '/' + (res.data.threshold || '?'));
					}
				}
			} catch (e) {
				console.warn('[AdFeed] 静默上报失败:', e.message || e);
				// 失败也静默处理，不影响用户体验
			}
		}
	}
}
</script>

<style lang="scss" scoped>
.ad-feed-message {
	width: 100%;
	padding: 0;
	margin: 0;
	margin-bottom: 20rpx;
}

/* ★ 发送者信息条（与 rewardedVideoMessage 风格一致） */
.msg-header {
	display: flex;
	align-items: center;
	padding: 16rpx 24rpx 10rpx;
}

.msg-avatar {
	width: 52rpx;
	height: 52rpx;
	border-radius: 50%;
	margin-right: 12rpx;
	flex-shrink: 0;
}

.msg-nickname {
	font-size: 24rpx;
	color: #999;
	font-weight: 400;
}

.header-tag {
	margin-left: 12rpx;
	background: linear-gradient(135deg, #ff9500, #ff6b00);
	padding: 2rpx 12rpx;
	border-radius: 6rpx;
}

.tag-text {
	font-size: 20rpx;
	color: #fff;
	font-weight: 600;
}

.header-time {
	margin-left: auto;
}

.time-text {
	font-size: 22rpx;
	color: #ccc;
}

.ad-card {
	width: 100%;
	background-color: #fff;
	overflow: hidden;
	box-shadow: 0 1rpx 4rpx rgba(0, 0, 0, 0.04);
}

.ad-card-header {
	display: flex;
	align-items: center;
	padding: 16rpx 24rpx;
	background: linear-gradient(135deg, #fff5eb, #fff9f3);
	border-bottom: 1rpx solid #f5e6d3;
}

.ad-card-title {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
	flex: 1;
}

.ad-reward-tag-wrap {
	flex-shrink: 0;
}

.ad-reward-tag {
	font-size: 24rpx;
	color: #ff6b00;
	font-weight: bold;
}

/* 广告容器 */
.ad-container {
	width: 100%;
}

.ad-preview {
	display: flex;
	align-items: center;
	padding: 30rpx 24rpx;
}

.ad-preview-content {
	flex: 1;
	display: flex;
	flex-direction: column;
}

.ad-preview-icon {
	font-size: 56rpx;
	margin-bottom: 12rpx;
}

.ad-preview-title {
	font-size: 28rpx;
	color: #333;
	font-weight: 600;
	margin-bottom: 8rpx;
}

.ad-preview-desc {
	font-size: 22rpx;
	color: #999;
}

</style>

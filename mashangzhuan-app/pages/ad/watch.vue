<template>
	<view class="ad-watch-page">
		<!-- 自定义导航栏 -->
		<view class="nav-bar">
			<view class="nav-back" @click="handleBack">
				<u-icon name="arrow-left" color="#333" size="40"></u-icon>
			</view>
			<view class="nav-title">
				<text class="nav-title-text">{{ pageTitle }}</text>
			</view>
			<view class="nav-placeholder"></view>
		</view>

		<!-- 广告内容区 -->
		<view class="ad-content">
			<!-- 有 adpid：显示原生广告组件 -->
			<view class="ad-native" v-if="adpid">
				<!-- #ifdef APP-PLUS || MP-WEIXIN || MP -->
				<view v-if="adType === 'reward'">
					<ad-rewarded-video
						ref="adRewardedVideo"
						:adpid="adpid"
						:loadnext="false"
						:preload="false"
						:disabled="true"
						v-slot:default="{ loading: adLoading, error: adError }"
						@load="onNativeAdLoad"
						@close="onNativeRewardedClose"
						@error="onNativeAdError">
					</ad-rewarded-video>
					<view v-if="adError" class="ad-fallback">
						<view class="fallback-card">
							<text class="fallback-icon">📺</text>
							<text class="fallback-text">广告加载中，请耐心等待...</text>
						</view>
					</view>
				</view>
				<view v-else>
					<ad :adpid="adpid" @load="onNativeAdLoad" @error="onNativeAdError" style="width: 100%; min-height: 300px;"></ad>
				</view>
				<!-- #endif -->

				<!-- #ifdef H5 -->
				<!-- H5 环境：显示模拟广告内容 -->
				<view class="ad-simulated">
					<view class="simulated-banner">
						<text class="sim-badge">广告</text>
						<text class="sim-title">{{ adType === 'reward' ? '推荐视频' : '精选推荐' }}</text>
					</view>
					<view class="simulated-body">
						<view class="sim-image-area">
							<text class="sim-image-icon">{{ adType === 'reward' ? '🎬' : '📱' }}</text>
							<text class="sim-image-text">{{ adType === 'reward' ? '精彩视频内容' : '精选好物推荐' }}</text>
						</view>
						<view class="sim-desc">
							<text class="sim-desc-text">广告内容加载中，请在此页面停留观看...</text>
						</view>
					</view>
				</view>
				<!-- #endif -->
			</view>

			<!-- 无 adpid：纯模拟广告 -->
			<view class="ad-simulated" v-else>
				<view class="simulated-banner">
					<text class="sim-badge">广告</text>
					<text class="sim-title">{{ adType === 'reward' ? '推荐视频' : '精选推荐' }}</text>
				</view>
				<view class="simulated-body">
					<view class="sim-image-area">
						<text class="sim-image-icon">{{ adType === 'reward' ? '🎬' : '📱' }}</text>
						<text class="sim-image-text">{{ adType === 'reward' ? '精彩视频内容' : '精选好物推荐' }}</text>
					</view>
					<view class="sim-desc">
						<text class="sim-desc-text">请在此页面停留观看广告内容...</text>
					</view>
				</view>
			</view>
		</view>

		<!-- 倒计时区域（固定在底部） -->
		<view class="countdown-panel">
			<!-- 进度条 -->
			<view class="countdown-progress">
				<view class="progress-track">
					<view class="progress-fill" :style="{ width: progressPercent + '%' }"></view>
				</view>
			</view>

			<!-- 信息区 -->
			<view class="countdown-info">
				<view class="countdown-left">
					<view class="countdown-circle" :class="{ 'circle-done': watchDone }">
						<text class="countdown-number">{{ watchDone ? '✓' : watchRemaining }}</text>
					</view>
					<view class="countdown-text-area">
						<text class="countdown-main-text" v-if="!watchDone">
							请观看广告 {{ watchRemaining }}秒后领取奖励
						</text>
						<text class="countdown-main-text text-done" v-else>
							观看完成，快来领取奖励吧！
						</text>
						<text class="countdown-sub-text">
							奖励：+{{ rewardCoin }} 金币
						</text>
					</view>
				</view>
			</view>

			<!-- 操作按钮 -->
			<view class="countdown-actions">
				<!-- 未完成：灰色返回按钮 + 提示 -->
				<view class="action-btn action-back" @click="handleBack" v-if="!watchDone && !claiming">
					<text class="action-text">返回（未完成，无奖励）</text>
				</view>
				<!-- 已完成：领取奖励按钮 -->
				<view class="action-btn action-claim" @click="claimReward" v-else-if="watchDone && !claimed">
					<text class="action-text">领取 +{{ rewardCoin }} 金币</text>
				</view>
				<!-- 已领取：返回按钮 -->
				<view class="action-btn action-done" @click="goBack" v-else-if="claimed">
					<text class="action-text">返回红包群</text>
				</view>
				<!-- 领取中 -->
				<view class="action-btn action-claiming" v-else-if="claiming">
					<text class="action-text">领取中...</text>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
export default {
	data() {
		return {
			adType: 'reward',       // 'feed' 或 'reward'
			adpid: '',
			rewardCoin: 200,
			watchSeconds: 30,       // 需要观看的总秒数
			watchRemaining: 30,     // 剩余秒数
			watchDone: false,       // 观看是否完成
			claimed: false,         // 是否已领取奖励
			claiming: false,        // 领取中
			watchTimer: null,       // 倒计时定时器
			nativeAdLoaded: false,  // 原生广告是否加载
			nativeRewardedEnded: false, // 原生激励视频是否播放完
			msgId: '',              // 原消息ID（用于返回后通知父页面）
		};
	},

	computed: {
		pageTitle() {
			return this.adType === 'reward' ? '观看视频赚金币' : '浏览广告赚金币';
		},
		progressPercent() {
			if (this.watchSeconds <= 0) return 100;
			const elapsed = this.watchSeconds - this.watchRemaining;
			return Math.min(100, Math.round((elapsed / this.watchSeconds) * 100));
		}
	},

	onLoad(options) {
		// 从 URL 参数读取配置
		this.adType = options.type || 'reward';
		this.adpid = options.adpid || '';
		this.rewardCoin = parseInt(options.rewardCoin) || (this.adType === 'reward' ? 200 : 50);
		this.watchSeconds = parseInt(options.watchSeconds) || 30;
		this.watchRemaining = this.watchSeconds;
		this.msgId = options.msgId || '';

		// 禁止页面返回手势（强制停留观看）
		// #ifdef APP-PLUS
		try {
			const webview = this.$scope.$getAppWebview();
			if (webview) webview.setStyle({ 'popGesture': 'none' });
		} catch (e) {}
		// #endif

		// 启动倒计时
		this.startCountdown();
	},

	onUnload() {
		this.clearWatchTimer();
	},

	// 拦截物理返回键（Android）
	onBackPress() {
		if (!this.watchDone && !this.claimed) {
			uni.showModal({
				title: '提示',
				content: '观看不足' + this.watchSeconds + '秒，现在返回将无法获得奖励。确定返回吗？',
				confirmText: '确定返回',
				cancelText: '继续观看',
				success: (res) => {
					if (res.confirm) {
						this.notifyParent(false);
						uni.navigateBack();
					}
				}
			});
			return true; // 阻止默认返回
		}
		if (this.watchDone && !this.claimed) {
			uni.showToast({ title: '请先领取奖励', icon: 'none' });
			return true;
		}
		return false;
	},

	methods: {
		// ==================== 倒计时 ====================

		startCountdown() {
			console.log('[AdWatch] 开始倒计时, 总时长=' + this.watchSeconds + '秒, 类型=' + this.adType);
			this.watchTimer = setInterval(() => {
				this.watchRemaining--;
				if (this.watchRemaining <= 0) {
					this.watchRemaining = 0;
					this.watchDone = true;
					this.clearWatchTimer();
					uni.vibrateShort({ type: 'medium' });
					uni.showToast({ title: '观看完成！', icon: 'none', duration: 1500 });
				}
			}, 1000);
		},

		clearWatchTimer() {
			if (this.watchTimer) {
				clearInterval(this.watchTimer);
				this.watchTimer = null;
			}
		},

		// ==================== 原生广告回调 ====================

		onNativeAdLoad() {
			console.log('[AdWatch] 原生广告加载成功');
			this.nativeAdLoaded = true;
		},

		onNativeAdError(err) {
			console.warn('[AdWatch] 原生广告错误:', err);
		},

		onNativeRewardedClose(res) {
			console.log('[AdWatch] 原生激励视频关闭, isEnded=', res && res.isEnded);
			if (res && res.isEnded) {
				this.nativeRewardedEnded = true;
			}
		},

		// ==================== 领取奖励 ====================

		async claimReward() {
			if (!this.watchDone || this.claimed || this.claiming) return;
			this.claiming = true;

			try {
				const transactionId = (this.adType === 'reward' ? 'rv_' : 'af_') + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
				const res = await this.$api.adCallback({
					ad_type: this.adType === 'reward' ? 'reward' : 'feed',
					adpid: this.adpid,
					ad_provider: 'uniad',
					ad_source: this.adType === 'reward' ? 'redbag_page_video' : 'redbag_page',
					transaction_id: transactionId,
				});

				if (res && res.code === 1 && res.data) {
					this.claimed = true;
					const amount = res.data.user_amount_coin || this.rewardCoin;
					uni.showToast({ title: '🎉 获得 +' + amount + ' 金币', icon: 'none', duration: 2500 });

					// 通知父页面
					this.notifyParent(true, amount);
				} else {
					this.claiming = false;
					const msg = (res && res.msg) || '奖励领取失败';
					if (msg !== '重复回调') {
						uni.showToast({ title: msg, icon: 'none' });
					} else {
						this.claimed = true;
						this.notifyParent(true, this.rewardCoin);
					}
				}
			} catch (e) {
				this.claiming = false;
				console.error('[AdWatch] 领取失败:', e);
				uni.showToast({ title: '网络异常，请重试', icon: 'none' });
			}
		},

		/**
		 * 通知父页面（红包群页面）广告观看结果
		 * 使用 uni.$emit 事件总线
		 */
		notifyParent(success, amount) {
			uni.$emit('ad-watch-result', {
				msgId: this.msgId,
				adType: this.adType,
				success: success,
				amount: amount || this.rewardCoin,
				adpid: this.adpid,
			});
		},

		// ==================== 返回 ====================

		handleBack() {
			if (!this.watchDone && !this.claimed) {
				uni.showModal({
					title: '提示',
					content: '观看不足' + this.watchSeconds + '秒，现在返回将无法获得奖励。确定返回吗？',
					confirmText: '确定返回',
					cancelText: '继续观看',
					success: (res) => {
						if (res.confirm) {
							this.notifyParent(false);
							this.clearWatchTimer();
							uni.navigateBack();
						}
					}
				});
			} else if (this.watchDone && !this.claimed) {
				uni.showToast({ title: '请先领取奖励', icon: 'none' });
			} else {
				this.goBack();
			}
		},

		goBack() {
			// 恢复返回手势
			// #ifdef APP-PLUS
			try {
				const webview = this.$scope.$getAppWebview();
				if (webview) webview.setStyle({ 'popGesture': 'close' });
			} catch (e) {}
			// #endif
			uni.navigateBack();
		}
	}
}
</script>

<style lang="scss" scoped>
.ad-watch-page {
	min-height: 100vh;
	background: #f5f5f5;
	display: flex;
	flex-direction: column;
}

/* 导航栏 */
.nav-bar {
	display: flex;
	align-items: center;
	height: 88rpx;
	padding: 0 20rpx;
	background: #fff;
	border-bottom: 1rpx solid #eee;
}

.nav-back {
	width: 60rpx;
}

.nav-title {
	flex: 1;
	text-align: center;
}

.nav-title-text {
	font-size: 32rpx;
	font-weight: bold;
	color: #333;
}

.nav-placeholder {
	width: 60rpx;
}

/* 广告内容区 */
.ad-content {
	flex: 1;
	display: flex;
	flex-direction: column;
}

.ad-native {
	flex: 1;
}

/* H5 模拟广告 */
.ad-simulated {
	flex: 1;
	display: flex;
	flex-direction: column;
}

.simulated-banner {
	display: flex;
	align-items: center;
	padding: 20rpx 30rpx;
	background: #fff;
	border-bottom: 1rpx solid #f0f0f0;
}

.sim-badge {
	background: linear-gradient(135deg, #ff9500, #ff6b00);
	color: #fff;
	font-size: 20rpx;
	font-weight: bold;
	padding: 4rpx 14rpx;
	border-radius: 6rpx;
	margin-right: 16rpx;
}

.sim-title {
	font-size: 28rpx;
	color: #333;
	font-weight: 600;
}

.simulated-body {
	flex: 1;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 60rpx 40rpx;
}

.sim-image-area {
	width: 100%;
	max-width: 500rpx;
	height: 500rpx;
	background: linear-gradient(145deg, #f8f8f8, #e8e8e8);
	border-radius: 24rpx;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	margin-bottom: 40rpx;
}

.sim-image-icon {
	font-size: 120rpx;
	margin-bottom: 20rpx;
}

.sim-image-text {
	font-size: 30rpx;
	color: #999;
}

.sim-desc {
	padding: 24rpx 40rpx;
	background: #fff8f0;
	border-radius: 16rpx;
	border: 1rpx solid #ffe8d0;
}

.sim-desc-text {
	font-size: 26rpx;
	color: #ff9500;
	text-align: center;
}

/* 原生广告加载失败回退 */
.ad-fallback {
	padding: 60rpx 30rpx;
}

.fallback-card {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 80rpx 40rpx;
	background: #f8f8f8;
	border-radius: 20rpx;
}

.fallback-icon {
	font-size: 80rpx;
	margin-bottom: 20rpx;
}

.fallback-text {
	font-size: 28rpx;
	color: #999;
}

/* ==================== 底部倒计时面板 ==================== */
.countdown-panel {
	background: #fff;
	border-top: 1rpx solid #eee;
	padding-bottom: env(safe-area-inset-bottom);
}

/* 进度条 */
.countdown-progress {
	padding: 24rpx 30rpx 0;
}

.progress-track {
	width: 100%;
	height: 10rpx;
	background: #f0f0f0;
	border-radius: 5rpx;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #ff9500, #ff3838);
	border-radius: 5rpx;
	transition: width 1s linear;
}

/* 信息区 */
.countdown-info {
	padding: 24rpx 30rpx;
}

.countdown-left {
	display: flex;
	align-items: center;
}

.countdown-circle {
	width: 96rpx;
	height: 96rpx;
	border-radius: 50%;
	background: linear-gradient(135deg, #ff6b35, #ff3838);
	display: flex;
	align-items: center;
	justify-content: center;
	margin-right: 24rpx;
	flex-shrink: 0;
	box-shadow: 0 4rpx 16rpx rgba(255, 56, 56, 0.3);
}

.countdown-circle.circle-done {
	background: linear-gradient(135deg, #52c41a, #389e0d);
	box-shadow: 0 4rpx 16rpx rgba(82, 196, 26, 0.3);
}

.countdown-number {
	font-size: 40rpx;
	color: #fff;
	font-weight: 800;
	font-variant-numeric: tabular-nums;
}

.countdown-text-area {
	display: flex;
	flex-direction: column;
}

.countdown-main-text {
	font-size: 28rpx;
	color: #333;
	font-weight: 600;
	margin-bottom: 6rpx;
}

.countdown-main-text.text-done {
	color: #52c41a;
}

.countdown-sub-text {
	font-size: 24rpx;
	color: #ff6b35;
	font-weight: 500;
}

/* 操作按钮 */
.countdown-actions {
	padding: 16rpx 30rpx 28rpx;
}

.action-btn {
	height: 88rpx;
	border-radius: 44rpx;
	display: flex;
	align-items: center;
	justify-content: center;
}

.action-text {
	font-size: 30rpx;
	font-weight: 700;
}

.action-back {
	background: #f5f5f5;
}

.action-back .action-text {
	color: #999;
}

.action-claim {
	background: linear-gradient(135deg, #ff6b35, #ff3838);
	box-shadow: 0 6rpx 20rpx rgba(255, 56, 56, 0.3);
}

.action-claim .action-text {
	color: #fff;
}

.action-done {
	background: linear-gradient(135deg, #52c41a, #389e0d);
}

.action-done .action-text {
	color: #fff;
}

.action-claiming {
	background: #ddd;
}

.action-claiming .action-text {
	color: #999;
}
</style>

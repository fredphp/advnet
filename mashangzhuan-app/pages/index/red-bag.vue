<template>
	<view class="page-content">
		<!-- 顶部导航栏 -->
		<view class="custom-navbar">
			<view class="navbar-content">
				<view class="back-btn" @click="goBack">
					<u-icon name="arrow-left" color="#333" size="40"></u-icon>
				</view>
				<view class="group-info">
					<text class="group-name">赚金币</text>
				</view>
				<view class="right-btns">
					<!-- 广告红包入口 -->
					<view class="ad-packet-btn" @click="toggleAdPacketPanel">
						<text class="ad-packet-icon">🧧</text>
						<view class="ad-badge" v-if="adPacketBadge > 0">
							<text class="ad-badge-text">{{ adPacketBadge > 99 ? '99+' : adPacketBadge }}</text>
						</view>
					</view>
					<!-- 提现入口 -->
					<view class="withdraw-btn" @click="goWithdraw">
						<image class="redbag-icon" src="/static/image/redbag-icon.png" mode="aspectFit"></image>
						<text class="withdraw-text">提现</text>
					</view>
				</view>
			</view>
		</view>

		<!-- Banner 广告 -->
		<view class="ad-section">
			<ad-banner></ad-banner>
		</view>

		<!-- 收益概览卡片 -->
		<view class="income-card">
			<!-- 收益统计 -->
			<view class="income-stats">
				<view class="stat-item">
					<text class="stat-label">今日收益</text>
					<text class="stat-value accent">+{{ overview.today_income || 0 }}</text>
					<text class="stat-unit">金币</text>
				</view>
				<view class="stat-divider"></view>
				<view class="stat-item">
					<text class="stat-label">累计收益</text>
					<text class="stat-value">{{ overview.total_ad_income || 0 }}</text>
					<text class="stat-unit">金币</text>
				</view>
			</view>

			<!-- 可释放余额进度条 -->
			<view class="threshold-section">
				<view class="threshold-header">
					<text class="threshold-label">可释放余额</text>
					<text class="threshold-value">{{ overview.ad_freeze_balance || 0 }} / {{ overview.redpacket_threshold || 1000 }} 金币</text>
				</view>
				<view class="progress-bar">
					<view class="progress-fill" :style="{width: freezeProgress + '%'}"></view>
				</view>
				<text class="threshold-hint reached" v-if="freezeProgress >= 100">
					🎉 已达标！红包已生成，请点击下方领取
				</text>
				<text class="threshold-hint" v-else>
					再获得 {{ remainingToThreshold }} 金币即可生成红包
				</text>
			</view>

			<!-- 未领取红包提示 -->
			<view class="unclaimed-row" v-if="overview.unclaimed_packet_count > 0" @click="toggleAdPacketPanel">
				<view class="unclaimed-left">
					<text class="unclaimed-icon">🧧</text>
					<text class="unclaimed-info">
						{{ overview.unclaimed_packet_count }}个红包待领取，共{{ overview.unclaimed_packet_amount || 0 }}金币
					</text>
				</view>
				<text class="unclaimed-arrow">›</text>
			</view>
		</view>

		<!-- 信息流广告列表 -->
		<scroll-view class="feed-scroll" scroll-y :style="{height: scrollHeight + 'px'}" @scrolltolower="loadMoreFeedAds">
			<view v-for="(item, index) in feedSlots" :key="item.id" class="feed-item">
				<!-- 广告标题栏 -->
				<view class="feed-header">
					<view class="feed-badge-wrap">
						<text class="feed-badge-text">广告</text>
					</view>
					<text class="feed-title">观看广告赚金币</text>
				</view>

				<!-- 广告区域 -->
				<view class="feed-ad-area">
					<!-- #ifdef APP-PLUS || MP-WEIXIN || MP -->
					<ad v-if="feedAdpid"
						:adpid="feedAdpid"
						@load="onAdLoad($event, index)"
						@error="onAdError($event, index)"
						@close="onAdClose($event, index)"
						style="width: 100%; min-height: 120px;" />
					<!-- #endif -->

					<!-- #ifdef H5 -->
					<view class="feed-ad-h5" @click="handleH5AdClick(index)">
						<view class="h5-content">
							<text class="h5-icon">🎁</text>
							<text class="h5-text">点击观看广告赚金币</text>
							<text class="h5-reward">+{{ overview.reward_per_feed || 50 }} 金币</text>
						</view>
					</view>
					<!-- #endif -->

					<!-- 未配置广告位ID -->
					<view v-if="!feedAdpid" class="feed-ad-empty">
						<text class="empty-text">请配置信息流广告位ID (adpid)</text>
					</view>
				</view>

				<!-- 广告状态提示 -->
				<view class="feed-status-bar" v-if="item.status">
					<text :class="['feed-status-text', item.statusType]">{{ item.status }}</text>
				</view>
			</view>

			<!-- 加载更多 -->
			<view class="load-more-area" v-if="canLoadMore" @click="loadMoreFeedAds">
				<text class="load-more-text">加载更多广告</text>
			</view>
			<view class="load-more-area" v-else>
				<text class="load-more-text no-more">— 没有更多了 —</text>
			</view>
		</scroll-view>

		<!-- 广告红包面板（底部弹窗） -->
		<view class="panel-mask" v-if="showAdPacketPanel" @click="showAdPacketPanel = false">
			<view class="panel-popup" @click.stop>
				<view class="panel-header">
					<text class="panel-title">广告红包</text>
					<view class="panel-close" @click="showAdPacketPanel = false">
						<u-icon name="close" color="#999" size="36"></u-icon>
					</view>
				</view>
				<!-- 一键领取按钮 -->
				<view class="claim-all-bar" v-if="adPacketBadge > 0">
					<view class="claim-all-btn" @click="claimAllPackets">
						<text class="claim-all-text">一键领取全部 ({{ adPacketBadge }}个)</text>
					</view>
				</view>
				<ad-red-packet-list
					:list-height="panelHeight"
					@claimed="onAdPacketClaimed" />
			</view>
		</view>

		<!-- 底部导航 -->
		<fa-tabbar></fa-tabbar>
	</view>
</template>

<script>
import AdBanner from '@/components/ad/adBanner.vue'
import AdRedPacketList from '@/components/ad/adRedPacketList.vue'

export default {
	components: {
		AdBanner,
		AdRedPacketList,
	},

	onLoad(opt) {
		this.loadAdOverview()
		this.initFeedSlots()
	},

	onShow() {
		this.loadAdOverview()
	},

	onUnload() {
		this.clearH5Timers()
	},

	data() {
		return {
			// 广告收益概览数据
			overview: {
				today_income: 0,
				total_ad_income: 0,
				ad_freeze_balance: 0,
				unclaimed_packet_count: 0,
				unclaimed_packet_amount: 0,
				redpacket_threshold: 1000,
				reward_per_feed: 50,
			},

			// 广告红包面板
			showAdPacketPanel: false,
			adPacketBadge: 0,
			panelHeight: 300,

			// 页面布局
			scrollHeight: 0,

			// ==================== 信息流广告配置 ====================
			// ★★★ 重要：请将下方 feedAdpid 替换为你在 DCloud 广告平台创建的「信息流广告」广告位ID ★★★
			// 获取方式：登录 https://uniad.dcloud.net.cn/ → 创建广告位 → 信息流广告 → 复制广告位ID
			feedAdpid: '',

			// 广告槽位列表
			feedSlots: [],
			feedSlotCounter: 0,
			maxFeedSlots: 10,
			initialSlotCount: 3,

			// H5 模拟广告定时器
			h5Timers: {},
		};
	},

	computed: {
		/**
		 * 可释放余额达到红包基数的百分比
		 */
		freezeProgress() {
			const threshold = this.overview.redpacket_threshold || 1000
			const balance = this.overview.ad_freeze_balance || 0
			return Math.min(100, Math.round(balance / threshold * 100))
		},

		/**
		 * 距离红包基数还差多少金币
		 */
		remainingToThreshold() {
			const threshold = this.overview.redpacket_threshold || 1000
			const balance = this.overview.ad_freeze_balance || 0
			return Math.max(0, threshold - balance)
		},

		/**
		 * 是否还能加载更多广告
		 */
		canLoadMore() {
			return this.feedSlots.length < this.maxFeedSlots
		},
	},

	mounted() {
		this.$nextTick(() => {
			this.calcScrollHeight()
		})
	},

	methods: {
		// ==================== 数据加载 ====================

		/**
		 * 加载广告收益概览
		 * 包含：今日收益、累计收益、可释放余额、红包基数、未领取红包数
		 */
		async loadAdOverview() {
			try {
				const res = await this.$api.adOverview({})
				if (res && res.code === 1 && res.data) {
					const d = res.data
					this.overview = {
						today_income: d.today_income || 0,
						total_ad_income: d.total_ad_income || 0,
						ad_freeze_balance: d.ad_freeze_balance || 0,
						unclaimed_packet_count: d.unclaimed_packet_count || 0,
						unclaimed_packet_amount: d.unclaimed_packet_amount || 0,
						redpacket_threshold: d.redpacket_threshold || 1000,
						reward_per_feed: d.reward_per_feed || 50,
					}
					this.adPacketBadge = this.overview.unclaimed_packet_count

					// 如果刚刚自动生成了红包，显示提示
					if (d.redpacket_just_created) {
						uni.showToast({
							title: '🎉 红包已生成！快去领取',
							icon: 'none',
							duration: 3000
						})
					}
				}
			} catch (e) {
				console.warn('[RedBag] loadAdOverview failed:', e)
			}
		},

		// ==================== 信息流广告槽位管理 ====================

		/**
		 * 初始化广告槽位
		 */
		initFeedSlots() {
			this.feedSlots = []
			this.feedSlotCounter = 0
			for (let i = 0; i < this.initialSlotCount; i++) {
				this.addFeedSlot()
			}
		},

		/**
		 * 添加一个广告槽位
		 */
		addFeedSlot() {
			if (this.feedSlots.length >= this.maxFeedSlots) return
			this.feedSlotCounter++
			this.feedSlots.push({
				id: 'slot_' + this.feedSlotCounter + '_' + Date.now(),
				status: '',
				statusType: '',
				reported: false,
			})
		},

		/**
		 * 加载更多广告
		 */
		loadMoreFeedAds() {
			if (!this.canLoadMore) return
			for (let i = 0; i < 2; i++) {
				this.addFeedSlot()
			}
		},

		// ==================== uni-ad 广告事件 ====================

		/**
		 * 广告加载成功
		 */
		onAdLoad(e, index) {
			console.log('[RedBag] 广告加载成功, slot:', index)
			const slot = this.feedSlots[index]
			if (slot) {
				this.$set(slot, 'status', '广告已展示')
				this.$set(slot, 'statusType', 'success')
			}
		},

		/**
		 * 广告关闭 → 上报收益
		 */
		onAdClose(e, index) {
			console.log('[RedBag] 广告关闭, slot:', index)
			this.reportAdReward(index)
		},

		/**
		 * 广告加载失败
		 */
		onAdError(e, index) {
			console.warn('[RedBag] 广告加载失败, slot:', index, e)
			const slot = this.feedSlots[index]
			if (slot) {
				this.$set(slot, 'status', '广告加载失败，请稍后再试')
				this.$set(slot, 'statusType', 'error')
			}
		},

		/**
		 * H5 环境下点击模拟广告
		 */
		handleH5AdClick(index) {
			const slot = this.feedSlots[index]
			if (!slot || slot.reported) {
				if (slot && slot.reported) {
					uni.showToast({ title: '已获得奖励', icon: 'none' })
				}
				return
			}

			this.$set(slot, 'status', '观看广告中...')
			this.$set(slot, 'statusType', 'loading')

			const timer = setTimeout(() => {
				this.reportAdReward(index)
			}, 2000)
			this.h5Timers[index] = timer
		},

		/**
		 * 上报广告收益到后端
		 * 后端处理流程：
		 *   1. 记录广告收益日志
		 *   2. 增加用户可释放余额 (ad_freeze_balance)
		 *   3. 检查可释放余额是否 >= 红包基数 (redpacket_threshold)
		 *   4. 若达标 → 自动生成红包 (AdRedPacket) → 清空可释放余额
		 *   5. 用户点击领取红包 → 金币进入余额 (balance)
		 */
		async reportAdReward(index) {
			const slot = this.feedSlots[index]
			if (!slot || slot.reported) return

			slot.reported = true
			this.$set(slot, 'status', '收益结算中...')
			this.$set(slot, 'statusType', 'loading')

			try {
				const transactionId = 'ad_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)

				const res = await this.$api.adCallback({
					ad_type: 'feed',
					adpid: this.feedAdpid || '',
					ad_provider: 'uniad',
					ad_source: 'redbag_page',
					transaction_id: transactionId,
				})

				if (res && res.code === 1 && res.data) {
					const amount = res.data.user_amount_coin || 0
					this.$set(slot, 'status', '✅ 获得 +' + amount + ' 金币')
					this.$set(slot, 'statusType', 'success')

					if (amount > 0) {
						uni.showToast({
							title: '获得 +' + amount + ' 金币',
							icon: 'none',
							duration: 2000
						})
					}

					// 后端自动生成了红包
					if (res.data.redpacket_created) {
						setTimeout(() => {
							uni.showToast({
								title: '🎉 红包已生成！' + (res.data.redpacket_amount || '') + '金币',
								icon: 'none',
								duration: 3000
							})
						}, 2200)
					}

					// 刷新概览数据
					this.loadAdOverview()
				} else {
					slot.reported = false
					const msg = (res && res.msg) || '奖励获取失败'
					if (msg === '重复回调') {
						this.$set(slot, 'status', '✅ 已记录')
						this.$set(slot, 'statusType', 'success')
						slot.reported = true
					} else {
						this.$set(slot, 'status', msg)
						this.$set(slot, 'statusType', 'error')
					}
				}
			} catch (e) {
				slot.reported = false
				this.$set(slot, 'status', '网络异常，请重试')
				this.$set(slot, 'statusType', 'error')
				console.error('[RedBag] reportAdReward failed:', e)
			}
		},

		// ==================== 广告红包面板 ====================

		toggleAdPacketPanel() {
			this.showAdPacketPanel = !this.showAdPacketPanel
			if (this.showAdPacketPanel) {
				this.adPacketBadge = 0
			}
		},

		async claimAllPackets() {
			try {
				uni.showLoading({ title: '领取中...', mask: true })
				const res = await this.$api.adRedpacketClaimAll({})
				uni.hideLoading()

				if (res && res.code === 1) {
					const total = res.data.total_amount || 0
					const count = res.data.claim_count || 0
					if (count > 0) {
						uni.showToast({
							title: '成功领取' + count + '个红包，+' + total + '金币',
							icon: 'none',
							duration: 3000
						})
						this.adPacketBadge = 0
						this.loadAdOverview()
					}
				} else {
					uni.showToast({ title: (res && res.msg) || '领取失败', icon: 'none' })
				}
			} catch (e) {
				uni.hideLoading()
				console.error('[RedBag] claimAllPackets failed:', e)
			}
		},

		onAdPacketClaimed(data) {
			console.log('[RedBag] 广告红包领取成功:', JSON.stringify(data))
			if (data && data.amount > 0) {
				uni.showToast({
					title: '领取成功 +' + data.amount + ' 金币',
					icon: 'none',
					duration: 2000
				})
				this.loadAdOverview()
			}
		},

		// ==================== 辅助方法 ====================

		goBack() {
			uni.navigateBack()
		},

		goWithdraw() {
			uni.navigateTo({ url: '/pages/my/withdraw/index' })
		},

		calcScrollHeight() {
			const sysInfo = uni.getSystemInfoSync()
			const navH = 88
			const adH = 220
			const cardH = 320
			const tabH = 100
			const statusBarH = sysInfo.statusBarHeight || 0
			this.scrollHeight = sysInfo.windowHeight - navH - adH - cardH - tabH - statusBarH
			if (this.scrollHeight < 200) {
				this.scrollHeight = 200
			}
		},

		clearH5Timers() {
			Object.keys(this.h5Timers).forEach(key => {
				if (this.h5Timers[key]) {
					clearTimeout(this.h5Timers[key])
				}
			})
			this.h5Timers = {}
		},
	}
}
</script>

<style lang="scss" scoped>
.page-content {
	min-height: 100vh;
	background: #f5f5f5;
	display: flex;
	flex-direction: column;
}

/* ==================== 导航栏 ==================== */
.custom-navbar {
	height: 88rpx;
	background: #fff;
	display: flex;
	align-items: center;
	padding: 0 20rpx;
	border-bottom: 1rpx solid #eee;
	flex-shrink: 0;
}

.navbar-content {
	display: flex;
	align-items: center;
	width: 100%;
}

.back-btn {
	width: 60rpx;
}

.group-info {
	flex: 1;
	text-align: center;
}

.group-name {
	font-size: 32rpx;
	font-weight: bold;
	color: #333;
}

.right-btns {
	display: flex;
	align-items: center;
}

.ad-packet-btn {
	position: relative;
	padding: 10rpx 20rpx;
}

.ad-packet-icon {
	font-size: 40rpx;
}

.ad-badge {
	position: absolute;
	top: -5rpx;
	right: 5rpx;
	background: #ff4d4f;
	border-radius: 20rpx;
	min-width: 30rpx;
	height: 30rpx;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 0 8rpx;
}

.ad-badge-text {
	color: #fff;
	font-size: 20rpx;
}

.withdraw-btn {
	display: flex;
	align-items: center;
	padding: 10rpx 20rpx;
	margin-left: 10rpx;
}

.redbag-icon {
	width: 40rpx;
	height: 40rpx;
}

.withdraw-text {
	font-size: 26rpx;
	color: #ff6b35;
	margin-left: 8rpx;
}

/* ==================== Banner 广告 ==================== */
.ad-section {
	padding: 16rpx 20rpx;
	flex-shrink: 0;
}

/* ==================== 收益概览卡片 ==================== */
.income-card {
	margin: 0 20rpx 20rpx;
	background: #fff;
	border-radius: 16rpx;
	padding: 30rpx;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
	flex-shrink: 0;
}

.income-stats {
	display: flex;
	justify-content: space-around;
	margin-bottom: 30rpx;
}

.stat-item {
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
}

.stat-label {
	font-size: 24rpx;
	color: #999;
	margin-bottom: 8rpx;
}

.stat-value {
	font-size: 44rpx;
	font-weight: bold;
	color: #333;
	line-height: 1.2;
}

.stat-value.accent {
	color: #ff6b35;
}

.stat-unit {
	font-size: 22rpx;
	color: #bbb;
	margin-top: 4rpx;
}

.stat-divider {
	width: 1rpx;
	background: #eee;
	align-self: stretch;
}

/* 红包基数进度条 */
.threshold-section {
	margin-bottom: 20rpx;
}

.threshold-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 12rpx;
}

.threshold-label {
	font-size: 26rpx;
	color: #666;
	font-weight: 500;
}

.threshold-value {
	font-size: 24rpx;
	color: #999;
}

.progress-bar {
	height: 16rpx;
	background: #f0f0f0;
	border-radius: 8rpx;
	overflow: hidden;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #ff9500, #ff6b00);
	border-radius: 8rpx;
	transition: width 0.5s ease;
	min-width: 0;
}

.threshold-hint {
	font-size: 24rpx;
	color: #999;
	margin-top: 10rpx;
	display: block;
}

.threshold-hint.reached {
	color: #ff6b35;
	font-weight: 600;
}

/* 未领取红包提示 */
.unclaimed-row {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 20rpx 24rpx;
	background: linear-gradient(135deg, #fff8f0, #fff5eb);
	border-radius: 12rpx;
	border: 1rpx solid #ffe8d0;
}

.unclaimed-left {
	display: flex;
	align-items: center;
}

.unclaimed-icon {
	font-size: 36rpx;
	margin-right: 12rpx;
}

.unclaimed-info {
	font-size: 26rpx;
	color: #ff6b35;
	font-weight: 500;
}

.unclaimed-arrow {
	font-size: 36rpx;
	color: #ff6b35;
	font-weight: bold;
}

/* ==================== 信息流广告列表 ==================== */
.feed-scroll {
	flex: 1;
	padding: 0 20rpx 20rpx;
}

.feed-item {
	margin-bottom: 20rpx;
	background: #fff;
	border-radius: 16rpx;
	overflow: hidden;
	box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.06);
}

.feed-header {
	display: flex;
	align-items: center;
	padding: 16rpx 20rpx;
	background: #fff8f0;
	border-bottom: 1rpx solid #f0e6d8;
}

.feed-badge-wrap {
	background: linear-gradient(135deg, #ff9500, #ff6b00);
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
	margin-right: 12rpx;
}

.feed-badge-text {
	font-size: 20rpx;
	color: #fff;
	font-weight: bold;
}

.feed-title {
	font-size: 26rpx;
	color: #333;
	font-weight: 500;
}

.feed-ad-area {
	width: 100%;
	min-height: 120px;
}

/* H5 模拟广告 */
.feed-ad-h5 {
	width: 100%;
	padding: 40rpx 20rpx;
	background: linear-gradient(135deg, #ff9500, #ff6b00);
	display: flex;
	align-items: center;
	justify-content: center;
	cursor: pointer;
}

.h5-content {
	display: flex;
	flex-direction: column;
	align-items: center;
}

.h5-icon {
	font-size: 56rpx;
	margin-bottom: 12rpx;
}

.h5-text {
	font-size: 28rpx;
	color: #fff;
	margin-bottom: 8rpx;
}

.h5-reward {
	font-size: 32rpx;
	color: #ffd700;
	font-weight: bold;
}

/* 未配置广告位 */
.feed-ad-empty {
	padding: 40rpx 20rpx;
	text-align: center;
}

.empty-text {
	font-size: 24rpx;
	color: #ccc;
}

/* 广告状态提示 */
.feed-status-bar {
	padding: 16rpx 20rpx;
	text-align: center;
}

.feed-status-text {
	font-size: 24rpx;
}

.feed-status-text.success {
	color: #52c41a;
}

.feed-status-text.error {
	color: #999;
}

.feed-status-text.loading {
	color: #1890ff;
}

/* 加载更多 */
.load-more-area {
	text-align: center;
	padding: 30rpx 0;
}

.load-more-text {
	font-size: 26rpx;
	color: #999;
}

.load-more-text.no-more {
	color: #ccc;
}

/* ==================== 广告红包面板 ==================== */
.panel-mask {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.5);
	z-index: 999;
	display: flex;
	align-items: flex-end;
}

.panel-popup {
	width: 100%;
	background: #fff;
	border-radius: 24rpx 24rpx 0 0;
	max-height: 70vh;
	display: flex;
	flex-direction: column;
}

.panel-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
	flex-shrink: 0;
}

.panel-title {
	font-size: 32rpx;
	font-weight: bold;
}

.panel-close {
	padding: 10rpx;
}

.claim-all-bar {
	padding: 16rpx 30rpx;
	border-bottom: 1rpx solid #f0f0f0;
	flex-shrink: 0;
}

.claim-all-btn {
	background: linear-gradient(135deg, #ff6b35, #ff4d00);
	border-radius: 40rpx;
	padding: 16rpx 0;
	text-align: center;
}

.claim-all-text {
	color: #fff;
	font-size: 28rpx;
	font-weight: bold;
}
</style>

<template>
	<view class="ad-redpacket-panel">
		<!-- 顶部摘要 -->
		<view class="panel-header">
			<view class="summary-info">
				<text class="summary-title">广告红包</text>
				<text class="summary-count" v-if="summary.unclaimed_count > 0">
					{{ summary.unclaimed_count }} 个待领取
				</text>
			</view>
			<view class="summary-amount" v-if="summary.unclaimed_amount > 0">
				<text class="amount-label">待领取</text>
				<text class="amount-value">{{ summary.unclaimed_amount }} 金币</text>
			</view>
		</view>

		<!-- 待释放金币进度 -->
		<view class="freeze-progress-bar" v-if="overviewLoaded">
			<view class="freeze-progress-info">
				<text class="freeze-progress-label">待释放金币</text>
				<text class="freeze-progress-value">{{ freezeBalance }} / {{ redpacketThreshold }}</text>
			</view>
			<view class="freeze-progress-track">
				<view class="freeze-progress-fill" :style="{ width: freezePercent + '%' }"></view>
			</view>
		</view>

		<!-- 一键领取按钮 -->
		<view class="claim-all-bar" v-if="summary.unclaimed_count > 0">
			<view class="claim-all-btn" @click="claimAll">
				<text class="claim-all-text">一键领取全部 ({{ summary.unclaimed_count }} 个)</text>
			</view>
		</view>

		<!-- 红包列表 -->
		<scroll-view class="packet-list" scroll-y :style="{ height: listHeight + 'px' }"
			@scrolltolower="loadMore">
			<view class="empty-tip" v-if="list.length === 0 && !loading">
				<text class="empty-text">暂无广告红包</text>
				<text class="empty-hint">浏览广告即可获得红包奖励</text>
			</view>

			<view class="packet-item" v-for="(packet, index) in list" :key="packet.id"
				:class="{ claimed: packet.status === 1, expired: packet.status === 2 }">
				<!-- 红包图标 -->
				<view class="packet-icon-wrap">
					<text class="packet-icon">🧧</text>
				</view>

				<!-- 红包信息 -->
				<view class="packet-info">
					<view class="packet-main">
						<text class="packet-amount">{{ packet.amount }} 金币</text>
						<text class="packet-status"
							:class="{ 'status-unclaimed': packet.status === 0, 'status-claimed': packet.status === 1, 'status-expired': packet.status === 2 }">
							{{ statusText(packet.status) }}
						</text>
					</view>
					<text class="packet-time">{{ formatTime(packet.createtime) }}</text>
				</view>

				<!-- 操作按钮 -->
				<view class="packet-action" v-if="packet.status === 0">
					<view class="claim-btn" @click="claimPacket(packet)">
						<text class="claim-btn-text">领取</text>
					</view>
				</view>
				<view class="packet-action" v-else-if="packet.status === 1">
					<text class="done-icon">✅</text>
				</view>
				<view class="packet-action" v-else>
					<text class="expired-icon">⏰</text>
				</view>
			</view>

			<view class="loading-more" v-if="loading">
				<text>加载中...</text>
			</view>
			<view class="no-more" v-if="noMore && list.length > 0">
				<text>没有更多了</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
export default {
	name: 'AdRedPacketList',

	props: {
		listHeight: {
			type: Number,
			default: 300
		}
	},

	data() {
		return {
			list: [],
			loading: false,
			noMore: false,
			page: 1,
			limit: 20,
			summary: {
				unclaimed_count: 0,
				unclaimed_amount: 0,
			},
			freezeBalance: 0,
			redpacketThreshold: 1000,
			overviewLoaded: false,
			// 一键领取批量模式：观看一个广告后继续领取剩余
			batchClaiming: false,
			batchPacketIds: [],
			batchCurrentIndex: 0,
		};
	},

	computed: {
		freezePercent() {
			if (this.redpacketThreshold <= 0) return 100;
			return Math.min(100, Math.round((this.freezeBalance / this.redpacketThreshold) * 100));
		}
	},

	mounted() {
		this.loadSummary();
		this.loadList();
		// 监听广告观看结果
		uni.$on('ad-watch-result', this.onAdWatchResult);
	},

	beforeDestroy() {
		uni.$off('ad-watch-result', this.onAdWatchResult);
	},

	methods: {
		/**
		 * 加载摘要（含冻结余额信息）
		 */
		async loadSummary() {
			try {
				const res = await this.$api.adOverview({});
				if (res && res.code === 1 && res.data) {
					this.summary = {
						unclaimed_count: res.data.unclaimed_packet_count || 0,
						unclaimed_amount: Math.floor(res.data.unclaimed_packet_amount || 0),
					};
					this.freezeBalance = Math.floor(res.data.ad_freeze_balance || 0);
					this.redpacketThreshold = Math.floor(res.data.redpacket_threshold || 1000);
					this.overviewLoaded = true;
				}
			} catch (e) {
				console.error('[AdRedPacketList] 加载摘要失败:', e);
			}
		},

		/**
		 * 加载红包列表
		 */
		async loadList() {
			if (this.loading || this.noMore) return;

			this.loading = true;
			try {
				const res = await this.$api.adRedpacketList({
					page: this.page,
					limit: this.limit,
				});

				if (res && res.code === 1 && res.data) {
					const newList = res.data.list || [];

					if (this.page === 1) {
						this.list = newList;
					} else {
						this.list = this.list.concat(newList);
					}

					// 判断是否还有更多
					if (newList.length < this.limit) {
						this.noMore = true;
					}
				} else {
					if (this.page === 1) {
						this.list = [];
					}
					this.noMore = true;
				}
			} catch (e) {
				console.error('[AdRedPacketList] 加载列表失败:', e);
			} finally {
				this.loading = false;
			}
		},

		/**
		 * 加载更多
		 */
		loadMore() {
			if (this.noMore || this.loading) return;
			this.page++;
			this.loadList();
		},

		/**
		 * 领取单个红包 — 跳转到观看广告页面
		 */
		claimPacket(packet) {
			if (packet.status !== 0) return;
			this.navigateToWatchAd(packet);
		},

		/**
		 * 跳转到广告观看页面
		 */
		navigateToWatchAd(packet) {
			const params = {
				type: 'redpacket_claim',
				packet_id: packet.id,
				rewardCoin: packet.amount || 0,
				watchSeconds: 30,
				msgId: 'redpacket_' + packet.id,
			};
			const query = Object.keys(params).map(k => k + '=' + params[k]).join('&');
			uni.navigateTo({
				url: '/pages/ad/watch?' + query,
			});
		},

		/**
		 * 监听广告观看结果事件
		 */
		onAdWatchResult(eventData) {
			if (!eventData || eventData.adType !== 'redpacket_claim') return;

			if (eventData.success) {
				// 广告观看成功，后端已通过 claimWithAd 完成领取
				// 提取 packet_id 从 msgId
				const packetId = eventData.msgId ? eventData.msgId.replace('redpacket_', '') : '';
				if (packetId) {
					// 更新本地列表中对应红包的状态
					const packet = this.list.find(p => String(p.id) === String(packetId));
					if (packet) {
						this.$set(packet, 'status', 1);
					}
				}

				const amount = eventData.amount || 0;
				uni.showToast({
					title: '领取成功 +' + amount + ' 金币',
					icon: 'none',
					duration: 2000
				});

				// 更新摘要
				this.loadSummary();

				// 通知父组件
				this.$emit('claimed', {
					amount: amount,
					packetId: packetId,
				});

				// 批量领取模式：继续领取下一个
				if (this.batchClaiming) {
					this.continueBatchClaim();
				}
			}
			// 如果不成功，不做任何处理（用户未看完广告）
		},

		/**
		 * 一键领取 — 逐个跳转观看广告
		 */
		claimAll() {
			const unclaimed = this.list.filter(p => p.status === 0);
			if (unclaimed.length === 0) {
				uni.showToast({ title: '没有可领取的红包', icon: 'none' });
				return;
			}

			// 进入批量领取模式
			this.batchClaiming = true;
			this.batchPacketIds = unclaimed.map(p => p.id);
			this.batchCurrentIndex = 0;

			// 先去观看第一个红包的广告
			const firstPacket = unclaimed[0];
			this.navigateToWatchAd(firstPacket);
		},

		/**
		 * 批量领取：继续领取下一个
		 */
		continueBatchClaim() {
			this.batchCurrentIndex++;
			if (this.batchCurrentIndex >= this.batchPacketIds.length) {
				// 全部领取完毕
				this.batchClaiming = false;
				this.batchPacketIds = [];
				this.batchCurrentIndex = 0;
				// 刷新列表
				this.page = 1;
				this.noMore = false;
				this.loadList();
				return;
			}

			// 检查下一个是否还在列表中且未领取
			const nextId = this.batchPacketIds[this.batchCurrentIndex];
			const nextPacket = this.list.find(p => p.id === nextId && p.status === 0);
			if (nextPacket) {
				// 继续跳转观看广告
				this.navigateToWatchAd(nextPacket);
			} else {
				// 已领取或不存在，跳过
				this.continueBatchClaim();
			}
		},

		/**
		 * 刷新
		 */
		refresh() {
			this.page = 1;
			this.noMore = false;
			this.loadSummary();
			this.loadList();
		},

		statusText(status) {
			const map = { 0: '待领取', 1: '已领取', 2: '已过期' };
			return map[status] || '未知';
		},

		formatTime(timestamp) {
			if (!timestamp) return '';
			const date = new Date(timestamp * 1000);
			const month = (date.getMonth() + 1).toString().padStart(2, '0');
			const day = date.getDate().toString().padStart(2, '0');
			const hours = date.getHours().toString().padStart(2, '0');
			const minutes = date.getMinutes().toString().padStart(2, '0');
			return `${month}-${day} ${hours}:${minutes}`;
		}
	}
}
</script>

<style lang="scss" scoped>
.ad-redpacket-panel {
	background-color: #fff;
	border-radius: 16rpx;
	overflow: hidden;
}

.panel-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	padding: 24rpx 24rpx 16rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.summary-info {
	display: flex;
	align-items: center;
}

.summary-title {
	font-size: 32rpx;
	font-weight: bold;
	color: #333;
	margin-right: 12rpx;
}

.summary-count {
	font-size: 24rpx;
	color: #ff6b00;
	background-color: #fff3e6;
	padding: 4rpx 12rpx;
	border-radius: 20rpx;
}

.summary-amount {
	display: flex;
	align-items: baseline;
}

.amount-label {
	font-size: 24rpx;
	color: #999;
	margin-right: 8rpx;
}

.amount-value {
	font-size: 32rpx;
	font-weight: bold;
	color: #e74c3c;
}

/* 待释放金币进度条 */
.freeze-progress-bar {
	padding: 16rpx 24rpx;
	background: linear-gradient(135deg, #fff9f0, #fff3e6);
	border-bottom: 1rpx solid #ffe8d0;
}

.freeze-progress-info {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 10rpx;
}

.freeze-progress-label {
	font-size: 24rpx;
	color: #ff9500;
	font-weight: 500;
}

.freeze-progress-value {
	font-size: 24rpx;
	color: #ff6b00;
	font-weight: 600;
}

.freeze-progress-track {
	width: 100%;
	height: 8rpx;
	background: #ffe0b2;
	border-radius: 4rpx;
	overflow: hidden;
}

.freeze-progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #ff9500, #ff6b00);
	border-radius: 4rpx;
	transition: width 0.5s ease;
}

.claim-all-bar {
	padding: 16rpx 24rpx;
	border-bottom: 1rpx solid #f0f0f0;
}

.claim-all-btn {
	background: linear-gradient(135deg, #e74c3c, #c0392b);
	border-radius: 40rpx;
	padding: 16rpx 0;
	text-align: center;
}

.claim-all-text {
	font-size: 28rpx;
	color: #fff;
	font-weight: 500;
}

.packet-list {
	padding: 0 24rpx;
}

.empty-tip {
	display: flex;
	flex-direction: column;
	align-items: center;
	padding: 60rpx 0;
}

.empty-text {
	font-size: 28rpx;
	color: #999;
	margin-bottom: 12rpx;
}

.empty-hint {
	font-size: 24rpx;
	color: #ccc;
}

.packet-item {
	display: flex;
	align-items: center;
	padding: 24rpx 0;
	border-bottom: 1rpx solid #f5f5f5;

	&:last-child {
		border-bottom: none;
	}

	&.claimed {
		opacity: 0.6;
	}

	&.expired {
		opacity: 0.5;
	}
}

.packet-icon-wrap {
	margin-right: 20rpx;
}

.packet-icon {
	font-size: 48rpx;
}

.packet-info {
	flex: 1;
	display: flex;
	flex-direction: column;
}

.packet-main {
	display: flex;
	align-items: center;
	margin-bottom: 8rpx;
}

.packet-amount {
	font-size: 30rpx;
	font-weight: bold;
	color: #333;
	margin-right: 16rpx;
}

.packet-status {
	font-size: 22rpx;
	padding: 4rpx 12rpx;
	border-radius: 6rpx;
}

.status-unclaimed {
	color: #ff6b00;
	background-color: #fff3e6;
}

.status-claimed {
	color: #38a169;
	background-color: #f0fff4;
}

.status-expired {
	color: #999;
	background-color: #f5f5f5;
}

.packet-time {
	font-size: 22rpx;
	color: #bbb;
}

.packet-action {
	margin-left: 16rpx;
}

.claim-btn {
	background: linear-gradient(135deg, #e74c3c, #c0392b);
	border-radius: 30rpx;
	padding: 10rpx 30rpx;
}

.claim-btn-text {
	font-size: 24rpx;
	color: #fff;
}

.done-icon, .expired-icon {
	font-size: 36rpx;
}

.loading-more, .no-more {
	text-align: center;
	padding: 20rpx 0;
	font-size: 24rpx;
	color: #999;
}
</style>

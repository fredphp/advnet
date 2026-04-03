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
			}
		};
	},

	mounted() {
		this.loadSummary();
		this.loadList();
	},

	methods: {
		/**
		 * 加载摘要
		 */
		async loadSummary() {
			try {
				const res = await this.$api.adOverview({});
				if (res && res.code === 1 && res.data) {
					this.summary = {
						unclaimed_count: res.data.unclaimed_packet_count || 0,
						unclaimed_amount: Math.floor(res.data.unclaimed_packet_amount || 0),
					};
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
		 * 领取单个红包
		 */
		async claimPacket(packet) {
			if (packet.status !== 0) return;

			try {
				uni.showLoading({ title: '领取中...', mask: true });
				const res = await this.$api.adRedpacketClaim({ packet_id: packet.id });
				uni.hideLoading();

				if (res && res.code === 1) {
					// 更新本地状态
					this.$set(packet, 'status', 1);

					const amount = (res.data && res.data.amount) || 0;
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
						balance: (res.data && res.data.balance) || 0,
					});
				} else {
					uni.showToast({
						title: (res && res.msg) || '领取失败',
						icon: 'none'
					});
				}
			} catch (e) {
				uni.hideLoading();
				console.error('[AdRedPacketList] 领取红包失败:', e);
			}
		},

		/**
		 * 一键领取
		 */
		async claimAll() {
			try {
				uni.showLoading({ title: '领取中...', mask: true });
				const res = await this.$api.adRedpacketClaimAll({});
				uni.hideLoading();

				if (res && res.code === 1) {
					const totalAmount = (res.data && res.data.total_amount) || 0;
					const claimCount = (res.data && res.data.claim_count) || 0;

					if (claimCount > 0) {
						uni.showToast({
							title: '成功领取 ' + claimCount + ' 个红包，共 +' + totalAmount + ' 金币',
							icon: 'none',
							duration: 3000
						});

						// 刷新列表和摘要
						this.page = 1;
						this.noMore = false;
						this.loadList();
						this.loadSummary();

						// 通知父组件
						this.$emit('claimed', {
							amount: totalAmount,
							balance: (res.data && res.data.balance) || 0,
						});
					} else {
						uni.showToast({ title: '没有可领取的红包', icon: 'none' });
					}
				} else {
					uni.showToast({
						title: (res && res.msg) || '领取失败',
						icon: 'none'
					});
				}
			} catch (e) {
				uni.hideLoading();
				console.error('[AdRedPacketList] 一键领取失败:', e);
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

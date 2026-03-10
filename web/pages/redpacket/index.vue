<template>
	<view class="container">
		<!-- 顶部栏 -->
		<view class="header">
			<view class="header-left">
				<text class="group-name">包包群</text>
				<view class="online-badge">{{ onlineCount }}人在线</view>
			</view>
			<view class="header-right">
				<view class="rp-count">红包: {{ activeCount }}</view>
				<view class="status-dot" :class="{ connected: isConnected }"></view>
			</view>
		</view>

		<!-- 红包列表 -->
		<scroll-view scroll-y class="message-list" @scrolltolower="loadMore">
			<view class="message-content">
				<!-- 空状态 -->
				<view v-if="list.length === 0 && !loading" class="empty-state">
					<text class="empty-icon">🧧</text>
					<text class="empty-text">暂无红包，等待发送中...</text>
				</view>

				<!-- 红包列表 -->
				<view v-for="(item, index) in list" :key="item.id" class="redpacket-message">
					<!-- 头像 -->
					<view class="avatar">
						<image v-if="item.sender_avatar" :src="item.sender_avatar" mode="aspectFill"></image>
						<text v-else>{{ (item.sender_name || '系统').slice(0, 2) }}</text>
					</view>

					<view class="message-body">
						<!-- 发送者信息 -->
						<view class="sender-info">
							<text class="sender-name">{{ item.sender_name || '系统' }}</text>
							<text class="send-time">{{ formatTime(item.createtime) }}</text>
						</view>

						<!-- 红包卡片 -->
						<view class="redpacket-card" :class="{ claimed: item.is_claimed, expired: item.status !== 'normal' }"
							@click="handleCardClick(item)">
							<!-- 红包头部 -->
							<view class="rp-header" :class="item.type">
								<view class="rp-type-badge">
									<text>{{ getTypeIcon(item.type) }}</text>
									<text>{{ getTypeLabel(item.type) }}</text>
								</view>
								<text class="rp-name">{{ item.name }}</text>
								<text v-if="item.description" class="rp-desc">{{ item.description }}</text>

								<!-- 资源图片 -->
								<view v-if="item.resource && item.resource.logo" class="resource-preview">
									<image class="resource-logo" :src="item.resource.logo" mode="aspectFill"></image>
									<view class="resource-info">
										<text class="resource-name">{{ item.resource.name }}</text>
										<text v-if="item.resource.description" class="resource-desc">{{ item.resource.description }}</text>
									</view>
								</view>

								<!-- 跳转按钮 -->
								<view v-if="item.resource && !item.is_claimed && item.status === 'normal'" class="jump-btn"
									@click.stop="handleJump(item)">
									<text>{{ getJumpLabel(item.resource.type) }}</text>
								</view>
							</view>

							<!-- 红包底部 -->
							<view class="rp-footer">
								<view class="rp-stats">
									<text>💰 {{ item.claimed_count || 0 }}人已领</text>
									<text>•</text>
									<text>剩余{{ item.remain_count }}个</text>
								</view>
								<view class="rp-action" :class="getActionClass(item)">
									<text>{{ getActionLabel(item) }}</text>
								</view>
							</view>
						</view>
					</view>
				</view>

				<!-- 加载更多 -->
				<view v-if="loading" class="loading">
					<text>加载中...</text>
				</view>
			</view>
		</scroll-view>

		<!-- 领取结果弹窗 -->
		<view v-if="showResult" class="modal-overlay" @click="closeResult">
			<view class="modal-content" @click.stop>
				<view class="modal-header">
					<text class="modal-icon">🎉</text>
					<text class="modal-title">恭喜您！</text>
				</view>
				<view class="modal-body">
					<text class="modal-rp-name">{{ claimResult.task_name }}</text>
					<text class="modal-amount">¥{{ claimResult.amount.toFixed(2) }}</text>
				</view>
				<view class="modal-footer">
					<view class="modal-btn" @click="closeResult">太棒了！</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				list: [],
				page: 1,
				hasMore: true,
				loading: false,
				isConnected: false,
				onlineCount: 0,
				activeCount: 0,
				showResult: false,
				claimResult: {
					amount: 0,
					task_name: ''
				},
				socketTask: null,
				userId: '',
				token: ''
			}
		},
		onLoad() {
			// 获取用户信息
			const userInfo = uni.getStorageSync('userInfo');
			if (userInfo) {
				this.userId = userInfo.id;
				this.token = userInfo.token;
			} else {
				this.userId = 'guest_' + Date.now();
			}

			// 加载红包列表
			this.loadList();

			// 连接 WebSocket
			this.connectWebSocket();
		},
		onUnload() {
			// 断开 WebSocket
			if (this.socketTask) {
				this.socketTask.close();
			}
		},
		methods: {
			// 加载红包列表
			async loadList() {
				if (this.loading || !this.hasMore) return;
				this.loading = true;

				try {
					const res = await this.$api.get('/api/redpacket/list', {
						page: this.page,
						limit: 20,
						status: 'normal'
					});

					if (res.code === 1) {
						if (this.page === 1) {
							this.list = res.data.list;
						} else {
							this.list = [...this.list, ...res.data.list];
						}
						this.hasMore = res.data.has_more;
						this.activeCount = this.list.filter(item => item.status === 'normal').length;
					}
				} catch (e) {
					console.error('加载红包列表失败:', e);
				} finally {
					this.loading = false;
				}
			},

			// 加载更多
			loadMore() {
				if (this.hasMore && !this.loading) {
					this.page++;
					this.loadList();
				}
			},

			// 连接 WebSocket
			connectWebSocket() {
				// 通过网关访问 WebSocket
				const wsUrl = `${this.$config.wsUrl}/?XTransformPort=3002&userId=${this.userId}&token=${this.token}`;

				this.socketTask = uni.connectSocket({
					url: wsUrl,
					success: () => {
						console.log('WebSocket 连接中...');
					}
				});

				uni.onSocketOpen(() => {
					console.log('WebSocket 连接成功');
					this.isConnected = true;
				});

				uni.onSocketClose(() => {
					console.log('WebSocket 连接关闭');
					this.isConnected = false;
					// 重连
					setTimeout(() => {
						this.connectWebSocket();
					}, 3000);
				});

				uni.onSocketError((err) => {
					console.error('WebSocket 错误:', err);
					this.isConnected = false;
				});

				uni.onSocketMessage((res) => {
					try {
						const data = JSON.parse(res.data);
						this.handleSocketMessage(data);
					} catch (e) {
						console.error('解析消息失败:', e);
					}
				});
			},

			// 处理 WebSocket 消息
			handleSocketMessage(data) {
				switch (data.type) {
					case 'connected':
						console.log('已连接:', data);
						break;
					case 'online_count':
						this.onlineCount = data.count;
						break;
					case 'task_notification':
						// 收到新红包推送
						this.handleNewRedpacket(data);
						break;
					case 'system_message':
						// 系统消息
						uni.showToast({
							title: data.title,
							icon: 'none'
						});
						break;
				}
			},

			// 处理新红包
			handleNewRedpacket(data) {
				// 提示用户
				uni.showToast({
					title: '🧧 新红包来了！',
					icon: 'none'
				});

				// 刷新列表
				this.page = 1;
				this.hasMore = true;
				this.loadList();
			},

			// 点击红包卡片
			handleCardClick(item) {
				if (item.is_claimed || item.status !== 'normal') {
					return;
				}
				this.claimRedpacket(item);
			},

			// 领取红包
			async claimRedpacket(item) {
				try {
					uni.showLoading({
						title: '领取中...'
					});

					const res = await this.$api.post('/api/redpacket/claim', {
						id: item.id
					});

					uni.hideLoading();

					if (res.code === 1) {
						// 显示结果
						this.claimResult = res.data;
						this.showResult = true;

						// 更新列表
						item.is_claimed = true;
						item.claim_amount = res.data.amount;
						item.remain_count--;
						item.claimed_count++;
					} else {
						uni.showToast({
							title: res.msg || '领取失败',
							icon: 'none'
						});
					}
				} catch (e) {
					uni.hideLoading();
					uni.showToast({
						title: '领取失败',
						icon: 'none'
					});
				}
			},

			// 跳转处理
			handleJump(item) {
				const resource = item.resource;
				if (!resource) return;

				switch (resource.type) {
					case 'miniapp':
					case 'game':
						// 跳转小程序
						// #ifdef MP-WEIXIN
						uni.navigateToMiniProgram({
							appId: resource.miniapp_id,
							path: resource.miniapp_path,
							fail: (err) => {
								uni.showToast({
									title: '跳转失败',
									icon: 'none'
								});
							}
						});
						// #endif
						break;
					case 'download':
						// 下载App
						// #ifdef H5
						window.location.href = resource.download_url;
						// #endif
						// #ifdef APP-PLUS
						plus.runtime.openURL(resource.download_url);
						// #endif
						break;
					case 'video':
						// 播放视频
						uni.navigateTo({
							url: `/pages/video/play?url=${encodeURIComponent(resource.video_url)}`
						});
						break;
				}
			},

			// 关闭结果弹窗
			closeResult() {
				this.showResult = false;
			},

			// 格式化时间
			formatTime(timestamp) {
				const date = new Date(timestamp * 1000);
				const now = new Date();
				const isToday = date.toDateString() === now.toDateString();

				if (isToday) {
					return date.toLocaleTimeString('zh-CN', {
						hour: '2-digit',
						minute: '2-digit'
					});
				}
				return date.toLocaleDateString('zh-CN', {
					month: '2-digit',
					day: '2-digit',
					hour: '2-digit',
					minute: '2-digit'
				});
			},

			// 获取类型图标
			getTypeIcon(type) {
				const icons = {
					normal: '🧧',
					lucky: '🍀',
					video: '🎬',
					miniapp: '📱',
					download: '⬇️',
					game: '🎮'
				};
				return icons[type] || '🧧';
			},

			// 获取类型标签
			getTypeLabel(type) {
				const labels = {
					normal: '普通红包',
					lucky: '拼手气红包',
					video: '视频红包',
					miniapp: '小程序红包',
					download: '下载App红包',
					game: '游戏红包'
				};
				return labels[type] || '红包';
			},

			// 获取跳转标签
			getJumpLabel(type) {
				const labels = {
					miniapp: '打开小程序',
					game: '开始游戏',
					download: '立即下载',
					video: '观看视频'
				};
				return labels[type] || '立即参与';
			},

			// 获取操作按钮样式
			getActionClass(item) {
				if (item.status !== 'normal') return 'expired';
				if (item.is_claimed) return 'claimed';
				return 'claim';
			},

			// 获取操作按钮文本
			getActionLabel(item) {
				if (item.status !== 'normal') return '已结束';
				if (item.is_claimed) return `已领取 ¥${item.claim_amount.toFixed(2)}`;
				return '领取';
			}
		}
	}
</script>

<style lang="scss">
	.container {
		display: flex;
		flex-direction: column;
		min-height: 100vh;
		background-color: #f5f5f5;
	}

	.header {
		background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
		color: white;
		padding: 24rpx 32rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
		position: sticky;
		top: 0;
		z-index: 100;
	}

	.header-left {
		display: flex;
		align-items: center;
		gap: 16rpx;
	}

	.group-name {
		font-size: 36rpx;
		font-weight: 600;
	}

	.online-badge {
		background: rgba(255, 255, 255, 0.2);
		padding: 4rpx 16rpx;
		border-radius: 20rpx;
		font-size: 24rpx;
	}

	.header-right {
		display: flex;
		align-items: center;
		gap: 16rpx;
	}

	.rp-count {
		background: rgba(255, 255, 255, 0.2);
		padding: 8rpx 20rpx;
		border-radius: 24rpx;
		font-size: 24rpx;
	}

	.status-dot {
		width: 16rpx;
		height: 16rpx;
		border-radius: 50%;
		background: #ff4757;
	}

	.status-dot.connected {
		background: #4cd137;
	}

	.message-list {
		flex: 1;
		overflow: hidden;
	}

	.message-content {
		padding: 32rpx;
		display: flex;
		flex-direction: column;
		gap: 32rpx;
		padding-bottom: 120rpx;
	}

	.empty-state {
		text-align: center;
		padding: 120rpx 40rpx;
		color: #999;
	}

	.empty-icon {
		font-size: 128rpx;
		display: block;
		margin-bottom: 32rpx;
	}

	.empty-text {
		font-size: 28rpx;
	}

	.redpacket-message {
		display: flex;
		gap: 20rpx;
	}

	.avatar {
		width: 80rpx;
		height: 80rpx;
		border-radius: 16rpx;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		display: flex;
		align-items: center;
		justify-content: center;
		color: white;
		font-weight: 600;
		flex-shrink: 0;
		overflow: hidden;

		image {
			width: 100%;
			height: 100%;
		}
	}

	.message-body {
		flex: 1;
		min-width: 0;
	}

	.sender-info {
		display: flex;
		align-items: center;
		gap: 16rpx;
		margin-bottom: 12rpx;
	}

	.sender-name {
		font-size: 26rpx;
		font-weight: 500;
		color: #333;
	}

	.send-time {
		font-size: 22rpx;
		color: #999;
	}

	.redpacket-card {
		max-width: 560rpx;
		border-radius: 24rpx;
		overflow: hidden;
		box-shadow: 0 4rpx 24rpx rgba(0, 0, 0, 0.1);
	}

	.redpacket-card.claimed {
		opacity: 0.7;
	}

	.redpacket-card.expired {
		opacity: 0.5;
	}

	.rp-header {
		padding: 32rpx;
		color: white;
		position: relative;
	}

	.rp-header.normal {
		background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
	}

	.rp-header.lucky {
		background: linear-gradient(135deg, #ff9f43 0%, #ff6b81 100%);
	}

	.rp-header.video {
		background: linear-gradient(135deg, #a55eea 0%, #eb4d4b 100%);
	}

	.rp-header.miniapp {
		background: linear-gradient(135deg, #26de81 0%, #20bf6b 100%);
	}

	.rp-header.download {
		background: linear-gradient(135deg, #45aaf2 0%, #4b7bec 100%);
	}

	.rp-header.game {
		background: linear-gradient(135deg, #a55eea 0%, #8854d0 100%);
	}

	.rp-type-badge {
		display: inline-flex;
		align-items: center;
		gap: 8rpx;
		background: rgba(255, 255, 255, 0.2);
		padding: 4rpx 16rpx;
		border-radius: 20rpx;
		font-size: 22rpx;
		margin-bottom: 16rpx;
	}

	.rp-name {
		font-size: 32rpx;
		font-weight: 600;
		display: block;
		margin-bottom: 8rpx;
	}

	.rp-desc {
		font-size: 26rpx;
		opacity: 0.9;
	}

	.resource-preview {
		margin-top: 24rpx;
		border-radius: 16rpx;
		overflow: hidden;
		background: rgba(0, 0, 0, 0.1);
	}

	.resource-logo {
		width: 100%;
		height: 160rpx;
	}

	.resource-info {
		padding: 16rpx 20rpx;
		background: rgba(0, 0, 0, 0.15);
	}

	.resource-name {
		font-size: 24rpx;
		display: block;
	}

	.resource-desc {
		font-size: 20rpx;
		opacity: 0.8;
		margin-top: 4rpx;
		display: block;
	}

	.jump-btn {
		margin-top: 24rpx;
		padding: 16rpx 32rpx;
		background: rgba(255, 255, 255, 0.2);
		border-radius: 32rpx;
		text-align: center;
		font-size: 26rpx;
	}

	.rp-footer {
		background: white;
		padding: 20rpx 28rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.rp-stats {
		display: flex;
		align-items: center;
		gap: 16rpx;
		color: #666;
		font-size: 24rpx;
	}

	.rp-action {
		padding: 12rpx 32rpx;
		border-radius: 32rpx;
		font-size: 26rpx;
		font-weight: 500;
	}

	.rp-action.claim {
		background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
		color: white;
	}

	.rp-action.claimed {
		background: #26de81;
		color: white;
	}

	.rp-action.expired {
		background: #dfe6e9;
		color: #666;
	}

	.loading {
		text-align: center;
		padding: 40rpx;
		color: #999;
		font-size: 26rpx;
	}

	.modal-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background: rgba(0, 0, 0, 0.5);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 1000;
	}

	.modal-content {
		background: white;
		border-radius: 32rpx;
		width: 600rpx;
		overflow: hidden;
	}

	.modal-header {
		background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
		padding: 60rpx 40rpx;
		text-align: center;
		color: white;
	}

	.modal-icon {
		font-size: 120rpx;
		display: block;
		margin-bottom: 20rpx;
	}

	.modal-title {
		font-size: 40rpx;
		font-weight: 600;
	}

	.modal-body {
		padding: 40rpx;
		text-align: center;
	}

	.modal-rp-name {
		color: #666;
		display: block;
		margin-bottom: 20rpx;
	}

	.modal-amount {
		font-size: 72rpx;
		font-weight: 700;
		color: #ff4757;
	}

	.modal-footer {
		padding: 0 40rpx 40rpx;
	}

	.modal-btn {
		width: 100%;
		padding: 24rpx;
		border-radius: 48rpx;
		background: linear-gradient(135deg, #ff4757 0%, #ff6b81 100%);
		color: white;
		font-size: 32rpx;
		font-weight: 600;
		text-align: center;
	}
</style>

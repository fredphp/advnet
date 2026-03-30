<template>
	<view class="fui-wrap recharge-detail-container">
		<fa-navbar title="提现日志" :border-bottom="false"></fa-navbar>
		<!-- 搜索区域 -->
		<view class="search-section">
			<view class="time-picker">
				<view style="width:40%">
					<picker mode="date" :value="startDate" @change="bindStartDateChange">
						<view class="picker">{{ startDate || '开始日期' }}</view>
					</picker>
				</view>
				<text class="separator">至</text>
				<view style="width:40%">
					<picker mode="date" :value="endDate" @change="bindEndDateChange">
						<view class="picker">{{ endDate || '结束日期' }}</view>
					</picker>
				</view>
			</view>
			<button class="search-btn" @click="handleSearch">搜索</button>
		</view>

		<!-- 列表区域 -->
		<scroll-view scroll-y class="list-container" @scrolltolower="loadMore" refresher-enabled
			:refresher-triggered="refreshing" @refresherrefresh="onRefresh">
			<!-- 数据为空提示 -->
			<!-- <view class="empty-tip" v-if="list.length === 0 && !loading">
				<image src="/static/images/empty.png" class="empty-img"></image>
				<text class="empty-text">暂无提现记录</text>
			</view> -->
			<u-empty text="暂无提现记录" mode="data" v-if="list.length === 0 && !loading"></u-empty>

			<!-- 提现记录列表 -->
			<view class="list-item" v-for="(item, index) in list" :key="index">
				<view class="item-left">
					<view class="item-title">提现</view>
					<view class="item-time">{{ formatTime(item.createTime) }}</view>
				</view>
				<view class="item-right">
					<view class="item-amount">+{{ item.amount }}元</view>
					<view class="item-status" :class="statusClass">
						{{ getStatusText(item.status) }}
					</view>
				</view>
			</view>

			<!-- 加载更多提示 -->
			<view class="load-more" v-if="loading">
				<uni-load-more :status="loadMoreStatus"></uni-load-more>
			</view>
		</scroll-view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				startDate: '', // 开始日期
				endDate: '', // 结束日期
				list: [], // 充值记录列表
				page: 1, // 当前页码
				pageSize: 10, // 每页数量
				total: 0, // 总记录数
				loading: false, // 加载状态
				refreshing: false, // 刷新状态
				loadMoreStatus: 'more' // 加载更多状态
			}
		},
		onLoad() {
			// 默认查询最近一个月的记录
			const endDate = this.formatDate(new Date());
			const startDate = this.formatDate(new Date(new Date().setMonth(new Date().getMonth() - 1)));

			this.startDate = startDate;
			this.endDate = endDate;

			this.getRechargeList();
		},
		methods: {
			// 格式化日期
			formatDate(date) {
				const year = date.getFullYear();
				const month = (date.getMonth() + 1).toString().padStart(2, '0');
				const day = date.getDate().toString().padStart(2, '0');
				return `${year}-${month}-${day}`;
			},

			// 格式化时间
			formatTime(timestamp) {
				const date = new Date(timestamp);
				return `${date.getFullYear()}-${(date.getMonth() + 1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')} ${date.getHours().toString().padStart(2, '0')}:${date.getMinutes().toString().padStart(2, '0')}`;
			},

			// 开始日期选择
			bindStartDateChange(e) {
				this.startDate = e.detail.value;
			},

			// 结束日期选择
			bindEndDateChange(e) {
				this.endDate = e.detail.value;
			},

			// 处理搜索
			handleSearch() {
				if (!this.startDate || !this.endDate) {
					uni.showToast({
						title: '请选择时间范围',
						icon: 'none'
					});
					return;
				}

				if (new Date(this.startDate) > new Date(this.endDate)) {
					uni.showToast({
						title: '开始日期不能大于结束日期',
						icon: 'none'
					});
					return;
				}

				this.page = 1;
				this.list = [];
				this.getRechargeList();
			},

			// 获取充值记录列表
			getRechargeList() {
				this.$u.get("/addons/cowithdraw/api/get_withdraw_log", {
					start_date: this.startDate,
					end_date: this.endDate
				}).then(res => {
					if (res.code == 1) {
						this.list = res.data;
					}
				})
				// if (this.loading) return;

				// this.loading = true;
				// this.loadMoreStatus = 'loading';

				// // 模拟API请求
				// setTimeout(() => {
				// 	// 模拟数据
				// 	const mockData = this.generateMockData();

				// 	if (this.page === 1) {
				// 		this.list = mockData;
				// 		this.total = 30; // 模拟总条数
				// 	} else {
				// 		this.list = [...this.list, ...mockData];
				// 	}

				// 	this.loading = false;
				// 	this.refreshing = false;

				// 	// 判断是否还有更多数据
				// 	if (this.list.length >= this.total) {
				// 		this.loadMoreStatus = 'noMore';
				// 	} else {
				// 		this.loadMoreStatus = 'more';
				// 	}
				// }, 800);
			},

			// 生成模拟数据
			generateMockData() {
				const statusList = [0, 1, 2]; // 0-处理中 1-成功 2-失败
				const result = [];
				const baseTime = new Date(this.endDate).getTime();

				for (let i = 0; i < this.pageSize; i++) {
					const randomDays = Math.floor(Math.random() * 30);
					const randomHours = Math.floor(Math.random() * 24);
					const randomMinutes = Math.floor(Math.random() * 60);

					result.push({
						id: (this.page - 1) * this.pageSize + i + 1,
						amount: (Math.floor(Math.random() * 20) + 1) * 50, // 50-1000之间的50的倍数
						status: statusList[Math.floor(Math.random() * statusList.length)],
						createTime: baseTime - randomDays * 24 * 3600 * 1000 - randomHours * 3600 * 1000 -
							randomMinutes * 60 * 1000,
						paymentMethod: ['wechat', 'alipay', 'bank'][Math.floor(Math.random() * 3)]
					});
				}

				return result;
			},

			// 获取状态文本
			getStatusText(status) {
				const statusMap = {
					0: '处理中',
					1: '成功',
					2: '失败'
				};
				return statusMap[status] || '';
			},

			// 获取状态类名
			getStatusClass(status) {
				const classMap = {
					0: 'processing',
					1: 'success',
					2: 'failed'
				};
				return classMap[status] || '';
				//getStatusClass(item.status)
			},

			// 下拉刷新
			onRefresh() {
				if (this.loading) return;

				this.refreshing = true;
				this.page = 1;
				this.getRechargeList();
			},

			// 加载更多
			loadMore() {
				if (this.loading || this.loadMoreStatus === 'noMore') return;

				this.page += 1;
				this.getRechargeList();
			}
		}
	}
</script>

<style lang="scss">
	.recharge-detail-container {
		padding: 20rpx;

		min-height: 100vh;
	}

	.search-section {
		display: flex;
		align-items: center;
		background-color: #fff;
		border-radius: 16rpx;
		padding: 20rpx;
		margin-bottom: 20rpx;
		box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);

		.time-picker {
			flex: 1;
			display: flex;
			align-items: center;

			.picker {
				padding: 10rpx 20rpx;
				background-color: #f9f9f9;
				border-radius: 8rpx;
				font-size: 28rpx;
				color: #333;
			}

			.separator {
				margin: 0 20rpx;
				font-size: 28rpx;
				color: #999;
			}
		}

		.search-btn {
			margin-left: 20rpx;
			background-color: #d30010;
			color: #fff;
			font-size: 26rpx;
			height: 50rpx;
			line-height: 50rpx;
			border-radius: 8rpx;
			padding: 0 30rpx;
		}
	}

	.list-container {
		height: calc(100vh - 160rpx);

		.empty-tip {
			display: flex;
			flex-direction: column;
			align-items: center;
			padding: 100rpx 0;

			.empty-img {
				width: 200rpx;
				height: 200rpx;
				margin-bottom: 30rpx;
				opacity: 0.6;
			}

			.empty-text {
				font-size: 28rpx;
				color: #999;
			}
		}

		.list-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			background-color: #fff;
			border-radius: 16rpx;
			padding: 30rpx;
			margin-bottom: 20rpx;
			box-shadow: 0 4rpx 12rpx rgba(0, 0, 0, 0.05);

			.item-left {
				.item-title {
					font-size: 32rpx;
					color: #333;
					margin-bottom: 10rpx;
				}

				.item-time {
					font-size: 24rpx;
					color: #999;
				}
			}

			.item-right {
				text-align: right;

				.item-amount {
					font-size: 36rpx;
					font-weight: bold;
					color: #ff6a00;
					margin-bottom: 10rpx;
				}

				.item-status {
					font-size: 24rpx;

					&.success {
						color: #67c23a;
					}

					&.failed {
						color: #f56c6c;
					}

					&.processing {
						color: #e6a23c;
					}
				}
			}
		}

		.load-more {
			padding: 30rpx 0;
			text-align: center;
			font-size: 28rpx;
			color: #999;
		}
	}
</style>
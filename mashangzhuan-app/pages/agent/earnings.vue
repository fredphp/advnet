<template>
	<view class="earnings-page">
		<!-- 顶部导航栏 -->
		<fa-navbar title="收益明细" :border-bottom="false"></fa-navbar>

		<!-- 收益概览 -->
		<u-gap height="32"></u-gap>
		<view class="earnings-overview">
			<view class="overview-item">
				<text class="overview-label">累计收益</text>
				<text class="overview-value">¥{{userInfo.total_income}}</text>
			</view>
			<view class="overview-item">
				<text class="overview-label">本月收益</text>
				<text class="overview-value">¥{{userInfo.month_reward}}</text>
			</view>
			<view class="overview-item">
				<text class="overview-label">待结算</text>
				<text class="overview-value">¥{{ userInfo.nosettle_money }}</text>
			</view>
		</view>

		<!-- 筛选 -->
		<!-- <view class="subsection-filter">
			<u-subsection 
			:current="activeType" 
			:list="earningTypes" 
			@change="handleTypeChange" 
			bg-color="#fff"	button-color="rgba(230,33,41,0.1)" active-color="#E62129" inactive-color="#000"
			></u-subsection>
		</view> -->
		<!-- 时间筛选 -->
		<!-- <view class="date-filter">
			<view class="filter-button" @click="timeShow = true">
				<text class="button-text">{{ currentDateText }}</text>
				<u-icon name="calendar" color="#86909C" size="28"></u-icon>
			</view>
			<u-picker v-model="timeShow" mode="time" @confirm="handleDateConfirm" @cancel="handleDateCancel">

			</u-picker>

		</view> -->

		<!-- 收益图表 -->
		<view class="earnings-chart" v-if="false">
			<view class="chart-header">
				<text class="chart-title">收益趋势</text>
				<view class="chart-subsection">
					<u-subsection
					:current="chartPeriod" 
					:list="periodOptions" 
					@change="handleChartPeriodChange" 
					font-size="24" height="60"
					bg-color="#F7F8FA" button-color="rgba(230,33,41,1)" active-color="#fff" inactive-color="#666"></u-subsection>
				</view>
			</view>

			<view class="chart-container">
				<canvas canvas-id="earningsChart" type="line" :canvas-data="chartData" :force-use-old-canvas="true"
					class="chart"></canvas>
			</view>
		</view>
		
		<view class="search-section">
			<view class="time-picker">
				<view style="width: 40%;">
					<picker mode="date" :value="startDate" @change="bindStartDateChange">
						<view class="picker">{{ startDate || '开始日期' }}</view>
					</picker>
				</view>
				<text class="separator">至</text>
				<view style="width: 40%;">
					<picker mode="date" :value="endDate" @change="bindEndDateChange">
						<view class="picker">{{ endDate || '结束日期' }}</view>
					</picker>
				</view>
			</view>
			<button class="search-btn" @click="handleSearch">搜索</button>
		</view>

		<!-- 收益明细列表 -->
		<view class="earnings-list">
			<view class="list-header">
				<text class="header-title">收益明细</text>
			</view>

			<view class="earning-item" v-for="(item, index) in filteredEarnings" :key="index" v-if="item.goods">
				<view class="item-info">
					<view class="item-icon icon-order">
						<u-icon name="red-packet-fill" color="#FFFFFF" size="36"></u-icon>
					</view>
					<view class="item-detail">
						<view class="item-title">{{ item.goods.title }}</view>
						<view class="item-time">{{ item.createtime }}</view>
					</view>
				</view>
				<text class="item-amount"
					:class="{'amount-positive': item.reward_money >= 0, 'amount-negative': item.reward_money < 0}">
					{{ item.reward_money >= 0 ? '+' : '' }}¥{{ Math.abs(item.reward_money).toFixed(2) }}
				</text>
			</view>

			<view class="no-data" v-if="filteredEarnings.length === 0">
				<u-empty mode="list" text="暂无收益记录"></u-empty>
			</view>
		</view>

		<!-- 加载更多 -->
		<view class="load-more" v-if="hasMore">
			<!-- <u-loading-icon mode="circle" size="24"></u-loading-icon> -->
			<text class="load-text">加载更多</text>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				// 收益概览数据
				totalEarnings: '12,560.00',
				monthlyEarnings: '3,280.00',
				pendingEarnings: '1,250.00',

				// 时间筛选
				selectedDateRange: ['2023-08-01', '2023-08-31'],
				currentDateText: '2023-08-01 至 2023-08-31',
				dateRangeText: ['开始日期', '结束日期'],
				timeShow: false,
				// 收益类型筛选
				earningTypes: [{
						name: '全部'
					},
					{
						name: '订单佣金'
					},
					{
						name: '团队奖励'
					},
					{
						name: '其他奖励'
					},

				],
				periodOptions: [{
						name: '本周'
					},
					{
						name: '本月'
					},
					{
						name: '本季度'
					},
					{
						name: '本年'
					},

				],
				activeType: 0,

				// 图表周期
				chartPeriod: 1, // 0:周, 1:月, 2:年

				// 图表数据
				chartData: {
					categories: ['1日', '5日', '10日', '15日', '20日', '25日', '30日'],
					series: [{
						name: '收益',
						data: [150, 320, 280, 450, 380, 520, 410],
						color: '#de0011',
						lineWidth: 2
					}]
				},

				// 收益明细数据
				earnings: [
				],

				// 是否有更多数据
				hasMore: true,
				startDate:'',
				endDate:'',
				userInfo:{}
			};
		},
		computed: {
			// 筛选后的收益列表
			filteredEarnings() {
				let result = [...this.earnings];

				// 根据类型筛选
				if (this.activeType > 0) {
					const typeMap = ['', 'order', 'team', 'reward'];
					result = result.filter(item => item.type === typeMap[this.activeType]);
				}

				// 这里可以添加日期范围筛选逻辑

				return result;
			}
		},
		onLoad() {
			const endDate = this.formatDate(new Date());
			const startDate = this.formatDate(new Date(new Date().setMonth(new Date().getMonth() - 1)));
			
			this.startDate = startDate;
			this.endDate = endDate;
			// 加载收益数据
			this.loadEarningsData();
			// 初始化图表
			this.initChart();
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
			// 加载收益数据
			loadEarningsData() {
				this.$u.get("/addons/coagent/api/get_agent_user").then(res => {
					if(res.code == 1){
						this.userInfo = res.data;
						this.getAgentOrder();
					}
				})
			},
			getAgentOrder(){
				this.$u.get("/addons/coagent/api/get_agent_order").then(res => {
					if(res.code == 1){
						this.earnings = res.data;
						// console.log(this.recentOrders);
					}
				})
			},

			// 初始化图表
			initChart() {
				// 这里可以根据需要初始化图表数据
				console.log('图表初始化完成');
			},

			// 日期选择确认
			handleDateConfirm(e) {
				this.selectedDateRange = e.value;
				this.currentDateText = `${e.value[0]} 至 ${e.value[1]}`;
				// 执行日期筛选逻辑
				console.log('选择日期范围:', this.selectedDateRange);
			},

			// 日期选择取消
			handleDateCancel() {
				console.log('取消日期选择');
			},

			// 收益类型切换
			handleTypeChange(index) {
				this.activeType = index;
			},

			// 图表周期切换
			handleChartPeriodChange(index) {
				this.chartPeriod = index;
				// 更新图表数据
				if (index === 0) {
					// 周数据
					this.chartData.categories = ['周一', '周二', '周三', '周四', '周五', '周六', '周日'];
					this.chartData.series[0].data = [120, 180, 250, 190, 320, 450, 380];
				} else if (index === 1) {
					// 月数据
					this.chartData.categories = ['1日', '5日', '10日', '15日', '20日', '25日', '30日'];
					this.chartData.series[0].data = [150, 320, 280, 450, 380, 520, 410];
				} else {
					// 年数据
					this.chartData.categories = ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月',
						'12月'
					];
					this.chartData.series[0].data = [850, 1200, 980, 1500, 1800, 2100, 1950, 3280, 0, 0, 0, 0];
				}
			},

			// 加载更多
			loadMoreData() {
				// 模拟加载更多数据
				if (this.hasMore) {
					setTimeout(() => {
						// 复制现有数据并修改部分信息作为新数据
						const newEarnings = this.earnings.slice(0, 3).map(item => ({
							...item,
							id: `E${Math.floor(Math.random() * 1000000000)}`,
							time: '2023-08-15 10:30',
							amount: (Math.random() * 100).toFixed(2)
						}));

						this.earnings = [...this.earnings, ...newEarnings];

						// 控制加载更多次数
						if (this.earnings.length >= 15) {
							this.hasMore = false;
						}
					}, 1000);
				}
			}
		},
		onReachBottom() {
			// 页面滚动到底部时加载更多
			this.loadMoreData();
		}
	};
</script>

<style scoped lang="scss">
	.earnings-page {
		background-color: #F5F7FA;
		min-height: 100vh;
		font-size: 28rpx;
		padding-bottom: 32rpx;
		color: #333;
	}

	// 收益概览
	.earnings-overview {
		margin: 0 32rpx 32rpx 32rpx;
		border-radius: 20rpx;
		display: flex;
		padding: 32rpx 0;
		color: #fff;
		background: linear-gradient(to right, #FF8D3B 0%, #E62129 100%);
		margin-bottom: 20rpx;

		.overview-item {
			flex: 1;
			text-align: center;

			.overview-label {
				font-size: 24rpx;
			}

			.overview-value {
				font-size: 32rpx;
				font-weight: bold;

				margin-top: 10rpx;
				display: block;
			}
		}
	}
	
	.subsection-filter{
		margin: 32rpx;
		margin-bottom: 24rpx;
	}

	// 时间筛选
	.date-filter {
		display: flex;
		align-items: center;
		padding: 0 32rpx 24rpx;
		.filter-button {
			display: flex;
			align-items: center;
			justify-content: space-between;
			color: #000000;
			.button-text {
				font-size: 26rpx;
				margin-right: 4rpx;
			}
		}
	}

	// 收益图表
	.earnings-chart {
		background-color: #FFFFFF;
		border-radius: 20rpx;
		margin: 0 32rpx 24rpx;
		padding: 24rpx;

		.chart-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20rpx;

			.chart-title {
				flex: 1;
				font-size: 32rpx;
				 
				color: #111;
			}
			.chart-subsection{
				width: 360rpx;
			}
		}

		.chart-container {
			width: 100%;
			height: 360rpx;

			.chart {
				width: 100%;
				height: 100%;
			}
		}
	}

	// 收益明细列表
	.earnings-list {
		background-color: #FFFFFF;
		border-radius: 20rpx;
		margin: 0 32rpx;

		.list-header {
			padding: 24rpx 32rpx;
			border-bottom: 1px solid #F2F3F5;

			.header-title {
				font-size: 32rpx;
				color: #111;
			}
		}

		.earning-item {
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 24rpx 28rpx;
			border-bottom: 1px solid #F2F3F5;

			&:last-child {
				border-bottom: none;
			}

			.item-info {
				flex: 1;
				display: flex;

				.item-icon {
					width: 72rpx;
					height: 72rpx;
					border-radius: 50%;
					display: flex;
					align-items: center;
					justify-content: center;

					&.icon-order {
						background-color: #E62129;
					}

					&.icon-team {
						background-color: #FE5D44;
					}

					&.icon-reward {
						background-color: #00bcd4;
					}
				}

				.item-detail {
					margin-left: 20rpx;
					flex: 1;

					.item-title {
						font-size: 28rpx;
						color: #333;
					}

					.item-time {
						font-size: 24rpx;
						color: #999;
						margin-top: 5rpx;
						display: inline-block;
					}
				}
			}

			.item-amount {
				font-size: 32rpx;
				font-weight: 500;

				&.amount-positive {
					color: #FE5D44;
				}

				&.amount-negative {
					color: #00B42A;
				}
			}
		}

		.no-data {
			padding: 100rpx 0;
			text-align: center;
		}
	}

	// 加载更多
	.load-more {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 40rpx 0;
		color: #86909C;

		.load-text {
			margin-left: 15rpx;
			font-size: 26rpx;
		}
	}
	.search-section {
		display: flex;
		align-items: center;
		background-color: #fff;
		border-radius: 16rpx;
		padding: 20rpx;
		margin: 20rpx 30rpx;
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
</style>
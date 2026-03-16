<template>
	<view class="ranking-page">
		<fa-navbar title="业绩排行" :border-bottom="false"></fa-navbar>

		<!-- 排行类型筛选 -->
		<!-- <view class="type-filter">
			<u-tabs 
			:current="activeType" 
			:list="typeOptions" 
			@change="handleTypeChange"
			:is-scroll="false"
			font-size="28"
			:bar-style="{'background-color': '#E62129'}"
			active-color="#111111" inactive-color="#999999" :show-bar="true"
			></u-tabs>
		</view> -->

		<!-- 排行周期筛选 -->
		<!-- <view class="period-filter">
			<u-subsection 
			:current="activePeriod" 
			:list="periodOptions" 
			@change="handlePeriodChange"
			font-size="26" 
			bg-color="#fff" button-color="rgba(230,33,41,1)" active-color="#fff" inactive-color="#666" 
			></u-subsection>
		</view> -->

		<!-- 前三名展示 -->
		<view style="margin-top:44rpx;">
			<view class="top-three">
				<view class="second-place">
					<view class="rank-item rank-2">
						<text class="rank-name">{{ secondPlace.user.nickname }}</text>
						<u-avatar :src="secondPlace.user.avatar" size="72"></u-avatar>
						<text class="rank-amount">¥{{ secondPlace.total_income }}</text>
						<image :src="$IMG_URL+'/images/rank-silver.png'" mode="widthFix" class="rank-medal"></image>
					</view>
				</view>

				<view class="first-place">
					<view class="rank-item rank-1">
						<text class="rank-name">{{ firstPlace.user.nickname }}</text>
						<u-avatar :src="firstPlace.user.avatar" size="88"></u-avatar>
						<text class="rank-amount">¥{{ firstPlace.total_income }}</text>
						<image :src="$IMG_URL+'/images/rank-gold.png'" mode="widthFix" class="rank-medal"></image>
					</view>
				</view>

				<view class="third-place">
					<view class="rank-item rank-3" v-if="thirdPlace">
						<text class="rank-name">{{ thirdPlace.user.nickname }}</text>
						<u-avatar :src="thirdPlace.user.avatar" size="72"></u-avatar>
						<text class="rank-amount">¥{{ thirdPlace.total_income }}</text>
						<image :src="$IMG_URL+'/images/rank-bronze.png'" mode="widthFix" class="rank-medal"></image>
					</view>
				</view>
			</view>

			<!-- 排行榜列表 -->
			<view class="ranking-list">
				<view class="list-header">
					<text class="header-rank">排名</text>
					<text class="header-name">分销商</text>
					<text class="header-amount">业绩(元)</text>
				</view>

				<view class="rank-item" v-for="(item, index) in rankingList" :key="index"
					:class="{'current-user': item.isCurrentUser}" v-if="index > 2">
					<text class="item-rank">{{ index + 1 }}</text>
					<view class="item-user">
						<u-avatar :src="item.user.avatar" size="60"></u-avatar>
						<text class="item-name">{{ item.user.nickname }}</text>
						<u-tag text="我" size="mini" mode="dark" shape="circle" bg-color="#de0011" color="#FFFFFF"
							:custom-style="{marginLeft: '8px'}" v-if="item.isCurrentUser"></u-tag>
					</view>
					<text class="item-amount">¥{{ item.total_income }}</text>
				</view>

				<view class="no-data" v-if="rankingList.length === 0">
					<u-empty mode="list" text="暂无排行数据"></u-empty>
				</view>
			</view>

			<!-- 我的排名 -->
			<u-gap height="132"></u-gap>
			<view class="my-rank" v-if="myRank > 0">
				<u-avatar size="82"></u-avatar>
				<view class="u-flex-1 u-m-l-20">
					<view class="rank-text">我的排名</view>
					<view class="rank-amount">¥{{ myAmount }}</view>
				</view>

				<view class="rank-value">{{ myRank }}名</view>

			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				// 排行周期选项
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
				activePeriod: 1, // 默认本月

				// 排行类型选项
				typeOptions: [{
						name: '团队业绩'
					},
					{
						name: '个人业绩'
					},
					{
						name: '邀请人数'
					}
				],
				activeType: 0, // 默认团队业绩

				// 前三名数据
				firstPlace: {
					avatar: 'https://picsum.photos/200/200?random=20',
					name: '王大锤',
					amount: '25,680.00'
				},
				secondPlace: {
					avatar: 'https://picsum.photos/200/200?random=21',
					name: '李晓华',
					amount: '18,950.00'
				},
				thirdPlace: {
					avatar: 'https://picsum.photos/200/200?random=22',
					name: '张小红',
					amount: '15,320.00'
				},

				// 排行榜列表数据
				rankingList: [{
						avatar: 'https://picsum.photos/200/200?random=23',
						name: '赵小刚',
						amount: '12,850.00',
						isCurrentUser: false
					},
					{
						avatar: 'https://picsum.photos/200/200?random=24',
						name: '陈思思',
						amount: '11,630.00',
						isCurrentUser: false
					},
					{
						avatar: 'https://picsum.photos/200/200?random=25',
						name: '分销达人', // 当前用户
						amount: '10,560.00',
						isCurrentUser: true
					},
					{
						avatar: 'https://picsum.photos/200/200?random=26',
						name: '刘建国',
						amount: '9,870.00',
						isCurrentUser: false
					},
					{
						avatar: 'https://picsum.photos/200/200?random=27',
						name: '黄小丽',
						amount: '8,450.00',
						isCurrentUser: false
					},
					{
						avatar: 'https://picsum.photos/200/200?random=28',
						name: '吴志强',
						amount: '7,620.00',
						isCurrentUser: false
					}
				],

				// 我的排名和业绩
				myRank: 6,
				myAmount: '10,560.00'
			};
		},
		onLoad() {
			// 加载排行数据
			this.loadRankingData();
		},
		methods: {
			// 加载排行数据
			loadRankingData() {
				this.$u.get("/addons/coagent/api/get_rank_list").then(res => {
					if (res.code == 1) {
						this.firstPlace = res.data.list[0]
						this.secondPlace = res.data.list[1]
						this.thirdPlace = res.data.list[2]
						this.rankingList = res.data.list
						this.myRank = res.data.my_rank
						this.myAmount = res.data.my_amount
					}
				})
			},

			// 周期切换
			handlePeriodChange(index) {
				this.activePeriod = index;
				// 加载对应周期的排行数据
				console.log('切换到', this.periodOptions[index]);
			},

			// 类型切换
			handleTypeChange(index) {
				this.activeType = index;
				// 加载对应类型的排行数据
				console.log('切换到', this.typeOptions[index].name);
			}
		}
	};
</script>

<style scoped lang="scss">
	.ranking-page {
		background-color: #F5F7FA;
		min-height: 100vh;
		// font-size: 28rpx;
		// color: #1D2129;
		// padding-bottom: 30rpx;
	}

	// 排行周期筛选
	.period-filter {
		margin: 32rpx;

	}

	// 排行类型筛选
	.type-filter {
		background-color: #FFFFFF;

	}

	// 前三名展示
	.top-three {
		display: flex;
		justify-content: space-around;
		align-items: flex-end;
		background-color: #FFFFFF;
		padding: 48rpx 32rpx 40rpx 32rpx;
		border-radius: 32rpx 32rpx 0 0;
		margin: 0 32rpx;
		margin-bottom: 0;
		gap: 16rpx;
		color: #333;

		.first-place,
		.second-place,
		.third-place {
			flex: 1;
			display: flex;
			flex-direction: column;
			align-items: center;
			border-top: 2px solid transparent;
			border-radius: 16rpx;
		}

		.first-place {
			order: 2;
			margin-bottom: 30rpx;
			border-top-color: #FBD568;
			background: linear-gradient(to bottom, #FFFAEA 0%, #FFFFFF 100%);
		}

		.second-place {
			order: 1;
			border-top-color: #D8DFE7;
			background: linear-gradient(to bottom, #F4F9FF 0%, #FFFFFF 100%);
		}

		.third-place {
			order: 3;
			border-top-color: #E6A668;
			background: linear-gradient(to bottom, #FEF3ED 0%, #FFFFFF 100%);
		}

		.rank-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			position: relative;
			padding-top: 32rpx;

			.rank-number {
				position: absolute;
				top: -20rpx;
				right: -10rpx;
				width: 40rpx;
				height: 40rpx;
				border-radius: 50%;
				background-color: #FFFFFF;
				color: #E62129;
				font-weight: bold;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 24rpx;
				z-index: 2;
			}

			.rank-name {

				font-weight: 500;
				margin-bottom: 12rpx;
			}

			.rank-amount {
				margin-top: 10rpx;
				font-size: 28rpx;
				color: #E62129;
				font-weight: bold;
			}

			.rank-medal {
				width: 80rpx;
				height: 80rpx;
				position: absolute;
				top: -40rpx;
				left: 50%;
				transform: translateX(-50%);
				z-index: 1;
			}
		}

		.rank-1 {
			.rank-name {

				font-weight: bold;
			}
		}
	}

	// 排行榜列表
	.ranking-list {
		background-color: #FFFFFF;
		border-radius: 0 0 32rpx 32rpx;
		margin: 0 32rpx;

		.list-header {
			display: flex;
			padding: 25rpx 30rpx;
			border-bottom: 1px solid #F2F3F5;
			font-weight: bold;
			color: #86909C;
			font-size: 26rpx;

			.header-rank {
				width: 15%;
			}

			.header-name {
				width: 55%;
			}

			.header-amount {
				width: 30%;
				text-align: right;
			}
		}

		.rank-item {
			display: flex;
			align-items: center;
			padding: 25rpx 30rpx;
			border-bottom: 1px solid #F2F3F5;

			&:last-child {
				border-bottom: none;
			}

			&.current-user {
				background-color: rgba(222, 0, 17, 0.05);
			}

			.item-rank {
				width: 15%;
				color: #86909C;
				font-weight: 500;
			}

			.item-user {
				width: 55%;
				display: flex;
				align-items: center;

				.item-name {
					margin-left: 15rpx;
					margin-right: 4rpx;
				}
			}

			.item-amount {
				width: 30%;
				text-align: right;
				font-weight: 500;
				color: #1D2129;
			}
		}

		.no-data {
			padding: 100rpx 0;
			text-align: center;
		}
	}

	// 我的排名
	.my-rank {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #FFFFFF;
		padding: 24rpx 36rpx;
		display: flex;
		align-items: center;
		justify-content: space-between;

		.rank-text {
			font-size: 32rpx;
		}

		.rank-value {
			color: #FD587F;
			font-weight: bold;
			font-size: 32rpx;
		}

		.rank-amount {
			color: #999999;
			font-size: 28rpx;

		}
	}
</style>
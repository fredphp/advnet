<template>
	<view class="ranking-page">
		<fa-navbar title="邀请排行" :border-bottom="false"></fa-navbar>

		<!-- 前三名展示 -->
		<view class="top-three" v-if="rankingList.length > 0">
			<view class="second-place" v-if="secondPlace">
				<view class="rank-item rank-2">
					<text class="rank-name">{{ secondPlace.nickname }}</text>
					<u-avatar :src="secondPlace.avatar || '/static/image/avatar.png'" size="72"></u-avatar>
					<text class="rank-amount">{{ secondPlace.total_income }}人</text>
					<view class="rank-badge badge-2">2</view>
				</view>
			</view>

			<view class="first-place" v-if="firstPlace">
				<view class="rank-item rank-1">
					<text class="rank-name">{{ firstPlace.nickname }}</text>
					<u-avatar :src="firstPlace.avatar || '/static/image/avatar.png'" size="88"></u-avatar>
					<text class="rank-amount">{{ firstPlace.total_income }}人</text>
					<view class="rank-badge badge-1">1</view>
				</view>
			</view>

			<view class="third-place" v-if="thirdPlace">
				<view class="rank-item rank-3">
					<text class="rank-name">{{ thirdPlace.nickname }}</text>
					<u-avatar :src="thirdPlace.avatar || '/static/image/avatar.png'" size="72"></u-avatar>
					<text class="rank-amount">{{ thirdPlace.total_income }}人</text>
					<view class="rank-badge badge-3">3</view>
				</view>
			</view>
		</view>

		<!-- 排行榜列表 -->
		<view class="ranking-list" v-if="rankingList.length > 0">
			<view class="list-header">
				<text class="header-rank">排名</text>
				<text class="header-name">分销商</text>
				<text class="header-amount">邀请人数</text>
			</view>

			<view class="rank-item" v-for="(item, index) in rankingList" :key="index"
				:class="{'current-user': item.isCurrentUser}" v-if="index >= 3">
				<text class="item-rank">{{ index + 1 }}</text>
				<view class="item-user">
					<u-avatar :src="item.avatar || '/static/image/avatar.png'" size="60"></u-avatar>
					<text class="item-name">{{ item.nickname }}</text>
					<u-tag text="我" size="mini" mode="dark" shape="circle" bg-color="#de0011" color="#FFFFFF"
						:custom-style="{marginLeft: '8rpx'}" v-if="item.isCurrentUser"></u-tag>
				</view>
				<text class="item-amount">{{ item.total_income }}人</text>
			</view>
		</view>

		<!-- 加载中 -->
		<view class="loading-wrap" v-if="loading">
			<view class="loading-spinner"></view>
			<text class="loading-text">加载中...</text>
		</view>

		<!-- 空状态 -->
		<view class="empty-wrap" v-if="!loading && rankingList.length === 0">
			<u-empty mode="list" text="暂无排行数据"></u-empty>
		</view>

		<!-- 我的排名 -->
		<view class="my-rank" v-if="myRank > 0">
			<view class="my-rank-left">
				<text class="rank-text">我的排名</text>
				<text class="rank-count">邀请 {{ myAmount }} 人</text>
			</view>
			<view class="my-rank-value">第{{ myRank }}名</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				// 前三名
				firstPlace: null,
				secondPlace: null,
				thirdPlace: null,

				// 排行列表
				rankingList: [],

				// 我的排名
				myRank: 0,
				myAmount: 0,

				loading: false,
			};
		},
		onLoad() {
			this.loadRankingData();
		},
		onPullDownRefresh() {
			this.loadRankingData();
		},
		methods: {
			loadRankingData() {
				if (this.loading) return;
				this.loading = true;

				this.$api.inviteRanking({ type: 'invite', limit: 20 }).then(res => {
					if (res && res.code == 1) {
						this.firstPlace = res.data.firstPlace || null;
						this.secondPlace = res.data.secondPlace || null;
						this.thirdPlace = res.data.thirdPlace || null;
						this.rankingList = res.data.list || [];
						this.myRank = res.data.myRank || 0;
						this.myAmount = res.data.myAmount || 0;
					}
				}).catch(err => {
					console.error('[Ranking] 接口异常:', err);
					uni.showToast({ title: '加载失败', icon: 'none' });
				}).finally(() => {
					this.loading = false;
					uni.stopPullDownRefresh();
				});
			},
		}
	};
</script>

<style scoped lang="scss">
	.ranking-page {
		background-color: #F5F7FA;
		min-height: 100vh;
		padding-bottom: calc(env(safe-area-inset-bottom) + 140rpx);
	}

	/* 前三名展示 */
	.top-three {
		display: flex;
		justify-content: space-around;
		align-items: flex-end;
		background-color: #FFFFFF;
		padding: 60rpx 32rpx 40rpx 32rpx;
		border-radius: 32rpx 32rpx 0 0;
		margin: 24rpx 24rpx 0;
		gap: 16rpx;
		color: #333;

		.first-place,
		.second-place,
		.third-place {
			flex: 1;
			display: flex;
			flex-direction: column;
			align-items: center;
			position: relative;
			border-top: 2rpx solid transparent;
		}

		.first-place {
			order: 2;
			margin-bottom: 30rpx;
			border-top-color: #FBD568;
			background: linear-gradient(to bottom, #FFFAEA 0%, #FFFFFF 100%);
			padding-top: 40rpx;
		}

		.second-place {
			order: 1;
			border-top-color: #D8DFE7;
			background: linear-gradient(to bottom, #F4F9FF 0%, #FFFFFF 100%);
			padding-top: 20rpx;
		}

		.third-place {
			order: 3;
			border-top-color: #E6A668;
			background: linear-gradient(to bottom, #FEF3ED 0%, #FFFFFF 100%);
			padding-top: 20rpx;
		}

		.rank-item {
			display: flex;
			flex-direction: column;
			align-items: center;
			position: relative;
			padding-top: 32rpx;

			.rank-name {
				font-size: 26rpx;
				font-weight: 500;
				margin-bottom: 12rpx;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
				max-width: 160rpx;
				text-align: center;
			}

			.rank-amount {
				margin-top: 10rpx;
				font-size: 28rpx;
				color: #E62129;
				font-weight: bold;
			}

			.rank-badge {
				width: 48rpx;
				height: 48rpx;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 24rpx;
				font-weight: bold;
				color: #fff;
				position: absolute;
				top: -24rpx;
				left: 50%;
				transform: translateX(-50%);
				z-index: 1;
				box-shadow: 0 4rpx 8rpx rgba(0, 0, 0, 0.15);
			}

			.badge-1 { background: linear-gradient(135deg, #FFD700, #FFA500); }
			.badge-2 { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); }
			.badge-3 { background: linear-gradient(135deg, #CD7F32, #A0522D); }
		}

	/* 排行榜列表 */
	.ranking-list {
		background-color: #FFFFFF;
		border-radius: 0 0 32rpx 32rpx;
		margin: 0 24rpx;

		.list-header {
			display: flex;
			padding: 24rpx 30rpx;
			border-bottom: 1rpx solid #F2F3F5;
			font-weight: bold;
			color: #86909C;
			font-size: 24rpx;

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
			padding: 24rpx 30rpx;
			border-bottom: 1rpx solid #F7F8FA;

			&:last-child {
				border-bottom: none;
			}

			&.current-user {
				background-color: rgba(230, 33, 41, 0.04);
			}

			.item-rank {
				width: 15%;
				color: #86909C;
				font-weight: 500;
				font-size: 26rpx;
			}

			.item-user {
				width: 55%;
				display: flex;
				align-items: center;

				.item-name {
					margin-left: 14rpx;
					font-size: 26rpx;
					color: #333;
					overflow: hidden;
					text-overflow: ellipsis;
					white-space: nowrap;
					max-width: 200rpx;
				}
			}

			.item-amount {
				width: 30%;
				text-align: right;
				font-weight: 600;
				color: #E62129;
				font-size: 26rpx;
			}
		}
	}

	/* 加载中 */
	.loading-wrap {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 200rpx 0;
	}

	.loading-spinner {
		width: 40rpx;
		height: 40rpx;
		border: 4rpx solid #E0E0E0;
		border-top-color: #E62129;
		border-radius: 50%;
		animation: spin 0.7s linear infinite;
	}

	.loading-text {
		margin-top: 16rpx;
		font-size: 26rpx;
		color: #C0C4CC;
	}

	@keyframes spin {
		to {
			transform: rotate(360deg);
		}
	}

	/* 空状态 */
	.empty-wrap {
		padding: 200rpx 0;
	}

	/* 我的排名 */
	.my-rank {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #FFFFFF;
		padding: 24rpx 36rpx;
		padding-bottom: calc(24rpx + env(safe-area-inset-bottom));
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-shadow: 0 -2rpx 20rpx rgba(0, 0, 0, 0.05);

		.my-rank-left {
			display: flex;
			flex-direction: column;
		}

		.rank-text {
			font-size: 26rpx;
			color: #999;
		}

		.rank-count {
			font-size: 24rpx;
			color: #666;
			margin-top: 4rpx;
		}

		.my-rank-value {
			color: #E62129;
			font-weight: bold;
			font-size: 34rpx;
		}
	}
</style>

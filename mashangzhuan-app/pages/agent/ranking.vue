<template>
	<view class="ranking-page">
		<!-- 顶部横幅 -->
		<view class="rank-banner">
			<view class="banner-title">邀请排行榜</view>
			<view class="banner-sub">TOP INVITERS</view>
			<view class="banner-stars">
				<text class="star-item">✦</text>
				<text class="star-item">✦</text>
				<text class="star-item">✦</text>
				<text class="star-item star-small">✧</text>
				<text class="star-item star-small">✧</text>
			</view>
		</view>

		<!-- 前三名展示 -->
		<view class="top-three" v-if="rankingList.length > 0">
			<!-- 亚军 -->
			<view class="top-col top-col-2" v-if="secondPlace">
				<text class="top-medal">🥈</text>
				<view class="top-avatar-wrap top-avatar-2">
					<u-avatar :src="secondPlace.avatar || '/static/image/avatar.png'" size="72"></u-avatar>
				</view>
				<text class="top-name">{{ secondPlace.nickname }}</text>
				<view class="top-count">
					<text class="top-count-num">{{ secondPlace.total_income }}</text>
					<text class="top-count-unit">人</text>
				</view>
				<view class="top-podium top-podium-2"></view>
			</view>

			<!-- 冠军 -->
			<view class="top-col top-col-1" v-if="firstPlace">
				<text class="top-medal top-medal-1">👑</text>
				<view class="top-avatar-wrap top-avatar-1">
					<u-avatar :src="firstPlace.avatar || '/static/image/avatar.png'" size="96"></u-avatar>
				</view>
				<text class="top-name top-name-1">{{ firstPlace.nickname }}</text>
				<view class="top-count">
					<text class="top-count-num top-count-gold">{{ firstPlace.total_income }}</text>
					<text class="top-count-unit">人</text>
				</view>
				<view class="top-podium top-podium-1"></view>
			</view>

			<!-- 季军 -->
			<view class="top-col top-col-3" v-if="thirdPlace">
				<text class="top-medal">🥉</text>
				<view class="top-avatar-wrap top-avatar-3">
					<u-avatar :src="thirdPlace.avatar || '/static/image/avatar.png'" size="72"></u-avatar>
				</view>
				<text class="top-name">{{ thirdPlace.nickname }}</text>
				<view class="top-count">
					<text class="top-count-num">{{ thirdPlace.total_income }}</text>
					<text class="top-count-unit">人</text>
				</view>
				<view class="top-podium top-podium-3"></view>
			</view>
		</view>

		<!-- 排行榜列表 -->
		<view class="ranking-list-card" v-if="rankingList.length > 0">
			<view class="list-header">
				<text class="lh-rank">排名</text>
				<text class="lh-name">分销商</text>
				<text class="lh-count">邀请人数</text>
			</view>
			<view class="list-body">
				<view class="list-row" v-for="(item, idx) in restList" :key="idx"
					:class="{'list-row-me': item.isCurrentUser}">
					<view class="lr-rank">
						<view class="lr-medal" v-if="idx < 3">
							<text class="lr-medal-num">{{ idx + 4 }}</text>
						</view>
						<text class="lr-rank-text" v-else>{{ idx + 4 }}</text>
					</view>
					<view class="lr-user">
						<u-avatar :src="item.avatar || '/static/image/avatar.png'" size="60"></u-avatar>
						<text class="lr-nickname">{{ item.nickname }}</text>
						<view class="lr-me-tag" v-if="item.isCurrentUser">我</view>
					</view>
					<view class="lr-count">
						<text class="lr-count-num">{{ item.total_income }}</text>
						<text class="lr-count-unit">人</text>
					</view>
				</view>
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
		<view class="my-rank-bar" v-if="myRank > 0">
			<view class="my-rank-left">
				<text class="my-rank-trophy">🏆</text>
				<view class="my-rank-info">
					<text class="my-rank-label">我的排名</text>
					<text class="my-rank-detail">邀请 {{ myAmount }} 人</text>
				</view>
			</view>
			<view class="my-rank-badge">
				<text class="my-rank-badge-num">第{{ myRank }}</text>
				<text class="my-rank-badge-name">名</text>
			</view>
		</view>
	</view>
</template>

<script>
	import uAvatar from '@/uview-ui/components/u-avatar/u-avatar.vue';
	import uTag from '@/uview-ui/components/u-tag/u-tag.vue';

	export default {
		components: {
			uAvatar,
			uTag
		},
		data() {
			return {
				firstPlace: null,
				secondPlace: null,
				thirdPlace: null,
				rankingList: [],
				myRank: 0,
				myAmount: 0,
				loading: false,
			};
		},
		computed: {
			restList() {
				return this.rankingList.filter((_, i) => i >= 3);
			}
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
	/* ========== 页面整体 ========== */
	.ranking-page {
		background: linear-gradient(180deg, #FFF8F0 0%, #F5F7FA 28%);
		min-height: 100vh;
		padding-bottom: calc(env(safe-area-inset-bottom) + 130rpx);
	}

	/* ========== 顶部横幅 ========== */
	.rank-banner {
		background: linear-gradient(135deg, #C41A1A 0%, #E62129 45%, #FF6B35 100%);
		padding: 50rpx 0 70rpx;
		text-align: center;
		border-radius: 0 0 40rpx 40rpx;
		position: relative;
	}

	.banner-title {
		font-size: 40rpx;
		font-weight: bold;
		color: #FFFFFF;
		letter-spacing: 6rpx;
	}

	.banner-sub {
		font-size: 20rpx;
		color: rgba(255, 255, 255, 0.65);
		letter-spacing: 10rpx;
		margin-top: 8rpx;
	}

	.banner-stars {
		display: flex;
		justify-content: center;
		gap: 14rpx;
		margin-top: 14rpx;
	}

	.star-item {
		color: rgba(255, 215, 0, 0.75);
		font-size: 22rpx;
	}

	.star-small {
		font-size: 16rpx;
		color: rgba(255, 215, 0, 0.5);
	}

	/* ========== 前三名 ========== */
	.top-three {
		display: flex;
		justify-content: center;
		align-items: flex-end;
		padding: 36rpx 20rpx 0;
		gap: 12rpx;
		margin-top: -36rpx;
		position: relative;
		z-index: 2;
	}

	.top-col {
		flex: 1;
		display: flex;
		flex-direction: column;
		align-items: center;
		background: #FFFFFF;
		border-radius: 24rpx 24rpx 0 0;
		padding: 24rpx 8rpx 0;
		position: relative;
	}

	/* 冠军列 — 最高 */
	.top-col-1 {
		order: 2;
		padding-top: 50rpx;
		border-top: 4rpx solid #F5C342;
		background: linear-gradient(180deg, #FFFAEA 0%, #FFFFFF 40%);
		z-index: 3;
	}

	/* 亚军列 — 左 */
	.top-col-2 {
		order: 1;
		border-top: 3rpx solid #D0D5DA;
		background: linear-gradient(180deg, #F6F9FC 0%, #FFFFFF 40%);
	}

	/* 季军列 — 右 */
	.top-col-3 {
		order: 3;
		border-top: 3rpx solid #E0B88A;
		background: linear-gradient(180deg, #FEF5ED 0%, #FFFFFF 40%);
	}

	/* 奖牌 */
	.top-medal {
		font-size: 36rpx;
		line-height: 1;
		margin-bottom: 6rpx;
	}

	.top-medal-1 {
		font-size: 48rpx;
		margin-bottom: 4rpx;
	}

	/* 头像外框 */
	.top-avatar-wrap {
		border-radius: 50%;
		padding: 4rpx;
	}

	.top-avatar-1 {
		padding: 6rpx;
		background: linear-gradient(135deg, #FFD700, #FFA500);
		box-shadow: 0 4rpx 20rpx rgba(255, 215, 0, 0.35);
	}

	.top-avatar-2 {
		background: linear-gradient(135deg, #E0E4E8, #B0B8C0);
		box-shadow: 0 4rpx 16rpx rgba(176, 184, 192, 0.25);
	}

	.top-avatar-3 {
		background: linear-gradient(135deg, #EDCB9E, #C49A6C);
		box-shadow: 0 4rpx 16rpx rgba(196, 154, 108, 0.25);
	}

	/* 名字 */
	.top-name {
		font-size: 24rpx;
		font-weight: 500;
		color: #555;
		margin-top: 10rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		max-width: 150rpx;
		text-align: center;
	}

	.top-name-1 {
		font-size: 28rpx;
		font-weight: 600;
		color: #8B6914;
		max-width: 170rpx;
	}

	/* 邀请人数 */
	.top-count {
		display: flex;
		align-items: baseline;
		margin-top: 6rpx;
	}

	.top-count-num {
		font-size: 28rpx;
		font-weight: 700;
		color: #D4380D;
	}

	.top-count-gold {
		font-size: 34rpx;
		color: #B8860B;
	}

	.top-count-unit {
		font-size: 20rpx;
		color: #999;
		margin-left: 2rpx;
	}

	/* 领奖台底座 */
	.top-podium {
		width: 100%;
		border-radius: 0 0 0 0;
		margin-top: 16rpx;
	}

	.top-podium-1 {
		height: 20rpx;
		background: linear-gradient(180deg, #FBD568, #F5C342);
		border-radius: 0 0 12rpx 12rpx;
	}

	.top-podium-2 {
		height: 14rpx;
		background: linear-gradient(180deg, #E0E4E8, #D0D5DA);
		border-radius: 0 0 12rpx 12rpx;
	}

	.top-podium-3 {
		height: 14rpx;
		background: linear-gradient(180deg, #EDCB9E, #E0B88A);
		border-radius: 0 0 12rpx 12rpx;
	}

	/* ========== 列表卡片 ========== */
	.ranking-list-card {
		background: #FFFFFF;
		border-radius: 24rpx;
		margin: 0 24rpx;
		box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.04);
		overflow: hidden;
	}

	.list-header {
		display: flex;
		align-items: center;
		padding: 22rpx 30rpx;
		border-bottom: 1rpx solid #F2F3F5;

		.lh-rank {
			width: 80rpx;
			font-size: 22rpx;
			font-weight: 600;
			color: #969BA3;
		}

		.lh-name {
			flex: 1;
			font-size: 22rpx;
			font-weight: 600;
			color: #969BA3;
		}

		.lh-count {
			width: 140rpx;
			text-align: right;
			font-size: 22rpx;
			font-weight: 600;
			color: #969BA3;
		}
	}

	.list-body {
		/* 可滚动区域 */
	}

	/* 列表行 */
	.list-row {
		display: flex;
		align-items: center;
		padding: 22rpx 30rpx;
		border-bottom: 1rpx solid #F7F8FA;
	}

	.list-row:last-child {
		border-bottom: none;
	}

	.list-row-me {
		background: linear-gradient(90deg, rgba(230, 33, 41, 0.03), rgba(255, 215, 0, 0.05));
	}

	/* 排名列 */
	.lr-rank {
		width: 80rpx;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.lr-medal {
		width: 40rpx;
		height: 40rpx;
		border-radius: 50%;
		background: linear-gradient(135deg, #FFF8E8, #FFF0D0);
		border: 1rpx solid rgba(255, 215, 0, 0.3);
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.lr-medal-num {
		font-size: 20rpx;
		font-weight: 700;
		color: #B8860B;
	}

	.lr-rank-text {
		font-size: 26rpx;
		font-weight: 600;
		color: #B0B5BD;
	}

	/* 用户信息列 */
	.lr-user {
		flex: 1;
		display: flex;
		align-items: center;
		overflow: hidden;
	}

	.lr-nickname {
		margin-left: 16rpx;
		font-size: 26rpx;
		color: #333;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		flex: 1;
	}

	.lr-me-tag {
		background: #D4380D;
		color: #FFFFFF;
		font-size: 18rpx;
		padding: 2rpx 12rpx;
		border-radius: 20rpx;
		margin-left: 8rpx;
		flex-shrink: 0;
	}

	/* 数量列 */
	.lr-count {
		width: 140rpx;
		display: flex;
		align-items: baseline;
		justify-content: flex-end;
		flex-shrink: 0;
	}

	.lr-count-num {
		font-size: 28rpx;
		font-weight: 700;
		color: #D4380D;
	}

	.lr-count-unit {
		font-size: 20rpx;
		color: #999;
		margin-left: 2rpx;
	}

	/* ========== 加载中 ========== */
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

	/* ========== 空状态 ========== */
	.empty-wrap {
		padding: 200rpx 0;
	}

	/* ========== 底部我的排名 ========== */
	.my-rank-bar {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background: #FFFFFF;
		padding: 20rpx 36rpx;
		padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
		display: flex;
		align-items: center;
		justify-content: space-between;
		box-shadow: 0 -2rpx 20rpx rgba(0, 0, 0, 0.06);
		border-top: 2rpx solid rgba(255, 215, 0, 0.2);
		z-index: 99;
	}

	.my-rank-left {
		display: flex;
		align-items: center;
	}

	.my-rank-trophy {
		font-size: 38rpx;
		margin-right: 14rpx;
	}

	.my-rank-info {
		display: flex;
		flex-direction: column;
	}

	.my-rank-label {
		font-size: 24rpx;
		color: #999;
	}

	.my-rank-detail {
		font-size: 22rpx;
		color: #666;
		margin-top: 2rpx;
	}

	.my-rank-badge {
		display: flex;
		align-items: baseline;
		background: linear-gradient(135deg, #E62129, #FF6B35);
		padding: 10rpx 28rpx;
		border-radius: 40rpx;
	}

	.my-rank-badge-num {
		color: #FFFFFF;
		font-weight: 800;
		font-size: 32rpx;
	}

	.my-rank-badge-name {
		color: rgba(255, 255, 255, 0.85);
		font-size: 22rpx;
		margin-left: 4rpx;
	}
</style>

<template>
	<view class="team-page">
		<!-- 顶部导航栏 -->
		<!-- <fa-navbar title="我的团队" :border-bottom="false"></fa-navbar> -->


		<!-- 搜索框 -->
		<view class="search-box" v-if="false">
			<u-search placeholder="搜索团队成员" v-model="searchKeyword"
				:custom-style="{backgroundColor: '#F5F7FA', margin: '0 30rpx'}" @search="handleSearch"></u-search>
		</view>
		<!-- 团队数据概览 -->
		<view class="team-stats">
			<view class="stat-item">
				<text class="stat-value">{{ totalMembers }}</text>
				<text class="stat-label">团队总人数</text>
			</view>
			<view class="stat-item">
				<text class="stat-value">{{ firstLevelMembers }}</text>
				<text class="stat-label">一级成员</text>
			</view>
			<view class="stat-item">
				<text class="stat-value">{{ secondLevelMembers }}</text>
				<text class="stat-label">二级成员</text>
			</view>
			<!-- <view class="stat-item">
				<text class="stat-value">{{ thirdLevelMembers }}</text>
				<text class="stat-label">三级成员</text>
			</view> -->
		</view>

		<!-- 筛选区域 -->
		<view class="filter-section">
			<u-subsection 
			:current="activeFilter" 
			:list="filterOptions" @change="handleFilterChange"
			bg-color="#fff"	button-color="rgba(230,33,41,0.1)" active-color="#E62129" inactive-color="#000"   
			></u-subsection>
		</view>

		

		<!-- 团队列表 -->
		<view class="team-list">
			<view class="list-header" v-if="false">
				<view class="header-name">
					<text>成员信息</text>
					<view class="icons">
						<u-icon name="arrow-down-fill" size="20"></u-icon>
						<!-- <u-icon name="arrow-up-fill" size="20"></u-icon> -->
					</view>
				</view>
				<view class="header-join">
					<text>加入时间</text>
					<view class="icons">
						<u-icon name="arrow-down-fill" size="20"></u-icon>
					</view>
				</view>
				<view class="header-performance">
					<text>业绩</text>
					<view class="icons">
						<u-icon name="arrow-down-fill" size="20"></u-icon>
					</view>
				</view>
			</view>

			<view class="team-member" v-for="(member, index) in filteredMembers" :key="index"
				@click="goToMemberDetail(member.user_id)">
				<view class="member-info">
					<u-avatar :src="member.user.avatar" size="64"></u-avatar>
					<view class="member-detail">
						<view class="member-name-level">
							<text class="member-name">{{ member.user.nickname }}</text>
							<u-tag :text="member.level_name" size="mini" shape="circle" mode="dark"
								:bg-color="member.level_id === 1 ? '#FF3434' : member.level === 2 ? '#3485FF' : '#FFA634'"
								color="#FFFFFF" :custom-style="{marginLeft: '8px'}"></u-tag>
						</view>
						<text class="member-id">ID: {{ member.user_id }}</text>
					</view>
				</view>
				<text class="member-join">{{ member.user.logintime_text }}</text>
				<text class="member-performance">¥{{ member.total_income }}</text>
			</view>

			<view class="no-data" v-if="filteredMembers.length === 0">
				<u-empty mode="list" text="暂无团队成员"></u-empty>
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
				// 团队统计数据
				totalMembers: 156,
				firstLevelMembers: 32,
				secondLevelMembers: 68,
				thirdLevelMembers: 56,

				// 筛选选项
				filterOptions: [{
						name: '全部成员'
					},
					{
						name: '一级成员'
					},
					{
						name: '二级成员'
					}

				],
				activeFilter: 0,

				// 搜索关键词
				searchKeyword: '',

				// 团队成员列表
				teamMembers: [
				],

				// 是否有更多数据
				hasMore: true
			};
		},
		computed: {
			// 筛选后的成员列表
			filteredMembers() {
				let result = [...this.teamMembers];

				// 根据级别筛选
				if (this.activeFilter > 0) {
					const levelMap = ['', 1,2,3];
					result = result.filter(member => member.ulevel === levelMap[this.activeFilter]);
				}

				// 根据关键词搜索
				if (this.searchKeyword) {
					const keyword = this.searchKeyword.toLowerCase();
					result = result.filter(member =>
						member.name.toLowerCase().includes(keyword) ||
						member.id.toLowerCase().includes(keyword)
					);
				}

				return result;
			}
		},
		onLoad() {
			// 加载团队数据
			this.loadTeamData();
		},
		methods: {
			// 加载团队数据
			loadTeamData() {
				this.$u.get("/addons/coagent/api/get_agent_child").then(res => {
					if(res.code == 1){
						this.firstLevelMembers = res.data.team_1_count
						this.secondLevelMembers = res.data.team_2_count
						this.totalMembers = res.data.team_nums
						this.teamMembers = res.data.list
					}
				})
			},

			// 筛选切换
			handleFilterChange(index) {
				this.activeFilter = index;
			},

			// 搜索处理
			handleSearch() {
				console.log('搜索关键词:', this.searchKeyword);
			},

			// 查看成员详情
			goToMemberDetail(memberId) {
				uni.navigateTo({
					url: `/pages/distribution/member-detail?id=${memberId}`
				});
			},

			// 加载更多
			loadMoreData() {
				// 模拟加载更多数据
				if (this.hasMore) {
					setTimeout(() => {
						// 复制现有数据并修改部分信息作为新数据
						const newMembers = this.teamMembers.slice(0, 3).map(member => ({
							...member,
							id: `FX${Math.floor(Math.random() * 10000)}`,
							joinTime: '2023-08-15'
						}));

						this.teamMembers = [...this.teamMembers, ...newMembers];

						// 控制加载更多次数
						if (this.teamMembers.length >= 15) {
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
	.team-page {
		background-color: #F5F7FA;
		min-height: 100vh;
		font-size: 28rpx;
		color: #1D2129;
	}

	// 团队数据概览
	.team-stats {
		margin: 32rpx;
		display: flex;
		padding: 32rpx 0;
		border-radius: 20rpx;
		color: #fff;
		background: linear-gradient( to right, #FF8D3B 0%, #E62129 100%);
		 

		.stat-item {
			flex: 1;
			text-align: center;

			.stat-value {
				font-size: 32rpx;
				font-weight: bold;
				display: block;
			}

			.stat-label {
				font-size: 24rpx;
				margin-top: 10rpx;
			}
		}
	}

	// 筛选区域
	.filter-section {
		 margin: 32rpx ;
	}

	// 搜索框
	.search-box {
		padding:16rpx 32rpx;
		background-color: #FFFFFF;
		margin-bottom: 20rpx;
	}

	// 团队列表
	.team-list {
		background-color: #FFFFFF;
		border-radius: 20rpx;
		margin: 0 30rpx;

		.list-header {
			display: flex;
			padding: 25rpx 30rpx;
			border-bottom: 1px solid #eee;
			
			color: rgba(0,0,0,0.9);
			font-size: 28rpx;

			.header-name {
				width: 40%;
				display: flex;
			}

			.header-join {
				width: 30%;
				text-align: center;
				display: flex;
				justify-content: center;
			}

			.header-performance {
				width: 30%;
				text-align: right;
				display: flex;
				justify-content: flex-end;
			}
			.icons{
				margin-left: 8rpx;
				display: flex;
				align-items: center;
				justify-content: center;
			}
		}

		.team-member {
			display: flex;
			align-items: center;
			padding: 25rpx 30rpx;
			border-bottom: 1px solid #F2F3F5;
			transition: background-color 0.2s;

			&:last-child {
				border-bottom: none;
			}

			&:active {
				background-color: #F5F7FA;
			}

			.member-info {
				display: flex;
				align-items: center;
				width: 40%;

				.member-detail {
					margin-left: 15rpx;

					.member-name-level {
						display: flex;
						align-items: center;

						.member-name {
							font-size: 28rpx;
							color: #1D2129;
							margin-right: 8rpx;
						}
					}

					.member-id {
						font-size: 24rpx;
						color: #86909C;
						margin-top: 5rpx;
						display: inline-block;
					}
				}
			}

			.member-join {
				width: 30%;
				text-align: center;
				color: #86909C;
				font-size: 24rpx;
			}

			.member-performance {
				width: 30%;
				text-align: right;
				font-weight: 500;
				color: #de0011;
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
</style>
<template>
        <view class="team-page">
                <!-- 团队数据概览 -->
                <view class="team-stats">
                        <view class="stat-item">
                                <text class="stat-value">{{ totalMembers }}</text>
                                <text class="stat-label">团队总人数</text>
                        </view>
                        <view class="stat-divider"></view>
                        <view class="stat-item">
                                <text class="stat-value">{{ firstLevelMembers }}</text>
                                <text class="stat-label">一级成员</text>
                        </view>
                        <view class="stat-divider"></view>
                        <view class="stat-item">
                                <text class="stat-value">{{ secondLevelMembers }}</text>
                                <text class="stat-label">二级成员</text>
                        </view>
                </view>

                <!-- 筛选区域 -->
                <view class="filter-section">
                        <view class="filter-tabs">
                                <view class="tab-item" :class="{ active: activeFilter === 0 }" @click="handleFilterChange(0)">
                                        <text>全部成员</text>
                                </view>
                                <view class="tab-item" :class="{ active: activeFilter === 1 }" @click="handleFilterChange(1)">
                                        <text>一级成员</text>
                                </view>
                                <view class="tab-item" :class="{ active: activeFilter === 2 }" @click="handleFilterChange(2)">
                                        <text>二级成员</text>
                                </view>
                        </view>
                </view>

                <!-- 团队列表 -->
                <view class="team-list">
                        <view class="team-member" v-for="(member, index) in filteredMembers" :key="index"
                                @click="goToMemberDetail(member.user_id)">
                                <view class="member-info">
                                        <u-avatar :src="member.user.avatar" size="80"></u-avatar>
                                        <view class="member-detail">
                                                <view class="member-row-top">
                                                        <text class="member-name">{{ member.user.nickname }}</text>
                                                        <view class="member-tag" :class="'level-' + member.ulevel">
                                                                <text>{{ member.level_name }}</text>
                                                        </view>
                                                </view>
                                                <view class="member-id" @click="copyMemberCode(member)">
                                                        <text>邀请码: {{ member.invite_code || '--' }}</text>
                                                        <text class="copy-icon">复制</text>
                                                </view>
                                        </view>
                                </view>
                                <view class="member-right">
                                        <text class="member-performance">¥{{ member.total_income }}</text>
                                        <text class="member-join">{{ member.user.logintime_text }}</text>
                                </view>
                        </view>

                        <view class="no-data" v-if="filteredMembers.length === 0">
                                <text class="no-data-text">暂无团队成员</text>
                        </view>
                </view>

                <!-- 加载更多 -->
                <view class="load-more" v-if="loading">
                        <text class="load-text">加载中...</text>
                </view>
                <view class="load-more" v-else-if="!hasMore && teamMembers.length > 0">
                        <text class="load-text">— 没有更多了 —</text>
                </view>
        </view>
</template>

<script>
        import uTag from '@/uview-ui/components/u-tag/u-tag.vue'
        import uAvatar from '@/uview-ui/components/u-avatar/u-avatar.vue'

        export default {
                components: {
                        uTag,
                        uAvatar
                },
                data() {
                        return {
                                // 团队统计数据
                                totalMembers: 0,
                                firstLevelMembers: 0,
                                secondLevelMembers: 0,

                                activeFilter: 0,

                                // 搜索关键词
                                searchKeyword: '',

                                // 团队成员列表
                                teamMembers: [],

                                // 分页
                                currentPage: 1,
                                pageSize: 50,
                                loading: false,
                                hasMore: false
                        };
                },
                computed: {
                        filteredMembers() {
                                return this.teamMembers;
                        }
                },
                onLoad() {
                        this.loadTeamData();
                },
                methods: {
                        loadTeamData() {
                                this.currentPage = 1;
                                this.teamMembers = [];
                                this._fetchTeamList();
                        },

                        _fetchTeamList() {
                                if (this.loading) return;
                                this.loading = true;

                                this.$api.inviteTeamList({
                                        level: this.activeFilter,
                                        page: this.currentPage,
                                        limit: this.pageSize
                                }).then(res => {
                                        if (res && res.code == 1) {
                                                this.firstLevelMembers = res.data.team_1_count || 0;
                                                this.secondLevelMembers = res.data.team_2_count || 0;
                                                this.totalMembers = res.data.team_nums || 0;

                                                const newList = res.data.list || [];
                                                this.teamMembers = this.currentPage === 1
                                                        ? newList
                                                        : this.teamMembers.concat(newList);

                                                const currentTotal = res.data.total || 0;
                                                this.hasMore = this.teamMembers.length < currentTotal;
                                        }
                                }).catch(err => {
                                        console.error('[Teams] inviteTeamList接口异常:', err);
                                }).finally(() => {
                                        this.loading = false;
                                });
                        },

                        handleFilterChange(index) {
                                this.activeFilter = index;
                                this.loadTeamData();
                        },

                        goToMemberDetail(memberId) {
                                uni.navigateTo({
                                        url: `/pages/distribution/member-detail?id=${memberId}`
                                });
                        },

                        loadMoreData() {
                                if (this.loading || !this.hasMore) return;
                                this.currentPage++;
                                this._fetchTeamList();
                        },

                        copyMemberCode(member) {
                                const code = member.invite_code;
                                if (!code) {
                                        uni.showToast({ title: '邀请码为空', icon: 'none' });
                                        return;
                                }
                                uni.setClipboardData({
                                        data: code,
                                        success: () => {
                                                uni.showToast({ title: '已复制', icon: 'success' });
                                        }
                                });
                        }
                },
                onReachBottom() {
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
                padding-bottom: env(safe-area-inset-bottom);
        }

        /* 团队数据概览 */
        .team-stats {
                margin: 24rpx 24rpx 0;
                display: flex;
                align-items: center;
                padding: 36rpx 16rpx;
                border-radius: 20rpx;
                color: #fff;
                background: linear-gradient(135deg, #FF8D3B 0%, #E62129 100%);
                box-shadow: 0 8rpx 24rpx rgba(230, 33, 41, 0.25);

                .stat-item {
                        flex: 1;
                        text-align: center;

                        .stat-value {
                                font-size: 44rpx;
                                font-weight: bold;
                                display: block;
                                line-height: 1.2;
                        }

                        .stat-label {
                                font-size: 22rpx;
                                margin-top: 8rpx;
                                display: block;
                                opacity: 0.85;
                        }
                }

                .stat-divider {
                        width: 2rpx;
                        height: 60rpx;
                        background: rgba(255, 255, 255, 0.3);
                }
        }

        /* 筛选区域 - 自定义Tab */
        .filter-section {
                margin: 20rpx 24rpx 0;
                background: #fff;
                border-radius: 16rpx;
                padding: 6rpx;
                box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

                .filter-tabs {
                        display: flex;
                        border-radius: 12rpx;
                        overflow: hidden;
                }

                .tab-item {
                        flex: 1;
                        text-align: center;
                        padding: 20rpx 0;
                        font-size: 28rpx;
                        color: #666;
                        position: relative;
                        transition: all 0.25s ease;
                        border-radius: 12rpx;

                        &:active {
                                opacity: 0.7;
                        }

                        &.active {
                                color: #E62129;
                                font-weight: 600;
                                background: rgba(230, 33, 41, 0.08);
                        }
                }
        }

        /* 团队列表 */
        .team-list {
                margin: 20rpx 24rpx 0;
                background-color: #fff;
                border-radius: 20rpx;
                overflow: hidden;
                box-shadow: 0 2rpx 12rpx rgba(0, 0, 0, 0.04);

                .team-member {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 28rpx 30rpx;
                        border-bottom: 1rpx solid #F5F5F5;
                        transition: background-color 0.15s;

                        &:last-child {
                                border-bottom: none;
                        }

                        &:active {
                                background-color: #FAFAFA;
                        }

                        .member-info {
                                display: flex;
                                align-items: center;
                                flex: 1;
                                min-width: 0;

                                .member-detail {
                                        margin-left: 20rpx;
                                        flex: 1;
                                        min-width: 0;

                                        .member-row-top {
                                                display: flex;
                                                align-items: center;
                                                margin-bottom: 8rpx;

                                                .member-name {
                                                        font-size: 30rpx;
                                                        color: #1D2129;
                                                        font-weight: 500;
                                                        overflow: hidden;
                                                        text-overflow: ellipsis;
                                                        white-space: nowrap;
                                                        max-width: 240rpx;
                                                }

                                                .member-tag {
                                                        margin-left: 12rpx;
                                                        padding: 4rpx 16rpx;
                                                        border-radius: 20rpx;
                                                        font-size: 20rpx;
                                                        flex-shrink: 0;

                                                        text {
                                                                line-height: 1.4;
                                                        }

                                                        &.level-1 {
                                                                background: rgba(230, 33, 41, 0.1);
                                                                color: #E62129;
                                                        }

                                                        &.level-2 {
                                                                background: rgba(52, 133, 255, 0.1);
                                                                color: #3485FF;
                                                        }
                                                }
                                        }

                                        .member-id {
                                                font-size: 22rpx;
                                                color: #C0C4CC;
                                                display: flex;
                                                align-items: center;

                                                .copy-icon {
                                                        margin-left: 8rpx;
                                                        color: #999;
                                                        font-size: 20rpx;
                                                        border: 1rpx solid #ddd;
                                                        border-radius: 6rpx;
                                                        padding: 1rpx 8rpx;
                                                }
                                        }
                                }
                        }

                        .member-right {
                                text-align: right;
                                flex-shrink: 0;
                                margin-left: 16rpx;

                                .member-performance {
                                        display: block;
                                        font-size: 30rpx;
                                        font-weight: 600;
                                        color: #E62129;
                                        margin-bottom: 6rpx;
                                }

                                .member-join {
                                        display: block;
                                        font-size: 20rpx;
                                        color: #C0C4CC;
                                }
                        }
                }

                .no-data {
                        padding: 120rpx 0;
                        text-align: center;

                        .no-data-text {
                                color: #C0C4CC;
                                font-size: 28rpx;
                        }
                }
        }

        /* 加载更多 */
        .load-more {
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 48rpx 0 60rpx;
                color: #C0C4CC;

                .load-text {
                        font-size: 24rpx;
                }
        }
</style>

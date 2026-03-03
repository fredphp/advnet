<template>
  <view class="invite-page">
    <!-- 邀请码卡片 -->
    <view class="invite-card">
      <view class="card-header">
        <text class="card-title">我的邀请码</text>
      </view>
      
      <view class="invite-code">
        <text class="code-text">{{ inviteCode }}</text>
      </view>
      
      <view class="invite-actions">
        <view class="action-item" @click="copyInviteCode">
          <image class="action-icon" src="/static/images/copy.png" mode="aspectFit"></image>
          <text>复制邀请码</text>
        </view>
        <view class="action-item" @click="shareToFriend">
          <image class="action-icon" src="/static/images/share.png" mode="aspectFit"></image>
          <text>分享给好友</text>
        </view>
        <view class="action-item" @click="generatePoster">
          <image class="action-icon" src="/static/images/poster.png" mode="aspectFit"></image>
          <text>生成海报</text>
        </view>
      </view>
      
      <view class="invite-link">
        <text class="link-label">邀请链接：</text>
        <text class="link-text">{{ inviteLink }}</text>
      </view>
    </view>
    
    <!-- 邀请统计 -->
    <view class="stat-card">
      <view class="stat-header">
        <text class="stat-title">邀请统计</text>
        <view class="stat-link" @click="goToInviteList">
          <text>查看全部</text>
          <text class="arrow">></text>
        </view>
      </view>
      
      <view class="stat-grid">
        <view class="stat-item">
          <text class="stat-value">{{ inviteStat.totalInviteCount }}</text>
          <text class="stat-label">累计邀请</text>
        </view>
        <view class="stat-item">
          <text class="stat-value">{{ inviteStat.level1Count }}</text>
          <text class="stat-label">一级邀请</text>
        </view>
        <view class="stat-item">
          <text class="stat-value">{{ inviteStat.level2Count }}</text>
          <text class="stat-label">二级邀请</text>
        </view>
        <view class="stat-item">
          <text class="stat-value">{{ inviteStat.validInviteCount }}</text>
          <text class="stat-label">有效邀请</text>
        </view>
      </view>
    </view>
    
    <!-- 佣金统计 -->
    <view class="commission-card">
      <view class="commission-header">
        <text class="commission-title">佣金收益</text>
        <view class="commission-total">
          <image class="coin-icon" src="/static/images/coin.png" mode="aspectFit"></image>
          <text class="total-amount">{{ commissionStat.totalCommission }}</text>
          <text class="total-unit">金币</text>
        </view>
      </view>
      
      <view class="commission-breakdown">
        <view class="breakdown-item">
          <view class="breakdown-left">
            <image class="type-icon" src="/static/images/icon-withdraw.png" mode="aspectFit"></image>
            <text class="type-name">提现分佣</text>
          </view>
          <text class="breakdown-value">+{{ commissionStat.withdrawCommission }}</text>
        </view>
        <view class="breakdown-item">
          <view class="breakdown-left">
            <image class="type-icon" src="/static/images/icon-video.png" mode="aspectFit"></image>
            <text class="type-name">视频分佣</text>
          </view>
          <text class="breakdown-value">+{{ commissionStat.videoCommission }}</text>
        </view>
        <view class="breakdown-item">
          <view class="breakdown-left">
            <image class="type-icon" src="/static/images/icon-redpacket.png" mode="aspectFit"></image>
            <text class="type-name">红包分佣</text>
          </view>
          <text class="breakdown-value">+{{ commissionStat.redPacketCommission }}</text>
        </view>
        <view class="breakdown-item">
          <view class="breakdown-left">
            <image class="type-icon" src="/static/images/icon-game.png" mode="aspectFit"></image>
            <text class="type-name">游戏分佣</text>
          </view>
          <text class="breakdown-value">+{{ commissionStat.gameCommission }}</text>
        </view>
      </view>
      
      <view class="today-commission">
        <text class="today-label">今日收益</text>
        <text class="today-value">+{{ commissionStat.todayCommission }}</text>
      </view>
    </view>
    
    <!-- 分佣规则 -->
    <view class="rules-card">
      <view class="rules-header">
        <text class="rules-title">分佣规则</text>
      </view>
      
      <view class="rules-content">
        <view class="rule-item" v-for="rule in commissionRules" :key="rule.type">
          <view class="rule-header">
            <text class="rule-name">{{ rule.name }}</text>
            <view class="rule-badge">{{ rule.level1Rate }}%</view>
          </view>
          <text class="rule-desc">{{ rule.desc }}</text>
          <view class="rule-example">
            <text>{{ rule.example }}</text>
          </view>
        </view>
      </view>
    </view>
    
    <!-- 邀请好友列表 -->
    <view class="invite-list-card">
      <view class="list-header">
        <text class="list-title">我邀请的好友</text>
        <view class="list-tabs">
          <view 
            class="tab-item"
            :class="{ active: currentLevel === 1 }"
            @click="changeLevel(1)"
          >
            <text>一级({{ inviteStat.level1Count }})</text>
          </view>
          <view 
            class="tab-item"
            :class="{ active: currentLevel === 2 }"
            @click="changeLevel(2)"
          >
            <text>二级({{ inviteStat.level2Count }})</text>
          </view>
        </view>
      </view>
      
      <view class="invite-users">
        <view class="user-item" v-for="user in inviteUsers" :key="user.userId">
          <image class="user-avatar" :src="user.avatar || '/static/images/avatar.png'" mode="aspectFill"></image>
          <view class="user-info">
            <text class="user-name">{{ user.nickname }}</text>
            <text class="user-time">{{ formatTime(user.registerTime) }}</text>
          </view>
          <view class="user-commission">
            <text class="commission-label">贡献</text>
            <text class="commission-value">+{{ user.commissionContribution }}</text>
          </view>
        </view>
        
        <view class="empty-users" v-if="inviteUsers.length === 0">
          <text>暂无邀请好友</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { get, post, showToast } from '@/utils/request'

// 状态
const inviteCode = ref('')
const inviteLink = ref('')
const currentLevel = ref(1)

const inviteStat = ref({
  totalInviteCount: 0,
  level1Count: 0,
  level2Count: 0,
  validInviteCount: 0
})

const commissionStat = ref({
  totalCommission: 0,
  withdrawCommission: 0,
  videoCommission: 0,
  redPacketCommission: 0,
  gameCommission: 0,
  todayCommission: 0
})

const inviteUsers = ref([])

const commissionRules = ref([
  {
    type: 'withdraw',
    name: '提现分佣',
    level1Rate: 20,
    desc: '下级提现成功后，上级获得佣金',
    example: '例：下级提现50元 → 一级上级得10元，二级上级得5元'
  },
  {
    type: 'video',
    name: '视频分佣',
    level1Rate: 1,
    desc: '下级观看视频获得金币时，上级获得佣金',
    example: '例：下级看视频得100金币 → 一级上级得1金币'
  },
  {
    type: 'red_packet',
    name: '红包分佣',
    level1Rate: 1,
    desc: '下级抢红包获得金币时，上级获得佣金',
    example: '例：下级抢红包得500金币 → 一级上级得5金币'
  }
])

// 初始化
onMounted(() => {
  fetchInviteCode()
  fetchInviteStat()
  fetchCommissionStat()
  fetchInviteUsers()
})

// 获取邀请码
const fetchInviteCode = async () => {
  try {
    const res = await get('/api/invite/myCode', {}, { showLoading: false })
    if (res.code === 1) {
      inviteCode.value = res.data.invite_code || ''
      inviteLink.value = res.data.invite_link || ''
    }
  } catch (e) {
    console.error(e)
  }
}

// 获取邀请统计
const fetchInviteStat = async () => {
  try {
    const res = await get('/api/invite/overview', {}, { showLoading: false })
    if (res.code === 1) {
      inviteStat.value = {
        totalInviteCount: res.data.total_invite_count || 0,
        level1Count: res.data.level1_count || 0,
        level2Count: res.data.level2_count || 0,
        validInviteCount: res.data.valid_invite_count || 0
      }
      
      commissionStat.value = {
        totalCommission: res.data.total_coin || 0,
        withdrawCommission: res.data.withdraw_commission || 0,
        videoCommission: res.data.video_commission || 0,
        redPacketCommission: res.data.red_packet_commission || 0,
        gameCommission: res.data.game_commission || 0,
        todayCommission: res.data.today_coin || 0
      }
    }
  } catch (e) {
    console.error(e)
  }
}

// 获取佣金统计
const fetchCommissionStat = async () => {
  // 已在 fetchInviteStat 中获取
}

// 获取邀请用户列表
const fetchInviteUsers = async () => {
  try {
    const res = await get('/api/invite/list', {
      level: currentLevel.value,
      page: 1,
      limit: 20
    }, { showLoading: false })
    
    if (res.code === 1) {
      inviteUsers.value = res.data.list || []
    }
  } catch (e) {
    console.error(e)
  }
}

// 切换层级
const changeLevel = (level) => {
  currentLevel.value = level
  fetchInviteUsers()
}

// 复制邀请码
const copyInviteCode = () => {
  uni.setClipboardData({
    data: inviteCode.value,
    success: () => {
      showToast('邀请码已复制')
    }
  })
}

// 分享给好友
const shareToFriend = () => {
  // #ifdef MP-WEIXIN
  // 微信小程序分享
  // #endif
  
  // #ifdef H5
  // H5 分享
  uni.setClipboardData({
    data: inviteLink.value,
    success: () => {
      showToast('链接已复制，去分享给好友吧')
    }
  })
  // #endif
}

// 生成海报
const generatePoster = () => {
  uni.navigateTo({ url: '/pages/invite-poster/invite-poster' })
}

// 查看全部邀请列表
const goToInviteList = () => {
  uni.navigateTo({ url: '/pages/invite-list/invite-list' })
}

// 格式化时间
const formatTime = (timestamp) => {
  const date = new Date(timestamp * 1000)
  return `${date.getMonth() + 1}月${date.getDate()}日`
}
</script>

<style lang="scss" scoped>
.invite-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30rpx;
}

.invite-card {
  background: linear-gradient(135deg, #FF6B00 0%, #FF2D55 100%);
  border-radius: 24rpx;
  padding: 40rpx;
  margin-bottom: 30rpx;
}

.card-header {
  margin-bottom: 20rpx;
}

.card-title {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.8);
}

.invite-code {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 16rpx;
  padding: 30rpx;
  text-align: center;
  margin-bottom: 30rpx;
}

.code-text {
  font-size: 56rpx;
  font-weight: bold;
  color: #fff;
  letter-spacing: 8rpx;
}

.invite-actions {
  display: flex;
  justify-content: space-around;
  margin-bottom: 30rpx;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  
  .action-icon {
    width: 64rpx;
    height: 64rpx;
    margin-bottom: 12rpx;
  }
  
  text {
    font-size: 24rpx;
    color: #fff;
  }
}

.invite-link {
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12rpx;
  padding: 20rpx;
  
  .link-label {
    font-size: 24rpx;
    color: rgba(255, 255, 255, 0.7);
  }
  
  .link-text {
    font-size: 22rpx;
    color: #fff;
    word-break: break-all;
  }
}

.stat-card,
.commission-card,
.rules-card,
.invite-list-card {
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  margin-bottom: 30rpx;
}

.stat-header,
.commission-header,
.rules-header,
.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24rpx;
}

.stat-title,
.commission-title,
.rules-title,
.list-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
}

.stat-link {
  display: flex;
  align-items: center;
  
  text {
    font-size: 26rpx;
    color: #999;
  }
  
  .arrow {
    margin-left: 8rpx;
  }
}

.stat-grid {
  display: flex;
  flex-wrap: wrap;
}

.stat-item {
  width: 50%;
  text-align: center;
  padding: 20rpx 0;
}

.stat-value {
  display: block;
  font-size: 44rpx;
  font-weight: bold;
  color: #333;
}

.stat-label {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-top: 8rpx;
}

.commission-header {
  flex-direction: column;
  align-items: flex-start;
}

.commission-total {
  display: flex;
  align-items: center;
  margin-top: 20rpx;
}

.coin-icon {
  width: 48rpx;
  height: 48rpx;
  margin-right: 12rpx;
}

.total-amount {
  font-size: 56rpx;
  font-weight: bold;
  color: #FF6B00;
}

.total-unit {
  font-size: 28rpx;
  color: #FF6B00;
  margin-left: 8rpx;
}

.commission-breakdown {
  margin-top: 20rpx;
  border-top: 1rpx solid #f5f5f5;
  padding-top: 20rpx;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16rpx 0;
}

.breakdown-left {
  display: flex;
  align-items: center;
}

.type-icon {
  width: 40rpx;
  height: 40rpx;
  margin-right: 16rpx;
}

.type-name {
  font-size: 28rpx;
  color: #333;
}

.breakdown-value {
  font-size: 28rpx;
  color: #FF6B00;
  font-weight: bold;
}

.today-commission {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20rpx;
  padding-top: 20rpx;
  border-top: 1rpx solid #f5f5f5;
}

.today-label {
  font-size: 26rpx;
  color: #999;
}

.today-value {
  font-size: 32rpx;
  color: #FF6B00;
  font-weight: bold;
}

.rules-content {
  margin-top: 10rpx;
}

.rule-item {
  padding: 20rpx;
  background: #f9f9f9;
  border-radius: 12rpx;
  margin-bottom: 16rpx;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.rule-header {
  display: flex;
  align-items: center;
  margin-bottom: 10rpx;
}

.rule-name {
  font-size: 28rpx;
  font-weight: bold;
  color: #333;
}

.rule-badge {
  margin-left: 16rpx;
  padding: 4rpx 16rpx;
  background: #FF6B00;
  border-radius: 20rpx;
  
  text {
    font-size: 22rpx;
    color: #fff;
  }
}

.rule-desc {
  font-size: 24rpx;
  color: #666;
  display: block;
  margin-bottom: 10rpx;
}

.rule-example {
  padding: 10rpx 16rpx;
  background: rgba(255, 107, 0, 0.1);
  border-radius: 8rpx;
  
  text {
    font-size: 22rpx;
    color: #FF6B00;
  }
}

.list-tabs {
  display: flex;
  gap: 16rpx;
}

.tab-item {
  padding: 8rpx 20rpx;
  background: #f5f5f5;
  border-radius: 20rpx;
  
  text {
    font-size: 24rpx;
    color: #666;
  }
  
  &.active {
    background: rgba(255, 107, 0, 0.1);
    
    text {
      color: #FF6B00;
    }
  }
}

.invite-users {
  margin-top: 20rpx;
}

.user-item {
  display: flex;
  align-items: center;
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f5f5f5;
  
  &:last-child {
    border-bottom: none;
  }
}

.user-avatar {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  margin-right: 20rpx;
}

.user-info {
  flex: 1;
}

.user-name {
  display: block;
  font-size: 28rpx;
  color: #333;
}

.user-time {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-top: 8rpx;
}

.user-commission {
  text-align: right;
}

.commission-label {
  display: block;
  font-size: 22rpx;
  color: #999;
}

.commission-value {
  display: block;
  font-size: 28rpx;
  color: #FF6B00;
  font-weight: bold;
}

.empty-users {
  padding: 60rpx 0;
  text-align: center;
  
  text {
    font-size: 28rpx;
    color: #999;
  }
}
</style>

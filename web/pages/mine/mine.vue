<template>
  <view class="mine-page">
    <!-- 用户信息卡片 -->
    <view class="user-card">
      <view class="user-header">
        <image 
          class="avatar" 
          :src="userInfo.avatar || '/static/images/avatar.png'" 
          mode="aspectFill"
        ></image>
        <view class="user-info">
          <text class="nickname">{{ userInfo.nickname || '未登录' }}</text>
          <text class="user-id" v-if="isLoggedIn">ID: {{ userInfo.id }}</text>
        </view>
        <view class="edit-btn" @click="goToEdit" v-if="isLoggedIn">
          <text>编辑资料</text>
        </view>
      </view>
      
      <!-- 数据统计 -->
      <view class="stat-row">
        <view class="stat-item">
          <text class="stat-value">{{ userInfo.total_invite || 0 }}</text>
          <text class="stat-label">邀请人数</text>
        </view>
        <view class="stat-item">
          <text class="stat-value">{{ userInfo.total_video || 0 }}</text>
          <text class="stat-label">观看视频</text>
        </view>
        <view class="stat-item">
          <text class="stat-value">{{ userInfo.total_task || 0 }}</text>
          <text class="stat-label">完成任务</text>
        </view>
      </view>
    </view>
    
    <!-- 金币卡片 -->
    <view class="coin-card">
      <view class="coin-header">
        <text class="coin-title">我的金币</text>
        <view class="coin-history" @click="goToIncome">
          <text>收支明细</text>
          <text class="arrow">></text>
        </view>
      </view>
      
      <view class="coin-content">
        <view class="coin-main">
          <image class="coin-icon" src="/static/images/coin.png" mode="aspectFit"></image>
          <text class="coin-amount">{{ coinStore.balance }}</text>
          <text class="coin-unit">金币</text>
        </view>
        <view class="coin-info">
          <view class="info-item">
            <text class="info-label">今日收益</text>
            <text class="info-value">+{{ coinStore.todayEarn }}</text>
          </view>
          <view class="info-item">
            <text class="info-label">冻结中</text>
            <text class="info-value">{{ coinStore.frozen }}</text>
          </view>
        </view>
      </view>
      
      <view class="coin-actions">
        <view class="action-btn primary" @click="goToWithdraw">
          <text>立即提现</text>
        </view>
        <view class="action-btn" @click="goToExchange">
          <text>金币兑换</text>
        </view>
      </view>
      
      <view class="cash-tips">
        <text>≈ ¥{{ coinStore.cashAmount }} (10000金币=1元)</text>
      </view>
    </view>
    
    <!-- 功能菜单 -->
    <view class="menu-card">
      <view class="menu-item" @click="goToInvite">
        <image class="menu-icon" src="/static/images/icon-invite.png" mode="aspectFit"></image>
        <text class="menu-text">邀请赚钱</text>
        <view class="menu-badge" v-if="inviteReward > 0">
          <text>+{{ inviteReward }}元</text>
        </view>
        <text class="menu-arrow">></text>
      </view>
      
      <view class="menu-item" @click="goToRedpacket">
        <image class="menu-icon" src="/static/images/icon-redpacket.png" mode="aspectFit"></image>
        <text class="menu-text">红包任务</text>
        <text class="menu-arrow">></text>
      </view>
      
      <view class="menu-item" @click="goToVideoRecord">
        <image class="menu-icon" src="/static/images/icon-video.png" mode="aspectFit"></image>
        <text class="menu-text">观看记录</text>
        <text class="menu-arrow">></text>
      </view>
      
      <view class="menu-item" @click="goToWithdrawRecord">
        <image class="menu-icon" src="/static/images/icon-withdraw.png" mode="aspectFit"></image>
        <text class="menu-text">提现记录</text>
        <text class="menu-arrow">></text>
      </view>
    </view>
    
    <!-- 设置菜单 -->
    <view class="menu-card">
      <view class="menu-item" @click="goToSettings">
        <image class="menu-icon" src="/static/images/icon-setting.png" mode="aspectFit"></image>
        <text class="menu-text">设置</text>
        <text class="menu-arrow">></text>
      </view>
      
      <view class="menu-item" @click="goToAbout">
        <image class="menu-icon" src="/static/images/icon-about.png" mode="aspectFit"></image>
        <text class="menu-text">关于我们</text>
        <text class="menu-arrow">></text>
      </view>
      
      <view class="menu-item" @click="goToFeedback">
        <image class="menu-icon" src="/static/images/icon-feedback.png" mode="aspectFit"></image>
        <text class="menu-text">意见反馈</text>
        <text class="menu-arrow">></text>
      </view>
    </view>
    
    <!-- 登录按钮 -->
    <view class="login-btn" v-if="!isLoggedIn" @click="goToLogin">
      <text>登录/注册</text>
    </view>
  </view>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/store/user'
import { useCoinStore } from '@/store/coin'

const userStore = useUserStore()
const coinStore = useCoinStore()

const isLoggedIn = computed(() => userStore.isLoggedIn)
const userInfo = computed(() => userStore.userInfo || {})
const inviteReward = ref(0)

onMounted(() => {
  if (isLoggedIn.value) {
    coinStore.fetchAccountInfo()
  }
})

// 页面跳转
const goToLogin = () => {
  uni.navigateTo({ url: '/pages/login/login' })
}

const goToEdit = () => {
  uni.navigateTo({ url: '/pages/edit-profile/edit-profile' })
}

const goToIncome = () => {
  uni.navigateTo({ url: '/pages/income/income' })
}

const goToWithdraw = () => {
  if (!isLoggedIn.value) {
    goToLogin()
    return
  }
  uni.navigateTo({ url: '/pages/withdraw/withdraw' })
}

const goToExchange = () => {
  uni.navigateTo({ url: '/pages/exchange/exchange' })
}

const goToInvite = () => {
  uni.switchTab({ url: '/pages/invite/invite' })
}

const goToRedpacket = () => {
  uni.switchTab({ url: '/pages/redpacket/redpacket' })
}

const goToVideoRecord = () => {
  uni.navigateTo({ url: '/pages/video-record/video-record' })
}

const goToWithdrawRecord = () => {
  uni.navigateTo({ url: '/pages/withdraw-record/withdraw-record' })
}

const goToSettings = () => {
  uni.navigateTo({ url: '/pages/settings/settings' })
}

const goToAbout = () => {
  uni.navigateTo({ url: '/pages/about/about' })
}

const goToFeedback = () => {
  uni.navigateTo({ url: '/pages/feedback/feedback' })
}
</script>

<style lang="scss" scoped>
.mine-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30rpx;
}

.user-card {
  background: linear-gradient(135deg, #FF6B00 0%, #FF2D55 100%);
  border-radius: 24rpx;
  padding: 40rpx;
  margin-bottom: 30rpx;
}

.user-header {
  display: flex;
  align-items: center;
  margin-bottom: 30rpx;
}

.avatar {
  width: 120rpx;
  height: 120rpx;
  border-radius: 50%;
  border: 4rpx solid rgba(255, 255, 255, 0.5);
}

.user-info {
  flex: 1;
  margin-left: 24rpx;
}

.nickname {
  display: block;
  font-size: 36rpx;
  font-weight: bold;
  color: #fff;
}

.user-id {
  display: block;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 8rpx;
}

.edit-btn {
  padding: 12rpx 24rpx;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 30rpx;
  
  text {
    font-size: 24rpx;
    color: #fff;
  }
}

.stat-row {
  display: flex;
  justify-content: space-around;
  padding-top: 20rpx;
  border-top: 1rpx solid rgba(255, 255, 255, 0.2);
}

.stat-item {
  text-align: center;
}

.stat-value {
  display: block;
  font-size: 40rpx;
  font-weight: bold;
  color: #fff;
}

.stat-label {
  display: block;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.7);
  margin-top: 8rpx;
}

.coin-card {
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  margin-bottom: 30rpx;
}

.coin-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20rpx;
}

.coin-title {
  font-size: 32rpx;
  font-weight: bold;
  color: #333;
}

.coin-history {
  display: flex;
  align-items: center;
  
  text {
    font-size: 26rpx;
    color: #666;
  }
  
  .arrow {
    margin-left: 8rpx;
    color: #999;
  }
}

.coin-content {
  padding: 20rpx 0;
  border-bottom: 1rpx solid #f5f5f5;
}

.coin-main {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 20rpx;
}

.coin-icon {
  width: 60rpx;
  height: 60rpx;
  margin-right: 16rpx;
}

.coin-amount {
  font-size: 64rpx;
  font-weight: bold;
  color: #FF6B00;
}

.coin-unit {
  font-size: 28rpx;
  color: #FF6B00;
  margin-left: 8rpx;
}

.coin-info {
  display: flex;
  justify-content: center;
  gap: 60rpx;
}

.info-item {
  text-align: center;
}

.info-label {
  font-size: 24rpx;
  color: #999;
}

.info-value {
  font-size: 28rpx;
  color: #333;
  margin-left: 8rpx;
}

.coin-actions {
  display: flex;
  gap: 20rpx;
  margin-top: 30rpx;
}

.action-btn {
  flex: 1;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 40rpx;
  background: #f5f5f5;
  
  text {
    font-size: 28rpx;
    color: #666;
  }
  
  &.primary {
    background: linear-gradient(90deg, #FF6B00 0%, #FF2D55 100%);
    
    text {
      color: #fff;
    }
  }
}

.cash-tips {
  text-align: center;
  margin-top: 20rpx;
  
  text {
    font-size: 24rpx;
    color: #999;
  }
}

.menu-card {
  background: #fff;
  border-radius: 24rpx;
  margin-bottom: 30rpx;
  overflow: hidden;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f5f5f5;
  
  &:last-child {
    border-bottom: none;
  }
}

.menu-icon {
  width: 48rpx;
  height: 48rpx;
  margin-right: 24rpx;
}

.menu-text {
  flex: 1;
  font-size: 30rpx;
  color: #333;
}

.menu-badge {
  background: #FF2D55;
  padding: 4rpx 16rpx;
  border-radius: 20rpx;
  
  text {
    font-size: 24rpx;
    color: #fff;
  }
}

.menu-arrow {
  font-size: 28rpx;
  color: #ccc;
  margin-left: 16rpx;
}

.login-btn {
  margin-top: 60rpx;
  height: 90rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(90deg, #FF6B00 0%, #FF2D55 100%);
  border-radius: 45rpx;
  
  text {
    font-size: 32rpx;
    color: #fff;
    font-weight: bold;
  }
}
</style>

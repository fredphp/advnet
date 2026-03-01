<template>
  <view class="settings-page">
    <!-- 账号设置 -->
    <view class="settings-group">
      <view class="group-title">账号设置</view>
      
      <view class="settings-item" @click="goToEditProfile">
        <text class="item-label">个人资料</text>
        <view class="item-right">
          <image class="avatar" :src="userInfo.avatar || '/static/images/avatar.png'" mode="aspectFill"></image>
          <text class="arrow">></text>
        </view>
      </view>
      
      <view class="settings-item" @click="goToBindPhone">
        <text class="item-label">绑定手机</text>
        <view class="item-right">
          <text class="item-value">{{ maskPhone(userInfo.mobile) }}</text>
          <text class="arrow">></text>
        </view>
      </view>
      
      <view class="settings-item" @click="goToBindWechat">
        <text class="item-label">绑定微信</text>
        <view class="item-right">
          <text class="item-value" :class="{ binded: isWechatBinded }">
            {{ isWechatBinded ? '已绑定' : '未绑定' }}
          </text>
          <text class="arrow">></text>
        </view>
      </view>
      
      <view class="settings-item" @click="goToWithdrawAccount">
        <text class="item-label">提现账户</text>
        <view class="item-right">
          <text class="item-value">{{ withdrawAccountText }}</text>
          <text class="arrow">></text>
        </view>
      </view>
    </view>
    
    <!-- 通知设置 -->
    <view class="settings-group">
      <view class="group-title">通知设置</view>
      
      <view class="settings-item">
        <text class="item-label">收益通知</text>
        <switch :checked="settings.incomeNotify" @change="toggleSetting('incomeNotify')" color="#FF6B00" />
      </view>
      
      <view class="settings-item">
        <text class="item-label">提现通知</text>
        <switch :checked="settings.withdrawNotify" @change="toggleSetting('withdrawNotify')" color="#FF6B00" />
      </view>
      
      <view class="settings-item">
        <text class="item-label">任务提醒</text>
        <switch :checked="settings.taskNotify" @change="toggleSetting('taskNotify')" color="#FF6B00" />
      </view>
    </view>
    
    <!-- 其他设置 -->
    <view class="settings-group">
      <view class="group-title">其他设置</view>
      
      <view class="settings-item" @click="clearCache">
        <text class="item-label">清除缓存</text>
        <view class="item-right">
          <text class="item-value">{{ cacheSize }}</text>
          <text class="arrow">></text>
        </view>
      </view>
      
      <view class="settings-item" @click="goToAbout">
        <text class="item-label">关于我们</text>
        <view class="item-right">
          <text class="item-value">v{{ appVersion }}</text>
          <text class="arrow">></text>
        </view>
      </view>
      
      <view class="settings-item" @click="goToFeedback">
        <text class="item-label">意见反馈</text>
        <text class="arrow">></text>
      </view>
      
      <view class="settings-item" @click="goToAgreement">
        <text class="item-label">用户协议</text>
        <text class="arrow">></text>
      </view>
      
      <view class="settings-item" @click="goToPrivacy">
        <text class="item-label">隐私政策</text>
        <text class="arrow">></text>
      </view>
    </view>
    
    <!-- 退出登录 -->
    <view class="logout-btn" @click="logout">
      <text>退出登录</text>
    </view>
  </view>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useUserStore } from '@/store/user'
import { showToast, showLoading, hideLoading } from '@/utils/request'

const userStore = useUserStore()

// 用户信息
const userInfo = computed(() => userStore.userInfo || {})

// 状态
const isWechatBinded = ref(false)
const withdrawAccountText = ref('未设置')
const cacheSize = ref('0KB')
const appVersion = ref('1.0.0')

const settings = ref({
  incomeNotify: true,
  withdrawNotify: true,
  taskNotify: true
})

// 初始化
onMounted(() => {
  loadSettings()
  calculateCacheSize()
  checkWechatBind()
  checkWithdrawAccount()
})

// 加载设置
const loadSettings = () => {
  const savedSettings = uni.getStorageSync('user_settings')
  if (savedSettings) {
    settings.value = { ...settings.value, ...savedSettings }
  }
}

// 切换设置
const toggleSetting = (key) => {
  settings.value[key] = !settings.value[key]
  uni.setStorageSync('user_settings', settings.value)
}

// 计算缓存大小
const calculateCacheSize = () => {
  uni.getStorageInfo({
    success: (res) => {
      const size = res.currentSize
      if (size < 1024) {
        cacheSize.value = size + 'KB'
      } else {
        cacheSize.value = (size / 1024).toFixed(2) + 'MB'
      }
    }
  })
}

// 清除缓存
const clearCache = () => {
  uni.showModal({
    title: '提示',
    content: '确定要清除缓存吗？',
    success: (res) => {
      if (res.confirm) {
        showLoading('清除中...')
        
        // 清除本地缓存（保留用户信息）
        const token = uni.getStorageSync('token')
        const userInfo = uni.getStorageSync('userInfo')
        
        uni.clearStorageSync()
        
        // 恢复用户信息
        if (token) uni.setStorageSync('token', token)
        if (userInfo) uni.setStorageSync('userInfo', userInfo)
        
        hideLoading()
        showToast('缓存已清除')
        cacheSize.value = '0KB'
      }
    }
  })
}

// 检查微信绑定
const checkWechatBind = async () => {
  // TODO: 调用接口检查
}

// 检查提现账户
const checkWithdrawAccount = async () => {
  // TODO: 调用接口检查
}

// 脱敏手机号
const maskPhone = (phone) => {
  if (!phone) return '未绑定'
  return phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2')
}

// 页面跳转
const goToEditProfile = () => {
  uni.navigateTo({ url: '/pages/edit-profile/edit-profile' })
}

const goToBindPhone = () => {
  uni.navigateTo({ url: '/pages/bind-phone/bind-phone' })
}

const goToBindWechat = () => {
  uni.navigateTo({ url: '/pages/bind-wechat/bind-wechat' })
}

const goToWithdrawAccount = () => {
  uni.navigateTo({ url: '/pages/withdraw-account/withdraw-account' })
}

const goToAbout = () => {
  uni.navigateTo({ url: '/pages/about/about' })
}

const goToFeedback = () => {
  uni.navigateTo({ url: '/pages/feedback/feedback' })
}

const goToAgreement = () => {
  uni.navigateTo({ url: '/pages/webview/webview?type=agreement' })
}

const goToPrivacy = () => {
  uni.navigateTo({ url: '/pages/webview/webview?type=privacy' })
}

// 退出登录
const logout = () => {
  uni.showModal({
    title: '提示',
    content: '确定要退出登录吗？',
    success: (res) => {
      if (res.confirm) {
        userStore.logout()
      }
    }
  })
}
</script>

<style lang="scss" scoped>
.settings-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30rpx;
  padding-bottom: 150rpx;
}

.settings-group {
  background: #fff;
  border-radius: 24rpx;
  margin-bottom: 30rpx;
  overflow: hidden;
}

.group-title {
  padding: 30rpx;
  font-size: 28rpx;
  color: #999;
}

.settings-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 30rpx;
  border-bottom: 1rpx solid #f5f5f5;
  
  &:last-child {
    border-bottom: none;
  }
}

.item-label {
  font-size: 30rpx;
  color: #333;
}

.item-right {
  display: flex;
  align-items: center;
}

.avatar {
  width: 60rpx;
  height: 60rpx;
  border-radius: 50%;
}

.item-value {
  font-size: 28rpx;
  color: #999;
  margin-right: 16rpx;
  
  &.binded {
    color: #52c41a;
  }
}

.arrow {
  font-size: 28rpx;
  color: #ccc;
}

.logout-btn {
  position: fixed;
  left: 30rpx;
  right: 30rpx;
  bottom: 60rpx;
  height: 90rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  border-radius: 45rpx;
  border: 1rpx solid #FF2D55;
  
  text {
    font-size: 32rpx;
    color: #FF2D55;
  }
}
</style>

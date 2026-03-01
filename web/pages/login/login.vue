<template>
  <view class="login-page">
    <!-- Logo -->
    <view class="logo-section">
      <image class="logo" src="/static/images/logo.png" mode="aspectFit"></image>
      <text class="app-name">金币视频</text>
      <text class="app-slogan">看视频 赚金币 领现金</text>
    </view>
    
    <!-- 微信授权登录 -->
    <view class="login-section">
      <button class="wechat-btn" open-type="getPhoneNumber" @getphonenumber="onGetPhoneNumber">
        <image class="wechat-icon" src="/static/images/wechat.png" mode="aspectFit"></image>
        <text>微信一键登录</text>
      </button>
      
      <view class="divider">
        <view class="line"></view>
        <text>其他登录方式</text>
        <view class="line"></view>
      </view>
      
      <!-- 手机号登录 -->
      <view class="phone-login">
        <view class="input-row">
          <text class="country-code">+86</text>
          <input 
            class="phone-input"
            type="number"
            v-model="phoneNumber"
            placeholder="请输入手机号"
            maxlength="11"
          />
        </view>
        
        <view class="input-row">
          <input 
            class="code-input"
            type="number"
            v-model="verifyCode"
            placeholder="请输入验证码"
            maxlength="6"
          />
          <view 
            class="code-btn" 
            :class="{ disabled: countdown > 0 }"
            @click="sendVerifyCode"
          >
            <text>{{ countdown > 0 ? `${countdown}s` : '获取验证码' }}</text>
          </view>
        </view>
        
        <view class="login-btn" @click="phoneLogin">
          <text>登录</text>
        </view>
      </view>
    </view>
    
    <!-- 协议 -->
    <view class="agreement">
      <view class="checkbox" :class="{ checked: agreed }" @click="agreed = !agreed">
        <image v-if="agreed" class="check-icon" src="/static/images/check.png" mode="aspectFit"></image>
      </view>
      <text class="agreement-text">
        登录即代表同意
        <text class="link" @click.stop="goToAgreement('user')">《用户协议》</text>
        和
        <text class="link" @click.stop="goToAgreement('privacy')">《隐私政策》</text>
      </text>
    </view>
    
    <!-- 邀请码 -->
    <view class="invite-section" v-if="showInviteCode">
      <view class="invite-title">填写邀请码(选填)</view>
      <input 
        class="invite-input"
        v-model="inviteCode"
        placeholder="请输入邀请码"
      />
    </view>
  </view>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useUserStore } from '@/store/user'
import { post, showToast, showLoading, hideLoading } from '@/utils/request'

const userStore = useUserStore()

// 状态
const phoneNumber = ref('')
const verifyCode = ref('')
const inviteCode = ref('')
const agreed = ref(false)
const countdown = ref(0)
const showInviteCode = ref(false)

// 登录来源页
let redirectUrl = '/pages/index/index'

onMounted(() => {
  // 获取跳转参数
  const pages = getCurrentPages()
  const currentPage = pages[pages.length - 1]
  const options = currentPage.options || {}
  
  if (options.redirect) {
    redirectUrl = decodeURIComponent(options.redirect)
  }
  
  if (options.invite_code) {
    inviteCode.value = options.invite_code
    showInviteCode.value = true
  }
})

// 微信授权登录
const onGetPhoneNumber = async (e) => {
  if (!agreed.value) {
    showToast('请先同意用户协议')
    return
  }
  
  if (e.detail.errMsg !== 'getPhoneNumber:ok') {
    showToast('授权失败')
    return
  }
  
  showLoading('登录中...')
  
  try {
    // 1. 获取微信code
    const loginRes = await new Promise((resolve, reject) => {
      uni.login({
        provider: 'weixin',
        success: resolve,
        fail: reject
      })
    })
    
    if (!loginRes.code) {
      throw new Error('获取code失败')
    }
    
    // 2. 调用后端登录
    const res = await post('/api/user/wechatLogin', {
      code: loginRes.code,
      encrypted_data: e.detail.encryptedData,
      iv: e.detail.iv,
      invite_code: inviteCode.value
    })
    
    hideLoading()
    
    if (res.code === 1) {
      // 保存token和用户信息
      userStore.token = res.data.token
      userStore.userInfo = res.data.user_info
      
      showToast('登录成功')
      
      // 跳转
      setTimeout(() => {
        uni.switchTab({ url: '/pages/index/index' })
      }, 1000)
    } else {
      showToast(res.msg || '登录失败')
    }
  } catch (e) {
    hideLoading()
    showToast('登录失败，请重试')
    console.error(e)
  }
}

// 发送验证码
const sendVerifyCode = async () => {
  if (countdown.value > 0) return
  
  if (!phoneNumber.value || phoneNumber.value.length !== 11) {
    showToast('请输入正确的手机号')
    return
  }
  
  showLoading('发送中...')
  
  try {
    const res = await post('/api/sms/send', {
      phone: phoneNumber.value,
      type: 'login'
    })
    
    hideLoading()
    
    if (res.code === 1) {
      showToast('验证码已发送')
      startCountdown()
    } else {
      showToast(res.msg || '发送失败')
    }
  } catch (e) {
    hideLoading()
    showToast('发送失败，请重试')
  }
}

// 开始倒计时
const startCountdown = () => {
  countdown.value = 60
  const timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) {
      clearInterval(timer)
    }
  }, 1000)
}

// 手机号登录
const phoneLogin = async () => {
  if (!agreed.value) {
    showToast('请先同意用户协议')
    return
  }
  
  if (!phoneNumber.value || phoneNumber.value.length !== 11) {
    showToast('请输入正确的手机号')
    return
  }
  
  if (!verifyCode.value || verifyCode.value.length !== 6) {
    showToast('请输入验证码')
    return
  }
  
  showLoading('登录中...')
  
  try {
    const res = await post('/api/user/phoneLogin', {
      phone: phoneNumber.value,
      code: verifyCode.value,
      invite_code: inviteCode.value
    })
    
    hideLoading()
    
    if (res.code === 1) {
      userStore.token = res.data.token
      userStore.userInfo = res.data.user_info
      
      showToast('登录成功')
      
      setTimeout(() => {
        uni.switchTab({ url: '/pages/index/index' })
      }, 1000)
    } else {
      showToast(res.msg || '登录失败')
    }
  } catch (e) {
    hideLoading()
    showToast('登录失败，请重试')
  }
}

// 跳转协议页
const goToAgreement = (type) => {
  uni.navigateTo({
    url: `/pages/webview/webview?type=${type}`
  })
}
</script>

<style lang="scss" scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #FFF5F0 0%, #FFFFFF 100%);
  padding: 0 60rpx;
}

.logo-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding-top: 150rpx;
  margin-bottom: 80rpx;
}

.logo {
  width: 160rpx;
  height: 160rpx;
}

.app-name {
  font-size: 48rpx;
  font-weight: bold;
  color: #333;
  margin-top: 30rpx;
}

.app-slogan {
  font-size: 28rpx;
  color: #999;
  margin-top: 16rpx;
}

.login-section {
  margin-bottom: 60rpx;
}

.wechat-btn {
  width: 100%;
  height: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #07C160;
  border-radius: 48rpx;
  border: none;
  
  text {
    font-size: 32rpx;
    color: #fff;
    margin-left: 16rpx;
  }
}

.wechat-icon {
  width: 48rpx;
  height: 48rpx;
}

.divider {
  display: flex;
  align-items: center;
  margin: 50rpx 0;
  
  text {
    font-size: 24rpx;
    color: #999;
    padding: 0 24rpx;
  }
  
  .line {
    flex: 1;
    height: 1rpx;
    background: #eee;
  }
}

.phone-login {
  .input-row {
    display: flex;
    align-items: center;
    height: 100rpx;
    border-bottom: 1rpx solid #f0f0f0;
  }
  
  .country-code {
    font-size: 32rpx;
    color: #333;
    padding-right: 20rpx;
    border-right: 1rpx solid #f0f0f0;
    margin-right: 20rpx;
  }
  
  .phone-input,
  .code-input {
    flex: 1;
    font-size: 32rpx;
    color: #333;
  }
  
  .code-btn {
    padding: 16rpx 24rpx;
    background: rgba(255, 107, 0, 0.1);
    border-radius: 24rpx;
    
    text {
      font-size: 26rpx;
      color: #FF6B00;
    }
    
    &.disabled {
      background: #f5f5f5;
      
      text {
        color: #999;
      }
    }
  }
}

.login-btn {
  margin-top: 60rpx;
  height: 96rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(90deg, #FF6B00 0%, #FF2D55 100%);
  border-radius: 48rpx;
  
  text {
    font-size: 32rpx;
    color: #fff;
    font-weight: bold;
  }
}

.agreement {
  display: flex;
  align-items: flex-start;
  padding: 0 20rpx;
}

.checkbox {
  width: 36rpx;
  height: 36rpx;
  border: 2rpx solid #ddd;
  border-radius: 50%;
  margin-right: 16rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  
  &.checked {
    border-color: #FF6B00;
    background: #FF6B00;
  }
}

.check-icon {
  width: 20rpx;
  height: 20rpx;
}

.agreement-text {
  flex: 1;
  font-size: 24rpx;
  color: #999;
  line-height: 1.6;
}

.link {
  color: #FF6B00;
}

.invite-section {
  margin-top: 60rpx;
  
  .invite-title {
    font-size: 28rpx;
    color: #666;
    margin-bottom: 20rpx;
  }
  
  .invite-input {
    width: 100%;
    height: 80rpx;
    background: #f5f5f5;
    border-radius: 12rpx;
    padding: 0 24rpx;
    font-size: 28rpx;
    color: #333;
  }
}
</style>

<template>
  <view class="withdraw-page">
    <!-- 金币余额 -->
    <view class="balance-card">
      <view class="balance-header">
        <text class="balance-title">可提现金币</text>
        <view class="history-btn" @click="goToHistory">
          <text>提现记录</text>
        </view>
      </view>
      
      <view class="balance-content">
        <image class="coin-icon" src="/static/images/coin.png" mode="aspectFit"></image>
        <text class="balance-amount">{{ accountInfo.balance }}</text>
      </view>
      
      <view class="balance-info">
        <text class="cash-text">≈ ¥{{ coinToCash(accountInfo.balance) }}</text>
        <text class="frozen-text" v-if="accountInfo.frozen > 0">
          (冻结中: {{ accountInfo.frozen }})
        </text>
      </view>
    </view>
    
    <!-- 提现金额输入 -->
    <view class="input-card">
      <view class="input-header">
        <text class="input-title">提现金币</text>
        <view class="all-btn" @click="withdrawAll">
          <text>全部提现</text>
        </view>
      </view>
      
      <view class="input-wrapper">
        <image class="input-icon" src="/static/images/coin.png" mode="aspectFit"></image>
        <input 
          class="input-field"
          type="number"
          v-model="coinAmount"
          placeholder="请输入提现金币数量"
          @input="onAmountChange"
        />
      </view>
      
      <!-- 预估金额 -->
      <view class="preview-info" v-if="coinAmount > 0">
        <view class="preview-row">
          <text class="preview-label">提现金额</text>
          <text class="preview-value">¥{{ previewData.cashAmount }}</text>
        </view>
        <view class="preview-row" v-if="previewData.feeAmount > 0">
          <text class="preview-label">手续费</text>
          <text class="preview-value fee">-¥{{ previewData.feeAmount }}</text>
        </view>
        <view class="preview-row highlight">
          <text class="preview-label">实际到账</text>
          <text class="preview-value">¥{{ previewData.actualAmount }}</text>
        </view>
      </view>
      
      <!-- 快捷金额 -->
      <view class="quick-amounts">
        <view 
          class="quick-item" 
          v-for="item in quickAmounts" 
          :key="item.value"
          :class="{ active: coinAmount === item.value }"
          @click="selectQuickAmount(item.value)"
        >
          <text>{{ item.label }}</text>
        </view>
      </view>
    </view>
    
    <!-- 提现方式 -->
    <view class="method-card">
      <view class="method-header">
        <text class="method-title">提现方式</text>
      </view>
      
      <view class="method-list">
        <view 
          class="method-item"
          :class="{ active: withdrawType === 'wechat' }"
          @click="selectMethod('wechat')"
        >
          <image class="method-icon" src="/static/images/wechat-pay.png" mode="aspectFit"></image>
          <view class="method-info">
            <text class="method-name">微信零钱</text>
            <text class="method-desc">实时到账</text>
          </view>
          <view class="method-check">
            <view class="check-icon" v-if="withdrawType === 'wechat'"></view>
          </view>
        </view>
        
        <view 
          class="method-item"
          :class="{ active: withdrawType === 'alipay' }"
          @click="selectMethod('alipay')"
        >
          <image class="method-icon" src="/static/images/alipay.png" mode="aspectFit"></image>
          <view class="method-info">
            <text class="method-name">支付宝</text>
            <text class="method-desc">24小时内到账</text>
          </view>
          <view class="method-check">
            <view class="check-icon" v-if="withdrawType === 'alipay'"></view>
          </view>
        </view>
        
        <view 
          class="method-item"
          :class="{ active: withdrawType === 'bank' }"
          @click="selectMethod('bank')"
        >
          <image class="method-icon" src="/static/images/bank-card.png" mode="aspectFit"></image>
          <view class="method-info">
            <text class="method-name">银行卡</text>
            <text class="method-desc">1-3工作日到账</text>
          </view>
          <view class="method-check">
            <view class="check-icon" v-if="withdrawType === 'bank'"></view>
          </view>
        </view>
      </view>
    </view>
    
    <!-- 收款账户 -->
    <view class="account-card" v-if="withdrawType !== 'wechat'">
      <view class="account-header">
        <text class="account-title">收款账户</text>
        <view class="add-btn" @click="goToBindAccount">
          <text>{{ hasAccount ? '更换账户' : '添加账户' }}</text>
        </view>
      </view>
      
      <view class="account-info" v-if="hasAccount">
        <text class="account-name">{{ accountInfo.withdrawName }}</text>
        <text class="account-number">{{ maskAccount(accountInfo.withdrawAccount) }}</text>
      </view>
      
      <view class="no-account" v-else>
        <text>请先绑定收款账户</text>
      </view>
    </view>
    
    <!-- 提现须知 -->
    <view class="tips-card">
      <view class="tips-header">
        <text class="tips-title">提现须知</text>
      </view>
      <view class="tips-content">
        <text class="tips-item">• 最低提现: {{ config.minWithdraw }}金币</text>
        <text class="tips-item">• 每日最多提现: {{ config.dailyLimit }}次</text>
        <text class="tips-item">• 金币换算: 10000金币 = 1元人民币</text>
        <text class="tips-item">• 提现审核: 1-3个工作日</text>
        <text class="tips-item">• 如有疑问请联系客服</text>
      </view>
    </view>
    
    <!-- 提现按钮 -->
    <view class="submit-btn" :class="{ disabled: !canSubmit }" @click="submitWithdraw">
      <text>{{ submitting ? '提交中...' : '确认提现' }}</text>
    </view>
  </view>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { get, post, showToast, showLoading, hideLoading } from '@/utils/request'

// 状态
const accountInfo = ref({
  balance: 0,
  frozen: 0
})

const config = ref({
  minWithdraw: 10000,
  maxWithdraw: 1000000,
  dailyLimit: 3,
  feeRate: 0
})

const coinAmount = ref(0)
const withdrawType = ref('wechat')
const hasAccount = ref(false)
const withdrawName = ref('')
const withdrawAccount = ref('')
const submitting = ref(false)

// 预览数据
const previewData = ref({
  cashAmount: '0.00',
  feeAmount: '0.00',
  actualAmount: '0.00'
})

// 快捷金额
const quickAmounts = [
  { label: '1万', value: 10000 },
  { label: '2万', value: 20000 },
  { label: '5万', value: 50000 },
  { label: '10万', value: 100000 },
  { label: '20万', value: 200000 },
  { label: '全部', value: -1 }
]

// 计算属性
const canSubmit = computed(() => {
  if (submitting.value) return false
  if (coinAmount.value < config.value.minWithdraw) return false
  if (coinAmount.value > accountInfo.value.balance) return false
  if (withdrawType.value !== 'wechat' && !hasAccount.value) return false
  return true
})

// 初始化
onMounted(() => {
  fetchConfig()
  fetchAccountInfo()
})

// 获取配置
const fetchConfig = async () => {
  try {
    const res = await get('/api/withdraw/config', {}, { showLoading: false })
    if (res.code === 1) {
      config.value = {
        minWithdraw: res.data.min_withdraw || 10000,
        maxWithdraw: res.data.max_withdraw || 1000000,
        dailyLimit: res.data.daily_limit || 3,
        feeRate: parseFloat(res.data.fee_rate) || 0
      }
    }
  } catch (e) {
    console.error(e)
  }
}

// 获取账户信息
const fetchAccountInfo = async () => {
  try {
    const res = await get('/api/withdraw/config', {}, { showLoading: false })
    if (res.code === 1) {
      accountInfo.value.balance = res.data.balance || 0
      accountInfo.value.frozen = res.data.frozen || 0
    }
  } catch (e) {
    console.error(e)
  }
}

// 金币转人民币
const coinToCash = (coin) => {
  return (coin / 10000).toFixed(2)
}

// 金额变化
const onAmountChange = () => {
  calculatePreview()
}

// 计算预览
const calculatePreview = async () => {
  if (coinAmount.value <= 0) {
    previewData.value = {
      cashAmount: '0.00',
      feeAmount: '0.00',
      actualAmount: '0.00'
    }
    return
  }
  
  try {
    const res = await post('/api/withdraw/preview', {
      coin_amount: coinAmount.value
    }, { showLoading: false })
    
    if (res.code === 1) {
      previewData.value = {
        cashAmount: res.data.cash_amount,
        feeAmount: res.data.fee_amount,
        actualAmount: res.data.actual_amount
      }
    }
  } catch (e) {
    // 本地计算
    const cash = coinAmount.value / 10000
    const fee = cash * config.value.feeRate
    previewData.value = {
      cashAmount: cash.toFixed(2),
      feeAmount: fee.toFixed(2),
      actualAmount: (cash - fee).toFixed(2)
    }
  }
}

// 选择快捷金额
const selectQuickAmount = (value) => {
  if (value === -1) {
    withdrawAll()
  } else {
    coinAmount.value = value
    calculatePreview()
  }
}

// 全部提现
const withdrawAll = () => {
  coinAmount.value = accountInfo.value.balance
  calculatePreview()
}

// 选择提现方式
const selectMethod = (type) => {
  withdrawType.value = type
  if (type !== 'wechat') {
    fetchBindAccount(type)
  }
}

// 获取绑定账户
const fetchBindAccount = async (type) => {
  try {
    const res = await get('/api/withdraw/accounts', {}, { showLoading: false })
    if (res.code === 1) {
      const account = res.data.list.find(a => a.type === type)
      if (account) {
        hasAccount.value = true
        withdrawName.value = account.name
        withdrawAccount.value = account.account
      } else {
        hasAccount.value = false
      }
    }
  } catch (e) {
    hasAccount.value = false
  }
}

// 脱敏账户
const maskAccount = (account) => {
  if (!account) return ''
  if (account.length > 8) {
    return account.substring(0, 4) + '****' + account.substring(account.length - 4)
  }
  return account
}

// 跳转绑定账户
const goToBindAccount = () => {
  uni.navigateTo({
    url: `/pages/bind-account/bind-account?type=${withdrawType.value}`
  })
}

// 跳转提现记录
const goToHistory = () => {
  uni.navigateTo({ url: '/pages/withdraw-record/withdraw-record' })
}

// 提交提现
const submitWithdraw = async () => {
  if (!canSubmit.value) return
  
  // 验证金额
  if (coinAmount.value < config.value.minWithdraw) {
    showToast(`最低提现${config.value.minWithdraw}金币`)
    return
  }
  
  if (coinAmount.value > accountInfo.value.balance) {
    showToast('金币余额不足')
    return
  }
  
  submitting.value = true
  showLoading('提交中...')
  
  try {
    const res = await post('/api/withdraw/apply', {
      coin_amount: coinAmount.value,
      withdraw_type: withdrawType.value,
      withdraw_account: withdrawAccount.value,
      withdraw_name: withdrawName.value
    })
    
    hideLoading()
    
    if (res.code === 1) {
      showToast('提现申请成功')
      
      // 更新余额
      accountInfo.value.balance -= coinAmount.value
      
      // 跳转结果页
      uni.redirectTo({
        url: `/pages/withdraw-result/withdraw-result?order_no=${res.data.order_no}`
      })
    } else {
      showToast(res.msg || '提现失败')
    }
  } catch (e) {
    hideLoading()
    showToast('网络错误，请稍后重试')
  } finally {
    submitting.value = false
  }
}
</script>

<style lang="scss" scoped>
.withdraw-page {
  min-height: 100vh;
  background: #f5f5f5;
  padding: 30rpx;
  padding-bottom: 150rpx;
}

.balance-card {
  background: linear-gradient(135deg, #FF6B00 0%, #FF2D55 100%);
  border-radius: 24rpx;
  padding: 40rpx;
  margin-bottom: 30rpx;
}

.balance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 30rpx;
}

.balance-title {
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.8);
}

.history-btn {
  padding: 8rpx 20rpx;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 20rpx;
  
  text {
    font-size: 24rpx;
    color: #fff;
  }
}

.balance-content {
  display: flex;
  align-items: center;
  margin-bottom: 20rpx;
}

.coin-icon {
  width: 60rpx;
  height: 60rpx;
  margin-right: 16rpx;
}

.balance-amount {
  font-size: 72rpx;
  font-weight: bold;
  color: #fff;
}

.balance-info {
  display: flex;
  align-items: baseline;
}

.cash-text {
  font-size: 32rpx;
  color: #fff;
}

.frozen-text {
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.7);
  margin-left: 16rpx;
}

.input-card,
.method-card,
.account-card,
.tips-card {
  background: #fff;
  border-radius: 24rpx;
  padding: 30rpx;
  margin-bottom: 30rpx;
}

.input-header,
.method-header,
.account-header,
.tips-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20rpx;
}

.input-title,
.method-title,
.account-title,
.tips-title {
  font-size: 30rpx;
  font-weight: bold;
  color: #333;
}

.all-btn,
.add-btn {
  padding: 8rpx 20rpx;
  background: rgba(255, 107, 0, 0.1);
  border-radius: 20rpx;
  
  text {
    font-size: 24rpx;
    color: #FF6B00;
  }
}

.input-wrapper {
  display: flex;
  align-items: center;
  border-bottom: 2rpx solid #f5f5f5;
  padding-bottom: 20rpx;
}

.input-icon {
  width: 48rpx;
  height: 48rpx;
  margin-right: 16rpx;
}

.input-field {
  flex: 1;
  font-size: 48rpx;
  font-weight: bold;
  color: #333;
}

.preview-info {
  margin-top: 20rpx;
  padding-top: 20rpx;
  border-top: 1rpx solid #f5f5f5;
}

.preview-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 16rpx;
  
  &.highlight {
    padding-top: 16rpx;
    border-top: 1rpx dashed #eee;
  }
}

.preview-label {
  font-size: 26rpx;
  color: #999;
}

.preview-value {
  font-size: 26rpx;
  color: #333;
  
  &.fee {
    color: #FF2D55;
  }
}

.highlight .preview-value {
  font-size: 36rpx;
  font-weight: bold;
  color: #FF6B00;
}

.quick-amounts {
  display: flex;
  flex-wrap: wrap;
  gap: 16rpx;
  margin-top: 20rpx;
}

.quick-item {
  width: calc(33.33% - 12rpx);
  height: 70rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f5f5f5;
  border-radius: 12rpx;
  
  text {
    font-size: 28rpx;
    color: #666;
  }
  
  &.active {
    background: rgba(255, 107, 0, 0.1);
    
    text {
      color: #FF6B00;
    }
  }
}

.method-list {
  margin-top: 20rpx;
}

.method-item {
  display: flex;
  align-items: center;
  padding: 24rpx 0;
  border-bottom: 1rpx solid #f5f5f5;
  
  &:last-child {
    border-bottom: none;
  }
  
  &.active {
    .method-name {
      color: #FF6B00;
    }
  }
}

.method-icon {
  width: 64rpx;
  height: 64rpx;
  margin-right: 20rpx;
}

.method-info {
  flex: 1;
}

.method-name {
  display: block;
  font-size: 30rpx;
  color: #333;
}

.method-desc {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-top: 4rpx;
}

.method-check {
  width: 40rpx;
  height: 40rpx;
  border-radius: 50%;
  border: 2rpx solid #ddd;
  display: flex;
  align-items: center;
  justify-content: center;
  
  .check-icon {
    width: 24rpx;
    height: 24rpx;
    border-radius: 50%;
    background: #FF6B00;
  }
}

.method-item.active .method-check {
  border-color: #FF6B00;
}

.account-info {
  padding: 20rpx;
  background: #f5f5f5;
  border-radius: 12rpx;
}

.account-name {
  display: block;
  font-size: 28rpx;
  color: #333;
}

.account-number {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-top: 8rpx;
}

.no-account {
  padding: 20rpx;
  text-align: center;
  
  text {
    font-size: 26rpx;
    color: #999;
  }
}

.tips-content {
  margin-top: 10rpx;
}

.tips-item {
  display: block;
  font-size: 24rpx;
  color: #999;
  line-height: 2;
}

.submit-btn {
  position: fixed;
  left: 30rpx;
  right: 30rpx;
  bottom: 60rpx;
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
  
  &.disabled {
    background: #ccc;
  }
}
</style>

/**
 * 金币状态管理 - 支持实时刷新
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { get } from '@/utils/request'

export const useCoinStore = defineStore('coin', () => {
  // 金币换算比例：10000金币 = 1元
  const COIN_RATE = 10000
  
  // 状态
  const balance = ref(0)           // 可用余额
  const frozen = ref(0)            // 冻结金额
  const todayEarn = ref(0)         // 今日收益
  const totalEarn = ref(0)         // 累计收益
  const totalWithdraw = ref(0)     // 累计提现
  
  // 刷新定时器
  let refreshTimer = null
  let lastRefreshTime = 0
  
  // 计算属性
  const availableBalance = computed(() => balance.value)
  const totalBalance = computed(() => balance.value + frozen.value)
  const cashAmount = computed(() => (balance.value / COIN_RATE).toFixed(2))
  const todayCash = computed(() => (todayEarn.value / COIN_RATE).toFixed(2))
  
  // 是否需要刷新（距离上次刷新超过5秒）
  const needRefresh = computed(() => {
    return Date.now() - lastRefreshTime > 5000
  })
  
  /**
   * 获取账户信息
   */
  const fetchAccountInfo = async (force = false) => {
    // 如果不是强制刷新且不需要刷新，直接返回
    if (!force && !needRefresh.value) {
      return { balance: balance.value }
    }
    
    try {
      const res = await get('/api/coin/account', {}, { showLoading: false })
      if (res.code === 1) {
        balance.value = res.data.balance || 0
        frozen.value = res.data.frozen || 0
        todayEarn.value = res.data.today_earn || 0
        totalEarn.value = res.data.total_earn || 0
        totalWithdraw.value = res.data.total_withdraw || 0
        lastRefreshTime = Date.now()
      }
      return res
    } catch (e) {
      console.error('获取账户信息失败:', e)
      return null
    }
  }
  
  /**
   * 更新金币（本地更新，用于即时显示）
   */
  const updateBalance = (amount) => {
    balance.value += amount
    if (amount > 0) {
      todayEarn.value += amount
      totalEarn.value += amount
    }
  }
  
  /**
   * 冻结金币
   */
  const freezeCoin = (amount) => {
    balance.value -= amount
    frozen.value += amount
  }
  
  /**
   * 解冻金币
   */
  const unfreezeCoin = (amount) => {
    frozen.value -= amount
    balance.value += amount
  }
  
  /**
   * 扣减冻结金币
   */
  const deductFrozen = (amount) => {
    frozen.value -= amount
    totalWithdraw.value += amount
  }
  
  /**
   * 开启自动刷新（每30秒刷新一次）
   */
  const startAutoRefresh = (interval = 30000) => {
    if (refreshTimer) {
      stopAutoRefresh()
    }
    
    // 立即刷新一次
    fetchAccountInfo(true)
    
    // 定时刷新
    refreshTimer = setInterval(() => {
      fetchAccountInfo(true)
    }, interval)
  }
  
  /**
   * 停止自动刷新
   */
  const stopAutoRefresh = () => {
    if (refreshTimer) {
      clearInterval(refreshTimer)
      refreshTimer = null
    }
  }
  
  /**
   * 金币转人民币
   */
  const coinToCash = (coin) => {
    return (coin / COIN_RATE).toFixed(2)
  }
  
  /**
   * 人民币转金币
   */
  const cashToCoin = (cash) => {
    return Math.floor(cash * COIN_RATE)
  }
  
  return {
    // 状态
    balance,
    frozen,
    todayEarn,
    totalEarn,
    totalWithdraw,
    
    // 计算属性
    availableBalance,
    totalBalance,
    cashAmount,
    todayCash,
    
    // 方法
    fetchAccountInfo,
    updateBalance,
    freezeCoin,
    unfreezeCoin,
    deductFrozen,
    startAutoRefresh,
    stopAutoRefresh,
    coinToCash,
    cashToCoin
  }
})

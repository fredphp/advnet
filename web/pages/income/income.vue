<template>
  <view class="income-page">
    <!-- 筛选栏 -->
    <view class="filter-bar">
      <scroll-view class="filter-scroll" scroll-x>
        <view 
          class="filter-item"
          :class="{ active: currentType === '' }"
          @click="changeType('')"
        >
          <text>全部</text>
        </view>
        <view 
          class="filter-item"
          :class="{ active: currentType === 'video' }"
          @click="changeType('video')"
        >
          <text>视频收益</text>
        </view>
        <view 
          class="filter-item"
          :class="{ active: currentType === 'red_packet' }"
          @click="changeType('red_packet')"
        >
          <text>红包收益</text>
        </view>
        <view 
          class="filter-item"
          :class="{ active: currentType === 'task' }"
          @click="changeType('task')"
        >
          <text>任务收益</text>
        </view>
        <view 
          class="filter-item"
          :class="{ active: currentType === 'withdraw' }"
          @click="changeType('withdraw')"
        >
          <text>提现</text>
        </view>
        <view 
          class="filter-item"
          :class="{ active: currentType === 'invite' }"
          @click="changeType('invite')"
        >
          <text>邀请奖励</text>
        </view>
      </scroll-view>
    </view>
    
    <!-- 统计卡片 -->
    <view class="stat-card">
      <view class="stat-row">
        <view class="stat-item">
          <text class="stat-label">今日收入</text>
          <text class="stat-value income">+{{ todayIncome }}</text>
        </view>
        <view class="stat-item">
          <text class="stat-label">今日支出</text>
          <text class="stat-value expense">-{{ todayExpense }}</text>
        </view>
      </view>
      <view class="stat-row">
        <view class="stat-item">
          <text class="stat-label">本月收入</text>
          <text class="stat-value income">+{{ monthIncome }}</text>
        </view>
        <view class="stat-item">
          <text class="stat-label">累计收益</text>
          <text class="stat-value income">+{{ totalIncome }}</text>
        </view>
      </view>
    </view>
    
    <!-- 明细列表 -->
    <view class="list-container">
      <view 
        class="list-group"
        v-for="(group, date) = " groupedList" 
        :key="date"
      >
        <view class="group-header">
          <text class="group-date">{{ formatDate(date) }}</text>
          <view class="group-stat">
            <text class="income" v-if="group.income > 0">+{{ group.income }}</text>
            <text class="expense" v-if="group.expense > 0">-{{ group.expense }}</text>
          </view>
        </view>
        
        <view 
          class="list-item"
          v-for="item in group.items"
          :key="item.id"
        >
          <view class="item-icon" :class="item.type">
            <image :src="getIcon(item.type)" mode="aspectFit"></image>
          </view>
          <view class="item-info">
            <text class="item-title">{{ item.title }}</text>
            <text class="item-time">{{ formatTime(item.createtime) }}</text>
          </view>
          <view class="item-amount" :class="{ income: item.amount > 0, expense: item.amount < 0 }">
            <text>{{ item.amount > 0 ? '+' : '' }}{{ item.amount }}</text>
          </view>
        </view>
      </view>
      
      <!-- 加载更多 -->
      <view class="load-more" v-if="hasMore" @click="loadMore">
        <text>{{ loading ? '加载中...' : '加载更多' }}</text>
      </view>
      
      <!-- 无更多 -->
      <view class="no-more" v-if="!hasMore && list.length > 0">
        <text>已显示全部记录</text>
      </view>
      
      <!-- 空状态 -->
      <view class="empty-state" v-if="!loading && list.length === 0">
        <image class="empty-icon" src="/static/images/empty.png" mode="aspectFit"></image>
        <text class="empty-text">暂无记录</text>
      </view>
    </view>
  </view>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { get, showLoading, hideLoading } from '@/utils/request'

// 状态
const currentType = ref('')
const list = ref([])
const loading = ref(false)
const hasMore = ref(true)
const page = ref(1)
const pageSize = 20

// 统计数据
const todayIncome = ref(0)
const todayExpense = ref(0)
const monthIncome = ref(0)
const totalIncome = ref(0)

// 分组列表
const groupedList = computed(() => {
  const groups = {}
  
  list.value.forEach(item => {
    const date = item.create_date || formatDate(item.createtime, 'YYYY-MM-DD')
    
    if (!groups[date]) {
      groups[date] = {
        items: [],
        income: 0,
        expense: 0
      }
    }
    
    groups[date].items.push(item)
    
    if (item.amount > 0) {
      groups[date].income += item.amount
    } else {
      groups[date].expense += Math.abs(item.amount)
    }
  })
  
  return groups
})

// 初始化
onMounted(() => {
  fetchList()
  fetchStat()
})

// 获取列表
const fetchList = async (refresh = true) => {
  if (loading.value) return
  
  if (refresh) {
    page.value = 1
    list.value = []
    hasMore.value = true
  }
  
  loading.value = true
  
  try {
    const res = await get('/api/coin/log', {
      type: currentType.value,
      page: page.value,
      limit: pageSize
    }, { showLoading: false })
    
    if (res.code === 1) {
      const newList = res.data.list || []
      
      if (refresh) {
        list.value = newList
      } else {
        list.value = [...list.value, ...newList]
      }
      
      hasMore.value = newList.length >= pageSize
      page.value++
    }
  } catch (e) {
    console.error(e)
  } finally {
    loading.value = false
  }
}

// 获取统计
const fetchStat = async () => {
  try {
    const res = await get('/api/coin/stat', {}, { showLoading: false })
    
    if (res.code === 1) {
      todayIncome.value = res.data.today_income || 0
      todayExpense.value = res.data.today_expense || 0
      monthIncome.value = res.data.month_income || 0
      totalIncome.value = res.data.total_income || 0
    }
  } catch (e) {
    console.error(e)
  }
}

// 切换类型
const changeType = (type) => {
  currentType.value = type
  fetchList(true)
}

// 加载更多
const loadMore = () => {
  if (!loading.value && hasMore.value) {
    fetchList(false)
  }
}

// 获取图标
const getIcon = (type) => {
  const iconMap = {
    video: '/static/images/icon-video.png',
    red_packet: '/static/images/icon-redpacket.png',
    task: '/static/images/icon-task.png',
    withdraw: '/static/images/icon-withdraw.png',
    invite: '/static/images/icon-invite.png',
    register: '/static/images/icon-gift.png',
    signin: '/static/images/icon-signin.png'
  }
  return iconMap[type] || '/static/images/icon-coin.png'
}

// 格式化日期
const formatDate = (timestamp, format = 'MM月DD日') => {
  const date = new Date(timestamp * 1000)
  const month = date.getMonth() + 1
  const day = date.getDate()
  const weekDays = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
  const weekDay = weekDays[date.getDay()]
  
  if (format === 'YYYY-MM-DD') {
    return `${date.getFullYear()}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`
  }
  
  // 判断是否是今天
  const today = new Date()
  if (date.toDateString() === today.toDateString()) {
    return '今天'
  }
  
  // 判断是否是昨天
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)
  if (date.toDateString() === yesterday.toDateString()) {
    return '昨天'
  }
  
  return `${month}月${day}日 ${weekDay}`
}

// 格式化时间
const formatTime = (timestamp) => {
  const date = new Date(timestamp * 1000)
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${hours}:${minutes}`
}
</script>

<style lang="scss" scoped>
.income-page {
  min-height: 100vh;
  background: #f5f5f5;
}

.filter-bar {
  background: #fff;
  padding: 20rpx 0;
}

.filter-scroll {
  white-space: nowrap;
  padding: 0 20rpx;
}

.filter-item {
  display: inline-block;
  padding: 16rpx 32rpx;
  margin-right: 16rpx;
  background: #f5f5f5;
  border-radius: 30rpx;
  
  text {
    font-size: 26rpx;
    color: #666;
  }
  
  &.active {
    background: rgba(255, 107, 0, 0.1);
    
    text {
      color: #FF6B00;
    }
  }
}

.stat-card {
  background: #fff;
  margin: 20rpx;
  padding: 30rpx;
  border-radius: 20rpx;
}

.stat-row {
  display: flex;
  justify-content: space-around;
  margin-bottom: 20rpx;
  
  &:last-child {
    margin-bottom: 0;
  }
}

.stat-item {
  text-align: center;
  flex: 1;
}

.stat-label {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-bottom: 10rpx;
}

.stat-value {
  display: block;
  font-size: 36rpx;
  font-weight: bold;
  
  &.income {
    color: #FF6B00;
  }
  
  &.expense {
    color: #333;
  }
}

.list-container {
  padding: 0 20rpx;
}

.list-group {
  margin-bottom: 20rpx;
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 30rpx;
  background: #fff;
  border-radius: 20rpx 20rpx 0 0;
}

.group-date {
  font-size: 28rpx;
  font-weight: bold;
  color: #333;
}

.group-stat {
  display: flex;
  gap: 20rpx;
  
  text {
    font-size: 24rpx;
    
    &.income {
      color: #FF6B00;
    }
    
    &.expense {
      color: #333;
    }
  }
}

.list-item {
  display: flex;
  align-items: center;
  padding: 24rpx 30rpx;
  background: #fff;
  border-bottom: 1rpx solid #f5f5f5;
  
  &:last-child {
    border-bottom: none;
    border-radius: 0 0 20rpx 20rpx;
  }
}

.item-icon {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 20rpx;
  
  image {
    width: 48rpx;
    height: 48rpx;
  }
  
  &.video { background: rgba(255, 107, 0, 0.1); }
  &.red_packet { background: rgba(255, 45, 85, 0.1); }
  &.task { background: rgba(76, 175, 80, 0.1); }
  &.withdraw { background: rgba(33, 150, 243, 0.1); }
  &.invite { background: rgba(156, 39, 176, 0.1); }
}

.item-info {
  flex: 1;
}

.item-title {
  display: block;
  font-size: 28rpx;
  color: #333;
}

.item-time {
  display: block;
  font-size: 24rpx;
  color: #999;
  margin-top: 8rpx;
}

.item-amount {
  font-size: 32rpx;
  font-weight: bold;
  
  &.income {
    color: #FF6B00;
  }
  
  &.expense {
    color: #333;
  }
}

.load-more,
.no-more {
  text-align: center;
  padding: 30rpx;
  
  text {
    font-size: 26rpx;
    color: #999;
  }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 100rpx 0;
}

.empty-icon {
  width: 200rpx;
  height: 200rpx;
}

.empty-text {
  font-size: 28rpx;
  color: #999;
  margin-top: 30rpx;
}
</style>

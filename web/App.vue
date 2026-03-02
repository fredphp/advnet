<template>
  <view>
    <router-view />
  </view>
</template>

<script setup>
import { onLaunch, onShow, onHide } from '@dcloudio/uni-app'
import { useUserStore } from '@/store/user'
import { useCoinStore } from '@/store/coin'

onLaunch(() => {
  console.log('App Launch')
  
  // 检查登录状态
  const userStore = useUserStore()
  if (userStore.isLoggedIn) {
    // 自动刷新用户信息
    userStore.fetchUserInfo()
    
    // 开启金币自动刷新
    const coinStore = useCoinStore()
    coinStore.startAutoRefresh(30000)
  }
})

onShow(() => {
  console.log('App Show')
})

onHide(() => {
  console.log('App Hide')
  
  // 停止金币刷新
  const coinStore = useCoinStore()
  coinStore.stopAutoRefresh()
})
</script>

<style lang="scss">
/* 全局样式 */
page {
  background-color: #f5f5f5;
}

/* 隐藏滚动条 */
::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}

/* 安全区域适配 */
.safe-area-bottom {
  padding-bottom: constant(safe-area-inset-bottom);
  padding-bottom: env(safe-area-inset-bottom);
}
</style>

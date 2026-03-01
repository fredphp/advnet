<template>
  <view class="container">
    <!-- 视频滑动容器 -->
    <swiper 
      class="video-swiper"
      :vertical="true"
      :current="currentIndex"
      :circular="false"
      :skip-hidden-item-layout="true"
      @change="onSwiperChange"
    >
      <swiper-item v-for="(video, index) in videoList" :key="video.id">
        <view class="video-wrapper">
          <!-- 视频播放组件 -->
          <video-player
            v-if="Math.abs(index - currentIndex) <= 1"
            :video-id="video.id"
            :video-src="video.video_url"
            :poster-src="video.cover_url"
            :video-info="video"
            :autoplay="index === currentIndex"
            :show-reward="true"
            :watch-reward-threshold="95"
            @play="onVideoPlay"
            @pause="onVideoPause"
            @ended="onVideoEnded"
            @timeUpdate="onTimeUpdate"
            @reward="onReward"
            @like="onLike"
            @comment="onComment"
            @share="onShare"
            @follow="onFollow"
          />
        </view>
      </swiper-item>
    </swiper>
    
    <!-- 顶部状态栏 -->
    <view class="top-bar" :style="{ paddingTop: statusBarHeight + 'px' }">
      <!-- Tab切换 -->
      <view class="tab-bar">
        <view 
          class="tab-item" 
          :class="{ active: currentTab === 'recommend' }"
          @click="switchTab('recommend')"
        >
          <text>推荐</text>
        </view>
        <view 
          class="tab-item"
          :class="{ active: currentTab === 'follow' }"
          @click="switchTab('follow')"
        >
          <text>关注</text>
        </view>
      </view>
      
      <!-- 金币显示 -->
      <view class="coin-bar" @click="goToWallet">
        <image class="coin-icon" src="/static/images/coin.png" mode="aspectFit"></image>
        <text class="coin-count">{{ formatCoin(coinStore.balance) }}</text>
        <text class="coin-unit">金币</text>
      </view>
    </view>
    
    <!-- 底部导航栏 -->
    <view class="bottom-nav">
      <view class="nav-item" @click="goToRedpacket">
        <image class="nav-icon" src="/static/images/redpacket.png" mode="aspectFit"></image>
        <text class="nav-text">红包</text>
      </view>
      <view class="nav-item publish" @click="goToPublish">
        <view class="publish-btn">
          <text class="publish-text">+</text>
        </view>
      </view>
      <view class="nav-item" @click="goToMine">
        <image class="nav-icon" src="/static/images/user.png" mode="aspectFit"></image>
        <text class="nav-text">我的</text>
      </view>
    </view>
    
    <!-- 加载更多提示 -->
    <view v-if="videoStore.loading" class="loading-tip">
      <text>加载中...</text>
    </view>
    
    <!-- 无更多提示 -->
    <view v-if="!videoStore.hasMore && videoList.length > 0" class="no-more-tip">
      <text>已经到底啦~</text>
    </view>
  </view>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useVideoStore } from '@/store/video'
import { useCoinStore } from '@/store/coin'
import { useUserStore } from '@/store/user'
import videoPlayer from '@/components/video-player.vue'

// Store
const videoStore = useVideoStore()
const coinStore = useCoinStore()
const userStore = useUserStore()

// 状态
const currentIndex = ref(0)
const currentTab = ref('recommend')
const statusBarHeight = ref(0)

// 计算属性
const videoList = computed(() => videoStore.videoList)

// 初始化
onMounted(() => {
  // 获取状态栏高度
  const systemInfo = uni.getSystemInfoSync()
  statusBarHeight.value = systemInfo.statusBarHeight || 20
  
  // 加载视频列表
  loadVideos()
  
  // 开启金币自动刷新
  coinStore.startAutoRefresh(30000)
})

// 加载视频
const loadVideos = (refresh = true) => {
  videoStore.fetchVideoList(refresh)
}

// Swiper切换
const onSwiperChange = (e) => {
  const current = e.detail.current
  const previous = currentIndex.value
  
  currentIndex.value = current
  
  // 上滑加载更多
  if (current > previous && current >= videoList.value.length - 3) {
    videoStore.fetchVideoList(false)
  }
}

// Tab切换
const switchTab = (tab) => {
  if (currentTab.value === tab) return
  
  currentTab.value = tab
  videoStore.reset()
  loadVideos()
}

// 视频事件处理
const onVideoPlay = () => {
  console.log('视频开始播放')
}

const onVideoPause = () => {
  console.log('视频暂停')
}

const onVideoEnded = () => {
  console.log('视频播放完成')
}

const onTimeUpdate = (data) => {
  const video = videoList.value[currentIndex.value]
  if (video && data.progress >= 95 && !video.reward_claimed) {
    // 可以领取奖励了
  }
}

const onReward = (videoId) => {
  videoStore.claimReward(videoId)
}

const onLike = (videoId) => {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/login/login' })
    return
  }
  videoStore.likeVideo(videoId)
}

const onComment = (videoId) => {
  uni.navigateTo({
    url: `/pages/comment/comment?video_id=${videoId}`
  })
}

const onShare = (videoId) => {
  // 分享逻辑
  uni.share({
    provider: 'weixin',
    scene: 'WXSceneSession',
    type: 0,
    title: '分享视频',
    success: () => {
      uni.showToast({ title: '分享成功', icon: 'success' })
    }
  })
}

const onFollow = (videoId) => {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/login/login' })
    return
  }
  // 关注逻辑
}

// 页面跳转
const goToWallet = () => {
  uni.navigateTo({ url: '/pages/income/income' })
}

const goToRedpacket = () => {
  uni.navigateTo({ url: '/pages/redpacket/redpacket' })
}

const goToPublish = () => {
  if (!userStore.isLoggedIn) {
    uni.navigateTo({ url: '/pages/login/login' })
    return
  }
  uni.navigateTo({ url: '/pages/publish/publish' })
}

const goToMine = () => {
  uni.switchTab({ url: '/pages/mine/mine' })
}

// 格式化金币
const formatCoin = (coin) => {
  if (coin >= 10000) {
    return (coin / 10000).toFixed(1) + 'w'
  }
  return coin.toString()
}

// 清理
onUnmounted(() => {
  coinStore.stopAutoRefresh()
})
</script>

<style lang="scss" scoped>
.container {
  position: relative;
  width: 100%;
  height: 100vh;
  background: #000;
}

.video-swiper {
  width: 100%;
  height: 100%;
}

.video-wrapper {
  width: 100%;
  height: 100%;
}

.top-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20rpx 30rpx;
  z-index: 100;
}

.tab-bar {
  display: flex;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 40rpx;
  padding: 4rpx;
}

.tab-item {
  padding: 12rpx 40rpx;
  font-size: 28rpx;
  color: rgba(255, 255, 255, 0.7);
  border-radius: 40rpx;
  
  &.active {
    color: #fff;
    background: rgba(255, 255, 255, 0.2);
    font-weight: bold;
  }
}

.coin-bar {
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 40rpx;
  padding: 12rpx 20rpx;
}

.coin-icon {
  width: 36rpx;
  height: 36rpx;
  margin-right: 8rpx;
}

.coin-count {
  font-size: 28rpx;
  color: #FFD700;
  font-weight: bold;
}

.coin-unit {
  font-size: 22rpx;
  color: #fff;
  margin-left: 4rpx;
}

.bottom-nav {
  position: fixed;
  bottom: 60rpx;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  justify-content: space-around;
  width: 500rpx;
  z-index: 100;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.nav-icon {
  width: 56rpx;
  height: 56rpx;
}

.nav-text {
  font-size: 22rpx;
  color: #fff;
  margin-top: 8rpx;
}

.nav-item.publish {
  margin: 0 40rpx;
}

.publish-btn {
  width: 100rpx;
  height: 70rpx;
  background: linear-gradient(90deg, #FF2D55 0%, #FF6B00 100%);
  border-radius: 20rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.publish-text {
  font-size: 50rpx;
  color: #fff;
  font-weight: 300;
}

.loading-tip,
.no-more-tip {
  position: fixed;
  bottom: 200rpx;
  left: 0;
  right: 0;
  text-align: center;
  font-size: 24rpx;
  color: rgba(255, 255, 255, 0.6);
}
</style>

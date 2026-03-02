<template>
  <view class="video-player">
    <!-- 视频容器 -->
    <view class="video-container" :style="{ height: videoHeight + 'px' }">
      <video
        v-if="videoSrc"
        :id="'video-' + videoId"
        :src="videoSrc"
        :poster="posterSrc"
        :initial-time="initialTime"
        :autoplay="autoplay"
        :loop="loop"
        :muted="muted"
        :show-center-play-btn="false"
        :show-play-btn="false"
        :show-progress="false"
        :show-fullscreen-btn="false"
        :enable-progress-gesture="false"
        :object-fit="objectFit"
        class="video-element"
        @play="onPlay"
        @pause="onPause"
        @ended="onEnded"
        @timeupdate="onTimeUpdate"
        @error="onError"
        @waiting="onWaiting"
        @loadedmetadata="onLoadedMetadata"
      ></video>
      
      <!-- 加载中 -->
      <view v-if="isLoading" class="loading-mask">
        <view class="loading-spinner"></view>
      </view>
      
      <!-- 播放按钮 -->
      <view v-if="showPlayBtn && !isPlaying" class="play-btn" @click="togglePlay">
        <image class="play-icon" src="/static/images/play.png" mode="aspectFit"></image>
      </view>
      
      <!-- 静音按钮 -->
      <view class="mute-btn" @click="toggleMute">
        <image 
          class="mute-icon" 
          :src="muted ? '/static/images/mute.png' : '/static/images/unmute.png'" 
          mode="aspectFit"
        ></image>
      </view>
      
      <!-- 进度条 -->
      <view v-if="showProgress" class="progress-bar">
        <view class="progress-played" :style="{ width: progressPercent + '%' }"></view>
      </view>
    </view>
    
    <!-- 视频信息 -->
    <view v-if="showInfo" class="video-info">
      <!-- 用户信息 -->
      <view class="user-info">
        <image class="avatar" :src="videoInfo.author_avatar || '/static/images/avatar.png'" mode="aspectFill"></image>
        <text class="nickname">{{ videoInfo.author_nickname || '用户' }}</text>
        <view class="follow-btn" v-if="!videoInfo.is_followed" @click="handleFollow">关注</view>
      </view>
      
      <!-- 描述 -->
      <view class="description" v-if="videoInfo.description">
        <text>{{ videoInfo.description }}</text>
      </view>
      
      <!-- 标签 -->
      <view class="tags" v-if="videoInfo.tags && videoInfo.tags.length">
        <text class="tag" v-for="(tag, index) in videoInfo.tags" :key="index">#{{ tag }}</text>
      </view>
    </view>
    
    <!-- 右侧操作栏 -->
    <view class="action-bar">
      <!-- 点赞 -->
      <view class="action-item" @click="handleLike">
        <image 
          class="action-icon" 
          :src="videoInfo.is_liked ? '/static/images/like-active.png' : '/static/images/like.png'"
          mode="aspectFit"
        ></image>
        <text class="action-text">{{ formatCount(videoInfo.like_count) }}</text>
      </view>
      
      <!-- 评论 -->
      <view class="action-item" @click="handleComment">
        <image class="action-icon" src="/static/images/comment.png" mode="aspectFit"></image>
        <text class="action-text">{{ formatCount(videoInfo.comment_count) }}</text>
      </view>
      
      <!-- 分享 -->
      <view class="action-item" @click="handleShare">
        <image class="action-icon" src="/static/images/share.png" mode="aspectFit"></image>
        <text class="action-text">{{ formatCount(videoInfo.share_count) }}</text>
      </view>
      
      <!-- 金币奖励 -->
      <view class="action-item reward" v-if="showReward">
        <view class="coin-icon">
          <image class="action-icon" src="/static/images/coin.png" mode="aspectFit"></image>
        </view>
        <text class="reward-text">+{{ videoInfo.reward_coin || 100 }}</text>
        <text class="reward-tip" v-if="rewardStatus === 'pending'">待领取</text>
        <text class="reward-tip claimed" v-if="rewardStatus === 'claimed'">已领取</text>
      </view>
    </view>
  </view>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

// Props
const props = defineProps({
  videoId: {
    type: [String, Number],
    required: true
  },
  videoSrc: {
    type: String,
    default: ''
  },
  posterSrc: {
    type: String,
    default: ''
  },
  videoInfo: {
    type: Object,
    default: () => ({})
  },
  autoplay: {
    type: Boolean,
    default: false
  },
  loop: {
    type: Boolean,
    default: true
  },
  muted: {
    type: Boolean,
    default: true
  },
  objectFit: {
    type: String,
    default: 'contain' // contain | cover | fill
  },
  initialTime: {
    type: Number,
    default: 0
  },
  showPlayBtn: {
    type: Boolean,
    default: true
  },
  showProgress: {
    type: Boolean,
    default: true
  },
  showInfo: {
    type: Boolean,
    default: true
  },
  showReward: {
    type: Boolean,
    default: true
  },
  watchRewardThreshold: {
    type: Number,
    default: 95 // 观看进度阈值(%)，达到后可领取奖励
  }
})

// Emits
const emit = defineEmits([
  'play',
  'pause',
  'ended',
  'timeUpdate',
  'error',
  'reward',
  'like',
  'comment',
  'share',
  'follow'
])

// 视频上下文
let videoContext = null

// 状态
const isLoading = ref(true)
const isPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const progressPercent = computed(() => {
  if (duration.value === 0) return 0
  return Math.min((currentTime.value / duration.value) * 100, 100)
})
const rewardStatus = ref('pending') // pending | ready | claimed

// 视频高度
const videoHeight = ref(0)

// 初始化视频高度
onMounted(() => {
  const systemInfo = uni.getSystemInfoSync()
  videoHeight.value = systemInfo.windowHeight
  
  // 创建视频上下文
  nextTick(() => {
    videoContext = uni.createVideoContext(`video-${props.videoId}`)
  })
})

// 监听播放状态
watch(() => props.autoplay, (val) => {
  if (val && videoContext) {
    videoContext.play()
  }
})

// 播放
const play = () => {
  if (videoContext) {
    videoContext.play()
  }
}

// 暂停
const pause = () => {
  if (videoContext) {
    videoContext.pause()
  }
}

// 切换播放/暂停
const togglePlay = () => {
  if (isPlaying.value) {
    pause()
  } else {
    play()
  }
}

// 切换静音
const toggleMute = () => {
  emit('mute', !props.muted)
}

// 事件处理
const onPlay = () => {
  isPlaying.value = true
  isLoading.value = false
  emit('play')
}

const onPause = () => {
  isPlaying.value = false
  emit('pause')
}

const onEnded = () => {
  isPlaying.value = false
  if (props.loop && videoContext) {
    videoContext.seek(0)
    videoContext.play()
  }
  emit('ended')
}

const onTimeUpdate = (e) => {
  currentTime.value = e.detail.currentTime
  duration.value = e.detail.duration
  
  // 检查是否达到奖励阈值
  if (rewardStatus.value === 'pending' && progressPercent.value >= props.watchRewardThreshold) {
    rewardStatus.value = 'ready'
  }
  
  emit('timeUpdate', {
    currentTime: e.detail.currentTime,
    duration: e.detail.duration,
    progress: progressPercent.value
  })
}

const onError = (e) => {
  isLoading.value = false
  console.error('视频播放错误:', e)
  emit('error', e)
}

const onWaiting = () => {
  isLoading.value = true
}

const onLoadedMetadata = () => {
  isLoading.value = false
}

// 操作处理
const handleLike = () => {
  emit('like', props.videoId)
}

const handleComment = () => {
  emit('comment', props.videoId)
}

const handleShare = () => {
  emit('share', props.videoId)
}

const handleFollow = () => {
  emit('follow', props.videoId)
}

// 格式化数量
const formatCount = (count) => {
  if (!count) return '0'
  if (count >= 10000) {
    return (count / 10000).toFixed(1) + 'w'
  }
  if (count >= 1000) {
    return (count / 1000).toFixed(1) + 'k'
  }
  return count.toString()
}

// 暴露方法
defineExpose({
  play,
  pause,
  togglePlay,
  currentTime,
  duration,
  progressPercent,
  rewardStatus
})

onUnmounted(() => {
  if (videoContext) {
    videoContext.pause()
  }
})
</script>

<style lang="scss" scoped>
.video-player {
  position: relative;
  width: 100%;
  background: #000;
}

.video-container {
  position: relative;
  width: 100%;
  overflow: hidden;
}

.video-element {
  width: 100%;
  height: 100%;
}

.loading-mask {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.3);
}

.loading-spinner {
  width: 60rpx;
  height: 60rpx;
  border: 4rpx solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.play-btn {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 120rpx;
  height: 120rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
}

.play-icon {
  width: 60rpx;
  height: 60rpx;
}

.mute-btn {
  position: absolute;
  right: 30rpx;
  bottom: 200rpx;
  width: 80rpx;
  height: 80rpx;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.5);
  border-radius: 50%;
}

.mute-icon {
  width: 40rpx;
  height: 40rpx;
}

.progress-bar {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 4rpx;
  background: rgba(255, 255, 255, 0.3);
}

.progress-played {
  height: 100%;
  background: #FF6B00;
  transition: width 0.3s;
}

.video-info {
  position: absolute;
  left: 30rpx;
  right: 140rpx;
  bottom: 100rpx;
  color: #fff;
}

.user-info {
  display: flex;
  align-items: center;
  margin-bottom: 20rpx;
}

.avatar {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  border: 2rpx solid #fff;
}

.nickname {
  margin-left: 16rpx;
  font-size: 30rpx;
  font-weight: 500;
}

.follow-btn {
  margin-left: 20rpx;
  padding: 8rpx 24rpx;
  background: #FF2D55;
  border-radius: 8rpx;
  font-size: 24rpx;
}

.description {
  margin-bottom: 16rpx;
  font-size: 28rpx;
  line-height: 1.5;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.tags {
  display: flex;
  flex-wrap: wrap;
}

.tag {
  margin-right: 16rpx;
  font-size: 26rpx;
  color: #FFD700;
}

.action-bar {
  position: absolute;
  right: 20rpx;
  bottom: 200rpx;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.action-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: 40rpx;
}

.action-icon {
  width: 60rpx;
  height: 60rpx;
}

.action-text {
  margin-top: 8rpx;
  font-size: 24rpx;
  color: #fff;
}

.action-item.reward {
  .coin-icon {
    width: 80rpx;
    height: 80rpx;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
    border-radius: 50%;
  }
  
  .coin-icon .action-icon {
    width: 50rpx;
    height: 50rpx;
  }
  
  .reward-text {
    margin-top: 8rpx;
    font-size: 28rpx;
    font-weight: bold;
    color: #FFD700;
  }
  
  .reward-tip {
    font-size: 20rpx;
    color: #fff;
    
    &.claimed {
      color: #999;
    }
  }
}
</style>

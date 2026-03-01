<template>
  <view class="video-player">
    <!-- 视频播放器 -->
    <video 
      ref="videoRef"
      :id="playerId"
      :src="videoSrc"
      :poster="poster"
      :initial-time="initialTime"
      :autoplay="autoplay"
      :loop="loop"
      :muted="muted"
      :show-center-play-btn="showCenterPlayBtn"
      :show-progress="showProgress"
      :enable-progress-gesture="enableProgressGesture"
      object-fit="cover"
      @play="onPlay"
      @pause="onPause"
      @ended="onEnded"
      @timeupdate="onTimeUpdate"
      @error="onError"
      @waiting="onWaiting"
      @loadedmetadata="onLoadedMetadata"
    />
    
    <!-- 奖励领取浮层 -->
    <view v-if="showRewardTip" class="reward-tip" @click="handleClaim">
      <view class="reward-content">
        <image class="coin-icon" src="/static/images/coin.png" mode="aspectFit" />
        <text class="reward-text">领取 +{{ rewardCoin }} 金币</text>
      </view>
    </view>
    
    <!-- 奖励已领取标记 -->
    <view v-if="rewarded" class="rewarded-mark">
      <text class="rewarded-text">+{{ rewardCoin }}</text>
    </view>
  </view>
</template>

<script>
import { reportWatchProgress, claimReward } from '@/api/video'

export default {
  name: 'VideoPlayer',
  props: {
    // 视频ID
    videoId: {
      type: [Number, String],
      required: true
    },
    // 视频地址
    src: {
      type: String,
      default: ''
    },
    // 封面图
    poster: {
      type: String,
      default: ''
    },
    // 初始播放位置
    initialTime: {
      type: Number,
      default: 0
    },
    // 自动播放
    autoplay: {
      type: Boolean,
      default: false
    },
    // 循环播放
    loop: {
      type: Boolean,
      default: false
    },
    // 静音
    muted: {
      type: Boolean,
      default: false
    },
    // 显示中间播放按钮
    showCenterPlayBtn: {
      type: Boolean,
      default: true
    },
    // 显示进度条
    showProgress: {
      type: Boolean,
      default: true
    },
    // 启用进度手势
    enableProgressGesture: {
      type: Boolean,
      default: true
    },
    // 视频时长(秒)
    duration: {
      type: Number,
      default: 0
    },
    // 合集ID
    collectionId: {
      type: [Number, String],
      default: null
    }
  },
  
  data() {
    return {
      playerId: 'video_' + this.videoId,
      videoContext: null,
      currentTime: 0,
      videoDuration: this.duration,
      watchStartTime: 0,
      sessionId: '',
      
      // 奖励相关
      rewarded: false,
      rewardCoin: 0,
      showRewardTip: false,
      
      // 上报相关
      lastReportTime: 0,
      watchDuration: 0,
      
      // 配置
      config: {
        watch_complete_threshold: 95,
        default_reward_coin: 100
      }
    }
  },
  
  computed: {
    videoSrc() {
      return this.src
    }
  },
  
  watch: {
    src(newVal) {
      if (newVal && this.videoContext) {
        this.resetWatchData()
      }
    }
  },
  
  mounted() {
    this.videoContext = uni.createVideoContext(this.playerId, this)
    this.sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    this.loadConfig()
  },
  
  beforeDestroy() {
    // 组件销毁时上报最后的观看进度
    this.reportProgress()
  },
  
  methods: {
    /**
     * 加载配置
     */
    async loadConfig() {
      try {
        const res = await this.$api.getRewardConfig()
        if (res.code === 1) {
          this.config = { ...this.config, ...res.data }
        }
      } catch (e) {
        console.error('加载配置失败', e)
      }
    },
    
    /**
     * 重置观看数据
     */
    resetWatchData() {
      this.currentTime = 0
      this.watchDuration = 0
      this.watchStartTime = 0
      this.lastReportTime = 0
      this.showRewardTip = false
      this.sessionId = 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
    },
    
    /**
     * 视频开始播放
     */
    onPlay() {
      this.watchStartTime = Date.now()
      this.$emit('play')
    },
    
    /**
     * 视频暂停
     */
    onPause() {
      this.reportProgress()
      this.$emit('pause')
    },
    
    /**
     * 视频播放结束
     */
    onEnded() {
      this.reportProgress()
      this.checkAndShowReward()
      this.$emit('ended')
    },
    
    /**
     * 时间更新
     */
    onTimeUpdate(e) {
      this.currentTime = e.detail.currentTime
      this.videoDuration = e.detail.duration || this.duration
      
      // 每秒上报一次进度
      const now = Date.now()
      if (now - this.lastReportTime > 1000) {
        this.watchDuration++
        this.lastReportTime = now
      }
      
      // 计算进度百分比
      const progress = this.videoDuration > 0 
        ? Math.min(100, Math.round((this.currentTime / this.videoDuration) * 100))
        : 0
      
      // 每5秒上报一次
      if (this.watchDuration % 5 === 0) {
        this.reportProgress()
      }
      
      // 检查是否可以领取奖励
      if (!this.rewarded && !this.showRewardTip) {
        if (progress >= this.config.watch_complete_threshold) {
          this.checkAndShowReward()
        }
      }
      
      this.$emit('timeupdate', {
        currentTime: this.currentTime,
        duration: this.videoDuration,
        progress
      })
    },
    
    /**
     * 视频加载完成
     */
    onLoadedMetadata(e) {
      this.videoDuration = e.detail.duration || this.duration
      this.$emit('loaded', {
        duration: this.videoDuration
      })
    },
    
    /**
     * 视频错误
     */
    onError(e) {
      console.error('视频播放错误', e)
      this.$emit('error', e)
    },
    
    /**
     * 视频加载中
     */
    onWaiting() {
      this.$emit('waiting')
    },
    
    /**
     * 上报观看进度
     */
    async reportProgress() {
      if (this.watchDuration <= 0) return
      
      const progress = this.videoDuration > 0 
        ? Math.min(100, Math.round((this.currentTime / this.videoDuration) * 100))
        : 0
      
      try {
        const res = await reportWatchProgress(this.videoId, {
          watch_duration: this.watchDuration,
          watch_progress: progress,
          current_position: Math.floor(this.currentTime),
          session_id: this.sessionId
        })
        
        // 更新奖励状态
        if (res.data && res.data.can_reward) {
          this.rewardCoin = res.data.reward_coin || this.config.default_reward_coin
          this.checkAndShowReward()
        }
        
        this.$emit('reported', res.data)
      } catch (e) {
        console.error('上报进度失败', e)
      }
    },
    
    /**
     * 检查并显示奖励提示
     */
    checkAndShowReward() {
      if (this.rewarded) return
      
      // 检查进度是否达到阈值
      const progress = this.videoDuration > 0 
        ? Math.min(100, Math.round((this.currentTime / this.videoDuration) * 100))
        : 0
      
      if (progress >= this.config.watch_complete_threshold) {
        this.showRewardTip = true
        this.rewardCoin = this.rewardCoin || this.config.default_reward_coin
      }
    },
    
    /**
     * 领取奖励
     */
    async handleClaim() {
      if (this.rewarded) return
      
      try {
        uni.showLoading({ title: '领取中...' })
        
        const res = await claimReward(this.videoId)
        
        uni.hideLoading()
        
        if (res.code === 1) {
          this.rewarded = true
          this.showRewardTip = false
          this.rewardCoin = res.data.reward_coin || this.rewardCoin
          
          // 显示成功动画
          uni.showToast({
            title: `+${this.rewardCoin} 金币`,
            icon: 'success'
          })
          
          this.$emit('reward', {
            coin: this.rewardCoin,
            balance: res.data.balance
          })
        } else {
          uni.showToast({
            title: res.msg || '领取失败',
            icon: 'none'
          })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({
          title: '领取失败',
          icon: 'none'
        })
      }
    },
    
    /**
     * 播放视频
     */
    play() {
      if (this.videoContext) {
        this.videoContext.play()
      }
    },
    
    /**
     * 暂停视频
     */
    pause() {
      if (this.videoContext) {
        this.videoContext.pause()
      }
    },
    
    /**
     * 跳转到指定位置
     */
    seek(position) {
      if (this.videoContext) {
        this.videoContext.seek(position)
      }
    },
    
    /**
     * 停止视频
     */
    stop() {
      if (this.videoContext) {
        this.videoContext.stop()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.video-player {
  position: relative;
  width: 100%;
  height: 100%;
  
  video {
    width: 100%;
    height: 100%;
  }
  
  .reward-tip {
    position: absolute;
    right: 20rpx;
    bottom: 120rpx;
    z-index: 10;
    
    .reward-content {
      display: flex;
      align-items: center;
      padding: 16rpx 24rpx;
      background: linear-gradient(135deg, #FFD700 0%, #FFA500 100%);
      border-radius: 40rpx;
      box-shadow: 0 4rpx 12rpx rgba(255, 165, 0, 0.4);
      animation: pulse 1.5s ease-in-out infinite;
    }
    
    .coin-icon {
      width: 40rpx;
      height: 40rpx;
      margin-right: 8rpx;
    }
    
    .reward-text {
      font-size: 28rpx;
      font-weight: bold;
      color: #fff;
    }
  }
  
  .rewarded-mark {
    position: absolute;
    right: 20rpx;
    bottom: 120rpx;
    padding: 8rpx 20rpx;
    background: rgba(0, 0, 0, 0.5);
    border-radius: 20rpx;
    
    .rewarded-text {
      font-size: 24rpx;
      color: #FFD700;
    }
  }
}

@keyframes pulse {
  0%, 100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}
</style>

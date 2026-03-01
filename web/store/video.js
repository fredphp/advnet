/**
 * 视频状态管理
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { get, post } from '@/utils/request'
import { useCoinStore } from './coin'

export const useVideoStore = defineStore('video', () => {
  // 视频列表
  const videoList = ref([])
  // 当前播放索引
  const currentIndex = ref(0)
  // 是否加载中
  const loading = ref(false)
  // 是否还有更多
  const hasMore = ref(true)
  // 分页参数
  const page = ref(1)
  const pageSize = 10
  
  // 当前视频
  const currentVideo = computed(() => {
    return videoList.value[currentIndex.value] || null
  })
  
  // 获取视频列表
  const fetchVideoList = async (refresh = false) => {
    if (loading.value) return
    
    if (refresh) {
      page.value = 1
      videoList.value = []
      hasMore.value = true
    }
    
    if (!hasMore.value) return
    
    loading.value = true
    
    try {
      const res = await get('/api/video/list', {
        page: page.value,
        limit: pageSize
      }, { showLoading: false })
      
      if (res.code === 1) {
        const list = res.data.list || []
        
        if (refresh) {
          videoList.value = list
        } else {
          videoList.value = [...videoList.value, ...list]
        }
        
        hasMore.value = list.length >= pageSize
        page.value++
      }
    } catch (e) {
      console.error('获取视频列表失败:', e)
    } finally {
      loading.value = false
    }
  }
  
  // 上滑下一个视频
  const nextVideo = () => {
    if (currentIndex.value < videoList.value.length - 1) {
      currentIndex.value++
      return true
    }
    
    // 已经是最后一个，加载更多
    if (hasMore.value) {
      fetchVideoList().then(() => {
        if (currentIndex.value < videoList.value.length - 1) {
          currentIndex.value++
        }
      })
    }
    
    return false
  }
  
  // 下滑上一个视频
  const prevVideo = () => {
    if (currentIndex.value > 0) {
      currentIndex.value--
      return true
    }
    return false
  }
  
  // 跳转到指定视频
  const goToVideo = (index) => {
    if (index >= 0 && index < videoList.value.length) {
      currentIndex.value = index
      return true
    }
    return false
  }
  
  // 上报观看进度
  const reportProgress = async (videoId, progress, duration) => {
    try {
      const res = await post('/api/video/reportProgress', {
        video_id: videoId,
        progress: progress,
        duration: duration
      }, { showLoading: false })
      
      // 如果获得金币奖励
      if (res.code === 1 && res.data.reward_coin > 0) {
        const coinStore = useCoinStore()
        coinStore.updateBalance(res.data.reward_coin)
        uni.showToast({
          title: `+${res.data.reward_coin}金币`,
          icon: 'none'
        })
      }
      
      return res
    } catch (e) {
      console.error('上报进度失败:', e)
      return null
    }
  }
  
  // 领取视频奖励
  const claimReward = async (videoId) => {
    try {
      const res = await post('/api/video/claimReward', {
        video_id: videoId
      })
      
      if (res.code === 1 && res.data.reward_coin > 0) {
        const coinStore = useCoinStore()
        coinStore.updateBalance(res.data.reward_coin)
        
        // 更新视频状态
        const video = videoList.value.find(v => v.id === videoId)
        if (video) {
          video.reward_claimed = true
        }
      }
      
      return res
    } catch (e) {
      console.error('领取奖励失败:', e)
      return null
    }
  }
  
  // 点赞视频
  const likeVideo = async (videoId) => {
    try {
      const res = await post('/api/video/like', {
        video_id: videoId
      }, { showLoading: false })
      
      if (res.code === 1) {
        // 更新本地状态
        const video = videoList.value.find(v => v.id === videoId)
        if (video) {
          video.is_liked = !video.is_liked
          video.like_count += video.is_liked ? 1 : -1
        }
      }
      
      return res
    } catch (e) {
      console.error('点赞失败:', e)
      return null
    }
  }
  
  // 重置状态
  const reset = () => {
    videoList.value = []
    currentIndex.value = 0
    page.value = 1
    hasMore.value = true
    loading.value = false
  }
  
  return {
    videoList,
    currentIndex,
    loading,
    hasMore,
    currentVideo,
    
    fetchVideoList,
    nextVideo,
    prevVideo,
    goToVideo,
    reportProgress,
    claimReward,
    likeVideo,
    reset
  }
})

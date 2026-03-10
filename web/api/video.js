/**
 * 视频收益相关接口
 */
import request from '@/utils/request'

/**
 * 上报观看进度
 * @param {Number} videoId 视频ID
 * @param {Object} data 观看数据
 */
export function reportWatchProgress(videoId, data) {
  return request({
    url: '/api/video_reward/watch',
    method: 'POST',
    data: {
      video_id: videoId,
      watch_duration: data.watch_duration || 0,
      watch_progress: data.watch_progress || 0,
      current_position: data.current_position || 0,
      session_id: data.session_id || generateSessionId()
    }
  })
}

/**
 * 领取视频奖励
 * @param {Number} videoId 视频ID
 */
export function claimReward(videoId) {
  return request({
    url: '/api/video_reward/claim',
    method: 'POST',
    data: {
      video_id: videoId
    }
  })
}

/**
 * 批量获取奖励状态
 * @param {Number[]} videoIds 视频ID数组
 */
export function getRewardStatus(videoIds) {
  return request({
    url: '/api/video_reward/status',
    method: 'POST',
    data: {
      video_ids: videoIds
    }
  })
}

/**
 * 获取合集进度
 * @param {Number} collectionId 合集ID
 */
export function getCollectionProgress(collectionId) {
  return request({
    url: '/api/video_reward/collection',
    method: 'GET',
    data: {
      collection_id: collectionId
    }
  })
}

/**
 * 获取今日统计
 */
export function getDailyStats() {
  return request({
    url: '/api/video_reward/daily',
    method: 'GET'
  })
}

/**
 * 获取奖励配置
 */
export function getRewardConfig() {
  return request({
    url: '/api/video_reward/config',
    method: 'GET'
  })
}

/**
 * 生成会话ID
 */
function generateSessionId() {
  return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9)
}

export default {
  reportWatchProgress,
  claimReward,
  getRewardStatus,
  getCollectionProgress,
  getDailyStats,
  getRewardConfig
}

/**
 * 红包任务相关接口
 */
import request from '@/utils/request'

/**
 * 点击红包 - 根据配置生成/累加红包金额
 * 返回数据包含：
 * - amount: 本次点击获得的金额
 * - base_amount: 基础金额
 * - accumulate_amount: 累加金额
 * - total_amount: 总金额
 * - click_count: 点击次数
 * - is_new_base: 是否重新生成基础金额
 * - max_limit: 封顶额度
 * - reached_limit: 是否达到封顶
 * 
 * @param {Object} params 参数
 * @param {Number} params.task_id 任务ID(可选)
 */
export function clickRedPacket(params = {}) {
  return request({
    url: '/api/redpacket/click',
    method: 'POST',
    data: {
      task_id: params.task_id || 0
    }
  })
}

/**
 * 领取红包金币 - 看完广告后领取
 * @param {Object} params 参数
 * @param {Number} params.task_id 任务ID(可选)
 */
export function claimRedPacket(params = {}) {
  return request({
    url: '/api/redpacket/claim',
    method: 'POST',
    data: {
      task_id: params.task_id || 0
    }
  })
}

/**
 * 获取当前累计金额
 */
export function getRedPacketAmount() {
  return request({
    url: '/api/redpacket/amount',
    method: 'GET'
  })
}

/**
 * 获取任务列表
 * @param {Object} params 查询参数
 */
export function getTaskList(params = {}) {
  return request({
    url: '/api/redpacket/tasks',
    method: 'GET',
    data: {
      page: params.page || 1,
      limit: params.limit || 20,
      task_type: params.task_type || '',
      category_id: params.category_id || 0
    }
  })
}

/**
 * 获取任务详情
 * @param {Number} taskId 任务ID
 */
export function getTaskDetail(taskId) {
  return request({
    url: '/api/redpacket/detail',
    method: 'GET',
    data: { task_id: taskId }
  })
}

/**
 * 获取任务分类
 */
export function getCategories() {
  return request({
    url: '/api/redpacket/categories',
    method: 'GET'
  })
}

/**
 * 领取任务
 * @param {Number} taskId 任务ID
 * @param {Object} deviceInfo 设备信息
 */
export function receiveTask(taskId, deviceInfo = {}) {
  return request({
    url: '/api/redpacket/receive',
    method: 'POST',
    data: {
      task_id: taskId,
      device_info: JSON.stringify(deviceInfo)
    }
  })
}

/**
 * 提交任务完成
 * @param {String} orderNo 订单号
 * @param {Object} data 提交数据
 */
export function submitTask(orderNo, data = {}) {
  return request({
    url: '/api/redpacket/submit',
    method: 'POST',
    data: {
      order_no: orderNo,
      duration: data.duration || 0,
      progress: data.progress || 100,
      screenshots: JSON.stringify(data.screenshots || []),
      proof_data: JSON.stringify(data.proof_data || {})
    }
  })
}

/**
 * 获取我的参与记录
 * @param {Object} params 查询参数
 */
export function getRecords(params = {}) {
  return request({
    url: '/api/redpacket/records',
    method: 'GET',
    data: {
      status: params.status,
      page: params.page || 1,
      limit: params.limit || 20
    }
  })
}

/**
 * 获取今日统计
 */
export function getTodayStat() {
  return request({
    url: '/api/redpacket/today',
    method: 'GET'
  })
}

/**
 * 取消任务
 * @param {String} orderNo 订单号
 */
export function cancelTask(orderNo) {
  return request({
    url: '/api/redpacket/cancel',
    method: 'POST',
    data: { order_no: orderNo }
  })
}

/**
 * 任务类型映射
 */
export const TaskTypeMap = {
  download_app: { name: '下载任务', icon: 'download' },
  mini_program: { name: '小程序任务', icon: 'miniapp' },
  play_game: { name: '游戏任务', icon: 'game' },
  watch_video: { name: '视频任务', icon: 'video' },
  share_link: { name: '分享任务', icon: 'share' },
  sign_in: { name: '签到任务', icon: 'sign' }
}

/**
 * 任务状态映射
 */
export const TaskStatusMap = {
  0: { name: '已领取', color: 'warning' },
  1: { name: '待审核', color: 'info' },
  2: { name: '审核通过', color: 'success' },
  3: { name: '已发放', color: 'success' },
  4: { name: '已拒绝', color: 'error' },
  5: { name: '已过期', color: 'gray' },
  6: { name: '已取消', color: 'gray' }
}

export default {
  clickRedPacket,
  claimRedPacket,
  getRedPacketAmount,
  getTaskList,
  getTaskDetail,
  getCategories,
  receiveTask,
  submitTask,
  getRecords,
  getTodayStat,
  cancelTask,
  TaskTypeMap,
  TaskStatusMap
}

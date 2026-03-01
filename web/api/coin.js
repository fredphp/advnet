/**
 * 金币相关接口
 */
import request from '@/utils/request'

/**
 * 获取金币余额
 */
export function getBalance() {
  return request({
    url: '/api/coin/balance',
    method: 'GET'
  })
}

/**
 * 获取账户详情
 */
export function getAccount() {
  return request({
    url: '/api/coin/account',
    method: 'GET'
  })
}

/**
 * 获取金币流水
 * @param {Object} params 查询参数
 */
export function getLogs(params = {}) {
  return request({
    url: '/api/coin/logs',
    method: 'GET',
    data: {
      page: params.page || 1,
      limit: params.limit || 20,
      type: params.type || '',
      month: params.month || ''
    }
  })
}

/**
 * 获取流水类型
 */
export function getTypes() {
  return request({
    url: '/api/coin/types',
    method: 'GET'
  })
}

export default {
  getBalance,
  getAccount,
  getLogs,
  getTypes
}

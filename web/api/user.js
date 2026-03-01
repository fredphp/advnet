/**
 * 用户相关 API
 */
import { get, post } from '@/utils/request'

/**
 * 微信登录
 * @param {string} code - 微信登录code
 * @param {object} data - 其他数据
 */
export function wechatLogin(code, data = {}) {
  return post('/api/user/wechatLogin', {
    code,
    ...data
  })
}

/**
 * 手机号登录
 * @param {string} phone - 手机号
 * @param {string} code - 验证码
 */
export function phoneLogin(phone, code) {
  return post('/api/user/phoneLogin', {
    phone,
    code
  })
}

/**
 * 获取用户信息
 */
export function getUserInfo() {
  return get('/api/user/info')
}

/**
 * 更新用户信息
 * @param {object} data - 用户数据
 */
export function updateUserInfo(data) {
  return post('/api/user/update', data)
}

/**
 * 发送验证码
 * @param {string} phone - 手机号
 * @param {string} type - 类型 login/bind
 */
export function sendVerifyCode(phone, type = 'login') {
  return post('/api/sms/send', {
    phone,
    type
  })
}

/**
 * 获取用户设置
 */
export function getUserSettings() {
  return get('/api/user/settings')
}

/**
 * 更新用户设置
 * @param {object} data - 设置数据
 */
export function updateUserSettings(data) {
  return post('/api/user/updateSettings', data)
}

/**
 * 绑定手机号
 * @param {string} phone - 手机号
 * @param {string} code - 验证码
 */
export function bindPhone(phone, code) {
  return post('/api/user/bindPhone', {
    phone,
    code
  })
}

/**
 * 绑定微信
 * @param {string} code - 微信code
 */
export function bindWechat(code) {
  return post('/api/user/bindWechat', {
    code
  })
}

/**
 * 意见反馈
 * @param {object} data - 反馈数据
 */
export function submitFeedback(data) {
  return post('/api/feedback/submit', data)
}

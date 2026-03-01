/**
 * HTTP请求封装
 */
const BASE_URL = '' // 由manifest.json配置

class Request {
  constructor() {
    this.baseUrl = BASE_URL
    this.timeout = 30000
  }

  /**
   * 发送请求
   */
  request(options = {}) {
    return new Promise((resolve, reject) => {
      // 获取token
      const token = uni.getStorageSync('token')
      
      uni.request({
        url: this.baseUrl + options.url,
        method: options.method || 'GET',
        data: options.data || {},
        timeout: this.timeout,
        header: {
          'Content-Type': 'application/json',
          'token': token,
          'x-device-id': this.getDeviceId(),
          ...options.header
        },
        success: (res) => {
          if (res.statusCode === 200) {
            const data = res.data
            if (data.code === 1 || data.code === 0) {
              resolve(data)
            } else if (data.code === 401) {
              // token过期，跳转登录
              uni.removeStorageSync('token')
              uni.navigateTo({ url: '/pages/login/login' })
              reject(new Error('请先登录'))
            } else {
              reject(new Error(data.msg || '请求失败'))
            }
          } else {
            reject(new Error('网络错误'))
          }
        },
        fail: (err) => {
          reject(new Error(err.errMsg || '网络请求失败'))
        }
      })
    })
  }

  /**
   * GET请求
   */
  get(url, data = {}) {
    return this.request({ url, method: 'GET', data })
  }

  /**
   * POST请求
   */
  post(url, data = {}) {
    return this.request({ url, method: 'POST', data })
  }

  /**
   * 获取设备ID
   */
  getDeviceId() {
    let deviceId = uni.getStorageSync('device_id')
    if (!deviceId) {
      deviceId = 'dev_' + Date.now() + '_' + Math.random().toString(36).substr(2, 16)
      uni.setStorageSync('device_id', deviceId)
    }
    return deviceId
  }
}

const request = new Request()

export default function(options) {
  return request.request(options)
}

export { request }

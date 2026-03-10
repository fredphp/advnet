/**
 * 网络请求封装
 * 支持：请求拦截、响应拦截、错误处理、Token刷新
 */

// API 基础地址
const BASE_URL = 'https://api.example.com'

// 请求超时时间
const TIMEOUT = 30000

// Token 存储 Key
const TOKEN_KEY = 'token'
const USER_INFO_KEY = 'userInfo'

/**
 * 获取存储的 Token
 */
export const getToken = () => {
  return uni.getStorageSync(TOKEN_KEY) || ''
}

/**
 * 设置 Token
 */
export const setToken = (token) => {
  uni.setStorageSync(TOKEN_KEY, token)
}

/**
 * 移除 Token
 */
export const removeToken = () => {
  uni.removeStorageSync(TOKEN_KEY)
  uni.removeStorageSync(USER_INFO_KEY)
}

/**
 * 获取用户信息
 */
export const getUserInfo = () => {
  return uni.getStorageSync(USER_INFO_KEY) || null
}

/**
 * 设置用户信息
 */
export const setUserInfo = (userInfo) => {
  uni.setStorageSync(USER_INFO_KEY, userInfo)
}

/**
 * 检查是否登录
 */
export const isLoggedIn = () => {
  return !!getToken()
}

/**
 * 跳转登录页
 */
const goToLogin = () => {
  // 保存当前页面路径
  const pages = getCurrentPages()
  const currentPage = pages[pages.length - 1]
  const currentRoute = currentPage ? `/${currentPage.route}` : '/pages/index/index'
  
  uni.navigateTo({
    url: `/pages/login/login?redirect=${encodeURIComponent(currentRoute)}`
  })
}

/**
 * 显示提示
 */
const showToast = (message, icon = 'none') => {
  uni.showToast({
    title: message,
    icon: icon,
    duration: 2000
  })
}

/**
 * 显示加载中
 */
export const showLoading = (title = '加载中...') => {
  uni.showLoading({
    title: title,
    mask: true
  })
}

/**
 * 隐藏加载中
 */
export const hideLoading = () => {
  uni.hideLoading()
}

/**
 * 请求队列（用于并发控制）
 */
const requestQueue = new Map()

/**
 * 生成请求唯一标识
 */
const generateRequestKey = (config) => {
  return `${config.method}-${config.url}-${JSON.stringify(config.data)}`
}

/**
 * 核心请求方法
 */
const request = (config) => {
  return new Promise((resolve, reject) => {
    // 生成请求标识，防止重复请求
    const requestKey = generateRequestKey(config)
    
    if (requestQueue.has(requestKey)) {
      return requestQueue.get(requestKey)
    }
    
    // 构建 Promise
    const requestPromise = new Promise((innerResolve, innerReject) => {
      // 检查网络状态
      uni.getNetworkType({
        success: (networkRes) => {
          if (networkRes.networkType === 'none') {
            showToast('网络不可用，请检查网络连接')
            innerReject(new Error('网络不可用'))
            return
          }
          
          // 发起请求
          uni.request({
            url: config.url.startsWith('http') ? config.url : BASE_URL + config.url,
            method: config.method || 'GET',
            data: config.data || {},
            timeout: config.timeout || TIMEOUT,
            header: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${getToken()}`,
              'X-Device-Id': uni.getDeviceInfo()?.deviceId || '',
              'X-Platform': uni.getSystemInfoSync().platform || '',
              'X-Version': '1.0.0',
              ...config.header
            },
            success: (res) => {
              // 响应拦截
              const data = res.data
                
              // 业务状态码判断
              if (data.code === 1) {
                innerResolve(data)
              } else if (data.code === 401) {
                // Token 过期或无效
                removeToken()
                showToast('登录已过期，请重新登录')
                goToLogin()
                innerReject(new Error('登录已过期'))
              } else if (data.code === 403) {
                showToast('没有权限访问')
                innerReject(new Error('没有权限'))
              } else if (data.code === 429) {
                showToast('请求过于频繁，请稍后再试')
                innerReject(new Error('请求过于频繁'))
              } else {
                // 业务错误
                showToast(data.msg || '请求失败')
                innerReject(new Error(data.msg || '请求失败'))
              }
            },
            fail: (err) => {
              console.error('请求失败:', err)
              showToast('网络请求失败')
              innerReject(err)
            },
            complete: () => {
              // 从队列中移除
              requestQueue.delete(requestKey)
              
              // 隐藏加载
              if (config.showLoading !== false) {
                hideLoading()
              }
            }
          })
        },
        fail: () => {
          showToast('获取网络状态失败')
          innerReject(new Error('获取网络状态失败'))
        }
      })
    })
    
    // 加入队列
    requestQueue.set(requestKey, requestPromise)
    
    // 返回结果
    requestPromise.then(resolve).catch(reject)
  })
}

/**
 * GET 请求
 */
export const get = (url, data = {}, config = {}) => {
  if (config.showLoading !== false) {
    showLoading(config.loadingText)
  }
  
  return request({
    url,
    method: 'GET',
    data,
    ...config
  })
}

/**
 * POST 请求
 */
export const post = (url, data = {}, config = {}) => {
  if (config.showLoading !== false) {
    showLoading(config.loadingText)
  }
  
  return request({
    url,
    method: 'POST',
    data,
    ...config
  })
}

/**
 * 上传文件
 */
export const upload = (url, filePath, formData = {}, config = {}) => {
  return new Promise((resolve, reject) => {
    if (config.showLoading !== false) {
      showLoading(config.loadingText || '上传中...')
    }
    
    uni.uploadFile({
      url: url.startsWith('http') ? url : BASE_URL + url,
      filePath: filePath,
      name: config.name || 'file',
      formData: formData,
      header: {
        'Authorization': `Bearer ${getToken()}`
      },
      success: (res) => {
        try {
          const data = JSON.parse(res.data)
          if (data.code === 1) {
            resolve(data)
          } else if (data.code === 401) {
            removeToken()
            goToLogin()
            reject(new Error('登录已过期'))
          } else {
            showToast(data.msg || '上传失败')
            reject(new Error(data.msg || '上传失败'))
          }
        } catch (e) {
          showToast('解析响应失败')
          reject(e)
        }
      },
      fail: (err) => {
        showToast('上传失败')
        reject(err)
      },
      complete: () => {
        hideLoading()
      }
    })
  })
}

/**
 * 下载文件
 */
export const download = (url, config = {}) => {
  return new Promise((resolve, reject) => {
    if (config.showLoading !== false) {
      showLoading(config.loadingText || '下载中...')
    }
    
    uni.downloadFile({
      url: url.startsWith('http') ? url : BASE_URL + url,
      header: {
        'Authorization': `Bearer ${getToken()}`
      },
      success: (res) => {
        if (res.statusCode === 200) {
          resolve(res.tempFilePath)
        } else {
          showToast('下载失败')
          reject(new Error('下载失败'))
        }
      },
      fail: (err) => {
        showToast('下载失败')
        reject(err)
      },
      complete: () => {
        hideLoading()
      }
    })
  })
}

export default {
  get,
  post,
  upload,
  download,
  getToken,
  setToken,
  removeToken,
  getUserInfo,
  setUserInfo,
  isLoggedIn,
  showLoading,
  hideLoading,
  showToast
}

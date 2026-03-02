/**
 * 用户状态管理
 */
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { get, post, getToken, setToken, removeToken, setUserInfo, getUserInfo } from '@/utils/request'

export const useUserStore = defineStore('user', () => {
  // 状态
  const token = ref(getToken() || '')
  const userInfo = ref(getUserInfo() || null)
  const isLoggedIn = computed(() => !!token.value)
  
  // 微信登录
  const wxLogin = async () => {
    return new Promise((resolve, reject) => {
      uni.login({
        provider: 'weixin',
        success: async (loginRes) => {
          try {
            // 调用后端登录接口
            const res = await post('/api/user/wxLogin', {
              code: loginRes.code
            })
            
            if (res.code === 1) {
              token.value = res.data.token
              userInfo.value = res.data.user_info
              setToken(res.data.token)
              setUserInfo(res.data.user_info)
              resolve(res.data)
            } else {
              reject(new Error(res.msg || '登录失败'))
            }
          } catch (e) {
            reject(e)
          }
        },
        fail: (err) => {
          reject(err)
        }
      })
    })
  }
  
  // 手机号登录
  const phoneLogin = async (phone, code) => {
    const res = await post('/api/user/phoneLogin', {
      phone,
      code
    })
    
    if (res.code === 1) {
      token.value = res.data.token
      userInfo.value = res.data.user_info
      setToken(res.data.token)
      setUserInfo(res.data.user_info)
    }
    
    return res
  }
  
  // 获取用户信息
  const fetchUserInfo = async () => {
    const res = await get('/api/user/info')
    if (res.code === 1) {
      userInfo.value = res.data
      setUserInfo(res.data)
    }
    return res
  }
  
  // 更新用户信息
  const updateUserInfo = (info) => {
    userInfo.value = { ...userInfo.value, ...info }
    setUserInfo(userInfo.value)
  }
  
  // 退出登录
  const logout = async () => {
    try {
      await post('/api/user/logout')
    } catch (e) {
      // 忽略错误
    }
    
    token.value = ''
    userInfo.value = null
    removeToken()
    
    // 跳转登录页
    uni.reLaunch({
      url: '/pages/login/login'
    })
  }
  
  return {
    token,
    userInfo,
    isLoggedIn,
    wxLogin,
    phoneLogin,
    fetchUserInfo,
    updateUserInfo,
    logout
  }
})

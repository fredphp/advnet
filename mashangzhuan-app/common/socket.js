// utils/socket.js
class SocketService {
  constructor() {
    this.socket = null
    this.isConnected = false
    this.options = null
    this.heartbeatTimer = null
    this.reconnectTimer = null
    this.reconnectAttempts = 0
    this.maxReconnectAttempts = 10
    
    // 回调函数
    this.onConnectedCallback = null
    this.onAuthFailedCallback = null
    this.onOnlineCountCallback = null
    this.onTaskCallback = null
    this.onSystemMessageCallback = null
    this.onChatMessageCallback = null
  }

  connect(options = {}) {
    this.options = options
    const { userId, token } = options
    
    // WebSocket 连接地址
    // 优先级: options.serverUrl > Nginx 代理 /ws > 直连 host:3002
    let serverUrl = options.serverUrl || ''
    if (!serverUrl) {
      const href = window.location || {}
      const host = href.hostname || 'localhost'
      const protocol = href.protocol === 'https:' ? 'wss:' : 'ws:'
      
      if (host === 'localhost' || host === '127.0.0.1') {
        // 本地开发：直连 WebSocket 端口
        serverUrl = `${protocol}//${host}:3002`
      } else {
        // 生产环境：通过 Nginx 代理 (location /ws)
        serverUrl = `${protocol}//${host}/ws`
      }
    }
    
    console.log('[Socket] 正在连接:', serverUrl)
    
    this.socket = new WebSocket(serverUrl)
    
    this.socket.onopen = () => {
      console.log('[Socket] ✅ 已连接')
      this.isConnected = true
      this.reconnectAttempts = 0
      
      // 发送认证消息
      this.authenticate(userId, token)
      
      // 启动心跳
      this.startHeartbeat()
    }
    
    this.socket.onmessage = (event) => {
      try {
        const message = JSON.parse(event.data)
        console.log('[Socket] 📩 收到消息, type:', message.type, message)
        this.handleMessage(message)
      } catch (e) {
        console.error('[Socket] 消息解析错误:', e, event.data)
      }
    }
    
    this.socket.onclose = (event) => {
      console.log('[Socket] 🔌 已断开, code:', event.code, 'reason:', event.reason)
      this.isConnected = false
      this.stopHeartbeat()
      this.attemptReconnect()
    }
    
    this.socket.onerror = (error) => {
      console.error('[Socket] ❌ 连接错误, serverUrl:', serverUrl)
    }
    
    return this.socket
  }

  authenticate(userId, token) {
    console.log('[Socket] 发送认证, userId:', userId, ', token:', token ? '有' : '无')
    this.send({
      type: 'auth',
      userId: userId || '',
      token: token || ''
    })
  }

  send(data) {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(data))
    } else {
      console.warn('[Socket] 发送失败，连接状态:', this.socket ? this.socket.readyState : 'null')
    }
  }

  handleMessage(message) {
    const type = message.type
    console.log('[Socket] handleMessage -> type:', type)
    
    switch (type) {
      case 'connected':
        console.log('[Socket] ✅ 认证成功, onlineCount:', message.onlineCount)
        if (this.onConnectedCallback) {
          this.onConnectedCallback(message)
        } else {
          console.warn('[Socket] ⚠️ onConnected 回调未注册')
        }
        break
        
      case 'auth_failed':
        console.error('[Socket] ❌ 认证失败:', message.msg)
        if (this.onAuthFailedCallback) {
          this.onAuthFailedCallback(message)
        }
        break
        
      case 'pong':
        // 心跳响应，静默处理
        break
        
      case 'online_count':
        if (this.onOnlineCountCallback) {
          this.onOnlineCountCallback(message.count)
        }
        break
        
      case 'task_notification':
        console.log('[Socket] 🎯 收到 task_notification, 回调已注册:', !!this.onTaskCallback)
        if (this.onTaskCallback) {
          this.onTaskCallback(message)
        } else {
          console.warn('[Socket] ⚠️ onTask 回调未注册！')
        }
        break
        
      case 'system_message':
        if (this.onSystemMessageCallback) {
          this.onSystemMessageCallback(message)
        }
        break
        
      case 'chat_message':
        if (this.onChatMessageCallback) {
          this.onChatMessageCallback(message)
        }
        break
        
      default:
        console.log('[Socket] 未处理的消息类型:', type, message)
    }
  }

  startHeartbeat() {
    this.stopHeartbeat()
    this.heartbeatTimer = setInterval(() => {
      this.send({ type: 'ping' })
    }, 30000)
  }

  stopHeartbeat() {
    if (this.heartbeatTimer) {
      clearInterval(this.heartbeatTimer)
      this.heartbeatTimer = null
    }
  }

  attemptReconnect() {
    if (this.reconnectAttempts >= this.maxReconnectAttempts) {
      console.error('[Socket] 达到最大重连次数:', this.maxReconnectAttempts)
      return
    }
    
    this.reconnectAttempts++
    const delay = Math.min(1000 * this.reconnectAttempts, 10000)
    console.log('[Socket] 将在', delay / 1000, '秒后重连, 第', this.reconnectAttempts, '次')
    
    this.reconnectTimer = setTimeout(() => {
      console.log('[Socket] 开始重连...')
      this.connect(this.options)
    }, delay)
  }

  // 注册回调
  onConnected(callback) { this.onConnectedCallback = callback }
  onAuthFailed(callback) { this.onAuthFailedCallback = callback }
  onOnlineCount(callback) { this.onOnlineCountCallback = callback }
  onTask(callback) { this.onTaskCallback = callback }
  onSystemMessage(callback) { this.onSystemMessageCallback = callback }
  onChatMessage(callback) { this.onChatMessageCallback = callback }

  disconnect() {
    this.stopHeartbeat()
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer)
      this.reconnectTimer = null
    }
    if (this.socket) {
      this.socket.close()
      this.socket = null
    }
    this.isConnected = false
  }
}

export default new SocketService()

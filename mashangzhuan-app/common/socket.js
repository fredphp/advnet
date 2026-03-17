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
    
    // ⚠️ 本地开发：直接连接 localhost:3002
    // 不需要 wss://，不需要 XTransformPort
    const serverUrl = 'ws://localhost:3002'
    
    console.log('正在连接 WebSocket:', serverUrl)
    
    this.socket = new WebSocket(serverUrl)
    
    this.socket.onopen = () => {
      console.log('✅ WebSocket 已连接')
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
        console.log('📩 收到消息:', message)
        this.handleMessage(message)
      } catch (e) {
        console.error('消息解析错误:', e)
      }
    }
    
    this.socket.onclose = (event) => {
      console.log('🔌 WebSocket 已断开', event.code, event.reason)
      this.isConnected = false
      this.stopHeartbeat()
      this.attemptReconnect()
    }
    
    this.socket.onerror = (error) => {
      console.error('❌ WebSocket 错误:', error)
    }
    
    return this.socket
  }

  authenticate(userId, token) {
    console.log('发送认证消息, userId:', userId)
    this.send({
      type: 'auth',
      userId: userId || '',
      token: token || ''
    })
  }

  send(data) {
    if (this.socket && this.socket.readyState === WebSocket.OPEN) {
      this.socket.send(JSON.stringify(data))
    }
  }

  handleMessage(message) {
    switch (message.type) {
      case 'connected':
        console.log('✅ 认证成功，在线人数:', message.onlineCount)
        if (this.onConnectedCallback) {
          this.onConnectedCallback(message)
        }
        break
        
      case 'auth_failed':
        console.error('❌ 认证失败:', message.msg)
        if (this.onAuthFailedCallback) {
          this.onAuthFailedCallback(message)
        }
        break
        
      case 'pong':
        break
        
      case 'online_count':
        if (this.onOnlineCountCallback) {
          this.onOnlineCountCallback(message.count)
        }
        break
        
      case 'task_notification':
        if (this.onTaskCallback) {
          this.onTaskCallback(message)
        }
        break
        
      case 'system_message':
        if (this.onSystemMessageCallback) {
          this.onSystemMessageCallback(message)
        }
        break
        
      default:
        console.log('未知消息类型:', message.type)
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
    if (this.reconnectAttempts >= this.maxReconnectAttempts) return
    
    this.reconnectAttempts++
    const delay = Math.min(1000 * this.reconnectAttempts, 10000)
    
    this.reconnectTimer = setTimeout(() => {
      this.connect(this.options)
    }, delay)
  }

  // 注册回调
  onConnected(callback) { this.onConnectedCallback = callback }
  onAuthFailed(callback) { this.onAuthFailedCallback = callback }
  onOnlineCount(callback) { this.onOnlineCountCallback = callback }
  onTask(callback) { this.onTaskCallback = callback }
  onSystemMessage(callback) { this.onSystemMessageCallback = callback }

  disconnect() {
    this.stopHeartbeat()
    if (this.reconnectTimer) {
      clearTimeout(this.reconnectTimer)
    }
    if (this.socket) {
      this.socket.close()
      this.socket = null
    }
    this.isConnected = false
  }
}

export default new SocketService()
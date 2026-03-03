/**
 * Pinia 状态管理入口
 */
import { createPinia } from 'pinia'

const pinia = createPinia()

export default pinia

// 导出各模块
export { useUserStore } from './user'
export { useCoinStore } from './coin'
export { useVideoStore } from './video'

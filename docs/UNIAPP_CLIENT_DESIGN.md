# UniApp 客户端结构设计

## 1. 项目目录结构

```
web/
├── api/                          # API 接口封装
│   ├── index.js                  # 统一导出入口
│   ├── user.js                   # 用户相关 API
│   ├── coin.js                   # 金币相关 API
│   ├── video.js                  # 视频相关 API
│   ├── redpacket.js              # 红包相关 API
│   ├── withdraw.js               # 提现相关 API
│   └── invite.js                 # 邀请相关 API
│
├── store/                        # Pinia 状态管理
│   ├── index.js                  # Store 入口
│   ├── user.js                   # 用户状态
│   ├── coin.js                   # 金币状态（含实时刷新）
│   └── video.js                  # 视频状态
│
├── utils/                        # 工具函数
│   └── request.js                # 网络请求封装
│
├── components/                   # 公共组件
│   ├── video-player.vue          # 视频播放器组件
│   └── red-packet-list.vue       # 红包列表组件
│
├── pages/                        # 页面目录
│   ├── index/                    # 首页（短视频浏览）
│   │   └── index.vue
│   ├── video/                    # 视频详情页
│   │   └── video.vue
│   ├── redpacket/                # 红包任务页
│   │   └── redpacket.vue
│   ├── invite/                   # 邀请统计页
│   │   └── invite.vue
│   ├── mine/                     # 个人中心页
│   │   └── mine.vue
│   ├── withdraw/                 # 提现页
│   │   └── withdraw.vue
│   ├── income/                   # 收支明细页
│   │   └── income.vue
│   ├── login/                    # 登录页
│   │   └── login.vue
│   └── settings/                 # 设置页
│       └── settings.vue
│
├── static/                       # 静态资源
│   └── images/                   # 图片资源
│
├── App.vue                       # 根组件
├── main.js                       # 入口文件
├── pages.json                    # 页面配置
└── package.json                  # 项目配置
```

---

## 2. API 调用封装

### 2.1 请求封装 (utils/request.js)

```javascript
// 核心特性
- Token 自动携带
- 请求/响应拦截
- 401 自动跳转登录
- 网络状态检测
- 请求队列防重复
- Loading 自动显示
```

### 2.2 使用示例

```javascript
import { get, post } from '@/utils/request'

// GET 请求
const res = await get('/api/user/info')

// POST 请求
const res = await post('/api/withdraw/apply', {
  coin_amount: 10000,
  withdraw_type: 'wechat'
})

// 不显示 Loading
const res = await get('/api/coin/account', {}, { showLoading: false })
```

---

## 3. 状态管理设计 (Pinia)

### 3.1 用户状态 (store/user.js)

```javascript
// 状态
token           // 登录令牌
userInfo        // 用户信息
isLoggedIn      // 是否已登录

// 方法
wxLogin()       // 微信登录
phoneLogin()    // 手机号登录
fetchUserInfo() // 获取用户信息
logout()        // 退出登录
```

### 3.2 金币状态 (store/coin.js)

```javascript
// 状态
balance         // 可用余额
frozen          // 冻结金额
todayEarn       // 今日收益
totalEarn       // 累计收益

// 计算属性
cashAmount      // 人民币金额
todayCash       // 今日人民币

// 核心方法
fetchAccountInfo()      // 获取账户信息
updateBalance()        // 本地更新余额
startAutoRefresh()     // 开启自动刷新 ⭐
stopAutoRefresh()      // 停止自动刷新

// 辅助方法
coinToCash()    // 金币转人民币
cashToCoin()    // 人民币转金币
```

### 3.3 金币实时刷新逻辑

```javascript
// 在 coin.js 中实现

// 刷新定时器
let refreshTimer = null
let lastRefreshTime = 0

/**
 * 开启自动刷新（每30秒刷新一次）
 */
const startAutoRefresh = (interval = 30000) => {
  if (refreshTimer) {
    stopAutoRefresh()
  }
  
  // 立即刷新一次
  fetchAccountInfo(true)
  
  // 定时刷新
  refreshTimer = setInterval(() => {
    fetchAccountInfo(true)
  }, interval)
}

/**
 * 停止自动刷新
 */
const stopAutoRefresh = () => {
  if (refreshTimer) {
    clearInterval(refreshTimer)
    refreshTimer = null
  }
}

// 在页面中使用
onMounted(() => {
  coinStore.startAutoRefresh(30000)  // 每30秒刷新
})

onUnmounted(() => {
  coinStore.stopAutoRefresh()  // 页面销毁时停止
})
```

---

## 4. 视频播放组件设计

### 4.1 组件属性

| 属性 | 类型 | 默认值 | 说明 |
|------|------|--------|------|
| videoId | String/Number | - | 视频ID |
| videoSrc | String | '' | 视频地址 |
| posterSrc | String | '' | 封面地址 |
| videoInfo | Object | {} | 视频信息 |
| autoplay | Boolean | false | 自动播放 |
| loop | Boolean | true | 循环播放 |
| muted | Boolean | true | 静音 |
| showReward | Boolean | true | 显示奖励 |
| watchRewardThreshold | Number | 95 | 奖励阈值(%) |

### 4.2 组件事件

| 事件 | 参数 | 说明 |
|------|------|------|
| play | - | 开始播放 |
| pause | - | 暂停播放 |
| ended | - | 播放完成 |
| timeUpdate | { currentTime, duration, progress } | 时间更新 |
| reward | videoId | 领取奖励 |
| like | videoId | 点赞 |
| comment | videoId | 评论 |
| share | videoId | 分享 |

### 4.3 暴露方法

```javascript
// 父组件调用
const videoRef = ref(null)

videoRef.value.play()      // 播放
videoRef.value.pause()     // 暂停
videoRef.value.togglePlay() // 切换

// 响应式状态
videoRef.value.currentTime    // 当前时间
videoRef.value.duration      // 总时长
videoRef.value.progressPercent // 进度百分比
videoRef.value.rewardStatus  // 奖励状态
```

### 4.4 使用示例

```vue
<template>
  <video-player
    ref="videoRef"
    :video-id="video.id"
    :video-src="video.video_url"
    :video-info="video"
    :autoplay="true"
    :show-reward="true"
    :watch-reward-threshold="95"
    @reward="handleReward"
    @like="handleLike"
  />
</template>

<script setup>
import { ref } from 'vue'
import videoPlayer from '@/components/video-player.vue'

const videoRef = ref(null)

const handleReward = (videoId) => {
  // 领取奖励
}

const handleLike = (videoId) => {
  // 点赞
}
</script>
```

---

## 5. 页面配置 (pages.json)

### 5.1 TabBar 配置

```json
{
  "tabBar": {
    "color": "#999999",
    "selectedColor": "#FF6B00",
    "list": [
      { "pagePath": "pages/index/index", "text": "首页" },
      { "pagePath": "pages/redpacket/redpacket", "text": "任务" },
      { "pagePath": "pages/invite/invite", "text": "邀请" },
      { "pagePath": "pages/mine/mine", "text": "我的" }
    ]
  }
}
```

### 5.2 页面列表

| 页面路径 | 标题 | 导航样式 |
|----------|------|----------|
| pages/index/index | 首页 | custom |
| pages/video/video | 视频 | custom |
| pages/redpacket/redpacket | 红包任务 | default |
| pages/invite/invite | 邀请赚钱 | default |
| pages/mine/mine | 个人中心 | default |
| pages/withdraw/withdraw | 提现 | default |
| pages/income/income | 收支明细 | default |
| pages/login/login | 登录 | custom |
| pages/settings/settings | 设置 | default |

---

## 6. 核心页面功能

### 6.1 首页（短视频浏览）

```
┌──────────────────────────────────────┐
│  [推荐] [关注]              💰 12500  │
├──────────────────────────────────────┤
│                                      │
│         全屏视频播放区域              │
│                                      │
│    ┌─────────────────────────┐       │
│    │ 用户头像 + 昵称         │       │
│    │ 视频描述...            │       │
│    │ #标签                  │       │
│    └─────────────────────────┘       │
│                          ┌────────┐ │
│                          │ ❤ 点赞 │ │
│                          │ 💬 评论 │ │
│                          │ ↗ 分享 │ │
│                          │ 💰+100 │ │
│                          └────────┘ │
├──────────────────────────────────────┤
│   [红包]  [+]发布  [我的]            │
└──────────────────────────────────────┘
```

**核心功能**：
- Swiper 垂直滑动切换视频
- 视频预加载（前后各1个）
- 滑动到底自动加载更多
- 金币实时显示

### 6.2 提现页

```
┌──────────────────────────────────────┐
│  可提现金币                          │
│  🪙 125,000                          │
│  ≈ ¥12.50 (冻结: 5000)              │
├──────────────────────────────────────┤
│  提现金币                     全部提现│
│  🪙 [请输入提现金币数量]             │
│                                      │
│  提现金额: ¥12.50                    │
│  手续费: -¥0.00                      │
│  实际到账: ¥12.50                    │
│                                      │
│  [1万] [2万] [5万] [10万] [20万] [全部]│
├──────────────────────────────────────┤
│  提现方式                            │
│  ◉ 微信零钱  ○ 支付宝  ○ 银行卡     │
├──────────────────────────────────────┤
│  提现须知                            │
│  • 最低提现: 10000金币               │
│  • 每日最多提现: 3次                 │
├──────────────────────────────────────┤
│         [ 确认提现 ]                 │
└──────────────────────────────────────┘
```

### 6.3 邀请统计页

```
┌──────────────────────────────────────┐
│  我的邀请码                          │
│  ┌────────────────────────────────┐ │
│  │       ABCD123456               │ │
│  └────────────────────────────────┘ │
│  [复制邀请码] [分享好友] [生成海报]  │
├──────────────────────────────────────┤
│  邀请统计                            │
│  累计邀请: 156  一级: 120  二级: 36  │
├──────────────────────────────────────┤
│  佣金收益                            │
│  🪙 128,500 金币                     │
│  ├ 提现分佣: +80,000                 │
│  ├ 视频分佣: +30,500                 │
│  ├ 红包分佣: +12,000                 │
│  └ 游戏分佣: +6,000                  │
│  今日收益: +5,200                     │
├──────────────────────────────────────┤
│  我邀请的好友 [一级] [二级]          │
│  ┌────────────────────────────────┐ │
│  │ [头像] 用户A   贡献: +1500     │ │
│  │ [头像] 用户B   贡献: +850      │ │
│  └────────────────────────────────┘ │
└──────────────────────────────────────┘
```

---

## 7. 金币实时刷新策略

### 7.1 刷新时机

| 场景 | 刷新策略 |
|------|----------|
| App 启动 | 立即刷新 |
| 进入首页 | 开启定时刷新（30秒） |
| 观看视频获得金币 | 本地立即更新 + 3秒后刷新 |
| 提现成功 | 本地立即更新 |
| App 切到后台 | 停止定时刷新 |
| App 回到前台 | 立即刷新 + 开启定时 |

### 7.2 代码实现

```javascript
// App.vue
import { useCoinStore } from '@/store/coin'

onLaunch(() => {
  const coinStore = useCoinStore()
  coinStore.startAutoRefresh(30000)
})

onHide(() => {
  const coinStore = useCoinStore()
  coinStore.stopAutoRefresh()
})

onShow(() => {
  const coinStore = useCoinStore()
  coinStore.fetchAccountInfo(true)
  coinStore.startAutoRefresh(30000)
})
```

### 7.3 本地更新 + 延迟刷新

```javascript
// 获得金币后立即显示
coinStore.updateBalance(rewardCoin)

// 3秒后从服务器刷新验证
setTimeout(() => {
  coinStore.fetchAccountInfo(true)
}, 3000)
```

---

## 8. 文件清单

```
web/
├── api/
│   ├── index.js
│   ├── user.js
│   ├── coin.js
│   ├── video.js
│   ├── redpacket.js
│   ├── withdraw.js
│   └── invite.js
├── store/
│   ├── index.js
│   ├── user.js
│   ├── coin.js
│   └── video.js
├── utils/
│   └── request.js
├── components/
│   ├── video-player.vue
│   └── red-packet-list.vue
├── pages/
│   ├── index/index.vue
│   ├── video/video.vue
│   ├── redpacket/redpacket.vue
│   ├── invite/invite.vue
│   ├── mine/mine.vue
│   ├── withdraw/withdraw.vue
│   ├── income/income.vue
│   ├── login/login.vue
│   └── settings/settings.vue
├── static/images/
├── App.vue
├── main.js
├── pages.json
└── package.json
```

---

## 9. 注意事项

1. **视频播放**：使用 Swiper + video 组件实现类似抖音体验
2. **金币刷新**：平衡实时性和性能，30秒刷新一次
3. **登录状态**：401 自动跳转登录，支持微信授权
4. **网络请求**：统一封装，支持 Token、Loading、错误处理
5. **状态管理**：使用 Pinia，轻量且支持 Composition API

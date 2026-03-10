<template>
  <view class="red-packet-list">
    <!-- 分类导航 -->
    <scroll-view class="category-nav" scroll-x>
      <view 
        v-for="item in categories" 
        :key="item.id"
        :class="['category-item', { active: currentCategory === item.id }]"
        @click="selectCategory(item.id)"
      >
        <image v-if="item.icon" :src="item.icon" class="category-icon" mode="aspectFit" />
        <text>{{ item.name }}</text>
      </view>
    </scroll-view>
    
    <!-- 任务列表 -->
    <view class="task-list">
      <view 
        v-for="task in tasks" 
        :key="task.id" 
        class="task-card"
        @click="handleTaskClick(task)"
      >
        <!-- 任务图标 -->
        <image class="task-icon" :src="task.icon || '/static/images/default-task.png'" mode="aspectFit" />
        
        <!-- 任务信息 -->
        <view class="task-info">
          <view class="task-header">
            <text class="task-name">{{ task.name }}</text>
            <view v-if="task.is_hot" class="hot-tag">热门</view>
          </view>
          <text class="task-desc">{{ task.description }}</text>
          <view class="task-meta">
            <text class="reward">奖励: {{ task.single_amount }}金币</text>
            <text class="remain">剩余: {{ task.remain_count }}份</text>
          </view>
        </view>
        
        <!-- 操作按钮 -->
        <view class="task-action">
          <view v-if="task.can_receive" class="btn-receive">领取</view>
          <view v-else class="btn-disabled">已领取</view>
        </view>
      </view>
      
      <!-- 加载更多 -->
      <view v-if="hasMore" class="load-more" @click="loadMore">
        <text>加载更多</text>
      </view>
      
      <!-- 空状态 -->
      <view v-if="tasks.length === 0 && !loading" class="empty-state">
        <image src="/static/images/empty.png" mode="aspectFit" />
        <text>暂无任务</text>
      </view>
    </view>
    
    <!-- 任务详情弹窗 -->
    <uni-popup ref="detailPopup" type="bottom" :safe-area="true">
      <view class="task-detail-popup">
        <view class="popup-header">
          <text class="popup-title">任务详情</text>
          <view class="popup-close" @click="closeDetailPopup">×</view>
        </view>
        
        <view v-if="currentTask" class="popup-content">
          <!-- 任务信息 -->
          <view class="detail-section">
            <view class="detail-row">
              <text class="label">任务名称</text>
              <text class="value">{{ currentTask.name }}</text>
            </view>
            <view class="detail-row">
              <text class="label">任务要求</text>
              <text class="value">{{ currentTask.progress_text }}</text>
            </view>
            <view class="detail-row">
              <text class="label">奖励金额</text>
              <text class="value reward">{{ currentTask.single_amount }} 金币</text>
            </view>
            <view class="detail-row">
              <text class="label">剩余数量</text>
              <text class="value">{{ currentTask.remain_count }} 份</text>
            </view>
          </view>
          
          <!-- 任务说明 -->
          <view class="detail-section">
            <text class="section-title">任务说明</text>
            <text class="section-content">{{ currentTask.description }}</text>
          </view>
          
          <!-- 操作按钮 -->
          <view class="popup-actions">
            <view 
              :class="['action-btn', { disabled: !currentTask.can_receive }]"
              @click="handleReceive(currentTask)"
            >
              {{ currentTask.can_receive ? '立即领取' : '已领取' }}
            </view>
          </view>
        </view>
      </view>
    </uni-popup>
  </view>
</template>

<script>
import { getTaskList, getCategories, receiveTask } from '@/api/redpacket'

export default {
  name: 'RedPacketList',
  data() {
    return {
      categories: [{ id: 0, name: '全部' }],
      currentCategory: 0,
      tasks: [],
      page: 1,
      limit: 20,
      hasMore: true,
      loading: false,
      currentTask: null
    }
  },
  
  mounted() {
    this.loadCategories()
    this.loadTasks()
  },
  
  methods: {
    /**
     * 加载分类
     */
    async loadCategories() {
      try {
        const res = await getCategories()
        if (res.code === 1) {
          this.categories = [{ id: 0, name: '全部' }, ...(res.data.list || [])]
        }
      } catch (e) {
        console.error('加载分类失败', e)
      }
    },
    
    /**
     * 加载任务列表
     */
    async loadTasks(refresh = false) {
      if (this.loading) return
      this.loading = true
      
      if (refresh) {
        this.page = 1
        this.tasks = []
        this.hasMore = true
      }
      
      try {
        const res = await getTaskList({
          page: this.page,
          limit: this.limit,
          category_id: this.currentCategory
        })
        
        if (res.code === 1) {
          const list = res.data.list || []
          this.tasks = refresh ? list : [...this.tasks, ...list]
          this.hasMore = list.length >= this.limit
          this.page++
        }
      } catch (e) {
        console.error('加载任务失败', e)
      } finally {
        this.loading = false
      }
    },
    
    /**
     * 选择分类
     */
    selectCategory(categoryId) {
      this.currentCategory = categoryId
      this.loadTasks(true)
    },
    
    /**
     * 加载更多
     */
    loadMore() {
      if (this.hasMore && !this.loading) {
        this.loadTasks()
      }
    },
    
    /**
     * 点击任务
     */
    handleTaskClick(task) {
      this.currentTask = task
      this.$refs.detailPopup.open()
    },
    
    /**
     * 关闭详情弹窗
     */
    closeDetailPopup() {
      this.$refs.detailPopup.close()
    },
    
    /**
     * 领取任务
     */
    async handleReceive(task) {
      if (!task.can_receive) return
      
      try {
        uni.showLoading({ title: '领取中...' })
        
        const deviceInfo = this.getDeviceInfo()
        const res = await receiveTask(task.id, deviceInfo)
        
        uni.hideLoading()
        
        if (res.code === 1) {
          this.closeDetailPopup()
          
          // 更新任务状态
          task.can_receive = false
          task.user_receive_count++
          
          // 跳转到任务执行页面
          this.navigateToTask(res.data)
        } else {
          uni.showToast({ title: res.msg || '领取失败', icon: 'none' })
        }
      } catch (e) {
        uni.hideLoading()
        uni.showToast({ title: '领取失败', icon: 'none' })
      }
    },
    
    /**
     * 跳转到任务执行页面
     */
    navigateToTask(data) {
      // 根据任务类型跳转
      const task = this.currentTask
      
      switch (task.task_type) {
        case 'download_app':
          // 跳转到下载页面
          uni.navigateTo({
            url: `/pages/task/download?order_no=${data.order_no}&url=${encodeURIComponent(data.task_url)}&params=${encodeURIComponent(JSON.stringify(data.task_params))}`
          })
          break
          
        case 'mini_program':
          // 跳转小程序
          this.openMiniProgram(data.task_params)
          break
          
        case 'play_game':
          // 跳转游戏页面
          uni.navigateTo({
            url: `/pages/task/game?order_no=${data.order_no}&duration=${task.required_duration}`
          })
          break
          
        case 'watch_video':
          // 跳转视频页面
          uni.navigateTo({
            url: `/pages/task/video?order_no=${data.order_no}&duration=${task.required_duration}`
          })
          break
          
        default:
          uni.showToast({ title: '请完成任务后返回提交', icon: 'none' })
      }
    },
    
    /**
     * 打开小程序
     */
    openMiniProgram(params) {
      if (!params.mini_app_id) {
        uni.showToast({ title: '小程序参数错误', icon: 'none' })
        return
      }
      
      uni.navigateToMiniProgram({
        appId: params.mini_app_id,
        path: params.path || '',
        success: () => {
          console.log('打开小程序成功')
        },
        fail: (err) => {
          console.error('打开小程序失败', err)
          uni.showToast({ title: '打开小程序失败', icon: 'none' })
        }
      })
    },
    
    /**
     * 获取设备信息
     */
    getDeviceInfo() {
      return {
        device_name: uni.getSystemInfoSync().model,
        brand: uni.getSystemInfoSync().brand,
        model: uni.getSystemInfoSync().model,
        os_version: uni.getSystemInfoSync().system,
        network_type: uni.getNetworkType()
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.red-packet-list {
  background: #f5f5f5;
  min-height: 100vh;
}

.category-nav {
  display: flex;
  white-space: nowrap;
  padding: 20rpx;
  background: #fff;
  
  .category-item {
    display: inline-flex;
    align-items: center;
    padding: 12rpx 24rpx;
    margin-right: 20rpx;
    border-radius: 30rpx;
    background: #f0f0f0;
    font-size: 26rpx;
    
    &.active {
      background: linear-gradient(135deg, #FF6B6B, #FF8E53);
      color: #fff;
    }
    
    .category-icon {
      width: 32rpx;
      height: 32rpx;
      margin-right: 8rpx;
    }
  }
}

.task-list {
  padding: 20rpx;
}

.task-card {
  display: flex;
  align-items: center;
  padding: 24rpx;
  margin-bottom: 20rpx;
  background: #fff;
  border-radius: 16rpx;
  box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.05);
  
  .task-icon {
    width: 100rpx;
    height: 100rpx;
    border-radius: 12rpx;
    margin-right: 20rpx;
  }
  
  .task-info {
    flex: 1;
    
    .task-header {
      display: flex;
      align-items: center;
      margin-bottom: 8rpx;
      
      .task-name {
        font-size: 30rpx;
        font-weight: bold;
        color: #333;
      }
      
      .hot-tag {
        margin-left: 12rpx;
        padding: 4rpx 12rpx;
        background: linear-gradient(135deg, #FF6B6B, #FF8E53);
        color: #fff;
        font-size: 20rpx;
        border-radius: 20rpx;
      }
    }
    
    .task-desc {
      font-size: 24rpx;
      color: #999;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin-bottom: 12rpx;
    }
    
    .task-meta {
      display: flex;
      font-size: 24rpx;
      
      .reward {
        color: #FF6B6B;
        margin-right: 24rpx;
      }
      
      .remain {
        color: #999;
      }
    }
  }
  
  .task-action {
    .btn-receive {
      padding: 12rpx 32rpx;
      background: linear-gradient(135deg, #FF6B6B, #FF8E53);
      color: #fff;
      font-size: 26rpx;
      border-radius: 30rpx;
    }
    
    .btn-disabled {
      padding: 12rpx 32rpx;
      background: #ccc;
      color: #fff;
      font-size: 26rpx;
      border-radius: 30rpx;
    }
  }
}

.load-more {
  text-align: center;
  padding: 30rpx;
  color: #666;
  font-size: 26rpx;
}

.empty-state {
  text-align: center;
  padding: 100rpx 0;
  
  image {
    width: 200rpx;
    height: 200rpx;
    margin-bottom: 20rpx;
  }
  
  text {
    color: #999;
    font-size: 28rpx;
  }
}

// 详情弹窗
.task-detail-popup {
  background: #fff;
  border-radius: 24rpx 24rpx 0 0;
  max-height: 80vh;
  
  .popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 30rpx;
    border-bottom: 1rpx solid #eee;
    
    .popup-title {
      font-size: 32rpx;
      font-weight: bold;
    }
    
    .popup-close {
      font-size: 48rpx;
      color: #999;
      line-height: 1;
    }
  }
  
  .popup-content {
    padding: 30rpx;
    
    .detail-section {
      margin-bottom: 30rpx;
      
      .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 16rpx 0;
        border-bottom: 1rpx solid #f5f5f5;
        
        .label {
          color: #666;
          font-size: 28rpx;
        }
        
        .value {
          color: #333;
          font-size: 28rpx;
          
          &.reward {
            color: #FF6B6B;
            font-weight: bold;
          }
        }
      }
      
      .section-title {
        font-size: 28rpx;
        font-weight: bold;
        color: #333;
        margin-bottom: 16rpx;
        display: block;
      }
      
      .section-content {
        font-size: 26rpx;
        color: #666;
        line-height: 1.6;
      }
    }
    
    .popup-actions {
      padding-top: 30rpx;
      
      .action-btn {
        background: linear-gradient(135deg, #FF6B6B, #FF8E53);
        color: #fff;
        text-align: center;
        padding: 24rpx;
        border-radius: 40rpx;
        font-size: 30rpx;
        font-weight: bold;
        
        &.disabled {
          background: #ccc;
        }
      }
    }
  }
}
</style>

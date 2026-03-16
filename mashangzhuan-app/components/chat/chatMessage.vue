<template>
  <view :class="['message', isMe ? 'me' : 'other']">
    <image 
      v-if="!isMe" 
      class="avatar" 
      :src="message.user.avatar" 
      mode="aspectFit"
    ></image>
	<image
	  v-if="isMe" 
	  class="avatar" 
	  :src="vuex_user.avatar || '/static/image/avatar.png'" 
	  mode="aspectFit"
	></image>
    
    <view class="content-wrapper">
      <!-- 用户昵称 -->
      <text v-if="!isMe && message.user.nickname" class="nickname">{{ message.user.nickname }}</text>
      
      <view class="content">
        <!-- 文本消息 -->
        <view v-if="message.type === 'text'" class="text">
          {{ message.content }}
        </view>
        
        <!-- 图片消息 -->
        <image 
          v-else-if="message.type === 'img'" 
          :src="message.url" 
          mode="widthFix"
          class="image"
          @click="previewImage(message.url)"
        ></image>
        
        <!-- 语音消息 -->
        <view 
          v-else-if="message.type === 'voice'" 
          class="voice" 
          @click="$emit('play-voice')"
        >
          <text>{{ message.duration }}"</text>
          <image 
            :src="isMe ? '/static/voice-right.png' : '/static/voice-left.png'" 
            mode="aspectFit"
          ></image>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  props: {
    message: {
      type: Object,
      required: true
    },
    isMe: {
      type: Boolean,
      default: false
    }
  },
  
  computed: {
    vuex_user() {
      return this.$store ? this.$store.state.vuex_user : {};
    }
  },
  
  methods: {
    formatTime(timestamp) {
      const date = new Date(timestamp)
      return `${date.getHours()}:${date.getMinutes().toString().padStart(2, '0')}`
    },
    
    previewImage(url) {
      uni.previewImage({
        urls: [url],
        current: 0
      })
    }
  }
}
</script>

<style lang="scss" scoped>
.message {
  display: flex;
  margin-bottom: 30rpx;
  padding: 0 20rpx;
}

.avatar {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  margin: 0 20rpx;
  flex-shrink: 0;
}

.content-wrapper {
  display: flex;
  flex-direction: column;
  max-width: 60%;
}

.nickname {
  font-size: 24rpx;
  color: #999;
  margin-bottom: 8rpx;
  margin-left: 10rpx;
}

.content {
  display: flex;
  flex-direction: column;
}

.me {
  flex-direction: row-reverse;
}

.me .content-wrapper {
  align-items: flex-end;
}

.text {
  background-color: #fff;
  color: #333;
  padding: 20rpx 24rpx;
  border-radius: 16rpx;
  word-break: break-word;
  font-size: 30rpx;
  line-height: 1.5;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}

.me .text {
  background: linear-gradient(135deg, #95ec69 0%, #7bd95a 100%);
  color: #333;
}

.image {
  max-width: 400rpx;
  max-height: 400rpx;
  border-radius: 16rpx;
}

.voice {
  display: flex;
  align-items: center;
  background-color: #fff;
  padding: 20rpx 30rpx;
  border-radius: 16rpx;
  min-width: 120rpx;
  box-shadow: 0 2rpx 8rpx rgba(0, 0, 0, 0.05);
}

.voice image {
  width: 40rpx;
  height: 40rpx;
  margin-left: 10rpx;
}

.me .voice {
  background: linear-gradient(135deg, #95ec69 0%, #7bd95a 100%);
}
</style>

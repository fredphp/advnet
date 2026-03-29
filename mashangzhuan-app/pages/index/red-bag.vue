<template>
        <view class="page-content">
                <view class="chat-container">
                        <!-- 顶部导航栏 -->
                        <view class="custom-navbar">
                                <view class="navbar-content">
                                        <view class="back-btn" @click="goBack">
                                                <u-icon name="arrow-left" color="#333" size="40"></u-icon>
                                        </view>
                                        <view class="group-info">
                                                <text class="group-name">红包群94</text>
                                                <text class="group-count">(2057)</text>
                                        </view>
                                        <view class="withdraw-btn" @click="goWithdraw">
                                                <image class="redbag-icon" src="/static/image/redbag.png" mode="aspectFit"></image>
                                                <text class="withdraw-text">提现</text>
                                        </view>
                                </view>
                        </view>

                        <!-- 广告区域 -->
                        <view class="ad-section">
                                <ad-banner></ad-banner>
                        </view>

                        <!-- 聊天消息列表 -->
                        <scroll-view class="message-list" scroll-y :scroll-into-view="scrollIntoViewId"
                                scroll-with-animation :style="{height: scrollHeight + 'px'}" @scrolltoupper="getHistoryMsg"
                                :scroll-top="scrollTop" :enable-back-to-top="true" :upper-threshold="50">
                                <view class="loading-more" v-if="loading">
                                        <text>加载中...</text>
                                </view>
                                <view class="connection-status" v-if="showConnected" :class="{ connected: isConnected }">
                                        <text>{{ isConnected ? '已连接' : '连接中...' }}</text>
                                </view>
                                <view v-for="(msg, index) in messages" :key="msg.id" :id="'msg-' + msg.id">
                                        <!-- 系统消息 -->
                                        <view class="system-message" v-if="msg.type === 'system'">
                                                {{ msg.content }}
                                        </view>

                                        <!-- 时间分隔 -->
                                        <view class="time-divider" v-if="showTimeDivider(index)">
                                                {{ formatTime(msg.time) }}
                                        </view>

                                        <!-- 普通消息 -->
                                        <chat-message v-if="msg.type === 'text' || msg.type === 'img'" :message="msg"
                                                :is-me="msg.sender === 'me'" @play-voice="playVoice(msg)" />

                                        <!-- 红包消息 -->
                                        <redbag-message v-if="msg.type === 'redbag'" :message="msg" :is-me="msg.sender === 'me'"
                                                @open-redbag="openRedbag(msg)" />
                                </view>
                                <view id="bottom-anchor"></view>
                        </scroll-view>
                </view>

                <!-- 底部导航 -->
                <fa-tabbar></fa-tabbar>
        </view>
</template>

<script>
        import ChatMessage from '@/components/chat/chatMessage.vue'
        import RedbagMessage from '@/components/chat/redbagMessage.vue'
        import AdBanner from '@/components/ad/adBanner.vue'
        import socketService from '@/common/socket.js'

        export default {
                components: {
                        ChatMessage,
                        RedbagMessage,
                        AdBanner
                },
                onLoad(opt) {
                        
                        this.groupId = opt.group_id || 'default_group';
                        
                        // const userInfo = uni.getStorageSync('user_info') || {}
                        const userInfo = uni.getStorageSync('user_info') || {}
                        this.user_info = userInfo
                        
                        // this.initScrollHeight();
                        // 启动轮询自动发送消息
                        // this.startAutoSendMessage();
                        
                        // 初始化 WebSocket
                        this.initSocket()
                },
                onUnload() {
                        // 断开 WebSocket 连接
                        socketService.disconnect()
                },
                data() {
                        return {
                                inputText: '',
                                groupId: '',
                                groupName: '红包群94',
                                onlineCount: 0,
                                user_info: {},
                                scrollTop: 0,
                                scrollViewRef: '',
                                scrollIntoViewId: "",
                                keyboardHeight: 0,
                                isKeyboardVisible: false,
                                loading: false,
                                showConnected:true,
                                page: 1,
                                pageSize: 20,
                                scrollHeight: 0,
                                hasMore: true,
                                isConnected: false,
                                messages: [],
                                // // 轮询定时器
                                // autoSendTimer: null,
                                // // 自动发送消息的索引
                                // autoMessageIndex: 0,
                                // // 预设的自动发送消息列表
                                // autoMessages: [
                                //      {
                                //              type: 'text',
                                //              content: '恭喜发财，大吉大利！',
                                //              sender: 'other',
                                //              user: {
                                //                      nickname: '用户9527',
                                //                      avatar: '/static/image/avatar.png'
                                //              }
                                //      }
                                // ],
                                // messages: [
                                //      {
                                //              "id": "msg_005",
                                //              "type": "text",
                                //              "content": "抢红包啦！",
                                //              "time": "2026-02-04 09:16:30",
                                //              "sender": "other",
                                //              "user": {
                                //                      nickname: '抢红包高手',
                                //                      avatar: '/static/image/avatar.png'
                                //              }
                                //      }
                                // ]
                        }
                },

                mounted() {
                        uni.onKeyboardHeightChange(res => {
                                this.keyboardHeight = res.height;
                                this.isKeyboardVisible = res.height > 0;
                                if (!this.isKeyboardVisible) {
                                        this.scrollToBottom();
                                } else {
                                        setTimeout(() => {
                                                this.scrollToBottom();
                                        }, 100);
                                }
                        });
                },

                methods: {
                        // 初始化 WebSocket
                        initSocket() {
                                // 连接 WebSocket
                                // ⚠️ 注意：如果是在 H5 环境，直接使用相对路径
                                // 如果是小程序或 App，需要使用完整的服务器地址
                                socketService.connect({
                                        userId: this.user_info.id || this.user_info.user_id,
                                        token: this.user_info.token || '',
                                        groupId: this.groupId
                                })
                          
                                // 监听连接成功
                                socketService.onConnected((data) => {
                                        this.isConnected = true
                                        this.onlineCount = data.onlineCount
                                        setTimeout(() => {
                                                this.showConnected = false;
                                        },1000)
                                })
                          
                                // 监听认证失败
                                socketService.onAuthFailed((data) => {
                                        this.isConnected = false
                                        uni.showToast({
                                                title: '认证失败，请重新登录',
                                                icon: 'none'
                                        })
                                })
                                // 监听在线人数
                                socketService.onOnlineCount((count) => {
                                        this.onlineCount = count
                                })
                                // 监听任务通知（红包等）
                                socketService.onTask((task) => {
                                        console.log("收到任务推送:", JSON.stringify(task));
                                        // 兼容服务器推送的 snake_case 和 camelCase 两种字段格式
                                        const taskId = task.taskId || task.task_id || 0;
                                        const taskName = task.taskName || task.task_name || '';
                                        const taskType = task.taskType || task.type || '';
                                        const description = task.description || '';
                                        const displayTitle = task.display_title || task.displayTitle || taskName;
                                        const reward = task.reward || 0;
                                        const timestamp = task.time || task.timestamp || Date.now() / 1000;
                                        const senderName = task.sender_name || task.senderName || '系统';
                                        const senderAvatar = task.sender_avatar || task.senderAvatar || '';
                                        const showRedPacket = task.show_red_packet !== false;

                                        this.messages.push({
                                                id: 'task_' + Date.now(),
                                                type: showRedPacket ? 'redbag' : 'text',
                                                content: displayTitle || taskName,
                                                amount: reward,
                                                status: 'unopened',
                                                time: timestamp * 1000,
                                                sender: 'other',
                                                user: {
                                                        nickname: senderName,
                                                        avatar: senderAvatar || '/static/image/avatar.png'
                                                },
                                                taskData: {
                                                        taskId,
                                                        taskName,
                                                        taskType,
                                                        description,
                                                        displayTitle,
                                                        resource: task.resource || null,
                                                        background_image: task.background_image || '',
                                                        jump_url: task.jump_url || ''
                                                }
                                        })
                                        this.scrollToBottom()
                                })
                                
                                // 监听系统消息
                                socketService.onSystemMessage((msg) => {
                                        this.messages.push({
                                                id: 'sys_' + Date.now(),
                                                type: 'system',
                                                content: msg.content,
                                                time: Date.now(),
                                                sender: 'system'
                                        })
                                        this.scrollToBottom()
                                })
                        },
                        // 返回上一页
                        goBack() {
                                uni.navigateBack();
                        },
                        // 去提现页面
                        goWithdraw() {
                                uni.navigateTo({
                                        url: '/pages/index/withdraw'
                                });
                        },
                        // 初始化scroll-view高度
                        initScrollHeight() {
                                const systemInfo = uni.getSystemInfoSync();
                                // 减去导航栏高度(88rpx) + 广告区域高度(200rpx) + tabbar高度(100rpx) + 状态栏高度
                                const navHeight = 88;
                                const adHeight = 200;
                                const tabbarHeight = 100;
                                const statusBarHeight = systemInfo.statusBarHeight || 0;
                                this.scrollHeight = systemInfo.windowHeight - navHeight - adHeight - tabbarHeight - statusBarHeight;
                        },
                        // // 启动自动发送消息轮询
                        // startAutoSendMessage() {
                        //      // 每3-8秒随机发送一条消息
                        //      const randomInterval = Math.floor(Math.random() * 5000) + 3000;
                        //      this.autoSendTimer = setInterval(() => {
                        //              this.sendAutoMessage();
                        //      }, randomInterval);
                        // },
                        // 停止自动发送消息
                        // stopAutoSendMessage() {
                        //      if (this.autoSendTimer) {
                        //              clearInterval(this.autoSendTimer);
                        //              this.autoSendTimer = null;
                        //      }
                        // },
                        // 发送自动消息
                        // sendAutoMessage() {
                        //      if (this.autoMessageIndex >= this.autoMessages.length) {
                        //              this.autoMessageIndex = 0; // 循环发送
                        //      }

                        //      const templateMsg = this.autoMessages[this.autoMessageIndex];
                        //      const newMsg = {
                        //              id: 'auto_' + Date.now(),
                        //              type: templateMsg.type,
                        //              content: templateMsg.content,
                        //              time: new Date().getTime(),
                        //              sender: templateMsg.sender,
                        //              user: templateMsg.user
                        //      };

                        //      // 根据消息类型添加额外字段
                        //      if (templateMsg.type === 'redbag') {
                        //              newMsg.status = templateMsg.status;
                        //              newMsg.amount = templateMsg.amount;
                        //      } else if (templateMsg.type === 'img') {
                        //              newMsg.url = templateMsg.url;
                        //              newMsg.imgWidth = templateMsg.imgWidth;
                        //              newMsg.imgHeight = templateMsg.imgHeight;
                        //      }

                        //      this.messages.push(newMsg);
                        //      this.autoMessageIndex++;
                        //      this.scrollToBottom();
                        // },
                        getHistoryMsg() {
                                // 加载历史消息逻辑
                        },
                        // 播放语音
                        playVoice(msg) {
                                // 语音播放逻辑
                        },
                        // 打开红包
                        openRedbag(msg) {
                                if (msg.status === 'unopened') {
                                        uni.showModal({
                                                title: '恭喜发财',
                                                content: `获得红包 ¥${msg.amount}`,
                                                showCancel: false,
                                                success: () => {
                                                        msg.status = 'opened';
                                                }
                                        });
                                }
                        },
                        // 滚动到底部
                        scrollToBottom() {
                                this.$nextTick(() => {
                                        const lastMsg = this.messages[this.messages.length - 1];
                                        if (lastMsg) {
                                                this.scrollIntoViewId = `msg-${lastMsg.id}`;
                                        }
                                });
                        },
                        // 格式化时间
                        formatTime(timestamp) {
                                const date = new Date(timestamp);
                                const hours = date.getHours().toString().padStart(2, '0');
                                const minutes = date.getMinutes().toString().padStart(2, '0');
                                return `${hours}:${minutes}`;
                        },
                        // 判断是否显示时间分隔
                        showTimeDivider(index) {
                                if (index === 0) {
                                        return true;
                                }
                                const currentMsg = this.messages[index];
                                const prevMsg = this.messages[index - 1];
                                if (prevMsg.type === 'system') return false;
                                return currentMsg.time - prevMsg.time > 300000;
                        }
                }
        }
</script>

<style lang="scss" scoped>
        .connection-status {
                text-align: center;
                padding: 10rpx;
                background-color: #fff3cd;
                color: #856404;
                font-size: 24rpx;
          
                &.connected {
                        background-color: #d4edda;
                        color: #155724;
                }
        }
        .page-content {
                background-color: #f5f5f5;
                min-height: 100vh;
        }

        .chat-container {
                display: flex;
                flex-direction: column;
                height: 100vh;
                background-color: #f5f5f5;
        }

        // 自定义导航栏
        .custom-navbar {
                background-color: #fff;
                padding-top: var(--status-bar-height);
                border-bottom: 1rpx solid #eee;

                .navbar-content {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        height: 88rpx;
                        padding: 0 20rpx;
                }

                .back-btn {
                        width: 60rpx;
                        height: 60rpx;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                }

                .group-info {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex: 1;

                        .group-name {
                                font-size: 32rpx;
                                font-weight: bold;
                                color: #333;
                        }

                        .group-count {
                                font-size: 28rpx;
                                color: #999;
                                margin-left: 8rpx;
                        }
                }

                .withdraw-btn {
                        display: flex;
                        align-items: center;
                        background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
                        padding: 10rpx 20rpx;
                        border-radius: 30rpx;

                        .redbag-icon {
                                width: 32rpx;
                                height: 32rpx;
                                margin-right: 6rpx;
                        }

                        .withdraw-text {
                                font-size: 24rpx;
                                color: #fff;
                                font-weight: 500;
                        }
                }
        }

        // 广告区域
        .ad-section {
                background-color: #fff;
                padding: 20rpx;
                border-bottom: 1rpx solid #eee;
        }

        // 消息列表
        .message-list {
                flex: 1;
                padding: 20rpx;
                overflow-y: auto;
                background-color: #f5f5f5;
        }

        .loading-more {
                padding: 20rpx 0;
                text-align: center;
                color: #999;
                font-size: 24rpx;
        }

        .system-message {
                text-align: center;
                font-size: 24rpx;
                color: #999;
                margin: 20rpx 0;
                background-color: rgba(0, 0, 0, 0.05);
                padding: 10rpx 20rpx;
                border-radius: 8rpx;
                display: inline-block;
        }

        .time-divider {
                text-align: center;
                font-size: 24rpx;
                color: #999;
                margin: 20rpx 0;
                position: relative;

                &::before,
                &::after {
                        content: "";
                        position: absolute;
                        top: 50%;
                        width: 30%;
                        height: 1rpx;
                        background-color: #ddd;
                }

                &::before {
                        left: 0;
                }

                &::after {
                        right: 0;
                }
        }
</style>

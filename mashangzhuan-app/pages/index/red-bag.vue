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
                                                <text class="group-count">({{ onlineCount }})</text>
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

                <!-- 红包领取弹窗 -->
                <view class="redbag-modal" v-if="showRedbagModal" @click="closeRedbagModal">
                        <view class="redbag-modal-content" @click.stop>
                                <view class="redbag-header">
                                        <image class="redbag-avatar" :src="currentRedbag.user.avatar" mode="aspectFit"></image>
                                        <text class="redbag-sender">{{ currentRedbag.user.nickname }}的红包</text>
                                        <text class="redbag-wish">{{ currentRedbag.content }}</text>
                                </view>
                                <view class="redbag-body">
                                        <view class="open-btn" @click="handleOpenRedbag">
                                                <text>开</text>
                                        </view>
                                </view>
                                <view class="redbag-footer">
                                        <text>视频红包</text>
                                </view>
                                <view class="close-btn" @click="closeRedbagModal">
                                        <u-icon name="close" color="#fff" size="40"></u-icon>
                                </view>
                        </view>
                </view>

                <!-- 红包领取结果弹窗 -->
                <view class="result-modal" v-if="showResultModal" @click="closeResultModal">
                        <view class="result-modal-content" @click.stop>
                                <view class="result-header">
                                        <text class="result-title">恭喜发财</text>
                                </view>
                                <view class="result-body">
                                        <text class="result-amount">{{ resultAmount }}</text>
                                        <text class="result-unit">金币</text>
                                </view>
                                <view class="result-footer">
                                        <text class="result-tip">已存入您的账户</text>
                                        <view class="result-btn" @click="closeResultModal">
                                                <text>好的</text>
                                        </view>
                                </view>
                        </view>
                </view>

                <!-- 底部导航 -->
                <fa-tabbar></fa-tabbar>
        </view>
</template>

<script>
        import ChatMessage from '@/components/chat/chatMessage.vue'
        import RedbagMessage from '@/components/chat/redbagMessage.vue'
        import AdBanner from '@/components/ad/adBanner.vue'

        export default {
                components: {
                        ChatMessage,
                        RedbagMessage,
                        AdBanner
                },
                onLoad(opt) {
                        this.to_user_id = opt.user_id;
                        this.initScrollHeight();
                        // 连接 WebSocket
                        this.connectWebSocket();
                        // 初始化红包金额
                        this.initRedpacketAmount();
                },
                onUnload() {
                        // 清除定时器
                        this.stopAutoSendMessage();
                        // 关闭 WebSocket
                        this.closeWebSocket();
                        // 重置红包
                        this.resetRedpacket();
                },
                data() {
                        return {
                                inputText: '',
                                to_user_id: 0,
                                user_info: {},
                                scrollTop: 0,
                                scrollViewRef: '',
                                scrollIntoViewId: "",
                                keyboardHeight: 0,
                                isKeyboardVisible: false,
                                loading: false,
                                page: 1,
                                pageSize: 20,
                                scrollHeight: 0,
                                hasMore: true,
                                // WebSocket 相关
                                socketTask: null,
                                isSocketConnected: false,
                                onlineCount: 2057,
                                // 红包相关
                                showRedbagModal: false,
                                showResultModal: false,
                                currentRedbag: null,
                                resultAmount: 0,
                                currentAmount: 0, // 当前累计金额
                                // 轮询定时器
                                autoSendTimer: null,
                                // 心跳定时器
                                heartbeatTimer: null,
                                // 自动发送消息的索引
                                autoMessageIndex: 0,
                                // 预设的自动发送消息列表
                                autoMessages: [{
                                                type: 'text',
                                                content: '恭喜发财，大吉大利！',
                                                sender: 'other',
                                                user: {
                                                        nickname: '用户9527',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        },
                                        {
                                                type: 'redbag',
                                                content: '恭喜发财，大吉大利',
                                                sender: 'other',
                                                status: 'unopened',
                                                amount: 0.88,
                                                user: {
                                                        nickname: '红包达人',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        },
                                        {
                                                type: 'img',
                                                url: 'https://picsum.photos/400/300?random=1',
                                                imgWidth: 400,
                                                imgHeight: 300,
                                                sender: 'other',
                                                user: {
                                                        nickname: '图片分享者',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        },
                                        {
                                                type: 'redbag',
                                                content: '恭喜发财，大吉大利',
                                                sender: 'other',
                                                status: 'unopened',
                                                amount: 1.68,
                                                user: {
                                                        nickname: '幸运星',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        },
                                        {
                                                type: 'text',
                                                content: '抢红包啦！手快有手慢无！',
                                                sender: 'other',
                                                user: {
                                                        nickname: '抢红包高手',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        }
                                ],
                                messages: [{
                                                "id": "msg_001",
                                                "type": "text",
                                                "content": "早上好呀，今天天气看起来不错～",
                                                "time": Date.now() - 3600000,
                                                "sender": "other",
                                                "user": {
                                                        nickname: '小明',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        },
                                        {
                                                "id": "msg_002",
                                                "type": "redbag",
                                                "content": "恭喜发财，大吉大利",
                                                "time": Date.now() - 3500000,
                                                "sender": "other",
                                                "status": "unopened",
                                                "amount": 0.88,
                                                "user": {
                                                        nickname: '红包达人',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        },
                                        {
                                                "id": "msg_003",
                                                "type": "img",
                                                "url": "https://picsum.photos/400/300?random=3",
                                                "time": Date.now() - 3400000,
                                                "sender": "other",
                                                "imgWidth": 400,
                                                "imgHeight": 300,
                                                "user": {
                                                        nickname: '图片分享者',
                                                        avatar: '/static/image/avatar.png'
                                                }
                                        }
                                ]
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
                        // ========== WebSocket 相关 ==========
                        connectWebSocket() {
                                // WebSocket 服务器地址
                                const wsUrl = this.getWebSocketUrl();

                                this.socketTask = uni.connectSocket({
                                        url: wsUrl,
                                        success: () => {
                                                console.log('WebSocket 连接中...');
                                        },
                                        fail: (err) => {
                                                console.error('WebSocket 连接失败:', err);
                                                // 连接失败时启动轮询发送消息
                                                this.startAutoSendMessage();
                                        }
                                });

                                // 监听 WebSocket 连接打开
                                uni.onSocketOpen(() => {
                                        console.log('WebSocket 连接已打开');
                                        this.isSocketConnected = true;

                                        // 发送认证消息
                                        this.sendSocketMessage({
                                                type: 'auth',
                                                userId: this.vuex_user.id || 'guest_' + Date.now(),
                                                token: this.vuex_token || ''
                                        });

                                        // 启动心跳
                                        this.startHeartbeat();
                                });

                                // 监听 WebSocket 消息
                                uni.onSocketMessage((res) => {
                                        try {
                                                const data = JSON.parse(res.data);
                                                this.handleSocketMessage(data);
                                        } catch (e) {
                                                console.error('解析 WebSocket 消息失败:', e);
                                        }
                                });

                                // 监听 WebSocket 关闭
                                uni.onSocketClose(() => {
                                        console.log('WebSocket 连接已关闭');
                                        this.isSocketConnected = false;
                                        // 连接关闭时启动轮询
                                        this.startAutoSendMessage();
                                });

                                // 监听 WebSocket 错误
                                uni.onSocketError((err) => {
                                        console.error('WebSocket 错误:', err);
                                        this.isSocketConnected = false;
                                        // 错误时启动轮询
                                        this.startAutoSendMessage();
                                });
                        },
                        // 获取 WebSocket URL
                        getWebSocketUrl() {
                                // 根据环境获取 WebSocket 地址
                                // #ifdef H5
                                const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
                                const host = window.location.hostname;
                                const port = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1' ? ':81' : '';
                                // WebSocket 端口 3002，通过网关转发
                                return `${protocol}//${host}${port}/?XTransformPort=3002`;
                                // #endif
                                // #ifndef H5
                                return 'ws://mashangzhuan.dev.coco3g.net:3002';
                                // #endif
                        },
                        // 发送 WebSocket 消息
                        sendSocketMessage(data) {
                                if (this.socketTask && this.isSocketConnected) {
                                        uni.sendSocketMessage({
                                                data: JSON.stringify(data)
                                        });
                                }
                        },
                        // 处理 WebSocket 消息
                        handleSocketMessage(data) {
                                switch (data.type) {
                                        case 'connected':
                                                console.log('WebSocket 认证成功:', data);
                                                this.onlineCount = data.onlineCount || this.onlineCount;
                                                break;
                                        case 'online_count':
                                                this.onlineCount = data.count;
                                                break;
                                        case 'task_notification':
                                                // 收到红包任务推送
                                                this.handleTaskNotification(data);
                                                break;
                                        case 'system_message':
                                                // 系统消息
                                                this.addSystemMessage(data.content);
                                                break;
                                        case 'pong':
                                                // 心跳响应
                                                break;
                                        default:
                                                console.log('未知消息类型:', data);
                                }
                        },
                        // 处理红包任务推送
                        handleTaskNotification(data) {
                                // 生成红包消息
                                const redbagMsg = {
                                        id: 'task_' + data.taskId + '_' + Date.now(),
                                        type: 'redbag',
                                        content: data.content || '恭喜发财，大吉大利',
                                        time: Date.now(),
                                        sender: 'other',
                                        status: 'unopened',
                                        amount: data.reward || 0,
                                        taskId: data.taskId,
                                        user: {
                                                nickname: data.taskName || '系统红包',
                                                avatar: '/static/image/avatar.png'
                                        }
                                };
                                this.messages.push(redbagMsg);
                                this.scrollToBottom();
                        },
                        // 添加系统消息
                        addSystemMessage(content) {
                                this.messages.push({
                                        id: 'sys_' + Date.now(),
                                        type: 'system',
                                        content: content,
                                        time: Date.now()
                                });
                                this.scrollToBottom();
                        },
                        // 心跳
                        startHeartbeat() {
                                this.heartbeatTimer = setInterval(() => {
                                        this.sendSocketMessage({ type: 'ping' });
                                }, 30000);
                        },
                        stopHeartbeat() {
                                if (this.heartbeatTimer) {
                                        clearInterval(this.heartbeatTimer);
                                        this.heartbeatTimer = null;
                                }
                        },
                        // 关闭 WebSocket
                        closeWebSocket() {
                                this.stopHeartbeat();
                                if (this.socketTask) {
                                        uni.closeSocket();
                                        this.socketTask = null;
                                }
                        },
                        // ========== 红包相关 ==========
                        // 初始化红包金额
                        async initRedpacketAmount() {
                                try {
                                        const res = await this.$api.redpacketClick({ reset: 1 });
                                        if (res.code === 1) {
                                                this.currentAmount = res.data.total_amount || 0;
                                        }
                                } catch (e) {
                                        console.error('初始化红包失败:', e);
                                }
                        },
                        // 点击红包
                        async clickRedpacket(taskId) {
                                try {
                                        const res = await this.$api.redpacketClick({ task_id: taskId });
                                        if (res.code === 1) {
                                                this.currentAmount = res.data.total_amount || 0;
                                                return res.data.total_amount;
                                        }
                                } catch (e) {
                                        console.error('点击红包失败:', e);
                                }
                                return 0;
                        },
                        // 领取红包
                        async claimRedpacket(taskId) {
                                try {
                                        const res = await this.$api.redpacketClaim({ task_id: taskId });
                                        if (res.code === 1) {
                                                return res.data;
                                        } else {
                                                uni.showToast({
                                                        title: res.msg || '领取失败',
                                                        icon: 'none'
                                                });
                                        }
                                } catch (e) {
                                        console.error('领取红包失败:', e);
                                }
                                return null;
                        },
                        // 重置红包
                        async resetRedpacket() {
                                try {
                                        await this.$api.redpacketReset();
                                } catch (e) {
                                        console.error('重置红包失败:', e);
                                }
                        },
                        // 打开红包弹窗
                        openRedbag(msg) {
                                if (msg.status === 'opened') {
                                        uni.showToast({
                                                title: '红包已领取',
                                                icon: 'none'
                                        });
                                        return;
                                }
                                this.currentRedbag = msg;
                                this.showRedbagModal = true;
                        },
                        // 关闭红包弹窗
                        closeRedbagModal() {
                                this.showRedbagModal = false;
                                this.currentRedbag = null;
                        },
                        // 处理开红包
                        async handleOpenRedbag() {
                                if (!this.currentRedbag) return;

                                uni.showLoading({ title: '加载中...' });

                                // 点击红包累加金额
                                await this.clickRedpacket(this.currentRedbag.taskId);

                                // 领取红包
                                const result = await this.claimRedpacket(this.currentRedbag.taskId);

                                uni.hideLoading();

                                if (result) {
                                        // 更新红包状态
                                        this.currentRedbag.status = 'opened';

                                        // 显示结果
                                        this.showRedbagModal = false;
                                        this.resultAmount = result.amount;
                                        this.showResultModal = true;
                                }
                        },
                        // 关闭结果弹窗
                        closeResultModal() {
                                this.showResultModal = false;
                        },
                        // ========== 消息相关 ==========
                        // 启动自动发送消息轮询
                        startAutoSendMessage() {
                                if (this.autoSendTimer) return;
                                // 每3-8秒随机发送一条消息
                                const randomInterval = Math.floor(Math.random() * 5000) + 3000;
                                this.autoSendTimer = setInterval(() => {
                                        this.sendAutoMessage();
                                }, randomInterval);
                        },
                        // 停止自动发送消息
                        stopAutoSendMessage() {
                                if (this.autoSendTimer) {
                                        clearInterval(this.autoSendTimer);
                                        this.autoSendTimer = null;
                                }
                        },
                        // 发送自动消息
                        sendAutoMessage() {
                                if (this.autoMessageIndex >= this.autoMessages.length) {
                                        this.autoMessageIndex = 0; // 循环发送
                                }

                                const templateMsg = this.autoMessages[this.autoMessageIndex];
                                const newMsg = {
                                        id: 'auto_' + Date.now(),
                                        type: templateMsg.type,
                                        content: templateMsg.content,
                                        time: Date.now(),
                                        sender: templateMsg.sender,
                                        user: templateMsg.user
                                };

                                // 根据消息类型添加额外字段
                                if (templateMsg.type === 'redbag') {
                                        newMsg.status = templateMsg.status;
                                        newMsg.amount = templateMsg.amount;
                                } else if (templateMsg.type === 'img') {
                                        newMsg.url = templateMsg.url;
                                        newMsg.imgWidth = templateMsg.imgWidth;
                                        newMsg.imgHeight = templateMsg.imgHeight;
                                }

                                this.messages.push(newMsg);
                                this.autoMessageIndex++;
                                this.scrollToBottom();
                        },
                        getHistoryMsg() {
                                // 加载历史消息逻辑
                        },
                        // 播放语音
                        playVoice(msg) {
                                // 语音播放逻辑
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
                },
                computed: {
                        vuex_user() {
                                return this.$store ? this.$store.state.vuex_user : {};
                        },
                        vuex_token() {
                                return this.$store ? this.$store.state.vuex_token : '';
                        }
                }
        }
</script>

<style lang="scss" scoped>
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

        // 红包领取弹窗
        .redbag-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
        }

        .redbag-modal-content {
                width: 600rpx;
                background: linear-gradient(180deg, #ff6b6b 0%, #ee5a5a 100%);
                border-radius: 24rpx;
                position: relative;
                overflow: hidden;

                .redbag-header {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        padding: 60rpx 40rpx 40rpx;

                        .redbag-avatar {
                                width: 100rpx;
                                height: 100rpx;
                                border-radius: 50%;
                                border: 4rpx solid rgba(255, 255, 255, 0.5);
                                margin-bottom: 20rpx;
                        }

                        .redbag-sender {
                                font-size: 32rpx;
                                color: #fff;
                                margin-bottom: 10rpx;
                        }

                        .redbag-wish {
                                font-size: 28rpx;
                                color: rgba(255, 255, 255, 0.8);
                        }
                }

                .redbag-body {
                        display: flex;
                        justify-content: center;
                        padding: 40rpx 0 60rpx;

                        .open-btn {
                                width: 160rpx;
                                height: 160rpx;
                                border-radius: 50%;
                                background: linear-gradient(135deg, #ffd700 0%, #ffb700 100%);
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                box-shadow: 0 10rpx 30rpx rgba(0, 0, 0, 0.2);

                                text {
                                        font-size: 60rpx;
                                        color: #ff5a5a;
                                        font-weight: bold;
                                }
                        }
                }

                .redbag-footer {
                        text-align: center;
                        padding: 20rpx;
                        background-color: rgba(0, 0, 0, 0.1);

                        text {
                                font-size: 24rpx;
                                color: rgba(255, 255, 255, 0.7);
                        }
                }

                .close-btn {
                        position: absolute;
                        top: 20rpx;
                        right: 20rpx;
                        width: 60rpx;
                        height: 60rpx;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                }
        }

        // 红包领取结果弹窗
        .result-modal {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
        }

        .result-modal-content {
                width: 500rpx;
                background-color: #fff;
                border-radius: 24rpx;
                overflow: hidden;

                .result-header {
                        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
                        padding: 40rpx;
                        text-align: center;

                        .result-title {
                                font-size: 36rpx;
                                color: #fff;
                                font-weight: bold;
                        }
                }

                .result-body {
                        padding: 40rpx;
                        text-align: center;
                        display: flex;
                        flex-direction: column;
                        align-items: center;

                        .result-amount {
                                font-size: 80rpx;
                                color: #ff5a5a;
                                font-weight: bold;
                        }

                        .result-unit {
                                font-size: 28rpx;
                                color: #999;
                                margin-top: 10rpx;
                        }
                }

                .result-footer {
                        padding: 20rpx 40rpx 40rpx;
                        text-align: center;

                        .result-tip {
                                font-size: 24rpx;
                                color: #999;
                                display: block;
                                margin-bottom: 20rpx;
                        }

                        .result-btn {
                                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a5a 100%);
                                padding: 20rpx 80rpx;
                                border-radius: 40rpx;
                                display: inline-block;

                                text {
                                        font-size: 28rpx;
                                        color: #fff;
                                }
                        }
                }
        }
</style>

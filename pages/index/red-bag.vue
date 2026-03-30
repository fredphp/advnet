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
                                                <image class="redbag-icon" src="/static/image/redbag-icon.png" mode="aspectFit"></image>
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

                                        <!-- 普通聊天/下载/广告/视频 任务卡片 -->
                                        <task-message 
                                                v-if="msg.type === 'task_chat' || msg.type === 'task_download' || msg.type === 'task_adv' || msg.type === 'task_video'" 
                                                :message="msg" 
                                                :is-me="false" 
                                                @task-click="handleTaskClick" />

                                        <!-- 红包消息（小程序类型） -->
                                        <redbag-message 
                                                v-if="msg.type === 'redbag'" 
                                                :message="msg" 
                                                :is-me="false" 
                                                @open-redbag="handleRedbagClick" />

                                        <!-- 普通文本/图片消息 -->
                                        <chat-message 
                                                v-if="msg.type === 'text' || msg.type === 'img'" 
                                                :message="msg" 
                                                :is-me="msg.sender === 'me'" 
                                                @play-voice="playVoice(msg)" />
                                </view>
                                <view id="bottom-anchor"></view>
                        </scroll-view>
                </view>

                <!-- 红包领取弹窗 -->
                <view class="redbag-modal-mask" v-if="showRedbagModal" @click="closeRedbagModal">
                        <view class="redbag-modal" @click.stop>
                                <!-- 顶部装饰 -->
                                <view class="modal-header">
                                        <image class="modal-logo" :src="currentRedbag.backgroundImage || '/static/image/redbag-icon.png'" mode="aspectFit"></image>
                                        <text class="modal-title">{{ currentRedbag.displayTitle || '恭喜发财' }}</text>
                                </view>

                                <!-- 金额显示区域 -->
                                <view class="modal-amount-area">
                                        <view class="amount-circle" :class="{ shaking: isClicking && !isOpened }">
                                                <text class="amount-number">{{ displayAmount }}</text>
                                                <text class="amount-label">金币</text>
                                        </view>
                                        <text class="amount-hint" v-if="!isOpened && currentAmount > 0">
                                                金币持续增长中...
                                        </text>
                                        <text class="amount-hint" v-else-if="isOpened">
                                                拆开红包获得 {{ displayAmount }} 金币
                                        </text>
                                        <text class="amount-hint" v-else>
                                                点击红包拆开
                                        </text>
                                </view>

                                <!-- 操作按钮 -->
                                <view class="modal-actions">
                                        <!-- 未拆开：拆红包按钮 -->
                                        <view v-if="!isOpened" class="action-btn open-btn" @click="openRedbag">
                                                <text class="action-text">拆红包</text>
                                        </view>
                                        <!-- 已拆开：领取按钮 → 跳转小程序 -->
                                        <view v-else-if="isOpened && !isClaimed" class="action-btn claim-btn" @click="claimRedbag">
                                                <text class="action-text">领取并去小程序</text>
                                        </view>
                                        <!-- 已领取 -->
                                        <view v-else class="action-btn done-btn">
                                                <text class="action-text">已领取</text>
                                        </view>
                                </view>

                                <!-- 关闭按钮 -->
                                <view class="modal-close" @click="closeRedbagModal">
                                        <text class="close-text">关闭</text>
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
import TaskMessage from '@/components/chat/taskMessage.vue'
import AdBanner from '@/components/ad/adBanner.vue'
import socketService from '@/common/socket.js'

export default {
        components: {
                ChatMessage,
                RedbagMessage,
                TaskMessage,
                AdBanner
        },

        onLoad(opt) {
                this.groupId = opt.group_id || 'default_group';
                this.user_info = uni.getStorageSync('user_info') || {};
                this.initSocket();
        },

        onUnload() {
                socketService.disconnect();
                this.stopRedbagClickTimer();
                // 离开页面时重置红包
                this.resetRedbag();
        },

        data() {
                return {
                        inputText: '',
                        groupId: '',
                        groupName: '红包群94',
                        onlineCount: 0,
                        user_info: {},
                        scrollTop: 0,
                        scrollIntoViewId: '',
                        loading: false,
                        showConnected: true,
                        scrollHeight: 0,
                        isConnected: false,
                        messages: [],

                        // 红包弹窗状态
                        showRedbagModal: false,
                        currentRedbag: {},
                        currentAmount: 0,
                        isOpened: false,
                        isClaimed: false,
                        isClicking: false,
                        clickTimer: null,
                        currentMsgRef: null,
                };
        },

        mounted() {
                this.$nextTick(() => {
                        this.calcScrollHeight();
                });
        },

        methods: {
                // ==================== WebSocket ====================
                initSocket() {
                        socketService.connect({
                                userId: this.user_info.id || this.user_info.user_id,
                                token: this.user_info.token || '',
                                groupId: this.groupId,
                                serverUrl: 'ws://advnet.cocos2026.cn:3002'
                        });

                        socketService.onConnected((data) => {
                                this.isConnected = true;
                                this.onlineCount = data.onlineCount;
                                setTimeout(() => { this.showConnected = false; }, 1000);
                        });

                        socketService.onAuthFailed(() => {
                                this.isConnected = false;
                                uni.showToast({ title: '认证失败，请重新登录', icon: 'none' });
                        });

                        socketService.onOnlineCount((count) => {
                                this.onlineCount = count;
                        });

                        // ★ 核心：监听任务推送，根据类型分发到不同消息卡片
                        socketService.onTask((task) => {
                                console.log('[RedBag] 收到任务推送:', JSON.stringify(task));
                                this.handleTaskPush(task);
                        });

                        socketService.onSystemMessage((msg) => {
                                this.messages.push({
                                        id: 'sys_' + Date.now(),
                                        type: 'system',
                                        content: msg.content,
                                        time: Date.now(),
                                        sender: 'system'
                                });
                                this.scrollToBottom();
                        });
                },

                // ==================== 任务推送分发（核心逻辑） ====================
                /**
                 * 根据任务类型，将推送转化为不同样式的消息卡片
                 * 
                 * 类型映射：
                 *  - chat     → type: 'task_chat'     → TaskMessage 聊天卡片
                 *  - download → type: 'task_download' → TaskMessage 下载卡片
                 *  - adv      → type: 'task_adv'      → TaskMessage 广告卡片
                 *  - video    → type: 'task_video'    → TaskMessage 视频卡片
                 *  - miniapp  → type: 'redbag'        → RedbagMessage 红包卡片
                 */
                handleTaskPush(task) {
                        const taskId = task.taskId || task.task_id || 0;
                        const taskName = task.taskName || task.task_name || '';
                        const taskType = task.taskType || task.type || '';
                        const displayTitle = task.display_title || task.displayTitle || taskName;
                        const displayDesc = task.display_description || task.displayDescription || (task.description || '');
                        const senderName = task.sender_name || task.senderName || '系统';
                        const senderAvatar = task.sender_avatar || task.senderAvatar || '';
                        const timestamp = task.time || task.timestamp || Date.now() / 1000;
                        const resource = task.resource || null;
                        const backgroundImage = task.background_image || '';

                        const baseMsg = {
                                id: 'task_' + Date.now(),
                                time: timestamp * 1000,
                                sender: 'other',
                                user: {
                                        nickname: senderName,
                                        avatar: senderAvatar || '/static/image/avatar.png'
                                },
                                displayTitle,
                                displayDesc,
                                backgroundImage,
                                taskData: {
                                        taskId,
                                        taskName,
                                        taskType,
                                        description: task.description || '',
                                        resource,
                                        background_image: backgroundImage,
                                        jump_url: task.jump_url || '',
                                }
                        };

                        switch (taskType) {
                                case 'chat':
                                        // 普通聊天任务 → 以普通文本气泡形式显示 description
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_chat',
                                                content: task.description || displayDesc || displayTitle,
                                        });
                                        break;

                                case 'download':
                                case 'download_app':
                                        // 下载App任务 → 显示下载卡片
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_download',
                                                content: displayTitle,
                                        });
                                        break;

                                case 'miniapp':
                                case 'mini_program':
                                        // 小程序游戏 → 显示红包卡片（仅展示，用户点击后才累加金额）
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'redbag',
                                                content: displayTitle,
                                                amount: 0,
                                                currentAmount: 0,
                                                status: 'unopened', // unopened → opened → claimed
                                        });
                                        break;

                                case 'adv':
                                        // 广告时长任务 → 显示广告卡片
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_adv',
                                                content: displayTitle,
                                                advDuration: task.adv_duration || (resource && resource.adv_duration) || 0,
                                        });
                                        break;

                                case 'video':
                                case 'watch_video':
                                        // 观看视频任务 → 显示视频卡片
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_video',
                                                content: displayTitle,
                                                videoDuration: task.video_duration || (resource && resource.video_duration) || 0,
                                        });
                                        break;

                                default:
                                        // 未知类型，按普通文本显示
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'text',
                                                content: displayTitle || taskName || '收到新任务',
                                        });
                        }

                        this.scrollToBottom();
                },

                // ==================== 红包点击累加逻辑 ====================
                /**
                 * 用户点击红包卡片后，开始每隔2秒调用 click 接口累加金额
                 * 用户不点击则不会请求接口
                 * 使用 uni.request 直接请求，避免全局拦截器弹出错误提示
                 */
                startRedbagClick(msgId, taskId) {
                        // 首次点击：reset=1 生成基础金额
                        this.doClickRedbag(msgId, taskId, 1);
                        // 之后每2秒累加一次
                        this.clickTimer = setInterval(() => {
                                this.doClickRedbag(msgId, taskId, 0);
                        }, 2000);
                },

                async doClickRedbag(msgId, taskId, reset) {
                        try {
                                // 直接用 uni.request，不走全局拦截器，避免弹出 toast
                                const [err, res] = await uni.request({
                                        url: '/api/redpacket/click',
                                        method: 'POST',
                                        header: {
                                                'content-type': 'application/json',
                                                'token': this.vuex_token || '',
                                                'uid': (this.vuex_user && this.vuex_user.id) || 0
                                        },
                                        data: { task_id: taskId, reset: reset || 0 }
                                });
                                if (!err && res.statusCode === 200 && res.data) {
                                        const result = res.data;
                                        if (result.code === 1 && result.data) {
                                                const amount = result.data.total_amount || 0;
                                                this.currentAmount = amount;
                                                // 同步更新消息列表中的金额
                                                const msg = this.messages.find(m => m.id === msgId);
                                                if (msg) {
                                                        this.$set(msg, 'currentAmount', amount);
                                                }
                                        }
                                } else {
                                        console.warn('[RedBag] click接口异常:', err, res);
                                }
                        } catch (e) {
                                // 静默处理，不打扰用户
                        }
                },

                stopRedbagClickTimer() {
                        if (this.clickTimer) {
                                clearInterval(this.clickTimer);
                                this.clickTimer = null;
                        }
                },

                // ==================== 红包弹窗交互 ====================
                /**
                 * 点击红包卡片 → 打开弹窗 → 开始累加金额
                 * 用户不点击红包卡片，则完全不会请求 click 接口
                 */
                handleRedbagClick(msg) {
                        if (msg.status === 'claimed') {
                                uni.showToast({ title: '已领取', icon: 'none' });
                                return;
                        }

                        // 记录当前操作的红包
                        this.currentRedbag = msg;
                        this.currentAmount = msg.currentAmount || 0;
                        this.isOpened = msg.status === 'opened';
                        this.isClaimed = msg.status === 'claimed';
                        this.isClicking = !this.isOpened;
                        this.currentMsgRef = msg;
                        this.showRedbagModal = true;

                        // ★ 用户点击红包后，才开始累加金额
                        if (!this.isOpened) {
                                const taskId = (msg.taskData && msg.taskData.taskId) || 0;
                                const msgId = msg.id;
                                if (taskId) {
                                        this.startRedbagClick(msgId, taskId);
                                }
                        }
                },

                /**
                 * 拆红包 → 停止累加，显示最终金额
                 */
                openRedbag() {
                        this.isOpened = true;
                        this.isClicking = false;
                        this.stopRedbagClickTimer();

                        // 更新消息状态
                        if (this.currentMsgRef) {
                                this.$set(this.currentMsgRef, 'status', 'opened');
                                this.$set(this.currentMsgRef, 'claimedAmount', this.currentAmount);
                        }
                },

                /**
                 * 领取红包 → 调用 claim 接口发放金币 → 跳转小程序广告页
                 */
                async claimRedbag() {
                        const taskId = (this.currentRedbag.taskData && this.currentRedbag.taskData.taskId) || 0;
                        try {
                                uni.showLoading({ title: '领取中...', mask: true });
                                // 用 uni.request 直接请求，避免拦截器弹错误 toast
                                const [err, res] = await uni.request({
                                        url: '/api/redpacket/claim',
                                        method: 'POST',
                                        header: {
                                                'content-type': 'application/json',
                                                'token': this.vuex_token || '',
                                                'uid': (this.vuex_user && this.vuex_user.id) || 0
                                        },
                                        data: { task_id: taskId }
                                });
                                uni.hideLoading();

                                if (!err && res.statusCode === 200 && res.data && res.data.code === 1) {
                                        this.isClaimed = true;
                                        this.stopRedbagClickTimer();

                                        // 更新消息状态
                                        if (this.currentMsgRef) {
                                                this.$set(this.currentMsgRef, 'status', 'claimed');
                                                this.$set(this.currentMsgRef, 'claimedAmount', this.currentAmount);
                                        }

                                        const claimAmount = res.data.data.amount || this.currentAmount;
                                        uni.showToast({
                                                title: '领取成功 +' + claimAmount + ' 金币',
                                                icon: 'none',
                                                duration: 2000
                                        });

                                        // 2秒后跳转到小程序
                                        setTimeout(() => {
                                                this.jumpToMiniapp();
                                        }, 2000);
                                } else {
                                        const msg = (res && res.data && res.data.msg) || '领取失败';
                                        uni.showToast({ title: msg, icon: 'none' });
                                }
                        } catch (e) {
                                uni.hideLoading();
                                console.error('[RedBag] claim失败:', e);
                        }
                },

                /**
                 * 跳转到小程序广告页面
                 */
                jumpToMiniapp() {
                        const resource = this.currentRedbag.taskData && this.currentRedbag.taskData.resource;
                        const miniappId = resource ? resource.miniapp_id : '';
                        const miniappPath = resource ? resource.miniapp_path : '';
                        const jumpUrl = (this.currentRedbag.taskData && this.currentRedbag.taskData.jump_url) || '';

                        // #ifdef MP-WEIXIN
                        if (miniappId) {
                                uni.navigateToMiniProgram({
                                        appId: miniappId,
                                        path: miniappPath || '',
                                        success: () => {
                                                console.log('[RedBag] 跳转小程序成功');
                                        },
                                        fail: (err) => {
                                                console.warn('[RedBag] 跳转小程序失败:', err);
                                                if (jumpUrl) {
                                                        uni.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(jumpUrl) });
                                                }
                                        }
                                });
                        } else if (jumpUrl) {
                                uni.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(jumpUrl) });
                        }
                        // #endif

                        // #ifdef H5
                        if (jumpUrl) {
                                window.location.href = jumpUrl;
                        } else {
                                uni.showToast({ title: '请在微信中打开小程序', icon: 'none' });
                        }
                        // #endif

                        // #ifdef APP-PLUS
                        if (jumpUrl) {
                                plus.runtime.openURL(jumpUrl);
                        } else {
                                uni.showToast({ title: '暂不支持跳转', icon: 'none' });
                        }
                        // #endif
                },

                closeRedbagModal() {
                        this.showRedbagModal = false;
                        this.isClicking = false;
                        this.stopRedbagClickTimer();
                        this.currentRedbag = {};
                        this.currentMsgRef = null;
                },

                async resetRedbag() {
                        this.stopRedbagClickTimer();
                        // 静默重置，不弹提示
                        try {
                                const [err, res] = await uni.request({
                                        url: '/api/redpacket/reset',
                                        method: 'POST',
                                        header: {
                                                'content-type': 'application/json',
                                                'token': this.vuex_token || '',
                                                'uid': (this.vuex_user && this.vuex_user.id) || 0
                                        },
                                        data: {}
                                });
                        } catch (e) {
                                // 静默处理
                        }
                },

                // ==================== 任务卡片点击处理 ====================
                /**
                 * 处理聊天/下载/广告/视频任务卡片的点击
                 */
                handleTaskClick({ type, message, taskData }) {
                        const jumpUrl = taskData.jump_url || '';
                        const resource = taskData.resource || {};

                        switch (type) {
                                case 'chat':
                                        // 聊天任务 → 可以跳转到聊天详情或打开输入框
                                        uni.showToast({ title: '聊天任务 · 请按要求聊天', icon: 'none' });
                                        break;

                                case 'download':
                                case 'download_app':
                                        // 下载App → 跳转到下载链接
                                        if (jumpUrl) {
                                                // #ifdef H5
                                                window.location.href = jumpUrl;
                                                // #endif
                                                // #ifdef APP-PLUS
                                                plus.runtime.openURL(jumpUrl);
                                                // #endif
                                                // #ifdef MP-WEIXIN
                                                uni.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(jumpUrl) });
                                                // #endif
                                        } else {
                                                uni.showToast({ title: '暂无下载链接', icon: 'none' });
                                        }
                                        break;

                                case 'adv':
                                        // 广告时长 → 显示广告弹窗或跳转广告页
                                        uni.showToast({ title: '广告任务 · 请观看广告', icon: 'none' });
                                        break;

                                case 'video':
                                case 'watch_video':
                                        // 视频任务 → 跳转视频播放
                                        if (resource.video_url) {
                                                uni.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(resource.video_url) });
                                        } else {
                                                uni.showToast({ title: '暂无视频链接', icon: 'none' });
                                        }
                                        break;

                                default:
                                        uni.showToast({ title: '未知任务类型', icon: 'none' });
                        }
                },

                // ==================== 辅助方法 ====================
                goBack() {
                        uni.navigateBack();
                },

                goWithdraw() {
                        uni.navigateTo({ url: '/pages/my/withdraw/index' });
                },

                calcScrollHeight() {
                        const sysInfo = uni.getSystemInfoSync();
                        const navH = 88;
                        const adH = 200;
                        const tabH = 100;
                        const statusBarH = sysInfo.statusBarHeight || 0;
                        this.scrollHeight = sysInfo.windowHeight - navH - adH - tabH - statusBarH;
                },

                getHistoryMsg() {},

                playVoice(msg) {},

                scrollToBottom() {
                        this.$nextTick(() => {
                                const lastMsg = this.messages[this.messages.length - 1];
                                if (lastMsg) {
                                        this.scrollIntoViewId = '';
                                        this.$nextTick(() => {
                                                this.scrollIntoViewId = 'msg-' + lastMsg.id;
                                        });
                                }
                        });
                },

                formatTime(timestamp) {
                        const date = new Date(timestamp);
                        const hours = date.getHours().toString().padStart(2, '0');
                        const minutes = date.getMinutes().toString().padStart(2, '0');
                        return `${hours}:${minutes}`;
                },

                showTimeDivider(index) {
                        if (index === 0) return true;
                        const currentMsg = this.messages[index];
                        const prevMsg = this.messages[index - 1];
                        if (prevMsg.type === 'system') return false;
                        return currentMsg.time - prevMsg.time > 300000;
                },

                // 计算属性：显示金额
                get displayAmount() {
                        return this.currentAmount || 0;
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

/* ===== 导航栏 ===== */
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
        background: linear-gradient(135deg, #e74c3c, #c0392b);
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

/* ===== 广告区域 ===== */
.ad-section {
        background-color: #fff;
        padding: 20rpx;
        border-bottom: 1rpx solid #eee;
}

/* ===== 消息列表 ===== */
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

.system-message {
        text-align: center;
        font-size: 24rpx;
        color: #999;
        margin: 20rpx 0;
        background-color: rgba(0, 0, 0, 0.05);
        padding: 10rpx 20rpx;
        border-radius: 8rpx;
        display: inline-block;
        align-self: center;
}

.time-divider {
        text-align: center;
        font-size: 24rpx;
        color: #999;
        margin: 20rpx 0;
        position: relative;

        &::before, &::after {
                content: "";
                position: absolute;
                top: 50%;
                width: 30%;
                height: 1rpx;
                background-color: #ddd;
        }

        &::before { left: 0; }
        &::after { right: 0; }
}

/* ===== 红包弹窗 ===== */
.redbag-modal-mask {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
}

.redbag-modal {
        width: 600rpx;
        background: linear-gradient(180deg, #e74c3c 0%, #c0392b 40%, #a93226 100%);
        border-radius: 32rpx;
        padding: 60rpx 40rpx 40rpx;
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        overflow: hidden;

        &::before {
                content: '';
                position: absolute;
                top: -60rpx;
                right: -60rpx;
                width: 200rpx;
                height: 200rpx;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.06);
        }

        &::after {
                content: '';
                position: absolute;
                bottom: -40rpx;
                left: -40rpx;
                width: 140rpx;
                height: 140rpx;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.04);
        }
}

.modal-header {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-bottom: 40rpx;

        .modal-logo {
                width: 100rpx;
                height: 100rpx;
                border-radius: 20rpx;
                margin-bottom: 16rpx;
                background: rgba(255, 255, 255, 0.15);
        }

        .modal-title {
                font-size: 36rpx;
                color: #fff;
                font-weight: 600;
        }
}

.modal-amount-area {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 20rpx 0 40rpx;
}

.amount-circle {
        width: 240rpx;
        height: 240rpx;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.12);
        border: 4rpx solid rgba(255, 255, 255, 0.2);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 16rpx;

        &.shaking {
                animation: shake 0.5s ease-in-out infinite;
        }
}

@keyframes shake {
        0%, 100% { transform: rotate(0deg); }
        25% { transform: rotate(-2deg); }
        75% { transform: rotate(2deg); }
}

.amount-number {
        font-size: 64rpx;
        font-weight: 700;
        color: #ffe082;
        line-height: 1;
}

.amount-label {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 8rpx;
}

.amount-hint {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.6);
}

.modal-actions {
        width: 100%;
        margin-bottom: 30rpx;
}

.action-btn {
        width: 100%;
        height: 88rpx;
        border-radius: 44rpx;
        display: flex;
        align-items: center;
        justify-content: center;

        .action-text {
                font-size: 32rpx;
                font-weight: 600;
        }
}

.open-btn {
        background: linear-gradient(135deg, #ffe082, #ffc107);
        .action-text { color: #c0392b; }
}

.claim-btn {
        background: linear-gradient(135deg, #ffe082, #ffc107);

        .action-text { color: #c0392b; }
}

.done-btn {
        background: rgba(255, 255, 255, 0.15);

        .action-text { color: rgba(255, 255, 255, 0.6); }
}

.modal-close {
        padding: 16rpx 60rpx;

        .close-text {
                font-size: 28rpx;
                color: rgba(255, 255, 255, 0.6);
        }
}
</style>

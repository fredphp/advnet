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

                <!-- 红包领取弹窗（遮罩层5秒内不可关闭） -->
                <view class="redbag-modal-mask" v-if="showRedbagModal" @click="tryCloseModal">
                        <view class="redbag-modal" @click.stop>
                                <!-- 顶部装饰 -->
                                <view class="modal-header">
                                        <image class="modal-logo" :src="currentRedbag.backgroundImage || '/static/image/redbag-icon.png'" mode="aspectFit"></image>
                                        <text class="modal-title">{{ currentRedbag.displayTitle || '恭喜发财' }}</text>
                                </view>

                                <!-- 金额显示区域 -->
                                <view class="modal-amount-area">
                                        <!-- 加载中 -->
                                        <view v-if="isLoadingAmount" class="amount-circle loading">
                                                <text class="amount-number loading-text">...</text>
                                                <text class="amount-label">获取中</text>
                                        </view>
                                        <!-- 有金额 -->
                                        <view v-else class="amount-circle" :class="{ shaking: false }">
                                                <text class="amount-number">{{ displayAmount }}</text>
                                                <text class="amount-label">金币</text>
                                        </view>

                                        <text class="amount-hint" v-if="isLoadingAmount">
                                                正在获取红包金额...
                                        </text>
                                        <text class="amount-hint" v-else-if="isClaimed">
                                                已领取 {{ displayAmount }} 金币
                                        </text>
                                        <text class="amount-hint" v-else-if="currentAmount > 0">
                                                恭喜获得 {{ displayAmount }} 金币
                                        </text>
                                        <text class="amount-hint" v-else>
                                                获取金额失败，请重试
                                        </text>
                                </view>

                                <!-- 操作按钮 -->
                                <view class="modal-actions">
                                        <!-- 加载中 -->
                                        <view v-if="isLoadingAmount" class="action-btn disabled-btn">
                                                <text class="action-text">获取中...</text>
                                        </view>
                                        <!-- 金额获取成功，未领取 → 领取按钮 -->
                                        <view v-else-if="!isClaimed && currentAmount > 0" class="action-btn claim-btn" @click="claimRedbag">
                                                <text class="action-text">领取并去小程序</text>
                                        </view>
                                        <!-- 已领取 -->
                                        <view v-else-if="isClaimed" class="action-btn done-btn">
                                                <text class="action-text">已领取</text>
                                        </view>
                                        <!-- 获取失败 -->
                                        <view v-else class="action-btn close-action-btn" @click="closeRedbagModal">
                                                <text class="action-text">关闭</text>
                                        </view>
                                </view>

                                <!-- 关闭按钮（5秒内禁用） -->
                                <view :class="['modal-close', { 'close-disabled': !canCloseModal }]" @click="tryCloseModal">
                                        <text class="close-text">{{ canCloseModal ? '关闭' : '请等待 ' + closeCountdown + 's' }}</text>
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
                // 离开页面时清理所有红包过期计时器
                this.clearAllRedbagTimers();
                // 清理关闭倒计时
                this.stopCloseLock();
                // 重置红包（清理服务端缓存）
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
                        isClaimed: false,
                        isLoadingAmount: false,
                        currentMsgRef: null,
                        // 5秒关闭锁
                        canCloseModal: false,
                        closeCountdown: 5,
                        closeCountdownTimer: null,

                        // 红包过期计时器 { msgId: timerId }
                        redbagTimers: {},
                };
        },

        computed: {
                displayAmount() {
                        return Number(this.currentAmount) || 0;
                }
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
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_chat',
                                                content: task.description || displayDesc || displayTitle,
                                        });
                                        break;

                                case 'download':
                                case 'download_app':
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_download',
                                                content: displayTitle,
                                        });
                                        break;

                                case 'miniapp':
                                case 'mini_program':
                                        // 小程序游戏 → 显示红包卡片
                                        const msgId = baseMsg.id;
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'redbag',
                                                content: displayTitle,
                                                amount: 0,
                                                currentAmount: 0,
                                                status: 'unopened', // unopened → opened → claimed / expired
                                        });
                                        // ★ 新红包到达后，启动 2~4 秒自动过期计时器
                                        this.startRedbagExpireTimer(msgId);
                                        break;

                                case 'adv':
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_adv',
                                                content: displayTitle,
                                                advDuration: task.adv_duration || (resource && resource.adv_duration) || 0,
                                        });
                                        break;

                                case 'video':
                                case 'watch_video':
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'task_video',
                                                content: displayTitle,
                                                videoDuration: task.video_duration || (resource && resource.video_duration) || 0,
                                        });
                                        break;

                                default:
                                        this.messages.push({
                                                ...baseMsg,
                                                type: 'text',
                                                content: displayTitle || taskName || '收到新任务',
                                        });
                        }

                        this.scrollToBottom();
                },

                // ==================== 红包自动过期机制 ====================

                /**
                 * 为新到达的红包启动自动过期计时器
                 * 2~4 秒内未点击 → 自动变为已过期（不可再点击）
                 */
                startRedbagExpireTimer(msgId) {
                        const delay = 2000 + Math.random() * 3000; // 2~5 秒随机过期
                        const timerId = setTimeout(() => {
                                const msg = this.messages.find(m => m.id === msgId);
                                if (msg && msg.status === 'unopened') {
                                        this.$set(msg, 'status', 'expired');
                                }
                                // 清理计时器引用
                                delete this.redbagTimers[msgId];
                        }, delay);
                        this.redbagTimers[msgId] = timerId;
                },

                /**
                 * 取消指定红包的过期计时器
                 */
                cancelRedbagExpireTimer(msgId) {
                        if (this.redbagTimers[msgId]) {
                                clearTimeout(this.redbagTimers[msgId]);
                                delete this.redbagTimers[msgId];
                        }
                },

                /**
                 * 清理所有红包过期计时器
                 */
                clearAllRedbagTimers() {
                        Object.keys(this.redbagTimers).forEach(msgId => {
                                clearTimeout(this.redbagTimers[msgId]);
                        });
                        this.redbagTimers = {};
                },

                // ==================== 红包点击（单次点击） ====================

                /**
                 * 用户点击红包卡片 → 打开弹窗 → 调用一次 click 接口获取金额
                 * 不再每 2 秒累加，只有点击新红包时才获取金额
                 */
                handleRedbagClick(msg) {
                        // 已领取 / 已过期 → 不可再点击
                        if (msg.status === 'claimed' || msg.status === 'expired') {
                                uni.showToast({ title: msg.status === 'expired' ? '已领取' : '已领取', icon: 'none' });
                                return;
                        }

                        // 已拆开但未领取（金额已获取）
                        if (msg.status === 'opened') {
                                // 直接打开弹窗显示已有金额，允许领取
                                this.currentRedbag = msg;
                                this.currentAmount = msg.currentAmount || msg.claimedAmount || 0;
                                this.isClaimed = false;
                                this.isLoadingAmount = false;
                                this.currentMsgRef = msg;
                                this.showRedbagModal = true;
                                this.startCloseLock();
                                return;
                        }

                        // ★ 新红包点击：取消过期计时器
                        this.cancelRedbagExpireTimer(msg.id);

                        // 记录当前操作的红包
                        this.currentRedbag = msg;
                        this.currentAmount = 0;
                        this.isClaimed = false;
                        this.isLoadingAmount = true;
                        this.currentMsgRef = msg;
                        this.showRedbagModal = true;
                        this.startCloseLock();

                        // 调用 click 接口（仅一次，reset=1 生成基础金额）
                        const taskId = (msg.taskData && msg.taskData.taskId) || 0;
                        if (taskId) {
                                this.doClickOnce(msg.id, taskId);
                        } else {
                                this.isLoadingAmount = false;
                        }
                },

                /**
                 * 单次调用 click 接口获取红包金额
                 */
                async doClickOnce(msgId, taskId) {
                        try {
                                const res = await this.$api.redpacketClick({ task_id: taskId, reset: 1 });
                                if (res && res.code === 1 && res.data) {
                                        const amount = res.data.total_amount || 0;
                                        this.currentAmount = amount;
                                        this.isLoadingAmount = false;

                                        // 同步更新消息列表中的金额和状态
                                        const msg = this.messages.find(m => m.id === msgId);
                                        if (msg) {
                                                this.$set(msg, 'currentAmount', amount);
                                                this.$set(msg, 'claimedAmount', amount);
                                                this.$set(msg, 'status', 'opened');
                                        }
                                } else {
                                        this.isLoadingAmount = false;
                                        console.warn('[RedBag] click接口异常:', res);
                                        // 恢复过期计时器，让用户可以重试
                                        this.startRedbagExpireTimer(msgId);
                                }
                        } catch (e) {
                                this.isLoadingAmount = false;
                                console.error('[RedBag] click失败:', e);
                                // 恢复过期计时器
                                this.startRedbagExpireTimer(msgId);
                                uni.showToast({ title: '网络异常，请重试', icon: 'none' });
                        }
                },

                // ==================== 红包领取 ====================

                /**
                 * 领取红包 → 调用 claim 接口发放金币 → 跳转小程序广告页
                 */
                async claimRedbag() {
                        const taskId = (this.currentRedbag.taskData && this.currentRedbag.taskData.taskId) || 0;
                        try {
                                uni.showLoading({ title: '领取中...', mask: true });
                                const res = await this.$api.redpacketClaim({ task_id: taskId });
                                uni.hideLoading();

                                if (res && res.code === 1) {
                                        this.isClaimed = true;

                                        // 更新消息状态
                                        if (this.currentMsgRef) {
                                                this.$set(this.currentMsgRef, 'status', 'claimed');
                                                this.$set(this.currentMsgRef, 'claimedAmount', this.currentAmount);
                                        }

                                        const claimAmount = (res.data && res.data.amount) || this.currentAmount;
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
                                        const msg = (res && res.msg) || '领取失败';
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

                /**
                 * 5秒关闭锁：弹窗打开后5秒内不可关闭
                 */
                startCloseLock() {
                        this.canCloseModal = false;
                        this.closeCountdown = 5;
                        if (this.closeCountdownTimer) {
                                clearInterval(this.closeCountdownTimer);
                        }
                        this.closeCountdownTimer = setInterval(() => {
                                this.closeCountdown--;
                                if (this.closeCountdown <= 0) {
                                        this.canCloseModal = true;
                                        clearInterval(this.closeCountdownTimer);
                                        this.closeCountdownTimer = null;
                                }
                        }, 1000);
                },

                stopCloseLock() {
                        if (this.closeCountdownTimer) {
                                clearInterval(this.closeCountdownTimer);
                                this.closeCountdownTimer = null;
                        }
                        this.canCloseModal = false;
                        this.closeCountdown = 5;
                },

                tryCloseModal() {
                        if (!this.canCloseModal) return;
                        this.closeRedbagModal();
                },

                closeRedbagModal() {
                        this.showRedbagModal = false;
                        this.isLoadingAmount = false;
                        this.currentRedbag = {};
                        this.currentMsgRef = null;
                        this.stopCloseLock();
                },

                async resetRedbag() {
                        // 静默重置，不弹提示
                        try {
                                await this.$api.redpacketReset({});
                        } catch (e) {
                                // 静默处理
                        }
                },

                // ==================== 任务卡片点击处理 ====================
                handleTaskClick({ type, message, taskData }) {
                        const jumpUrl = taskData.jump_url || '';
                        const resource = taskData.resource || {};

                        switch (type) {
                                case 'chat':
                                        uni.showToast({ title: '聊天任务 · 请按要求聊天', icon: 'none' });
                                        break;

                                case 'download':
                                case 'download_app':
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
                                        uni.showToast({ title: '广告任务 · 请观看广告', icon: 'none' });
                                        break;

                                case 'video':
                                case 'watch_video':
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

        &.loading {
                animation: pulse-glow 1s ease-in-out infinite;
        }
}

@keyframes pulse-glow {
        0%, 100% { opacity: 1; border-color: rgba(255, 255, 255, 0.2); }
        50% { opacity: 0.7; border-color: rgba(255, 255, 255, 0.4); }
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

.loading-text {
        font-size: 48rpx;
        color: rgba(255, 255, 255, 0.5);
        animation: dot-blink 1.5s steps(3, end) infinite;
}

@keyframes dot-blink {
        0% { opacity: 0.3; }
        50% { opacity: 1; }
        100% { opacity: 0.3; }
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

.claim-btn {
        background: linear-gradient(135deg, #ffe082, #ffc107);

        .action-text { color: #c0392b; }
}

.done-btn {
        background: rgba(255, 255, 255, 0.15);

        .action-text { color: rgba(255, 255, 255, 0.6); }
}

.disabled-btn {
        background: rgba(255, 255, 255, 0.1);

        .action-text { color: rgba(255, 255, 255, 0.5); }
}

.close-action-btn {
        background: rgba(255, 255, 255, 0.15);

        .action-text { color: rgba(255, 255, 255, 0.8); }
}

.modal-close {
        padding: 16rpx 60rpx;

        .close-text {
                font-size: 28rpx;
                color: rgba(255, 255, 255, 0.6);
        }

        &.close-disabled {
                opacity: 0.4;
                pointer-events: none;

                .close-text {
                        color: rgba(255, 255, 255, 0.3);
                }
        }
}
</style>

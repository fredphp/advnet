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
                                        <view class="right-btns">
                                                <!-- 广告红包入口 -->
                                                <view class="ad-packet-btn" @click="toggleAdPacketPanel">
                                                        <text class="ad-packet-icon">🧧</text>
                                                        <view class="ad-badge" v-if="adPacketBadge > 0">
                                                                <text class="ad-badge-text">{{ adPacketBadge }}</text>
                                                        </view>
                                                </view>
                                                <view class="withdraw-btn" @click="goWithdraw">
                                                        <image class="redbag-icon" src="/static/image/redbag-icon.png" mode="aspectFit"></image>
                                                        <text class="withdraw-text">提现</text>
                                                </view>
                                        </view>
                                </view>
                        </view>

                        <!-- 广告区域（保留原有 banner） -->
                        <view class="ad-section">
                                <ad-banner></ad-banner>
                        </view>

                        <!-- ★ 用户不活跃提示 -->
                        <view class="idle-overlay" v-if="isUserIdle">
                                <view class="idle-hint">
                                        <text class="idle-hint-icon">💤</text>
                                        <text class="idle-hint-text">滑动屏幕继续赚金币</text>
                                </view>
                        </view>

                        <!-- 消息列表 -->
                        <scroll-view class="message-list" scroll-y scroll-with-animation
                                :style="{height: scrollHeight + 'px'}"
                                :scroll-into-view="scrollToId"
                                @touchstart="onUserActivity"
                                @touchmove="onUserActivity"
                                @click="onUserActivity">
                                <view v-for="(msg, index) in messages" :key="msg.id" :id="'msg-' + msg.id">
                                        <!-- 系统消息 -->
                                        <view class="system-message" v-if="msg.type === 'system'">
                                                {{ msg.content }}
                                        </view>

                                        <!-- 时间分隔 -->
                                        <view class="time-divider" v-if="showTimeDivider(index)">
                                                {{ formatTime(msg.time) }}
                                        </view>

                                        <!-- 广告信息流消息（uni-ad feed 广告） -->
                                        <ad-feed-message
                                                v-if="msg.type === 'ad_feed'"
                                                :message="msg"
                                                :feed-progress="feedViewProgress"
                                                :last-user-activity-time="lastUserActivityTime"
                                                :ad-idle-timeout="adIdleTimeout"
                                                @ad-rewarded="handleAdRewarded" />

                                        <!-- 激励视频广告消息 -->
                                        <rewarded-video-message
                                                v-if="msg.type === 'rewarded_video'"
                                                :message="msg"
                                                :reward-progress="rewardViewProgress"
                                                @ad-rewarded="handleAdRewarded" />

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
                                <!-- 底部锚点：用于scroll-into-view定位 -->
                                <view :id="scrollAnchorId" class="scroll-bottom-anchor"></view>
                        </scroll-view>
                </view>

                <!-- 广告红包面板（底部弹窗） -->
                <view class="ad-packet-mask" v-if="showAdPacketPanel" @click="showAdPacketPanel = false">
                        <view class="ad-packet-popup" @click.stop>
                                <view class="popup-header">
                                        <text class="popup-title">广告红包</text>
                                        <view class="popup-close" @click="showAdPacketPanel = false">
                                                <u-icon name="close" color="#999" size="36"></u-icon>
                                        </view>
                                </view>
                                <ad-red-packet-list
                                        :list-height="panelHeight"
                                        @claimed="onAdPacketClaimed" />
                        </view>
                </view>

                <!-- 红包领取弹窗（微信红包风格） -->
                <view class="redbag-modal-mask" v-if="showRedbagModal" @click="tryCloseModal">
                        <view class="redbag-modal" @click.stop>
                                <!-- 顶部：发送者信息 -->
                                <view class="rm-sender">
                                        <image class="rm-sender-avatar" :src="(currentRedbag.user && currentRedbag.user.avatar) || '/static/image/avatar.png'" mode="aspectFill"></image>
                                        <text class="rm-sender-name">{{ (currentRedbag.user && currentRedbag.user.nickname) || '系统' }} 发出的红包</text>
                                </view>

                                <!-- 中间：祝福语 + 金额 -->
                                <view class="rm-body">
                                        <text class="rm-wish">恭喜发财，大吉大利</text>
                                        <!-- 加载中 -->
                                        <view v-if="isLoadingAmount" class="rm-amount-wrap">
                                                <text class="rm-amount-loading">获取中...</text>
                                        </view>
                                        <!-- 有金额 -->
                                        <view v-else class="rm-amount-wrap">
                                                <text class="rm-amount-num">{{ displayAmount }}</text>
                                                <text class="rm-amount-unit">金币</text>
                                        </view>
                                </view>

                                <!-- 底部：提示 + 开按钮 -->
                                <view class="rm-footer">
                                        <!-- 未领取：显示开按钮 -->
                                        <view v-if="!isClaimed && !isExpired && currentAmount > 0" class="rm-open-btn" @click="claimRedbag">
                                                <text class="rm-open-text">开</text>
                                        </view>
                                        <!-- 已领取 -->
                                        <text v-else-if="isClaimed" class="rm-footer-hint">已领取</text>
                                        <!-- 已过期 -->
                                        <text v-else-if="isExpired" class="rm-footer-hint">已过期</text>
                                        <!-- 加载中/失败 -->
                                        <text v-else class="rm-footer-hint">{{ isLoadingAmount ? '获取中...' : '领取失败' }}</text>
                                </view>

                                <!-- 关闭按钮 -->
                                <view class="rm-close" @click="tryCloseModal">
                                        <text class="rm-close-text">关闭</text>
                                </view>
                        </view>
                </view>

                <!-- ★ 待释放金币领取弹窗（微信红包风格） -->
                <view class="freeze-modal-mask" v-if="showFreezeBagModal" @click="closeFreezeBagModal">
                        <view class="freeze-modal" @click.stop>
                                <!-- 顶部：发送者信息 -->
                                <view class="rm-sender">
                                        <text class="rm-sender-emoji">🧧</text>
                                        <text class="rm-sender-name">广告收益红包</text>
                                </view>

                                <!-- 中间：祝福语 + 金额 -->
                                <view class="rm-body">
                                        <text class="rm-wish">恭喜发财，大吉大利</text>
                                        <view class="rm-amount-wrap">
                                                <text class="rm-amount-num">{{ freezeClaimed ? freezeClaimAmount : freezeSnapshotAmount }}</text>
                                                <text class="rm-amount-unit">金币</text>
                                        </view>
                                        <text class="rm-desc">{{ freezeDesc }}</text>
                                </view>

                                <!-- 底部：操作按钮 -->
                                <view class="rm-footer">
                                        <!-- 领取中 -->
                                        <text v-if="freezeClaiming" class="rm-footer-hint">领取中...</text>
                                        <!-- 已领取 -->
                                        <text v-else-if="freezeClaimed" class="rm-footer-hint">已领取</text>
                                        <!-- 从观看视频返回：手动领取 -->
                                        <view v-else-if="showFreezeClaimButton" class="rm-open-btn rm-claim-btn" @click="claimFreezeBalance">
                                                <text class="rm-open-text">领取红包</text>
                                        </view>
                                        <!-- 未观看视频 -->
                                        <view v-else class="rm-open-btn" @click="goFreezeClaim">
                                                <text class="rm-open-text">开</text>
                                        </view>
                                </view>

                                <!-- 关闭按钮 -->
                                <view class="rm-close" @click="closeFreezeBagModal">
                                        <text class="rm-close-text">{{ freezeClaimed ? '关闭' : '取消' }}</text>
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
import AdFeedMessage from '@/components/chat/adFeedMessage.vue'
import RewardedVideoMessage from '@/components/chat/rewardedVideoMessage.vue'
import AdRedPacketList from '@/components/ad/adRedPacketList.vue'

export default {
        components: {
                ChatMessage,
                RedbagMessage,
                TaskMessage,
                AdBanner,
                AdFeedMessage,
                RewardedVideoMessage,
                AdRedPacketList,
        },

        async onLoad(opt) {
                console.log('[RedBag] ★ 页面加载 v20250705-c (30s红包推送版)');
                this.groupId = opt.group_id || 'default_group';
                this.user_info = uni.getStorageSync('user_info') || {};

                // ★ 并行加载广告配置和聊天资源，等两者都完成后再生成消息和启动推送
                await Promise.all([
                        this.loadChatResources(),
                        this.loadAdOverview()
                ]);

                // ★ 资源和配置都加载完成后，再生成初始消息
                this.generateInitialMessages();
                // ★ 启动持续推送（此时聊天资源已就绪，feed_adpid 已就绪）
                this.startContinuousPush();
                // ★ 记录页面启动时间，用于激励视频间隔计算（首次需等待一个完整间隔后才推送）
                this.rewardedVideoLastPushTime = Date.now();
                // ★ 启动红包检查轮询（每10秒触发后端结算检查，按 settle_interval 间隔推送红包到信息流）
                this.startAdRedPacketPolling();
                // ★ 页面加载时立即执行一次结算检查和红包推送（不等轮询定时器）
                this.checkAndSettleRedPacket();
                this.pushRedBagToFeedIfNeeded();
                // ★ 启动用户活跃度检测（防止挂机刷广告）
                this.startUserIdleDetection();
        },

        onShow() {
                // 每次显示页面时刷新广告红包摘要
                this.loadAdOverview();
                // ★ 监听广告观看结果事件（用于 freeze_claim 返回后弹出领取界面）
                uni.$on('ad-watch-result', this.onAdWatchResult);
        },

        onHide() {
                // ★ 不在此处移除事件监听！
                // watch.vue 返回前会 uni.$emit('ad-watch-result')，如果此处移除了监听器，
                // 事件将在 onShow 重新注册之前发出，导致事件丢失，红包弹窗无法弹出。
                // 事件监听器仅在 onUnload 中移除即可。
        },

        onUnload() {
                // 停止持续推送（激励视频已合并到持续推送中，无需单独停止）
                this.stopContinuousPush();
                // 停止红包轮询
                this.stopAdRedPacketPolling();
                // 停止用户活跃度检测
                this.stopUserIdleDetection();
                // 离开页面时清理所有红包过期计时器
                this.clearAllRedbagTimers();
                // 清理关闭倒计时
                this.stopCloseLock();
                // 离开页面时重置累加数据
                this.resetRedbag();
                // 移除事件监听
                uni.$off('ad-watch-result', this.onAdWatchResult);
                // 重置待释放金币弹窗状态
                this.showFreezeBagModal = false;
                this.freezeClaimed = false;
                this.freezeClaiming = false;
        },

        data() {
                return {
                        groupId: '',
                        user_info: {},
                        scrollHeight: 0,
                        scrollToId: '',
                        scrollAnchorId: 'anchor-init',
                        messages: [],

                        // 广告配置（从 overview 接口获取）
                        adConfig: {
                                feed_adpid: '',
                                feed_ad_count: 3,
                                reward_per_feed: 50,
                                ad_income_enabled: 0,
                                // ★ 激励视频广告配置
                                rewarded_video_adpid: '',
                                reward_per_video: 200,
                                rewarded_video_interval: 120,  // 激励视频推送间隔（秒）
                        },

                        // 在线人数（模拟显示，不依赖WebSocket）
                        onlineCount: 128 + Math.floor(Math.random() * 200),

                        // 广告红包面板
                        showAdPacketPanel: false,
                        adPacketBadge: 0,
                        panelHeight: 300,

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

                        // ★ 持续推送相关
                        pushTimer: null,                  // 持续推送定时器
                        pushCounter: 0,                   // 推送计数（用于判断何时插广告）
                        pushChatCountBeforeAd: 0,          // 下一批聊天消息还需要推几条才插广告
                        maxMessages: 150,                 // 消息列表最大保留数量（防止内存溢出）

                        // ★ 激励视频推送（已合并到持续推送逻辑中）
                        rewardedVideoLastPushTime: 0,     // 上次推送激励视频的时间戳

                        // ★ 聊天资源（从后端 API 获取，带版本缓存）
                        chatNames: [],             // 系统用户列表 [{nickname, avatar}]
                        chatMsgs: [],              // 聊天消息模板列表 [string]
                        chatResourcesVersion: 0,   // 当前本地缓存的版本号

                        // ★ 广告浏览进度（从 overview 接口获取）
                        feedViewProgress: null,
                        rewardViewProgress: null,

                        // ★ 用户活跃度检测（防止挂机刷广告）
                        lastUserActivityTime: Date.now(),   // 用户上次操作时间戳
                        adIdleTimeout: 30,                  // 空闲超时（秒），从后端配置读取
                        userIdleCheckTimer: null,           // 空闲检测定时器
                        isUserIdle: false,                  // 用户是否处于不活跃状态

                        // ★ 广告红包轮询
                        adRedPacketPollTimer: null,        // 红包轮询定时器

                        // ★ 红包定期推送（随机5-30秒间隔在信息流中插入红包消息）
                        settleInterval: 30,              // 红包结算间隔（分钟），从后端配置读取（仅用于后端结算）
                        redBagLastPushTime: 0,           // 上次推送红包到信息流的时间戳
                        redBagNextInterval: 0,          // 下次推送的随机间隔（毫秒）

                        // ★ 待释放金币红包
                        freezeBalance: 0,              // 当前待释放金币余额
                        showFreezeBagModal: false,       // 是否显示待领取红包弹窗
                        freezeClaiming: false,           // 是否正在领取中
                        freezeClaimed: false,            // 是否已领取
                        freezeClaimAmount: 0,            // 领取到的金额
                        showFreezeClaimButton: false,    // 是否显示"直接领取"按钮（从观看视频返回时为true）
                        freezeSnapshotAmount: 0,         // ★ 点击红包时的快照金额（固定不变）
                };
        },

        computed: {
                displayAmount() {
                        return Number(this.currentAmount) || 0;
                },
                isExpired() {
                        return this.currentRedbag && this.currentRedbag.status === 'expired';
                },
                freezeDesc() {
                        if (this.freezeClaimed) return '已转入可提现金币余额';
                        if (this.freezeClaiming) return '领取中，请稍候...';
                        if (this.showFreezeClaimButton) return '点击下方按钮领取红包，将待释放金币转为可提现金币';
                        return '点一次能获得一次金币';
                }
        },

        mounted() {
                this.$nextTick(() => {
                        this.calcScrollHeight();
                });
        },

        methods: {
                // ==================== ★ 用户活跃度检测（防挂机） ====================

                /**
                 * ★ 用户操作回调（touchstart/touchmove/click 触发）
                 * 记录最后操作时间，如果从不活跃恢复，自动关闭提示
                 */
                onUserActivity() {
                        this.lastUserActivityTime = Date.now();
                        if (this.isUserIdle) {
                                this.isUserIdle = false;
                                console.log('[RedBag] 用户恢复活跃，广告计费已恢复');
                        }
                },

                /**
                 * ★ 启动用户活跃度检测定时器
                 * 每5秒检查一次，如果用户超过 adIdleTimeout 秒未操作则标记为不活跃
                 */
                startUserIdleDetection() {
                        if (this.userIdleCheckTimer) return;
                        this.userIdleCheckTimer = setInterval(() => {
                                const idleMs = (this.adIdleTimeout || 30) * 1000;
                                if (Date.now() - this.lastUserActivityTime > idleMs) {
                                        if (!this.isUserIdle) {
                                                this.isUserIdle = true;
                                                console.log('[RedBag] 用户不活跃超过' + this.adIdleTimeout + '秒，广告计费已暂停');
                                        }
                                }
                        }, 5000);
                        console.log('[RedBag] 用户活跃度检测已启动，超时=' + this.adIdleTimeout + '秒');
                },

                /**
                 * ★ 停止用户活跃度检测
                 */
                stopUserIdleDetection() {
                        if (this.userIdleCheckTimer) {
                                clearInterval(this.userIdleCheckTimer);
                                this.userIdleCheckTimer = null;
                                console.log('[RedBag] 用户活跃度检测已停止');
                        }
                },

                // ==================== ★ 广告收益回调 ====================

                /**
                 * 广告信息流获得奖励后的回调
                 * @param {Object} data { message, amount, logId }
                 */

                handleAdRewarded(data) {
                        console.log('[RedBag] 广告奖励回调:', JSON.stringify(data));

                        // 更新消息状态
                        if (data.message) {
                                this.$set(data.message, 'adRewarded', true);
                                this.$set(data.message, 'adRewardAmount', data.amount);
                        }

                        // 刷新广告红包摘要（可能自动生成了新红包，触发结算检查）
                        this.loadAdOverview();

                        // ★ 新流程：信息流广告展示即计费，不显示金币系统消息
                        // 只有激励视频完成时才显示提示（因为激励视频需要用户主动操作）
                        if (data.amount > 0 && !data.silent) {
                                const adType = data.adType || 'feed';
                                const adTypeText = adType === 'reward' ? '观看激励视频' : '浏览信息流广告';
                                this.messages.push({
                                        id: 'ad_reward_' + Date.now(),
                                        type: 'system',
                                        content: adTypeText + '获得 +' + data.amount + ' 金币，已存入待释放余额',
                                        time: Date.now(),
                                        sender: 'system'
                                });
                                this.scrollToBottom();
                        }
                },

                /**
                 * 加载广告收益概览
                 */
                async loadAdOverview() {
                        try {
                                const res = await this.$api.adOverview({});
                                console.log('[RedBag] overview 响应: code=' + (res ? res.code : 'null') + ', dataType=' + typeof (res && res.data));
                                if (res && res.code === 1 && res.data) {
                                        // ★ 后端已关闭加密，res.data 直接是对象
                                        const data = (typeof res.data === 'object') ? res.data : null;
                                        if (!data) {
                                                console.warn('[RedBag] overview 数据格式异常, raw:', typeof res.data, String(res.data).substring(0, 100));
                                                return;
                                        }

                                        this.adPacketBadge = data.unclaimed_packet_count || 0;

                                        // ★ 保存广告配置供持续推送使用
                                        this.adConfig.feed_adpid = data.feed_adpid || '';
                                        this.adConfig.feed_ad_count = data.feed_ad_count || 3;
                                        this.adConfig.reward_per_feed = data.reward_per_feed || 50;
                                        this.adConfig.ad_income_enabled = data.ad_income_enabled || 0;

                                        // ★ 保存激励视频广告配置
                                        this.adConfig.rewarded_video_adpid = data.rewarded_video_adpid || '';
                                        this.adConfig.reward_per_video = data.reward_per_video || 200;
                                        this.adConfig.rewarded_video_interval = data.rewarded_video_interval || 120;

                                        // ★ 保存广告浏览进度
                                        this.feedViewProgress = data.feed_view_progress || null;
                                        this.rewardViewProgress = data.reward_view_progress || null;

                                        // ★ 保存待释放金币余额
                                        this.freezeBalance = data.ad_freeze_balance || 0;

                                        // ★ 保存红包结算间隔（分钟），用于红包定期提醒
                                        this.settleInterval = data.settle_interval || 30;

                                        // ★ 保存广告空闲超时（秒），用户不操作超过此时间不计费
                                        this.adIdleTimeout = data.ad_idle_timeout || 30;

                                        console.log('[RedBag] 广告配置加载成功, feed_adpid=' + this.adConfig.feed_adpid + ', rewarded_video_adpid=' + this.adConfig.rewarded_video_adpid + ', enabled=' + this.adConfig.ad_income_enabled);

                                        // 检查广告配置并提示
                                        this.checkAdConfigAndHint();
                                } else {
                                        console.warn('[RedBag] overview 接口返回异常:', res ? JSON.stringify(res).substring(0, 200) : 'null response');
                                }
                        } catch (e) {
                                console.warn('[RedBag] 加载广告配置失败:', e.message || e);
                        }
                },

                // ==================== ★ 聊天资源管理 ====================

                /**
                 * ★ 加载聊天资源（系统用户 + 消息模板）
                 * 带版本号缓存：客户端版本与服务端一致时直接使用本地缓存
                 */
                async loadChatResources() {
                        try {
                                // 读取本地缓存版本
                                const cached = uni.getStorageSync('chat_resources') || {};
                                const localVersion = cached.version || 0;

                                const res = await this.$api.chatResources({ version: localVersion });
                                if (res && res.code === 1 && res.data) {
                                        if (res.data.updated && res.data.users && res.data.messages) {
                                                // 服务端数据有更新 → 解析并缓存到本地
                                                const names = (res.data.users || []).map(u => ({
                                                        nickname: u.nickname || ('用户' + u.id),
                                                        avatar: u.avatar || '/static/image/avatar.png'
                                                }));
                                                const msgs = (res.data.messages || [])
                                                        .map(m => m.description)
                                                        .filter(d => d && d.trim());

                                                const cacheData = {
                                                        version: res.data.version,
                                                        names: names,
                                                        msgs: msgs,
                                                        updateTime: Date.now()
                                                };
                                                uni.setStorageSync('chat_resources', cacheData);

                                                this.chatNames = names;
                                                this.chatMsgs = msgs;
                                                this.chatResourcesVersion = res.data.version;
                                                console.log('[RedBag] 聊天资源已更新, users=' + names.length + ', msgs=' + msgs.length + ', version=' + res.data.version);
                                        } else {
                                                // 版本未变化 → 使用本地缓存
                                                if (cached.names && cached.names.length > 0) {
                                                        this.chatNames = cached.names;
                                                        this.chatMsgs = cached.msgs || [];
                                                        this.chatResourcesVersion = cached.version;
                                                        console.log('[RedBag] 使用本地缓存聊天资源, users=' + cached.names.length);
                                                } else {
                                                        // 本地缓存为空 → 使用兜底数据
                                                        this._useFallbackChatData();
                                                }
                                        }
                                } else {
                                        // 接口失败 → 尝试本地缓存
                                        const cached = uni.getStorageSync('chat_resources') || {};
                                        if (cached.names && cached.names.length > 0) {
                                                this.chatNames = cached.names;
                                                this.chatMsgs = cached.msgs || [];
                                        } else {
                                                this._useFallbackChatData();
                                        }
                                }
                        } catch (e) {
                                console.warn('[RedBag] 加载聊天资源失败:', e);
                                // 异常 → 使用本地缓存或兜底数据
                                const cached = uni.getStorageSync('chat_resources') || {};
                                if (cached.names && cached.names.length > 0) {
                                        this.chatNames = cached.names;
                                        this.chatMsgs = cached.msgs || [];
                                } else {
                                        this._useFallbackChatData();
                                }
                        }
                },

                /**
                 * ★ 兜底聊天数据（本地缓存和接口都不可用时使用）
                 */
                _useFallbackChatData() {
                        this.chatNames = [
                                { nickname: '小明的妈妈', avatar: '/static/image/avatar.png' },
                                { nickname: '赚钱达人', avatar: '/static/image/avatar.png' },
                                { nickname: '金币猎手', avatar: '/static/image/avatar.png' },
                                { nickname: '福利小能手', avatar: '/static/image/avatar.png' },
                                { nickname: '幸运星', avatar: '/static/image/avatar.png' },
                        ];
                        this.chatMsgs = [
                                '今天又赚了不少金币 💰', '有没有人一起领红包呀',
                                '看广告真的能赚钱！', '每天来签到领红包',
                                '这个平台太良心了', '刚提现了，速度很快',
                        ];
                        console.warn('[RedBag] 使用兜底聊天数据');
                },

                /**
                 * ★ 根据昵称从 chatNames 中获取头像
                 */
                getAvatarForNickname(nickname) {
                        if (!nickname) return '/static/image/avatar.png';

                        const user = this.chatNames.find(n => n.nickname === nickname);
                        if (user) return user.avatar;

                        return '/static/image/avatar.png';
                },

                /**
                 * ★ 从 chatNames 中随机选一个用户（用于广告头像）
                 */
                getRandomAdAvatar() {
                        if (this.chatNames && this.chatNames.length > 0) {
                                return this.chatNames[Math.floor(Math.random() * this.chatNames.length)].avatar;
                        }
                        return '/static/image/avatar.png';
                },

                /**
                 * ★ 从 chatNames 中随机选一个昵称
                 */
                getRandomNickname() {
                        if (this.chatNames && this.chatNames.length > 0) {
                                return this.chatNames[Math.floor(Math.random() * this.chatNames.length)].nickname;
                        }
                        return '匿名用户';
                },

                /**
                 * ★ 从 chatMsgs 中随机选一条消息
                 */
                getRandomChatMsg() {
                        if (this.chatMsgs && this.chatMsgs.length > 0) {
                                return this.chatMsgs[Math.floor(Math.random() * this.chatMsgs.length)];
                        }
                        return '大家好！';
                },

                // ==================== ★ 信息流广告消息管理 ====================

                /**
                 * 生成初始消息（系统欢迎语 + 几条初始聊天消息）
                 */
                generateInitialMessages() {
                        this.messages.push({
                                id: 'welcome_' + Date.now(),
                                type: 'system',
                                content: '欢迎来到红包群，观看广告即可赚金币！',
                                time: Date.now(),
                                sender: 'system'
                        });

                        // ★ 从后端获取的用户/消息中随机生成初始聊天消息
                        const count = 3 + Math.floor(Math.random() * 3);
                        for (let i = 0; i < count; i++) {
                                this.messages.push(this.createFakeChatMessage());
                        }

                        // 初始化推送计数：随机 2-4 条聊天后插一条广告
                        this.pushChatCountBeforeAd = 2 + Math.floor(Math.random() * 3);
                        this.pushCounter = 0;

                        this.$nextTick(() => {
                                this.scrollToBottom();
                        });
                },

                /**
                 * ★ 启动持续推送定时器
                 * 模拟聊天群消息流：每隔 2-5 秒推一条，每 2-4 条聊天后插一条信息流广告
                 * 不会停止，直到页面卸载
                 */
                startContinuousPush() {
                        // 防止重复启动
                        if (this.pushTimer) return;

                        // 每次推送间隔 2-5 秒随机
                        const scheduleNext = () => {
                                const delay = 2000 + Math.random() * 3000;
                                this.pushTimer = setTimeout(() => {
                                        this.doPushOneMessage();
                                        scheduleNext(); // 递归调度，永不停止
                                }, delay);
                        };

                        // 延迟 1 秒后开始第一次推送（让初始消息先渲染完）
                        this.pushTimer = setTimeout(() => {
                                this.doPushOneMessage();
                                scheduleNext();
                        }, 1000);

                        console.log('[RedBag] 持续推送已启动');
                },

                /**
                 * 停止持续推送
                 */
                stopContinuousPush() {
                        if (this.pushTimer) {
                                clearTimeout(this.pushTimer);
                                this.pushTimer = null;
                                console.log('[RedBag] 持续推送已停止');
                        }
                },

                /**
                 * 推送一条消息（聊天或广告，根据计数器决定）
                 */
                doPushOneMessage() {
                        this.pushCounter++;

                        // 判断是否该推广告了
                        if (this.pushCounter >= this.pushChatCountBeforeAd) {
                                console.log('[RedBag] ★ 到达广告位, pushCounter=' + this.pushCounter + ', threshold=' + this.pushChatCountBeforeAd);
                                // 重置计数器，随机 2-4 条后下次再推广告
                                this.pushCounter = 0;
                                this.pushChatCountBeforeAd = 2 + Math.floor(Math.random() * 3);

                                // ★ 优先判断是否到了推送激励视频的时间
                                if (this.shouldPushRewardedVideo()) {
                                        const videoMsg = this.createRewardedVideoMessage(
                                                this.adConfig.rewarded_video_adpid,
                                                this.adConfig.reward_per_video || 200
                                        );
                                        this.messages.push(videoMsg);
                                        console.log('[RedBag] 推送激励视频广告, adpid=' + this.adConfig.rewarded_video_adpid + ', 奖励=' + (this.adConfig.reward_per_video || 200) + '金币');
                                } else {
                                        // ★ 无论是否配置了 adpid，都推送信息流广告卡片
                                        // adpid 仅在用户点击播放时才需要，卡片 UI 本身不依赖它
                                        const adMsg = this.createAdFeedMessage(
                                                this.adConfig.feed_adpid,
                                                this.adConfig.reward_per_feed || 50,
                                                Date.now()
                                        );
                                        this.messages.push(adMsg);
                                        console.log('[RedBag] 推送信息流广告, adpid=' + (this.adConfig.feed_adpid || '(未配置)'));
                                }
                        } else {
                                // ★ 推送普通聊天消息（从后端资源中随机选取）
                                this.messages.push(this.createFakeChatMessage());
                        }

                        // ★ 性能保护：限制消息列表最大数量，超出时删除最旧的消息
                        if (this.messages.length > this.maxMessages) {
                                const removeCount = this.messages.length - this.maxMessages;
                                this.messages.splice(0, removeCount);
                        }

                        // 随机更新在线人数（模拟波动）
                        if (Math.random() > 0.7) {
                                this.onlineCount += Math.floor(Math.random() * 11) - 5; // -5 ~ +5
                                this.onlineCount = Math.max(50, this.onlineCount);
                        }

                        this.scrollToBottom();
                },

                // ==================== ★ 激励视频推送管理 ====================

                /**
                 * ★ 判断是否到了推送激励视频的时间
                 * 每隔 rewarded_video_interval 秒（默认120秒）在信息流广告位置插入一条激励视频
                 */
                shouldPushRewardedVideo() {
                        if (!this.adConfig.rewarded_video_adpid) return false;

                        const intervalMs = (this.adConfig.rewarded_video_interval || 120) * 1000;
                        const now = Date.now();

                        // 如果从未推送过，或者距上次推送已超过间隔时间，则该推激励视频
                        if (this.rewardedVideoLastPushTime === 0 || (now - this.rewardedVideoLastPushTime) >= intervalMs) {
                                this.rewardedVideoLastPushTime = now;
                                return true;
                        }
                        return false;
                },

                /**
                 * 创建一条模拟聊天消息
                 */
                createFakeChatMessage() {
                        const nickname = this.getRandomNickname();
                        const content = this.getRandomChatMsg();
                        return {
                                id: 'fake_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                                type: 'text',
                                content: content,
                                time: Date.now(),
                                sender: nickname,
                                user: {
                                        nickname: nickname,
                                        avatar: this.getAvatarForNickname(nickname)
                                }
                        };
                },

                /**
                 * 创建一条信息流广告消息对象
                 */
                createAdFeedMessage(adpid, rewardCoin, index) {
                        const nick = this.getRandomNickname();
                        return {
                                id: 'ad_feed_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                                type: 'ad_feed',
                                time: Date.now(),
                                sender: 'system',
                                user: {
                                        nickname: nick,
                                        avatar: this.getAvatarForNickname(nick)
                                },
                                taskData: {
                                        adpid: adpid,
                                        reward_coin: rewardCoin,
                                        resource: {
                                                adpid: adpid
                                        }
                                }
                        };
                },

                /**
                 * 创建一条激励视频广告消息对象
                 */
                createRewardedVideoMessage(adpid, rewardCoin) {
                        const nick = this.getRandomNickname();
                        return {
                                id: 'rewarded_video_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                                type: 'rewarded_video',
                                time: Date.now(),
                                sender: 'system',
                                user: {
                                        nickname: nick,
                                        avatar: this.getAvatarForNickname(nick)
                                },
                                taskData: {
                                        adpid: adpid,
                                        reward_coin: rewardCoin,
                                        cooldown: this.adConfig.rewarded_video_interval || 120,
                                        resource: {
                                                adpid: adpid
                                        }
                                }
                        };
                },



                /**
                 * 切换广告红包面板
                 */
                toggleAdPacketPanel() {
                        this.showAdPacketPanel = !this.showAdPacketPanel;
                        if (this.showAdPacketPanel) {
                                this.adPacketBadge = 0;
                        }
                },

                // ==================== ★ 广告红包轮询推送 ====================

                /**
                 * ★ 结算检查 + 红包推送（合并为一个方法）
                 * 每10秒执行一次：
                 *   Step 1: 调用 /api/ad/checkSettle → 检查冻结余额是否达到阈值，达到则自动生成红包
                 *   Step 2: 检查是否到了 settle_interval 间隔，有红包则推送到信息流
                 */
                async checkAndSettleRedPacket() {
                        try {
                                // 触发后端结算检查（检查 freeze_balance 是否达到阈值自动生成红包）
                                try {
                                        const settleRes = await this.$api.adCheckSettle({});
                                        if (settleRes && settleRes.code === 1 && settleRes.data && settleRes.data.redpacket_created) {
                                                console.log('[RedBag] 后端自动生成红包: ' + (settleRes.data.redpacket_amount || 0) + ' 金币');
                                                // 刷新概览数据（更新 freezeBalance、adPacketBadge 等）
                                                this.loadAdOverview();
                                        }
                                } catch (e) {
                                        // 静默处理
                                }

                                // 检查是否到了推送红包到信息流的时间
                                this.pushRedBagToFeedIfNeeded();
                        } catch (e) {
                                // 静默处理
                        }
                },

                /**
                 * 启动广告红包轮询
                 */
                startAdRedPacketPolling() {
                        if (this.adRedPacketPollTimer) return;
                        this.adRedPacketPollTimer = setInterval(() => {
                                this.checkAndSettleRedPacket();
                        }, 10000); // 每10秒触发一次结算检查
                        console.log('[RedBag] 广告红包轮询已启动');
                },

                /**
                 * 停止广告红包轮询
                 */
                stopAdRedPacketPolling() {
                        if (this.adRedPacketPollTimer) {
                                clearInterval(this.adRedPacketPollTimer);
                                this.adRedPacketPollTimer = null;
                                console.log('[RedBag] 广告红包轮询已停止');
                        }
                },

                /**
                 * ★ 检查是否需要推送红包到信息流
                 * 每次推送后随机取5-30秒作为下次推送间隔
                 * 无条件推送，只要到了间隔时间就推送
                 */
                pushRedBagToFeedIfNeeded() {
                        const now = Date.now();

                        // 检查是否到了推送时间
                        if (this.redBagLastPushTime > 0 && (now - this.redBagLastPushTime) < this.redBagNextInterval) {
                                return;
                        }

                        // 更新推送时间
                        this.redBagLastPushTime = now;

                        // ★ 随机生成下次推送间隔（5-30秒）
                        this.redBagNextInterval = (5 + Math.random() * 25) * 1000;
                        console.log('[RedBag] 红包已推送，下次间隔=' + Math.round(this.redBagNextInterval / 1000) + '秒');

                        // 推送红包消息到信息流
                        this.pushRedBagToFeed();
                },

                /**
                 * ★ 推送一条红包消息到信息流
                 * 使用随机的系统用户头像和昵称，5秒后自动过期（模拟群内被抢完）
                 * 过期后下次 settle_interval 间隔到达时会继续推送新红包
                 */
                pushRedBagToFeed() {
                        const nickname = this.getRandomNickname();
                        const amount = this.freezeBalance > 0 ? this.freezeBalance : 0;

                        // 文案：如果有待释放余额就显示金额，否则只提示有红包
                        const content = amount > 0
                                ? '待释放金币 ' + amount + ' 个，快来领取吧！🧧'
                                : '有新红包待领取，手慢无！🧧';

                        const redbagMsg = {
                                id: 'redbag_feed_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                                type: 'redbag',
                                content: content,
                                time: Date.now(),
                                sender: nickname,
                                user: {
                                        nickname: nickname,
                                        avatar: this.getAvatarForNickname(nickname)
                                },
                                status: 'unopened',
                                amount: amount,
                                backgroundImage: '/static/image/redbag-icon.png',
                                displayTitle: '待释放金币红包',
                                taskData: {
                                        taskId: 0,
                                        packetId: 0,
                                        isAdRedPacket: true,
                                }
                        };

                        this.messages.push(redbagMsg);
                        console.log('[RedBag] 红包已推送到信息流, freezeBalance=' + this.freezeBalance + ', badge=' + this.adPacketBadge);

                        // ★ 5秒后自动标记为"已领完"（模拟其他群成员抢完），下次轮询会继续推送新红包
                        const msgId = redbagMsg.id;
                        setTimeout(() => {
                                const msg = this.messages.find(m => m.id === msgId);
                                if (msg && msg.status === 'unopened') {
                                        this.$set(msg, 'status', 'expired');
                                        console.log('[RedBag] 红包已过期, id=' + msgId);
                                }
                        }, 5000);

                        this.scrollToBottom();
                },

                // ==================== ★ 广告配置状态提示 ====================

                /**
                 * 检查广告是否可用并给出提示
                 * 如果没有配置 adpid，在消息列表显示提示
                 */
                checkAdConfigAndHint() {
                        if (!this.adConfig.feed_adpid && this.adConfig.ad_income_enabled) {
                                // 广告已开启但未配置 adpid
                                const hintExists = this.messages.some(m => m.id === 'hint_no_adpid');
                                if (!hintExists) {
                                        this.messages.push({
                                                id: 'hint_no_adpid',
                                                type: 'system',
                                                content: '⚠️ 管理员尚未配置信息流广告位ID(adpid)，请联系管理员在后台「广告配置」中填写',
                                                time: Date.now(),
                                                sender: 'system'
                                        });
                                        this.scrollToBottom();
                                }
                        }
                },

                /**
                 * 广告红包领取成功回调
                 */
                onAdPacketClaimed(data) {
                        console.log('[RedBag] 广告红包领取成功:', JSON.stringify(data));
                        if (data.amount > 0) {
                                uni.showToast({
                                        title: '领取成功 +' + data.amount + ' 金币',
                                        icon: 'none',
                                        duration: 2000
                                });
                        }
                },

                // ==================== ★ 待释放金币领取 ====================

                /**
                 * 打开待领取红包弹窗
                 * @param {boolean} showClaimButton - 是否显示"直接领取"按钮（从观看视频返回时显示）
                 */
                openFreezeBagModal(showClaimButton = false) {
                        if (this.freezeBalance <= 0) {
                                uni.showToast({ title: '暂无可领取的金币', icon: 'none' });
                                return;
                        }
                        this.freezeClaimed = false;
                        this.freezeClaiming = false;
                        this.freezeClaimAmount = 0;
                        this.showFreezeClaimButton = showClaimButton;
                        // ★ 如果不是从观看视频返回（即首次打开），快照当前冻结余额
                        if (!showClaimButton) {
                                this.freezeSnapshotAmount = this.freezeBalance;
                        }
                        this.showFreezeBagModal = true;
                },

                /**
                 * 关闭待领取红包弹窗
                 */
                closeFreezeBagModal() {
                        this.showFreezeBagModal = false;
                        if (this.freezeClaimed) {
                                // 领取成功后刷新数据
                                this.freezeClaimed = false;
                                this.loadAdOverview();
                        }
                },

                /**
                 * ★ 监听广告观看结果事件
                 * freeze_claim: 观看页已自动领取，弹窗显示领取结果
                 */
                onAdWatchResult(data) {
                        console.log('[RedBag] 收到 ad-watch-result 事件:', JSON.stringify(data));
                        if (data && data.adType === 'freeze_claim') {
                                const extra = data.progress || {};

                                // ★ 新流程：观看完成返回，弹出领取红包弹窗，用户手动点击领取
                                if (extra.freezeWatchDone) {
                                        this.freezeSnapshotAmount = extra.freezeSnapshotAmount || this.freezeSnapshotAmount;
                                        // ★ 重置领取状态（弹窗显示"领取红包"按钮）
                                        this.freezeClaimed = false;
                                        this.freezeClaiming = false;
                                        this.freezeClaimAmount = 0;
                                        this.showFreezeClaimButton = true;
                                        this.loadAdOverview().then(() => {
                                                setTimeout(() => {
                                                        this.showFreezeBagModal = true;
                                                }, 500);
                                        });
                                        return;
                                }

                                // ★ 旧流程兼容：观看页已自动领取
                                if (extra.freezeClaimed) {
                                        this.freezeClaimed = true;
                                        this.freezeClaimAmount = extra.freezeClaimAmount || data.amount || 0;
                                        this.loadAdOverview().then(() => {
                                                setTimeout(() => {
                                                        this.showFreezeClaimButton = false;
                                                        this.showFreezeBagModal = true;
                                                }, 500);
                                        });
                                } else if (data.success) {
                                        this.loadAdOverview().then(() => {
                                                setTimeout(() => {
                                                        this.openFreezeBagModal(true);
                                                }, 500);
                                        });
                                }
                        }
                },

                /**
                 * ★ 在红包页面直接领取待释放金币（调用 claimFreezeBalance API）
                 */
                async claimFreezeBalance() {
                        if (this.freezeClaiming || this.freezeClaimed) return;
                        this.freezeClaiming = true;

                        try {
                                const transactionId = 'fc_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                                const params = {
                                        transaction_id: transactionId,
                                };
                                // ★ 传入快照金额，只领取点击红包时的金额，不领取期间新增的金币
                                if (this.freezeSnapshotAmount > 0) {
                                        params.max_amount = this.freezeSnapshotAmount;
                                }
                                const res = await this.$api.adClaimFreezeBalance(params);

                                console.log('[RedBag] claimFreezeBalance 返回:', JSON.stringify(res));
                                if (res && res.code === 1 && res.data) {
                                        this.freezeClaimed = true;
                                        this.freezeClaimAmount = res.data.amount || 0;
                                        uni.showToast({ title: '🎉 领取成功 +' + this.freezeClaimAmount + ' 金币', icon: 'none', duration: 2000 });
                                } else {
                                        this.freezeClaiming = false;
                                        const msg = (res && res.msg) || '领取失败';
                                        uni.showToast({ title: msg, icon: 'none' });
                                }
                        } catch (e) {
                                this.freezeClaiming = false;
                                console.error('[RedBag] claimFreezeBalance 异常:', JSON.stringify(e));
                                uni.showToast({ title: '网络异常，请重试', icon: 'none' });
                        }
                },

                /**
                 * 跳转到观看激励视频页面领取冻结金币
                 */
                goFreezeClaim() {
                        this.showFreezeBagModal = false;
                        // ★ 快照当前冻结余额，传给观看页，观看完成后用此金额领取
                        this.freezeSnapshotAmount = this.freezeBalance;
                        const params = {
                                type: 'freeze_claim',
                                rewardCoin: this.freezeSnapshotAmount,
                                watchSeconds: 30,
                                msgId: 'freeze_' + Date.now(),
                                freezeSnapshotAmount: this.freezeSnapshotAmount,
                        };
                        const query = Object.keys(params).map(k => k + '=' + params[k]).join('&');
                        uni.navigateTo({
                                url: '/pages/ad/watch?' + query,
                                fail: (err) => {
                                        console.error('[RedBag] 跳转领取页失败:', err);
                                        uni.showToast({ title: '页面跳转失败', icon: 'none' });
                                }
                        });
                },

                // ==================== 红包自动过期机制 ====================

                /**
                 * 为新到达的红包启动自动过期计时器
                 */
                startRedbagExpireTimer(msgId) {
                        const delay = 2000 + Math.random() * 3000;
                        const timerId = setTimeout(() => {
                                const msg = this.messages.find(m => m.id === msgId);
                                if (msg && msg.status === 'unopened') {
                                        this.$set(msg, 'status', 'expired');
                                }
                                delete this.redbagTimers[msgId];
                        }, delay);
                        this.redbagTimers[msgId] = timerId;
                },

                cancelRedbagExpireTimer(msgId) {
                        if (this.redbagTimers[msgId]) {
                                clearTimeout(this.redbagTimers[msgId]);
                                delete this.redbagTimers[msgId];
                        }
                },

                clearAllRedbagTimers() {
                        Object.keys(this.redbagTimers).forEach(msgId => {
                                clearTimeout(this.redbagTimers[msgId]);
                        });
                        this.redbagTimers = {};
                },

                // ==================== 红包点击 ====================

                /**
                 * 用户点击红包卡片 → 打开弹窗 → 调用 click 接口
                 */
                handleRedbagClick(msg) {
                        if (msg.status === 'expired') {
                                uni.showToast({ title: '已过期', icon: 'none' });
                                return;
                        }

                        if (msg.status === 'claimed') {
                                this.currentRedbag = msg;
                                this.currentAmount = msg.claimedAmount || msg.currentAmount || 0;
                                this.isClaimed = true;
                                this.isLoadingAmount = false;
                                this.currentMsgRef = msg;
                                this.showRedbagModal = true;
                                this.startCloseLock();
                                return;
                        }

                        // ★ 广告红包（通知）：打开待释放金币弹窗，查看 freeze_balance 并领取
                        const taskData = msg.taskData || {};
                        if (taskData.isAdRedPacket) {
                                // ★ 新流程：点击红包 → 显示当前 ad_freeze_balance 金额
                                // 用户点击"观看视频领取" → 跳转激励视频 → claimFreezeBalance() → balance
                                this.cancelRedbagExpireTimer(msg.id);
                                this.openFreezeBagModal();
                                return;
                        }

                        this.cancelRedbagExpireTimer(msg.id);

                        this.currentRedbag = msg;
                        this.currentAmount = 0;
                        this.isClaimed = false;
                        this.isLoadingAmount = true;
                        this.currentMsgRef = msg;
                        this.showRedbagModal = true;
                        this.startCloseLock();

                        const taskId = (msg.taskData && msg.taskData.taskId) || 0;
                        if (taskId) {
                                this.doClickOnce(msg.id, taskId);
                        } else {
                                this.isLoadingAmount = false;
                        }
                },

                async doClickOnce(msgId, taskId) {
                        try {
                                const res = await this.$api.redpacketClick({ task_id: taskId, reset: 0 });
                                if (res && res.code === 1 && res.data) {
                                        const amount = res.data.total_amount || 0;
                                        this.currentAmount = amount;
                                        this.isLoadingAmount = false;

                                        const msg = this.messages.find(m => m.id === msgId);
                                        if (msg) {
                                                this.$set(msg, 'currentAmount', amount);
                                                this.$set(msg, 'claimedAmount', amount);
                                                this.$set(msg, 'status', 'opened');
                                        }
                                } else {
                                        this.isLoadingAmount = false;
                                        console.warn('[RedBag] click接口异常:', res);
                                        this.startRedbagExpireTimer(msgId);
                                }
                        } catch (e) {
                                this.isLoadingAmount = false;
                                console.error('[RedBag] click失败:', e);
                                this.startRedbagExpireTimer(msgId);
                                uni.showToast({ title: '网络异常，请重试', icon: 'none' });
                        }
                },

                // ==================== 红包领取 ====================

                async claimRedbag() {
                        const taskId = (this.currentRedbag.taskData && this.currentRedbag.taskData.taskId) || 0;
                        try {
                                uni.showLoading({ title: '领取中...', mask: true });
                                const res = await this.$api.redpacketClaim({ task_id: taskId });
                                uni.hideLoading();

                                if (res && res.code === 1) {
                                        this.isClaimed = true;

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

                // ==================== 关闭锁 ====================

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
                        if (!this.canCloseModal && !this.isClaimed) return;
                        this.closeRedbagModal();
                },

                closeRedbagModal() {
                        this.showRedbagModal = false;
                        this.isLoadingAmount = false;
                        this.isClaimed = false;
                        this.currentAmount = 0;
                        this.currentRedbag = {};
                        this.currentMsgRef = null;
                        this.stopCloseLock();
                },

                async resetRedbag() {
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

                scrollToBottom() {
                        // 统一使用 UniApp 的 scroll-into-view 机制
                        // 优势：只在 scroll-view 内部滚动，不会触发外层页面滚动导致 header 被推上去
                        // 注意：scrollToId 值必须每次变化，否则 UniApp 不会触发重新滚动
                        const newId = 'anchor-' + Date.now();
                        this.scrollAnchorId = newId;
                        this.$nextTick(() => {
                                this.scrollToId = newId;
                                // 滚动完成后清空，确保下次同 ID 也能触发滚动
                                setTimeout(() => {
                                        this.scrollToId = '';
                                }, 300);
                        });
                },

                showTimeDivider(index) {
                        if (index === 0) return false;
                        const prev = this.messages[index - 1];
                        if (!prev) return false;
                        return (Date.now() - prev.time) > 5 * 60 * 1000;
                },

                formatTime(time) {
                        const d = new Date(time);
                        const h = d.getHours().toString().padStart(2, '0');
                        const m = d.getMinutes().toString().padStart(2, '0');
                        return h + ':' + m;
                },

                playVoice(msg) {
                        // 预留语音播放
                },

                calcScrollHeight() {
                        const sysInfo = uni.getSystemInfoSync();
                        // 用 upx2px 精确转换 rpx 为 px
                        const navH = uni.upx2px(88);
                        const adH = uni.upx2px(200);
                        const tabH = uni.upx2px(120);
                        const statusBarH = sysInfo.statusBarHeight || 0;
                        this.scrollHeight = sysInfo.windowHeight - navH - adH - tabH - statusBarH;
                },

                getHistoryMsg() {
                        // 预留：加载更多历史消息
                },
        }
}
</script>

<style lang="scss" scoped>
/* ★ 用户不活跃提示 */
.idle-overlay {
        position: absolute;
        bottom: 30%;
        left: 50%;
        transform: translateX(-50%);
        z-index: 20;
        pointer-events: none;
        animation: idle-fade-in 0.5s ease;
}

.idle-hint {
        display: flex;
        flex-direction: column;
        align-items: center;
        background: rgba(0, 0, 0, 0.6);
        padding: 24rpx 48rpx;
        border-radius: 40rpx;
        backdrop-filter: blur(10px);
}

.idle-hint-icon {
        font-size: 48rpx;
        margin-bottom: 8rpx;
}

.idle-hint-text {
        font-size: 24rpx;
        color: rgba(255, 255, 255, 0.85);
        font-weight: 500;
}

@keyframes idle-fade-in {
        from { opacity: 0; transform: translateX(-50%) translateY(20rpx); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
}

.page-content {
        height: 100vh;
        background: #f5f5f5;
        display: flex;
        flex-direction: column;
        overflow: hidden;
}

.chat-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
}

/* 底部锚点：增加高度作为最后一条消息与tabbar之间的间距 */
.scroll-bottom-anchor {
        height: 0rpx;
        width: 100%;
        flex-shrink: 0;
}

.custom-navbar {
        height: 88rpx;
        background: #fff;
        display: flex;
        align-items: center;
        padding: 0 20rpx;
        border-bottom: 1rpx solid #eee;
        flex-shrink: 0;
        position: relative;
        z-index: 10;
}

.navbar-content {
        display: flex;
        align-items: center;
        width: 100%;
}

.back-btn {
        width: 60rpx;
}

.group-info {
        flex: 1;
        text-align: center;
}

.group-name {
        font-size: 32rpx;
        font-weight: bold;
        color: #333;
}

.group-count {
        font-size: 24rpx;
        color: #999;
}

.right-btns {
        display: flex;
        align-items: center;
}

.ad-packet-btn {
        position: relative;
        padding: 10rpx 20rpx;
}

.ad-packet-icon {
        font-size: 40rpx;
}

.ad-badge {
        position: absolute;
        top: -5rpx;
        right: 5rpx;
        background: #ff4d4f;
        border-radius: 20rpx;
        min-width: 30rpx;
        height: 30rpx;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 8rpx;
}

.ad-badge-text {
        color: #fff;
        font-size: 20rpx;
}

.withdraw-btn {
        display: flex;
        align-items: center;
        padding: 10rpx 20rpx;
        margin-left: 10rpx;
}

.redbag-icon {
        width: 40rpx;
        height: 40rpx;
}

.withdraw-text {
        font-size: 26rpx;
        color: #ff6b35;
        margin-left: 8rpx;
}

.ad-section {
        height: 200rpx;
        flex-shrink: 0;
}

.message-list {
        flex: 1;
        padding: 20rpx;
        min-height: 0;
        overflow: hidden;
}

.system-message {
        text-align: center;
        padding: 16rpx 0;
        color: #999;
        font-size: 24rpx;
}

.time-divider {
        text-align: center;
        padding: 20rpx 0;
        color: #bbb;
        font-size: 22rpx;
}

.connection-status {
        text-align: center;
        padding: 10rpx;
        font-size: 24rpx;
        color: #999;
}

.connection-status.connected {
        color: #52c41a;
}

/* 广告红包面板 */
.ad-packet-mask {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 999;
        display: flex;
        align-items: flex-end;
}

.ad-packet-popup {
        width: 100%;
        background: #fff;
        border-radius: 24rpx 24rpx 0 0;
        max-height: 70vh;
}

.popup-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 30rpx;
        border-bottom: 1rpx solid #f0f0f0;
}

.popup-title {
        font-size: 32rpx;
        font-weight: bold;
}

.popup-close {
        padding: 10rpx;
}

/* ==================== 微信红包弹窗样式（两个弹窗共用） ==================== */

.redbag-modal-mask,
.freeze-modal-mask {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: center;
}

.freeze-modal-mask {
        z-index: 1001;
}

.redbag-modal,
.freeze-modal {
        width: 560rpx;
        background: linear-gradient(180deg, #E84D3D 0%, #C93A2C 60%, #B33228 100%);
        border-radius: 20rpx;
        overflow: hidden;
        position: relative;
}

/* 顶部发送者信息 */
.rm-sender {
        display: flex;
        align-items: center;
        padding: 36rpx 40rpx 0;
}

.rm-sender-avatar {
        width: 64rpx;
        height: 64rpx;
        border-radius: 8rpx;
        margin-right: 16rpx;
        background: rgba(255,255,255,0.15);
}

.rm-sender-emoji {
        font-size: 48rpx;
        margin-right: 16rpx;
        line-height: 1;
}

.rm-sender-name {
        color: rgba(255, 255, 255, 0.9);
        font-size: 26rpx;
}

/* 中间祝福语 + 金额 */
.rm-body {
        padding: 30rpx 40rpx 20rpx;
        text-align: center;
}

.rm-wish {
        color: #FFE4B5;
        font-size: 34rpx;
        font-weight: 600;
        letter-spacing: 2rpx;
        display: block;
        margin-bottom: 20rpx;
}

.rm-amount-wrap {
        display: flex;
        align-items: baseline;
        justify-content: center;
        margin-bottom: 12rpx;
}

.rm-amount-num {
        color: #FFFFFF;
        font-size: 80rpx;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        line-height: 1;
}

.rm-amount-unit {
        color: rgba(255, 255, 255, 0.8);
        font-size: 26rpx;
        margin-left: 8rpx;
}

.rm-amount-loading {
        color: rgba(255, 255, 255, 0.6);
        font-size: 32rpx;
}

.rm-desc {
        color: rgba(255, 255, 255, 0.75);
        font-size: 22rpx;
        display: block;
}

/* 底部：开按钮 */
.rm-footer {
        padding: 10rpx 40rpx 30rpx;
        display: flex;
        align-items: center;
        justify-content: center;
}

.rm-open-btn {
        width: 100rpx;
        height: 100rpx;
        border-radius: 50%;
        background: linear-gradient(135deg, #FFE4B5 0%, #F5C97E 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.15);
}

/* 领取红包按钮（从观看视频返回时显示，需要更宽的圆角矩形） */
.rm-claim-btn {
        width: auto;
        min-width: 200rpx;
        padding: 0 40rpx;
        border-radius: 50rpx;
}

.rm-claim-btn .rm-open-text {
        font-size: 32rpx;
}

.rm-open-text {
        color: #B33228;
        font-size: 40rpx;
        font-weight: 800;
}

.rm-footer-hint {
        color: rgba(255, 255, 255, 0.7);
        font-size: 28rpx;
}

/* 关闭按钮 */
.rm-close {
        padding: 20rpx 0 30rpx;
        text-align: center;
}

.rm-close-text {
        color: rgba(255, 255, 255, 0.6);
        font-size: 26rpx;
}
</style>

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

                        <!-- 消息列表 -->
                        <!-- #ifdef H5 -->
                        <scroll-view class="message-list" scroll-y
                                :style="{height: scrollHeight + 'px'}">
                        <!-- #endif -->
                        <!-- #ifndef H5 -->
                        <scroll-view class="message-list" scroll-y scroll-with-animation
                                :style="{height: scrollHeight + 'px'}"
                                :scroll-into-view="scrollToId">
                        <!-- #endif -->
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
                                                @ad-rewarded="handleAdRewarded" />

                                        <!-- 激励视频广告消息 -->
                                        <rewarded-video-message
                                                v-if="msg.type === 'rewarded_video'"
                                                :message="msg"
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
                                        <text class="amount-hint" v-else-if="isExpired">
                                                已过期
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

                                <!-- 关闭按钮（5秒内禁用，已领取可立即关闭） -->
                                <view :class="['modal-close', { 'close-disabled': !canCloseModal && !isClaimed }]" @click="tryCloseModal">
                                        <text class="close-text">{{ (canCloseModal || isClaimed) ? '关闭' : '请等待 ' + closeCountdown + 's' }}</text>
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

        onLoad(opt) {
                this.groupId = opt.group_id || 'default_group';
                this.user_info = uni.getStorageSync('user_info') || {};
                this.loadAdOverview();
                // ★ 加载头像列表
                this.loadAvatarList();
                // 生成初始聊天消息（系统欢迎语）
                this.generateInitialMessages();
                // ★ 启动持续推送（聊天消息 + 信息流广告交替，永不停止）
                this.startContinuousPush();
                // ★ 启动激励视频推送（每120秒推一条激励视频广告）
                this.startRewardedVideoPush();
        },

        onShow() {
                // 每次显示页面时刷新广告红包摘要
                this.loadAdOverview();
        },

        onUnload() {
                // 停止持续推送
                this.stopContinuousPush();
                // 停止激励视频推送
                this.stopRewardedVideoPush();
                // 离开页面时清理所有红包过期计时器
                this.clearAllRedbagTimers();
                // 清理关闭倒计时
                this.stopCloseLock();
                // 离开页面时重置累加数据
                this.resetRedbag();
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

                        // ★ 激励视频推送定时器
                        rewardedVideoPushTimer: null,     // 激励视频推送定时器
                        rewardedVideoLastPushTime: 0,     // 上次推送激励视频的时间戳

                        // ★ 头像相关
                        avatarList: [],                    // 后台头像URL列表
                        nicknameAvatarMap: {},              // 昵称→头像的固定映射（同一用户始终同一头像）
                };
        },

        computed: {
                displayAmount() {
                        return Number(this.currentAmount) || 0;
                },
                isExpired() {
                        return this.currentRedbag && this.currentRedbag.status === 'expired';
                }
        },

        mounted() {
                this.$nextTick(() => {
                        this.calcScrollHeight();
                });
        },

        methods: {
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

                        // 刷新广告红包摘要（可能自动生成了新红包）
                        this.loadAdOverview();

                        if (data.amount > 0) {
                                // 根据广告类型显示不同的系统提示
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
                                if (res && res.code === 1 && res.data) {
                                        this.adPacketBadge = res.data.unclaimed_packet_count || 0;

                                        // ★ 保存广告配置供持续推送使用
                                        this.adConfig.feed_adpid = res.data.feed_adpid || '';
                                        this.adConfig.feed_ad_count = res.data.feed_ad_count || 3;
                                        this.adConfig.reward_per_feed = res.data.reward_per_feed || 50;
                                        this.adConfig.ad_income_enabled = res.data.ad_income_enabled || 0;

                                        // ★ 保存激励视频广告配置
                                        this.adConfig.rewarded_video_adpid = res.data.rewarded_video_adpid || '';
                                        this.adConfig.reward_per_video = res.data.reward_per_video || 200;
                                        this.adConfig.rewarded_video_interval = res.data.rewarded_video_interval || 120;

                                        // 检查广告配置并提示
                                        this.checkAdConfigAndHint();
                                }
                        } catch (e) {
                                // 静默处理
                        }
                },

                // ==================== ★ 头像管理 ====================

                /**
                 * 从后端加载头像列表（带缓存）
                 */
                async loadAvatarList() {
                        try {
                                const res = await this.$api.getAvatarList({});
                                if (res && res.code === 1 && res.data && res.data.avatars) {
                                        this.avatarList = res.data.avatars;
                                        console.log('[RedBag] 加载头像列表成功，数量:', this.avatarList.length);
                                }
                        } catch (e) {
                                console.warn('[RedBag] 加载头像列表失败:', e);
                        }
                },

                /**
                 * 根据昵称获取头像URL
                 * 同一昵称始终返回同一头像（通过nicknameAvatarMap缓存映射）
                 * 新昵称随机分配一个头像
                 */
                getAvatarForNickname(nickname) {
                        if (!nickname) return '/static/image/avatar.png';

                        // 已有映射，直接返回
                        if (this.nicknameAvatarMap[nickname]) {
                                return this.nicknameAvatarMap[nickname];
                        }

                        // 没有头像列表或列表为空，使用默认头像
                        if (!this.avatarList || this.avatarList.length === 0) {
                                return '/static/image/avatar.png';
                        }

                        // 基于昵称hash值确定性地选一个头像（同一昵称hash相同）
                        let hash = 0;
                        for (let i = 0; i < nickname.length; i++) {
                                hash = ((hash << 5) - hash) + nickname.charCodeAt(i);
                                hash = hash & hash; // 转为32位整数
                        }
                        const index = Math.abs(hash) % this.avatarList.length;
                        const avatar = this.avatarList[index];

                        // 存入映射
                        this.nicknameAvatarMap[nickname] = avatar;
                        return avatar;
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

                        // 初始几条聊天消息
                        const names = ['小明的妈妈', '赚钱达人', '金币猎手', '福利小能手', '幸运星', '福利王', '金币收藏家'];
                        const msgs = [
                                '今天又赚了不少金币 💰', '有没有人一起领红包呀',
                                '看广告真的能赚钱！', '每天来签到领红包',
                                '这个平台太良心了', '刚提现了，速度很快',
                                '大家加油赚金币！', '新人有福利吗',
                                '每天看几个广告就能提现', '推荐给朋友了一起赚'
                        ];

                        const count = 3 + Math.floor(Math.random() * 3);
                        for (let i = 0; i < count; i++) {
                                this.messages.push(this.createFakeChatMessage(names, msgs));
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
                        const names = ['小明的妈妈', '赚钱达人', '金币猎手', '福利小能手', '幸运星', '福利王', '金币收藏家', '宝妈小丽', '打工人阿强', '学生党小陈', '自由职业者', '退休大叔', '宝妈二丫', '程序员小哥'];
                        const msgs = [
                                '今天又赚了不少金币 💰', '有没有人一起领红包呀',
                                '看广告真的能赚钱！', '每天来签到领红包',
                                '这个平台太良心了', '刚提现了，速度很快',
                                '大家加油赚金币！', '新人有福利吗',
                                '每天看几个广告就能提现', '推荐给朋友了一起赚',
                                '哈哈哈又抢到一个大红包', '今天运气不错 🎉',
                                '有没有人一起组队', '坚持每天签到金币更多',
                                '上个月提现了200，太开心了', '这个是真的能赚',
                                '刚刚看了一个广告赚了50金币', '大家注意签到别漏了',
                                '红包群就是给力', '又到账了，开心',
                                '有没有大佬分享一下经验', '感觉每天赚得越来越多了'
                        ];

                        this.pushCounter++;

                        // 判断是否该推广告了
                        if (this.pushCounter >= this.pushChatCountBeforeAd) {
                                // 重置计数器，随机 2-4 条后下次再推广告
                                this.pushCounter = 0;
                                this.pushChatCountBeforeAd = 2 + Math.floor(Math.random() * 3);

                                // 推送信息流广告
                                if (this.adConfig.feed_adpid) {
                                        const adMsg = this.createAdFeedMessage(
                                                this.adConfig.feed_adpid,
                                                this.adConfig.reward_per_feed || 50,
                                                Date.now()
                                        );
                                        this.messages.push(adMsg);
                                        console.log('[RedBag] 推送信息流广告, adpid=' + this.adConfig.feed_adpid);
                                } else {
                                        // 没配置 adpid 时，推一条聊天消息代替
                                        this.messages.push(this.createFakeChatMessage(names, msgs));
                                }
                        } else {
                                // 推送普通聊天消息
                                this.messages.push(this.createFakeChatMessage(names, msgs));
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

                /**
                 * 创建一条模拟聊天消息
                 */
                createFakeChatMessage(names, msgs) {
                        const nameIdx = Math.floor(Math.random() * names.length);
                        const msgIdx = Math.floor(Math.random() * msgs.length);
                        const nickname = names[nameIdx];
                        return {
                                id: 'fake_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                                type: 'text',
                                content: msgs[msgIdx],
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
                        return {
                                id: 'ad_feed_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                                type: 'ad_feed',
                                time: Date.now(),
                                sender: 'system',
                                user: {
                                        nickname: '广告推荐',
                                        avatar: '/static/image/ad-avatar.png'
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
                        return {
                                id: 'rewarded_video_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6),
                                type: 'rewarded_video',
                                time: Date.now(),
                                sender: 'system',
                                user: {
                                        nickname: '限时福利',
                                        avatar: '/static/image/ad-avatar.png'
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

                // ==================== ★ 激励视频推送管理 ====================

                /**
                 * 启动激励视频定时推送
                 * 每隔 rewarded_video_interval 秒（默认120秒）推送一条激励视频广告
                 */
                startRewardedVideoPush() {
                        // 防止重复启动
                        if (this.rewardedVideoPushTimer) return;

                        // 如果没有配置激励视频 adpid，则不启动
                        if (!this.adConfig.rewarded_video_adpid) {
                                console.log('[RedBag] 未配置激励视频广告位ID，跳过激励视频推送');
                                return;
                        }

                        // 记录启动时间，延迟一个间隔后首次推送
                        this.rewardedVideoLastPushTime = Date.now();
                        const intervalMs = (this.adConfig.rewarded_video_interval || 120) * 1000;

                        console.log('[RedBag] 激励视频推送已启动，间隔=' + this.adConfig.rewarded_video_interval + '秒');

                        const scheduleNext = () => {
                                this.rewardedVideoPushTimer = setTimeout(() => {
                                        this.doPushRewardedVideo();
                                        scheduleNext(); // 递归调度
                                }, intervalMs);
                        };

                        // 延迟一个间隔后首次推送激励视频
                        this.rewardedVideoPushTimer = setTimeout(() => {
                                this.doPushRewardedVideo();
                                scheduleNext();
                        }, intervalMs);
                },

                /**
                 * 停止激励视频推送
                 */
                stopRewardedVideoPush() {
                        if (this.rewardedVideoPushTimer) {
                                clearTimeout(this.rewardedVideoPushTimer);
                                this.rewardedVideoPushTimer = null;
                                console.log('[RedBag] 激励视频推送已停止');
                        }
                },

                /**
                 * 推送一条激励视频广告消息
                 */
                doPushRewardedVideo() {
                        if (!this.adConfig.rewarded_video_adpid) {
                                console.log('[RedBag] 激励视频 adpid 未配置，跳过推送');
                                return;
                        }

                        // 如果 adpid 发生变化（后台热更新配置），则更新
                        if (this.adConfig.rewarded_video_adpid) {
                                const videoMsg = this.createRewardedVideoMessage(
                                        this.adConfig.rewarded_video_adpid,
                                        this.adConfig.reward_per_video || 200
                                );
                                this.messages.push(videoMsg);
                                this.rewardedVideoLastPushTime = Date.now();

                                console.log('[RedBag] 推送激励视频广告, adpid=' + this.adConfig.rewarded_video_adpid + ', 奖励=' + (this.adConfig.reward_per_video || 200) + '金币');

                                // 性能保护
                                if (this.messages.length > this.maxMessages) {
                                        const removeCount = this.messages.length - this.maxMessages;
                                        this.messages.splice(0, removeCount);
                                }

                                this.scrollToBottom();
                        }
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
                        const anchorId = this.scrollAnchorId;

                        // H5环境：直接用原生DOM scrollIntoView，完全绕过uni-app的scroll-view滚动机制
                        // #ifdef H5
                        this.$nextTick(() => {
                                // 用requestAnimationFrame确保浏览器已完成渲染
                                requestAnimationFrame(() => {
                                        requestAnimationFrame(() => {
                                                const el = document.getElementById(anchorId);
                                                if (el) {
                                                        el.scrollIntoView({ block: 'end', behavior: 'smooth' });
                                                }
                                        });
                                });
                        });
                        // #endif

                        // 非H5环境（APP/小程序）：用scroll-into-view绑定
                        // #ifndef H5
                        const newId = 'anchor-' + Date.now();
                        this.scrollAnchorId = newId;
                        this.$nextTick(() => {
                                this.scrollToId = newId;
                        });
                        // #endif
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
.page-content {
        min-height: 100vh;
        background: #f5f5f5;
        display: flex;
        flex-direction: column;
}

.chat-container {
        flex: 1;
        display: flex;
        flex-direction: column;
        overflow: hidden;
}

/* 底部锚点：增加高度作为最后一条消息与tabbar之间的间距 */
.scroll-bottom-anchor {
        height: 120rpx;
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
}

.message-list {
        flex: 1;
        padding: 20rpx;
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

/* 红包弹窗 */
.redbag-modal-mask {
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

.redbag-modal {
        width: 600rpx;
        background: linear-gradient(180deg, #e74c3c, #c0392b);
        border-radius: 24rpx;
        padding: 40rpx;
        text-align: center;
}

.modal-header {
        margin-bottom: 30rpx;
}

.modal-logo {
        width: 120rpx;
        height: 120rpx;
        border-radius: 50%;
        margin: 0 auto 20rpx;
}

.modal-title {
        color: #ffd700;
        font-size: 36rpx;
        font-weight: bold;
}

.modal-amount-area {
        padding: 40rpx 0;
}

.amount-circle {
        margin: 0 auto;
}

.amount-number {
        color: #fff;
        font-size: 72rpx;
        font-weight: bold;
}

.amount-number.loading-text {
        color: rgba(255, 255, 255, 0.6);
}

.amount-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 26rpx;
        display: block;
        margin-top: 10rpx;
}

.amount-hint {
        color: rgba(255, 255, 255, 0.9);
        font-size: 28rpx;
        margin-top: 20rpx;
        display: block;
}

.modal-actions {
        padding: 20rpx 0;
}

.action-btn {
        margin: 0 auto;
        width: 400rpx;
        height: 80rpx;
        line-height: 80rpx;
        border-radius: 40rpx;
        text-align: center;
}

.claim-btn {
        background: #ffd700;
        color: #c0392b;
        font-weight: bold;
}

.done-btn {
        background: rgba(255, 255, 255, 0.3);
        color: #fff;
}

.close-action-btn {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
}

.disabled-btn {
        background: rgba(255, 255, 255, 0.2);
        color: rgba(255, 255, 255, 0.5);
}

.modal-close {
        margin-top: 20rpx;
}

.close-text {
        color: rgba(255, 255, 255, 0.7);
        font-size: 28rpx;
}

.close-disabled {
        opacity: 0.4;
}
</style>

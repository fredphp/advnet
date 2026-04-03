<template>
        <view class="help-container">
                <!-- 顶部导航 -->
                <fa-navbar title="帮助中心" :border-bottom="false"></fa-navbar>

                <!-- 加载中 -->
                <view class="loading-wrapper" v-if="loading && !list.length">
                        <u-loading mode="circle" size="60" color="#E62129"></u-loading>
                        <text class="loading-text">加载中...</text>
                </view>

                <!-- 文章列表（手风琴） -->
                <view class="help-list" v-if="!loading || list.length">
                        <view
                                class="help-item"
                                v-for="(item, index) in list"
                                :key="item.id"
                        >
                                <!-- 标题行：点击展开/收起 -->
                                <view class="help-title-row" @click="toggle(index)">
                                        <view class="title-left">
                                                <view class="title-index">{{ index + 1 }}</view>
                                                <text class="title-text">{{ item.title }}</text>
                                        </view>
                                        <u-icon
                                                :name="expandedIndex === index ? 'arrow-up' : 'arrow-down'"
                                                size="28"
                                                color="#999"
                                        ></u-icon>
                                </view>

                                <!-- 展开内容区 -->
                                <view class="help-content-wrapper" v-if="expandedIndex === index">
                                        <view class="help-content">
                                                <u-parse
                                                        :html="item.content"
                                                        :tag-style="vuex_parse_style"
                                                        :domain="parseDomain"
                                                ></u-parse>
                                        </view>
                                        <view class="content-update-time">
                                                更新时间：{{ formatTime(item.updatetime) }}
                                        </view>
                                </view>
                        </view>
                </view>

                <!-- 空数据 -->
                <view class="empty-wrapper" v-if="!loading && !list.length">
                        <image class="empty-image" src="../../static/image/data.png" mode="aspectFit"></image>
                        <text class="empty-text">暂无帮助内容</text>
                </view>

                <!-- 回到顶部 -->
                <u-back-top
                        :scroll-top="scrollTop"
                        :icon-style="{ color: theme.bgColor }"
                        :custom-style="{ backgroundColor: theme.lightColor }"
                ></u-back-top>

                <!-- 底部导航 -->
                <fa-tabbar></fa-tabbar>

                <!-- 加载遮罩 -->
                <u-loading-page v-if="loading" :loading-text="'加载中...'"></u-loading-page>
        </view>
</template>

<script>
const CACHE_KEY = 'help_center_articles';
const VERSION_KEY = 'help_center_version';

export default {
        onLoad() {
                this.loadHelpList();
        },
        onShow() {
                // 每次显示时后台检查更新（静默刷新）
                if (this.list.length > 0) {
                        this.silentCheckUpdate();
                }
        },
        data() {
                return {
                        scrollTop: 0,
                        loading: false,
                        refreshing: false,
                        list: [],
                        expandedIndex: -1
                };
        },
        computed: {
                parseDomain() {
                        return (this.vuex_config && this.vuex_config.upload && this.vuex_config.upload.cdnurl)
                                ? this.vuex_config.upload.cdnurl : '';
                }
        },
        methods: {
                // 加载帮助文章列表（带缓存）
                async loadHelpList() {
                        this.loading = true;

                        try {
                                // 1. 先读取本地缓存
                                const cached = this.readCache();
                                if (cached && cached.list && cached.list.length > 0) {
                                        this.list = cached.list;
                                        this.loading = false;

                                        // 2. 后台静默检查是否有更新
                                        this.silentCheckUpdate();
                                        return;
                                }

                                // 3. 无缓存，请求接口
                                await this.fetchFromServer();
                        } catch (e) {
                                console.error('loadHelpList error:', e);
                                // 请求失败时尝试读取缓存兜底
                                const cached = this.readCache();
                                if (cached && cached.list) {
                                        this.list = cached.list;
                                }
                        } finally {
                                this.loading = false;
                        }
                },

                // 静默检查更新
                async silentCheckUpdate() {
                        if (this.refreshing) return;
                        try {
                                const localVersion = this.readVersion();
                                const res = await this.$api.helpList({
                                        version: localVersion || 0
                                });
                                if (res.code == 1 && !res.data.not_modified) {
                                        // 有更新，刷新数据
                                        this.list = res.data.list || [];
                                        this.writeCache(res.data.list, res.data.version);
                                }
                        } catch (e) {
                                // 静默检查失败不影响体验
                                console.log('silentCheckUpdate:', e);
                        }
                },

                // 从服务器获取数据
                async fetchFromServer() {
                        const res = await this.$api.helpList({});
                        if (res.code == 1) {
                                this.list = res.data.list || [];
                                // 写入缓存
                                if (this.list.length > 0) {
                                        this.writeCache(this.list, res.data.version);
                                }
                        } else {
                                this.$u.toast(res.msg || '加载失败');
                        }
                },

                // 展开/收起
                toggle(index) {
                        if (this.expandedIndex === index) {
                                this.expandedIndex = -1;
                        } else {
                                this.expandedIndex = index;
                        }
                },

                // 格式化时间戳
                formatTime(timestamp) {
                        if (!timestamp) return '';
                        const date = new Date(timestamp * 1000);
                        const y = date.getFullYear();
                        const m = String(date.getMonth() + 1).padStart(2, '0');
                        const d = String(date.getDate()).padStart(2, '0');
                        const h = String(date.getHours()).padStart(2, '0');
                        const min = String(date.getMinutes()).padStart(2, '0');
                        return `${y}-${m}-${d} ${h}:${min}`;
                },

                // ========== 缓存操作 ==========
                readCache() {
                        try {
                                const data = uni.getStorageSync(CACHE_KEY);
                                return data ? JSON.parse(data) : null;
                        } catch (e) {
                                return null;
                        }
                },

                writeCache(list, version) {
                        try {
                                uni.setStorageSync(CACHE_KEY, JSON.stringify({
                                        list: list,
                                        saveTime: Date.now()
                                }));
                                if (version) {
                                        uni.setStorageSync(VERSION_KEY, String(version));
                                }
                        } catch (e) {
                                console.log('writeCache error:', e);
                        }
                },

                readVersion() {
                        try {
                                const v = uni.getStorageSync(VERSION_KEY);
                                return v ? parseInt(v) : 0;
                        } catch (e) {
                                return 0;
                        }
                }
        },
        onPageScroll(e) {
                this.scrollTop = e.scrollTop;
        }
};
</script>

<style scoped lang="scss">
.help-container {
        min-height: 100vh;
        background-color: #F7F8FA;
        padding-bottom: 120rpx;
}

// 加载中
.loading-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 120rpx 0;

        .loading-text {
                margin-top: 20rpx;
                font-size: 26rpx;
                color: #999;
        }
}

// 文章列表
.help-list {
        padding: 20rpx 24rpx;
}

.help-item {
        background: #FFFFFF;
        border-radius: 16rpx;
        margin-bottom: 20rpx;
        overflow: hidden;
}

// 标题行
.help-title-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 28rpx 30rpx;
        cursor: pointer;
        transition: background-color 0.2s;

        &:active {
                background-color: #f9f9f9;
        }

        .title-left {
                display: flex;
                align-items: center;
                flex: 1;
                margin-right: 20rpx;
        }

        .title-index {
                width: 44rpx;
                height: 44rpx;
                border-radius: 10rpx;
                background: linear-gradient(135deg, #FF8D3B, #E62129);
                color: #fff;
                font-size: 24rpx;
                font-weight: bold;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 20rpx;
                flex-shrink: 0;
        }

        .title-text {
                font-size: 30rpx;
                font-weight: 500;
                color: #333;
                line-height: 1.4;
        }
}

// 内容区
.help-content-wrapper {
        border-top: 1rpx solid #f0f0f0;
        animation: slideDown 0.25s ease-out;
}

@keyframes slideDown {
        from {
                opacity: 0;
                max-height: 0;
        }
        to {
                opacity: 1;
                max-height: 2000rpx;
        }
}

.help-content {
        padding: 24rpx 30rpx;
        font-size: 28rpx;
        color: #666;
        line-height: 1.8;
        overflow: hidden;

        // u-parse 内部样式覆盖
        /deep/ image {
                max-width: 100% !important;
                height: auto !important;
                border-radius: 8rpx;
                margin: 10rpx 0;
        }

        /deep/ p {
                margin: 10rpx 0;
        }

        /deep/ a {
                color: #E62129;
                text-decoration: underline;
        }
}

.content-update-time {
        padding: 0 30rpx 20rpx;
        font-size: 22rpx;
        color: #ccc;
        text-align: right;
}

// 空数据
.empty-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 150rpx 0;

        .empty-image {
                width: 300rpx;
                height: 300rpx;
                margin-bottom: 30rpx;
        }

        .empty-text {
                font-size: 28rpx;
                color: #999;
        }
}
</style>

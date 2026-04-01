<template>
        <view>
                <!-- 顶部导航 -->
                <fa-navbar title="首页" :border-bottom="false"></fa-navbar>
                <!-- <view class="u-p-l-20 u-p-r-20 u-p-b-20" :style="[{backgroundColor:theme.bgColor}]">
                        <fa-search :mode="2" :no-focus="true" @focus="goPage('/pages/search/search')"></fa-search>
                </view> -->
                <view class="index-content">
                        <view class="u-font-36 title">
                                <text class="stroke"></text>
                                最近浏览
                        </view>
                        <view class="goods-list" style="justify-content: flex-start;">
                                <view class="item" v-for="(item, index) in recommends" :key="index" @click="goPage('/pages/goods/detail?id=' + item.id)" v-if="index < 2">
                                        <view class="images">
                                                <image :src="item.image" mode="aspectFill"></image>
                                                <view class="" style="width:100%;position: absolute;bottom:0rpx;text-align: center;">
                                                        <view class="price">
                                                                <text>最多可得￥{{ item.price }}</text>
                                                        </view>
                                                        <!-- <text class="market_price u-tips-color">￥{{ item.marketprice }}</text> -->
                                                </view>
                                        </view>
                                        <view class="name">
                                                <text class="u-line-1">{{ item.title }}</text>
                                        </view>
                                        <view class="foot u-flex u-row-between">
                                                <view class="">
                                                        <text class="u-m-r-10">79集</text>
                                                        <!-- <text>{{ item.sales }}</text> -->
                                                </view>
                                                <!-- <view class="">
                                                        <text class="u-m-r-10">浏览</text>
                                                        <text>最多可得￥{{ item.price }}</text>
                                                </view> -->
                                        </view>
                                        
                                </view>
                                <!-- 空数据 -->
                        </view>
                        <!-- 加载更多 -->
                </view>
                <view class="index-content">
                        <view class="u-font-36 title u-flex u-row-between">
                                <view>
                                        <text class="stroke"></text>
                                        热门短剧
                                </view>
                                <view @click="goPage('/pages/search/search')">
                                        <u-icon name="search"></u-icon>
                                </view>
                                
                        </view>
                        <u-tabs :list="videoCategory" active-color="#f93110" :is-scroll="true" :current="videoCurrent" @change="videoChange"></u-tabs>
                        <view class="goods-list">
                                <view class="item" v-for="(item, index) in recommends" :key="index" @click="goPage('/pages/goods/detail?id=' + item.id)">
                                        <view class="images">
                                                <image :src="item.image" mode="aspectFill"></image>
                                                <view class="" style="width:100%;position: absolute;bottom:0rpx;text-align: center;">
                                                        <view class="price">
                                                                <text>最多可得￥{{ item.price }}</text>
                                                        </view>
                                                        <!-- <text class="market_price u-tips-color">￥{{ item.marketprice }}</text> -->
                                                </view>
                                        </view>
                                        <view class="name">
                                                <text class="u-line-1">{{ item.title }}</text>
                                        </view>
                                        <view class="foot u-flex u-row-between">
                                                <view class="">
                                                        <text class="u-m-r-10">79集</text>
                                                        <!-- <text>{{ item.sales }}</text> -->
                                                </view>
                                                <!-- <view class="">
                                                        <text class="u-m-r-10">浏览</text>
                                                        <text>最多可得￥{{ item.price }}</text>
                                                </view> -->
                                        </view>
                                        
                                </view>
                                <!-- 空数据 -->
                                <view class="u-flex u-row-center fa-empty u-p-b-60" v-if="!recommends.length">
                                        <image src="../../static/image/data.png" mode=""></image>
                                        <view class="u-tips-color">暂无更多的推荐商品~</view>
                                </view>
                        </view>
                        <!-- 加载更多 -->
                        <view class="u-p-b-30" v-if="recommends.length"><u-loadmore :status="has_more ? status : 'nomore'" /></view>
                </view>
                <!-- 回到顶部 -->
                <u-back-top :scroll-top="scrollTop" :icon-style="{ color: theme.bgColor }" :custom-style="{ backgroundColor: theme.lightColor }"></u-back-top>
                <!-- 底部导航 -->
                <fa-tabbar></fa-tabbar>

                <!-- 弹窗公告 - 钉子吊挂相框风格 -->
                <view v-if="showNoticePopup || boardFalling" class="notice-mask" @click="closeNoticePopup">
                        <view class="notice-board" :class="{'board-fall': boardFalling}" @click.stop>
                                <!-- 顶部钉子 -->
                                <view class="board-nail" @click="closeNoticePopup">
                                        <view class="nail-cap"></view>
                                        <view class="nail-shaft"></view>
                                </view>
                                <!-- 左右两根绳子 -->
                                <view class="board-string board-string--left"></view>
                                <view class="board-string board-string--right"></view>
                                <!-- 相框公告 -->
                                <view class="board-frame">
                                        <view class="frame-inner">
                                                <view class="frame-title">{{ noticeInfo.title }}</view>
                                                <view class="frame-divider"></view>
                                                <scroll-view class="frame-content" scroll-y>
                                                        <u-parse
                                                                :html="noticeInfo.content"
                                                                :tag-style="vuex_parse_style"
                                                                :domain="vuex_config && vuex_config.upload && vuex_config.upload.cdnurl ? vuex_config.upload.cdnurl : ''"
                                                        ></u-parse>
                                                </scroll-view>
                                        </view>
                                </view>
                        </view>
                </view>
        </view>
</template>

<script>
export default {
        data() {
                return {
                        loading: true,
                        status: 'loadmore',
                        is_update: false,
                        has_more: false,
                        current: 0,
                        scrollTop: 0,
                        navigateList: [],
                        hots: [],
                        recommends: [],
                        videoCategory: [{
                                name: '全部'
                        }, {
                                name: '都市'
                        }, {
                                name: '悬疑'
                        }, {
                                name: '现言'
                        }, {
                                name: '古言'
                        }, {
                                name: '军事'
                        }, {
                                name: '玄幻'
                        }, {
                                name: '热血'
                        }, {
                                name: '历史'
                        }, {
                                name: '喜剧'
                        }, {
                                name: '动作'
                        }, {
                                name: '二次元'
                        }, {
                                name: '其它剧情'
                        }, {
                                name: '亲情'
                        }],
                        videoCurrent: 0,
                        showNoticePopup: false,
                        noticeInfo: {},
                        boardFalling: false,
                        noticeShown: false
                };
        },
        onShow() {
                this.getGoodsIndex();
        },
        onLoad() {
                this.getPopupNotice();
        },
        computed: {
                notice() {
                        let arr = [];
                        if (this.vuex_config.notice) {
                                this.vuex_config.notice.map(item => {
                                        arr.push(item.title);
                                });
                        }
                        return arr;
                },
                navigates() {
                        if (this.vuex_config.navigate) {
                                let arr1 = [],
                                        arr2 = [];
                                this.vuex_config.navigate.forEach((item, index) => {
                                        if (((index + 1) % 9 == 0 && index != 0) || index + 1 == this.vuex_config.navigate.length) {
                                                arr2.push(item);
                                                arr1.push(arr2);
                                                arr2 = [];
                                        } else {
                                                arr2.push(item);
                                        }
                                });
                                this.navigateList = arr1;
                        }
                        return 1;
                }
        },
        methods: {
                change(e) {
                        this.current = e.detail.current;
                },
                videoChange(index) {
                        this.videoCurrent = index;
                },
                grids(e) {
                        let path = e.path;
                        if (path == '/' || !path) {
                                return;
                        }
                        if (path.substr(0, 1) == 'p') {
                                path = '/' + path;
                        }
                        if (path.includes('http')) {
                                this.$u.vuex('vuex_webs', {
                                        path: e.path,
                                        title: e.name
                                });
                                this.$u.route('/pages/webview/webview');
                                return;
                        }
                        this.$u.route(path);
                },
                openPage(index) {
                        this.grids({
                                path: this.vuex_config.swiper[index].url,
                                name: this.vuex_config.swiper[index].title
                        });
                },
                click(index) {
                        if (this.vuex_config.notice) {
                                let url = this.vuex_config.notice[index].path;
                                if (url) {
                                        this.grids({
                                                path: url,
                                                name: this.vuex_config.notice[index].title
                                        });
                                }
                        }
                },
                getGoodsIndex() {
                        this.$api.getGoodsIndex().then(({code,data:res,msg}) => {
                                if (code) {
                                        this.hots = res.hots;
                                        this.recommends = res.recommends;
                                }
                        });
                },
                // 获取弹窗公告（仅首次加载时调用一次）
                getPopupNotice() {
                        if (this.noticeShown) return;
                        const readId = uni.getStorageSync('popup_notice_read_id') || 0;
                        if (readId) return;
                        this.$api.getPopupNotice({ read_id: readId }).then(res => {
                                if (res.code && res.data) {
                                        this.noticeShown = true;
                                        this.noticeInfo = res.data;
                                        // 立即记录已读，避免重复弹出
                                        uni.setStorageSync('popup_notice_read_id', res.data.id);
                                        this.showNoticePopup = true;
                                }
                        });
                },
                // 关闭弹窗公告（掉落动画）
                closeNoticePopup() {
                        this.boardFalling = true;
                        setTimeout(() => {
                                this.showNoticePopup = false;
                                this.boardFalling = false;
                        }, 500);
                }
        },
        onPageScroll(e) {
                this.scrollTop = e.scrollTop;
        },
        //下拉刷新
        onPullDownRefresh() {},
        onReachBottom() {}
};
</script>

<style lang="scss">
page {
        background-color: #f4f6f8;
}
</style>
<style lang="scss" scoped>
.indicator-dots {
        display: flex;
        justify-content: center;
        align-items: center;
}

.indicator-dots-item {
        background-color: $u-tips-color;
        height: 6px;
        width: 6px;
        border-radius: 10px;
        margin: 0 3px;
}

.indicator-dots-active {
        background-color: $u-type-primary;
}
.notice {
        margin-bottom: 30rpx;
}
.index-content {
        // margin-top: 30rpx;
        background-color: #ffffff;
        .title {
                font-weight: bold;
                position: relative;
                padding: 30rpx 50rpx;
                border-bottom: 1px solid #f4f6f8;
                .stroke {
                        &::before {
                                content: '';
                                width: 8rpx;
                                height: 36rpx;
                                background-color: #f93110;
                                position: absolute;
                                top: 36%;
                                left: 30rpx;
                                border-radius: 20rpx;
                        }
                }
        }
}

.goods-list {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin-top: 10rpx;
        padding: 0 18rpx;
        // gap: 10rpx;
        .item {
                width: 230rpx;
                // border-radius: 8rpx 8rpx 8rpx 8rpx;
                background-color: #ffffff;
                // box-shadow: 0px 0px 5px rgb(233, 235, 243);
                margin-bottom: 10rpx;
                border-radius: 10rpx;
                overflow: hidden;
                // margin-right:10rpx;
                // border: 1px solid #e9ebf3;
                .name {
                        // min-height: 110rpx;
                        padding:10rpx;
                }
                .foot {
                        padding: 0 15rpx;
                }
                .images {
                        position: relative;
                        width: 220rpx;
                        height: 308rpx;
                        image {
                                border-radius: 8rpx 8rpx 8rpx 8rpx;
                                width: 100%;
                                height: 100%;
                        }
                }
                .market_price {
                        text-decoration: line-through;
                        margin-left: 10rpx;
                }
        }
}
/* ========== 弹窗公告 - 钉子吊挂相框风格 ========== */

/* 遮罩层 */
.notice-mask {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        background: rgba(0, 0, 0, 0.45);
}

/* 整体容器 */
.notice-board {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: center;
        margin-top: 160rpx;
        animation: boardIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        transform-origin: top center;
}

/* 掉落动画 */
.board-fall {
        animation: boardFall 0.5s ease-in forwards !important;
}

@keyframes boardIn {
        0% {
                opacity: 0;
                transform: translateY(-80rpx) scale(0.7);
        }
        100% {
                opacity: 1;
                transform: translateY(0) scale(1);
        }
}

@keyframes boardFall {
        0% {
                opacity: 1;
                transform: translateY(0) rotate(0deg);
        }
        30% {
                opacity: 1;
                transform: translateY(10rpx) rotate(3deg);
        }
        100% {
                opacity: 0;
                transform: translateY(800rpx) rotate(15deg);
        }
}

/* 钉子 */
.board-nail {
        position: relative;
        z-index: 20;
        display: flex;
        flex-direction: column;
        align-items: center;
        cursor: pointer;
        margin-bottom: -6rpx;
}
.nail-cap {
        width: 44rpx;
        height: 44rpx;
        border-radius: 50%;
        background: radial-gradient(circle at 35% 35%, #e0e0e0, #999 60%, #777);
        box-shadow:
                0 3rpx 8rpx rgba(0, 0, 0, 0.4),
                inset 0 2rpx 4rpx rgba(255, 255, 255, 0.6);
        border: 2rpx solid #888;
}
.nail-shaft {
        width: 10rpx;
        height: 20rpx;
        background: linear-gradient(to bottom, #aaa, #777);
        border-radius: 0 0 5rpx 5rpx;
        margin-top: -4rpx;
}

/* 绳子（从钉子连接到相框上两个角） */
.board-string {
        position: absolute;
        top: 54rpx;
        width: 2rpx;
        height: 50rpx;
        background: linear-gradient(to bottom, #a0886a, #8b7355);
        z-index: 5;
        border-radius: 1rpx;
}
.board-string--left {
        left: 16rpx;
        transform-origin: top center;
        transform: rotate(12deg);
}
.board-string--right {
        right: 16rpx;
        transform-origin: top center;
        transform: rotate(-12deg);
}

/* 相框 */
.board-frame {
        width: 560rpx;
        border-radius: 6rpx;
        padding: 16rpx;
        background: linear-gradient(145deg, #d4b896, #a0886a, #c4a882, #8b7355);
        box-shadow:
                0 8rpx 30rpx rgba(0, 0, 0, 0.3),
                inset 0 1rpx 2rpx rgba(255, 255, 255, 0.3);
        position: relative;
}

/* 相框内部 */
.frame-inner {
        background: linear-gradient(180deg, #faf6ef 0%, #f5efe4 50%, #faf7f0 100%);
        border-radius: 2rpx;
        padding: 0;
        overflow: hidden;
        box-shadow: inset 0 0 20rpx rgba(0, 0, 0, 0.05);
}

.frame-title {
        text-align: center;
        font-size: 32rpx;
        font-weight: bold;
        color: #3d2b1f;
        padding: 40rpx 36rpx 14rpx;
        letter-spacing: 4rpx;
}

.frame-divider {
        margin: 0 50rpx 14rpx;
        height: 2rpx;
        background: linear-gradient(to right, transparent, #c4a67a, transparent);
}

.frame-content {
        max-height: 420rpx;
        padding: 4rpx 36rpx 36rpx;
        font-size: 25rpx;
        color: #5a4a3a;
        line-height: 1.9;
        overflow: hidden;
        word-break: break-all;
        word-wrap: break-word;
        overflow-wrap: break-word;
}
/* 修复富文本内元素溢出 */
.frame-content >>> view,
.frame-content >>> p,
.frame-content >>> div,
.frame-content >>> span,
.frame-content >>> img,
.frame-content >>> h1,
.frame-content >>> h2,
.frame-content >>> h3,
.frame-content >>> h4,
.frame-content >>> h5,
.frame-content >>> h6,
.frame-content >>> ul,
.frame-content >>> ol {
        max-width: 100% !important;
        overflow: hidden;
        word-break: break-all !important;
        box-sizing: border-box;
}
.frame-content >>> img {
        max-width: 100% !important;
        height: auto !important;
        display: block;
        margin: 10rpx auto;
}

.hots-list{     
        margin-top: 30rpx;
        padding: 0 30rpx 30rpx;
        .item {
                width: 100%;
                background-color: #ffffff;
                box-shadow: 0px 0px 5px rgb(233, 235, 243);
                margin-bottom: 30rpx;
                border-radius: 10rpx;
                overflow: hidden;
                border: 1px solid #e9ebf3;
                display: flex;
                justify-content: space-between;
                align-items: center;
                .images {
                        width: 220rpx;
                        height: 308rpx;
                        image {
                                width: 100%;
                                height: 100%;
                        }
                }
                .content{
                        flex: 1;
                        .name {
                                min-height: 110rpx;
                        }
                        .foot {
                                padding: 0 15rpx;
                        }
                        .market_price {
                                text-decoration: line-through;
                                margin-left: 10rpx;
                        }
                }
        }
}
</style>

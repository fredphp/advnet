<template>
  <view class="page">
    <!-- 顶部导航 -->
    <fa-navbar title="小游戏" :border-bottom="false"></fa-navbar>

    <!-- 搜索 -->
    <view class="top-bar">
      <u-search
        v-model="keyword"
        placeholder="搜索小游戏名称"
        :show-action="true"
        action-text="搜索"
        @search="onSearch"
        @custom="onSearch"
		active-color="#f93110"
        :clearabled="true"
      ></u-search>

      <!-- 分类Tab -->
      <view class="tabs-wrap">
        <u-tabs
          :list="tabs"
          :current="tabIndex"
          @change="onTabChange"
          :is-scroll="true"
          :bar-width="40"
          :bar-height="6"
          :active-color="themeColor"
        ></u-tabs>
      </view>
    </view>

    <!-- 列表 -->
    <view class="list">
      <u-empty
        v-if="!loading && list.length === 0"
        mode="list"
        text="暂无小游戏"
      ></u-empty>

      <view v-for="item in list" :key="item.id" class="card" @click="toDetail(item)">
        <!-- 封面 -->
        <view class="cover-wrap">
          <u-image
            :src="item.cover"
            width="220rpx"
            height="160rpx"
            border-radius="16"
            mode="aspectFill"
          >
            <u-loading slot="loading"></u-loading>
          </u-image>

          <!-- 角标 -->
          <view v-if="item.badge" class="badge" :class="item.badgeType">
            {{ item.badge }}
          </view>
        </view>

        <!-- 信息 -->
        <view class="info">
          <view class="row1">
            <text class="title u-line-1">{{ item.title }}</text>
            <u-rate
              :count="5"
              :value="item.rate"
              :disabled="true"
              size="18"
              inactive-color="#EAEAEA"
              active-color="#f93110"
            />
          </view>

          <view class="row2">
            <text class="desc u-line-2">{{ item.desc }}</text>
          </view>

          <view class="row3">
            <view class="tags">
              <u-tag
                v-for="(t, idx) in item.tags"
                :key="idx"
                :text="t"
                size="mini"
                mode="light"
                type="error"
                color="#f93110"
                bg-color="#fff1ef"
                border-color="#ffd6d1"
                class="tag"
              />
            </view>

            <u-button
              type="error"
              size="mini"
              @click.stop="play(item)"
            >进入</u-button>
          </view>

          <view class="meta">
            <text class="meta-text">人数 {{ item.players }}</text>
            <text class="dot">·</text>
            <text class="meta-text">{{ item.size }}</text>
            <text class="dot">·</text>
            <text class="meta-text">{{ item.version }}</text>
          </view>
        </view>
      </view>

      <!-- 底部加载 -->
      <view class="loadmore">
        <u-loadmore :status="loadStatus" :load-text="loadText"></u-loadmore>
      </view>
    </view>

    <!-- 返回顶部 -->
    <u-back-top :scroll-top="scrollTop" :bottom="160"></u-back-top>
	<!-- 底部导航 -->
	<fa-tabbar></fa-tabbar>
  </view>
</template>

<script>
export default {
  data() {
    return {
      themeColor: '#f93110',
      keyword: '',
      tabIndex: 0,
      tabs: [
        { name: '全部', id: 0 },
        { name: '益智', id: 1 },
        { name: '动作', id: 2 },
        { name: '休闲', id: 3 },
        { name: '棋牌', id: 4 },
        { name: '闯关', id: 5 }
      ],
      page: 1,
      pageSize: 10,
      list: [],
      loading: false,
      finished: false,
      loadStatus: 'loadmore', // loadmore / loading / nomore
      loadText: {
        loadmore: '上拉加载更多',
        loading: '加载中...',
        nomore: '没有更多了'
      },
      scrollTop: 0,
      btnStyle: {
        height: '56rpx',
          lineHeight: '56rpx',
          padding: '0 22rpx',
          borderRadius: '28rpx',
          backgroundColor: '#f93110',
          borderColor: '#f93110',
          color: '#ffffff'
      }
    };
  },
  onLoad() {
    this.fetchList(true);
  },
  onPullDownRefresh() {
    this.fetchList(true).finally(() => {
      uni.stopPullDownRefresh();
    });
  },
  onReachBottom() {
    this.fetchList(false);
  },
  onPageScroll(e) {
    this.scrollTop = e.scrollTop;
  },
  methods: {
    onSearch() {
      this.fetchList(true);
    },
    onTabChange(index) {
      this.tabIndex = index;
      this.fetchList(true);
    },

    // 获取列表：reset=true 代表重置分页
    async fetchList(reset = false) {
      if (this.loading) return;
      if (!reset && this.finished) return;

      this.loading = true;
      this.loadStatus = 'loading';

      if (reset) {
        this.page = 1;
        this.finished = false;
        this.list = [];
      }

      try {
        // 模拟接口参数
        const params = {
          keyword: this.keyword,
          categoryId: this.tabs[this.tabIndex].id,
          page: this.page,
          pageSize: this.pageSize
        };

        // TODO：替换成你的真实接口
        const res = await this.mockApi(params);

        const rows = res.list || [];
        this.list = this.list.concat(rows);

        // 分页判断
        if (rows.length < this.pageSize) {
          this.finished = true;
          this.loadStatus = 'nomore';
        } else {
          this.page += 1;
          this.loadStatus = 'loadmore';
        }
      } catch (e) {
        this.loadStatus = 'loadmore';
        uni.showToast({ title: '加载失败', icon: 'none' });
      } finally {
        this.loading = false;
      }
    },

    play(item) {
      // TODO：跳转到游戏容器页/小游戏页
      // uni.showToast({ title: `进入：${item.title}`, icon: 'none' });
      uni.navigateTo({ url: `/pages/game/wheel?id=${item.id}` });
    },

    toDetail(item) {
      // TODO：详情页
      uni.navigateTo({ url: `/pages/game/wheel?id=${item.id}` });
    },

    // 模拟接口（你改成真实请求即可）
    mockApi({ keyword, categoryId, page, pageSize }) {
      const all = [
        {
          id: 1,
          title: '方块消消乐',
          desc: '经典消除玩法，轻松解压，随时来一局。',
          cover: 'https://picsum.photos/400/300?random=11',
          badge: '热门',
          badgeType: 'hot',
          rate: 4.5,
          tags: ['休闲', '消除'],
          players: '12.3万',
          size: '18MB',
          version: 'v1.2.0',
          categoryId: 3
        },
        {
          id: 2,
          title: '数字华容道',
          desc: '动动脑筋，把数字按顺序排列，挑战最短步数。',
          cover: 'https://picsum.photos/400/300?random=12',
          badge: '新',
          badgeType: 'new',
          rate: 4.0,
          tags: ['益智', '闯关'],
          players: '6.7万',
          size: '9MB',
          version: 'v1.0.3',
          categoryId: 1
        },
        {
          id: 3,
          title: '像素跑酷',
          desc: '反应与节奏的结合，躲避障碍，冲刺高分。',
          cover: 'https://picsum.photos/400/300?random=13',
          badge: '',
          badgeType: '',
          rate: 4.2,
          tags: ['动作', '闯关'],
          players: '8.1万',
          size: '26MB',
          version: 'v2.1.1',
          categoryId: 2
        },
        {
          id: 4,
          title: '五子棋大师',
          desc: '人机对战/双人对战，随时开局。',
          cover: 'https://picsum.photos/400/300?random=14',
          badge: '',
          badgeType: '',
          rate: 4.6,
          tags: ['棋牌', '对战'],
          players: '20.5万',
          size: '12MB',
          version: 'v3.0.0',
          categoryId: 4
        }
      ];

      // 简单筛选
      let filtered = all;
      if (categoryId && categoryId !== 0) {
        filtered = filtered.filter(x => x.categoryId === categoryId);
      }
      if (keyword) {
        const k = keyword.trim().toLowerCase();
        filtered = filtered.filter(x => x.title.toLowerCase().includes(k));
      }

      // 分页
      const start = (page - 1) * pageSize;
      const end = start + pageSize;
      const pageList = filtered.slice(start, end);

      return new Promise(resolve => {
        setTimeout(() => {
          resolve({ list: pageList, total: filtered.length });
        }, 450);
      });
    }
  }
};
</script>

<style lang="scss" scoped>
.page {
  background: #f7f8fa;
  min-height: 100vh;
}

.top-bar {
  padding: 16rpx 24rpx 8rpx;
  background: #fff;
}

.tabs-wrap {
  margin-top: 10rpx;
}

.list {
  padding: 18rpx 24rpx 24rpx;
}

.card {
  background: #fff;
  border-radius: 20rpx;
  padding: 18rpx;
  display: flex;
  margin-bottom: 18rpx;
  box-shadow: 0 10rpx 28rpx rgba(0, 0, 0, 0.04);
}

.cover-wrap {
  position: relative;
  width: 220rpx;
  height: 160rpx;
  flex-shrink: 0;
}

.badge {
  position: absolute;
  left: 10rpx;
  top: 10rpx;
  padding: 6rpx 12rpx;
  border-radius: 999rpx;
  font-size: 22rpx;
  color: #fff;
}

.badge.hot {
  background: #f93110;
}
.badge.new {
  background: #ff7a65;
}

.info {
  flex: 1;
  padding-left: 16rpx;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.row1 {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.title {
  font-size: 30rpx;
  font-weight: 600;
  color: #1f2329;
  padding-right: 12rpx;
  max-width: 360rpx;
}

.row2 {
  margin: 8rpx 0;
}

.desc {
  font-size: 24rpx;
  color: #6b7280;
  line-height: 34rpx;
}

.row3 {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.tags {
  flex: 1;
  display: flex;
  flex-wrap: wrap;
  margin-right: 12rpx;
}

.tag {
  margin-right: 10rpx;
  margin-bottom: 8rpx;
}

.meta {
  margin-top: 6rpx;
  display: flex;
  align-items: center;
  color: #9aa0a6;
  font-size: 22rpx;
}

.meta-text {
  max-width: 200rpx;
}

.dot {
  margin: 0 10rpx;
}

.loadmore {
  padding: 18rpx 0 10rpx;
}
</style>

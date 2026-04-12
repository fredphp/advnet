<template>
  <view class="page">
    <view class="card">
      <text class="title">PangolinMedia Test</text>

      <view class="row">
        <text>AppId</text>
        <input v-model="form.appId" placeholder="请输入穿山甲 AppId" />
      </view>
      <view class="row">
        <text>Reward Slot</text>
        <input v-model="form.adSlots.reward" placeholder="激励视频广告位" />
      </view>
      <view class="row">
        <text>Feed Slot</text>
        <input v-model="form.adSlots.feed" placeholder="信息流广告位" />
      </view>
      <view class="row">
        <text>Splash Slot</text>
        <input v-model="form.adSlots.splash" placeholder="开屏广告位" />
      </view>
      <view class="row">
        <text>Interstitial Slot</text>
        <input v-model="form.adSlots.interstitial" placeholder="插屏广告位" />
      </view>

      <view class="switch-row">
        <text>已同意隐私</text>
        <switch :checked="form.privacy.consent" @change="onConsentChange" />
      </view>

      <view class="btns">
        <button type="primary" @click="onInit">初始化</button>
        <button @click="onDestroy">销毁</button>
      </view>
    </view>

    <view class="card">
      <text class="sub">广告测试</text>
      <view class="btns">
        <button @click="showReward">激励视频</button>
        <button @click="showFeed">信息流</button>
        <button @click="showSplash">开屏</button>
        <button @click="showInterstitial">插屏</button>
      </view>
    </view>

    <view class="card">
      <text class="sub">短剧测试</text>
      <view class="row">
        <text>DramaId</text>
        <input v-model="dramaId" placeholder="短剧ID" />
      </view>
      <view class="btns">
        <button @click="openHome">打开短剧首页</button>
        <button @click="openDetail">打开短剧详情</button>
        <button @click="getList">获取短剧列表</button>
      </view>
    </view>

    <view class="card">
      <text class="sub">调用结果</text>
      <text class="json">{{ lastResult }}</text>
    </view>

    <view class="card">
      <view class="log-header">
        <text class="sub">事件日志</text>
        <button size="mini" @click="clearLogs">清空</button>
      </view>
      <view v-for="(line, idx) in logs" :key="idx" class="log-line">{{ line }}</view>
    </view>
  </view>
</template>

<script>
// import * as PangolinMedia from '@/uni_modules/pangolin-media'

export default {
  data() {
    return {
      form: {
        appId: '',
        appName: 'Mashangzhuan',
        channel: 'test',
        debug: true,
        privacy: {
          consent: false,
          canUsePhoneState: true,
          canUseOAID: true,
          canUseAndroidId: true,
          canUseMac: true
        },
        adSlots: {
          reward: '',
          feed: '',
          splash: '',
          interstitial: ''
        },
        user: {
          userId: 'test_user'
        }
      },
      dramaId: '',
      logs: [],
      lastResult: '{}'
    }
  },
  onLoad() {
    // Object.values(PangolinMedia.Events).forEach((eventName) => {
    //   PangolinMedia.on(eventName, (payload) => {
    //     this.pushLog(`[${eventName}] ${JSON.stringify(payload || {})}`)
    //   })
    // })
  },
  methods: {
    onConsentChange(e) {
      this.form.privacy.consent = !!e.detail.value
    },
    async onInit() {
      // const res = await PangolinMedia.init(this.form)
      // this.handleResult('init', res)
    },
    async onDestroy() {
      // const res = await PangolinMedia.destroy()
      // this.handleResult('destroy', res)
    },
    async showReward() {
      // const res = await PangolinMedia.showRewardVideo({ slotId: this.form.adSlots.reward })
      // this.handleResult('showRewardVideo', res)
    },
    async showFeed() {
      // const res = await PangolinMedia.loadFeed({ slotId: this.form.adSlots.feed })
      // this.handleResult('loadFeed', res)
    },
    async showSplash() {
      // const res = await PangolinMedia.showSplash({ slotId: this.form.adSlots.splash })
      // this.handleResult('showSplash', res)
    },
    async showInterstitial() {
      // const res = await PangolinMedia.showInterstitial({ slotId: this.form.adSlots.interstitial })
      // this.handleResult('showInterstitial', res)
    },
    async openHome() {
      // const res = await PangolinMedia.openDramaHome({})
      // this.handleResult('openDramaHome', res)
    },
    async openDetail() {
      // const res = await PangolinMedia.openDramaDetail({ dramaId: this.dramaId })
      // this.handleResult('openDramaDetail', res)
    },
    async getList() {
      // const res = await PangolinMedia.getDramaList({ mode: 'recommend', page: 1, count: 10 })
      // this.handleResult('getDramaList', res)
    },
    handleResult(action, result) {
      this.lastResult = JSON.stringify(result, null, 2)
      this.pushLog(`[${action}] success=${result.success} code=${result.code} msg=${result.message}`)
      if (!result.success) {
        uni.showToast({ title: `${action}失败:${result.code}`, icon: 'none' })
      }
    },
    pushLog(line) {
      const now = new Date()
      const t = `${now.getHours()}:${now.getMinutes()}:${now.getSeconds()}`
      this.logs.unshift(`${t} ${line}`)
      if (this.logs.length > 80) this.logs = this.logs.slice(0, 80)
    },
    clearLogs() {
      this.logs = []
    }
  }
}
</script>

<style scoped>
.page { padding: 24rpx; }
.card { background: #fff; border-radius: 12rpx; padding: 20rpx; margin-bottom: 20rpx; }
.title { font-size: 34rpx; font-weight: 600; }
.sub { font-size: 30rpx; font-weight: 600; }
.row { margin-top: 12rpx; }
.row text { display: block; color: #555; margin-bottom: 8rpx; font-size: 24rpx; }
.row input { border: 1px solid #ddd; border-radius: 8rpx; padding: 14rpx; }
.switch-row { margin-top: 16rpx; display: flex; align-items: center; justify-content: space-between; }
.btns { margin-top: 16rpx; display: flex; flex-wrap: wrap; gap: 12rpx; }
.json { margin-top: 12rpx; display: block; font-size: 24rpx; color: #333; }
.log-header { display: flex; justify-content: space-between; align-items: center; }
.log-line { margin-top: 8rpx; font-size: 22rpx; color: #333; word-break: break-all; }
</style>

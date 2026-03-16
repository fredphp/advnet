# pangolin-media (UTS)

`pangolin-media` 已采用 UTS 结构开发，目录位于：

- `uni_modules/pangolin-media/index.uts`
- `uni_modules/pangolin-media/utssdk/app-android/index.uts`

## 用法
```js
import * as PangolinMedia from '@/uni_modules/pangolin-media'

await PangolinMedia.init({
  appId: 'your_app_id',
  appName: 'your_app_name',
  channel: 'official',
  debug: false,
  privacy: { consent: true },
  adSlots: {
    reward: 'slot_reward',
    feed: 'slot_feed',
    splash: 'slot_splash',
    interstitial: 'slot_interstitial'
  }
})
```

## 说明
- 当前仅实现 `APP-ANDROID`。
- 事件通过 `on/off` 订阅，事件名见 `Events` 常量。
- 已改为纯 UTS Android 实现，不再桥接旧 `nativeplugins` Java 模块。
- UTS 通过 Android 运行时反射直接调用穿山甲 SDK（`TTAdSdk` 等类），请确保 AAR/JAR 已正确集成。

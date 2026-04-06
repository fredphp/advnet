---
Task ID: 8-10
Agent: main
Task: 检查金币获取全流程并修复发现的问题

Work Log:
- 完整阅读了所有广告相关前后端核心文件
- 追踪了金币从广告浏览到可提现的完整链路
- 发现3个问题并修复

Stage Summary:
- 🔴 严重问题：App端激励视频双重计费（客户端recordView + DCloud serverNotify同时写金币）
  - 修复：watch.vue 中 App端有原生广告时跳过客户端上报，依赖DCloud服务端回调
- 🟡 次要问题：H5模拟广告也扣除30%平台分成（无真实广告成本）
  - 修复：AdIncomeService.php 中只有真实广告(amount>0)才扣平台分成，H5用户获得完整金币
- 🟡 claimWithAd双重计费（redpacket_claim模式）
  - 修复：watch.vue 中 App端redpacket_claim模式用adRedpacketClaim替代adRedpacketClaimWithAd

修改文件：
1. mashangzhuan-app/pages/ad/watch.vue - 两处条件编译(#ifdef APP-PLUS)跳过客户端上报
2. application/common/library/AdIncomeService.php - handleAdCallback()按amount判断是否真实广告

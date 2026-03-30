<template>
  <view class="page">
    <fa-navbar title="幸运大转盘"></fa-navbar>

    <view class="header">
      <!-- <view class="title">幸运大转盘</view> -->
      <view class="sub">剩余次数：<text class="strong">{{ chances }}</text></view>
    </view>

    <!-- 转盘区域 -->
    <view class="wheel-wrap">
      <!-- 外圈装饰 -->
      <!-- <view class="ring"></view> -->

      <!-- 转盘Canvas -->
      <canvas
        class="wheel-canvas"
        canvas-id="wheelCanvas"
        id="wheelCanvas"
        :style="{ width: canvasSize + 'px', height: canvasSize + 'px' }"
      ></canvas>

      <!-- 指针 -->
      <view class="pointer">
        <view class="pointer-tri"></view>
        <view class="pointer-btn" :class="{ disabled: spinning || chances<=0 }" @click="onStart">
          <text class="pointer-text">{{ spinning ? '抽奖中' : '开始' }}</text>
        </view>
      </view>

      <!-- 小灯泡装饰 -->
      <!-- <view class="bulbs">
        <view v-for="i in 16" :key="i" class="bulb" :class="{ on: bulbOn(i) }"></view>
      </view> -->
    </view>

    <!-- 按钮区 -->
    <view class="actions" v-if="false">
      <u-button type="error" :custom-style="btnStyle" @click="showRule=true">活动规则</u-button>
      <u-button type="default" :custom-style="btnStyle2" @click="mockAddChance">+1 次数</u-button>
    </view>

    <!-- 奖品列表 -->
    <view class="prize-panel" v-if="false">
      <view class="panel-title">奖品预览</view>
      <view class="prize-grid">
        <view class="prize-item" v-for="p in prizes" :key="p.id">
          <view class="prize-name u-line-1">{{ p.name }}</view>
          <view class="prize-rate">概率 {{ (p.weight/totalWeight*100).toFixed(1) }}%</view>
        </view>
      </view>
    </view>
	
	<view class="popup">
		<view class="popup-title u-text-left">活动规则</view>
		<view class="popup-text">
		  <view>1. 每次抽奖消耗 1 次机会。</view>
		  <view>2. 抽奖结果以服务端返回为准（本页演示含本地模拟）。</view>
		  <view>3. 奖品概率为示例，可按权重调整。</view>
		  <view>4. 如遇网络异常，请稍后重试。</view>
		</view>
	</view>
	

    <!-- 规则弹窗 -->
    <u-popup v-model="showRule" mode="bottom" border-radius="24">
      <view class="popup">
        <view class="popup-title">活动规则</view>
        <view class="popup-text">
          <view>1. 每次抽奖消耗 1 次机会。</view>
          <view>2. 抽奖结果以服务端返回为准（本页演示含本地模拟）。</view>
          <view>3. 奖品概率为示例，可按权重调整。</view>
          <view>4. 如遇网络异常，请稍后重试。</view>
        </view>
        <u-button type="error" :custom-style="btnStyle" @click="showRule=false">我知道了</u-button>
      </view>
    </u-popup>

    <!-- 结果弹窗 -->
    <u-modal
      v-model="showResult"
      :title="resultTitle"
      :content="resultContent"
      confirm-text="好的"
      confirm-color="#f93110"
      @confirm="showResult=false"
    ></u-modal>

    <u-toast ref="uToast"></u-toast>
  </view>
</template>

<script>
export default {
  data() {
    return {
      themeColor: '#f93110',
      canvasSize: 320, // px，onReady里会按屏幕计算
      ctx: null,

      // 奖品配置（weight = 权重，越大概率越高）
      prizes: [
        { id: 1, name: '谢谢参与', weight: 40, bg: '#fff1ef', color: '#f93110' },
        { id: 2, name: '5 积分', weight: 25, bg: '#ffffff', color: '#1f2329' },
        { id: 3, name: '10 积分', weight: 15, bg: '#fff1ef', color: '#f93110' },
        { id: 4, name: '1 元红包', weight: 10, bg: '#ffffff', color: '#1f2329' },
        { id: 5, name: '3 元红包', weight: 6,  bg: '#fff1ef', color: '#f93110' },
        { id: 6, name: '大奖：免单', weight: 4,  bg: '#ffffff', color: '#1f2329' }
      ],

      chances: 3,
      spinning: false,

      // 当前旋转角度（弧度）
      currentAngle: 0,
      // 动画定时器
      timer: null,

      // 灯泡闪烁
      bulbTick: 0,

      // 弹窗
      showRule: false,
      showResult: false,
      resultTitle: '',
      resultContent: '',

      btnStyle: {
        height: '80rpx',
        lineHeight: '80rpx',
        borderRadius: '40rpx',
        backgroundColor: '#f93110',
        borderColor: '#f93110',
        color: '#fff',
        margin: '0 10rpx'
      },
      btnStyle2: {
        height: '80rpx',
        lineHeight: '80rpx',
        borderRadius: '40rpx',
        backgroundColor: '#ffffff',
        borderColor: '#ffd6d1',
        color: '#f93110',
        margin: '0 10rpx'
      }
    };
  },
  computed: {
    totalWeight() {
      return this.prizes.reduce((s, p) => s + (p.weight || 0), 0);
    }
  },
  onReady() {
    // 自适应尺寸
    try {
      const sys = uni.getSystemInfoSync();
      const w = Math.min(sys.windowWidth, 375);
      this.canvasSize = Math.floor(w * 0.78); // 约占屏幕 78%
    } catch (e) {}

    // 初始化 canvas 上下文
    // #ifdef MP-WEIXIN
    this.ctx = uni.createCanvasContext('wheelCanvas', this);
    // #endif
    // #ifndef MP-WEIXIN
    this.ctx = uni.createCanvasContext('wheelCanvas');
    // #endif

    this.drawWheel();

    // 灯泡闪烁
    this.timerBulb = setInterval(() => {
      this.bulbTick++;
    }, 250);
  },
  onUnload() {
    if (this.timer) clearInterval(this.timer);
    if (this.timerBulb) clearInterval(this.timerBulb);
  },
  methods: {
    bulbOn(i) {
      return (i + this.bulbTick) % 2 === 0;
    },

    // 点击开始
    async onStart() {
      if (this.spinning) return;
      if (this.chances <= 0) {
        this.$refs.uToast.show({ title: '次数不足', type: 'warning' });
        return;
      }

      this.spinning = true;
      this.chances -= 1;

      // 1) 真实业务：先向服务端请求抽奖结果（服务端返回 prizeId）
      // const prizeId = await this.requestLotteryFromServer()

      // 2) 演示：本地按权重抽奖
      const prize = this.pickByWeight();
      const prizeIndex = this.prizes.findIndex(p => p.id === prize.id);

      // 计算需要停到该奖品的角度（指针在上方 12 点方向）
      const targetAngle = this.calcTargetAngle(prizeIndex);

      // 执行动画：先快转若干圈，再减速停到 targetAngle
      this.spinTo(targetAngle, () => {
        this.spinning = false;
        this.resultTitle = '抽奖结果';
        this.resultContent = `恭喜你获得：${prize.name}`;
        this.showResult = true;
      });
    },

    // 权重抽奖
    pickByWeight() {
      const r = Math.random() * this.totalWeight;
      let acc = 0;
      for (const p of this.prizes) {
        acc += p.weight;
        if (r <= acc) return p;
      }
      return this.prizes[this.prizes.length - 1];
    },

    // 计算目标停止角度（弧度）
    // 让目标扇形中心对齐到指针（上方 -90° 方向）
    // 指针方向：12点（上方）
    calcTargetAngle(prizeIndex) {
      const n = this.prizes.length;
      const slice = (Math.PI * 2) / n;
    
      // 扇形中心角（相对第0块）
      const center = (prizeIndex + 0.5) * slice;
    
      // canvas坐标系：0在右侧，-90°在上方
      const pointerAngle = -Math.PI / 2;
    
      // 我们绘制时：扇形中心全局角 = currentAngle + center
      // 目标：currentAngle + center = pointerAngle (mod 2π)
      let target = pointerAngle - center;
    
      // 规范到 [0, 2π)
      const twoPi = Math.PI * 2;
      target = (target % twoPi + twoPi) % twoPi;
    
      return target;
    },


    // 旋转到指定角度（包含多圈 + 缓动减速）
    spinTo(targetAngle, done) {
      if (this.timer) clearInterval(this.timer);
    
      const twoPi = Math.PI * 2;
    
      // 多转几圈
      const rounds = 5 + Math.floor(Math.random() * 3); // 5~7圈
    
      // 计算从当前角到目标角的“顺时针增量”
      const cur = (this.currentAngle % twoPi + twoPi) % twoPi;
      const tar = (targetAngle % twoPi + twoPi) % twoPi;
      let delta = tar - cur;
      if (delta < 0) delta += twoPi;
    
      // 总旋转量
      const total = rounds * twoPi + delta;
    
      const duration = 4200;
      const fps = 60;
      const step = 1000 / fps;
      let t = 0;
      const start = this.currentAngle;
    
      const easeOutCubic = (x) => 1 - Math.pow(1 - x, 3);
    
      this.timer = setInterval(() => {
        t += step;
        const p = Math.min(t / duration, 1);
        const eased = easeOutCubic(p);
    
        this.currentAngle = start + total * eased;
        this.drawWheel();
    
        if (p >= 1) {
          clearInterval(this.timer);
          this.timer = null;
    
          // 最后强制归一化，保证精准落位
          this.currentAngle = (this.currentAngle % twoPi + twoPi) % twoPi;
          this.drawWheel();
    
          done && done();
        }
      }, step);
    },

    // 计算从 current 到 target 的最小正向增量（顺时针）
    normalizeDelta(current, target) {
      const twoPi = Math.PI * 2;
      const c = (current % twoPi + twoPi) % twoPi;
      const t = (target % twoPi + twoPi) % twoPi;
      let d = t - c;
      if (d < 0) d += twoPi;
      return d;
    },

    // 绘制转盘
    drawWheel() {
      const ctx = this.ctx;
      if (!ctx) return;
    
      const size = this.canvasSize;
      const r = size / 2;
      const n = this.prizes.length;
      const slice = (Math.PI * 2) / n;
    
      ctx.clearRect(0, 0, size, size);
    
      // 以中心为原点绘制
      ctx.save();
      ctx.translate(r, r);
    
      // ✅ 不 rotate 整个画布，而是每个扇形用 currentAngle 偏移
      for (let i = 0; i < n; i++) {
        const p = this.prizes[i];
        const start = this.currentAngle + i * slice;
        const end = start + slice;
    
        // 扇形
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.arc(0, 0, r - 6, start, end);
        ctx.closePath();
        ctx.setFillStyle(p.bg);
        ctx.fill();
    
        // 分割线
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.arc(0, 0, r - 6, start, end);
        ctx.closePath();
        ctx.setStrokeStyle('#ffd6d1');
        ctx.setLineWidth(2);
        ctx.stroke();
    
        // ✅ 文字放进扇形内部
        const mid = start + slice * 2;
    
        ctx.save();
        // 旋转到扇形中心方向
        ctx.rotate(mid);
    
        // 沿着半径把文字放到扇形里面（可调：0.62~0.7）
        ctx.translate(0, -(r * 0.62));
    
        // 让文字沿扇形“切线方向”排布（更像你截图）
        ctx.rotate(Math.PI / 2);
    
        ctx.setFillStyle(p.color);
        ctx.setFontSize(16);
        ctx.setTextAlign('center');
        ctx.setTextBaseline('middle');
    
        const name = p.name || '';
        // 简单换行
        if (name.length > 6) {
          ctx.fillText(name.slice(0, 6), 0, -8);
          ctx.fillText(name.slice(6), 0, 12);
        } else {
          ctx.fillText(name, 0, 0);
        }
    
        ctx.restore();
      }
    
      // 中心按钮（与你图一致）
      ctx.beginPath();
      ctx.arc(0, 0, 48, 0, Math.PI * 2);
      ctx.setFillStyle(this.themeColor);
      ctx.fill();
    
      ctx.beginPath();
      ctx.arc(0, 0, 55, 0, Math.PI * 2);
      ctx.setStrokeStyle('#ffffff');
      ctx.setLineWidth(8);
      ctx.stroke();
    
      ctx.setFillStyle('#ffffff');
      ctx.setFontSize(20);
      ctx.setTextAlign('center');
      ctx.setTextBaseline('middle');
      ctx.fillText('开始', 0, 0);
    
      ctx.restore();
      ctx.draw();
    },

    // 演示：增加次数
    mockAddChance() {
      this.chances += 1;
      this.$refs.uToast.show({ title: '次数 +1', type: 'success' });
    },

    // 真实业务：服务端抽奖接口（示例）
    async requestLotteryFromServer() {
      // return new Promise((resolve, reject) => {
      //   uni.request({
      //     url: 'https://api.xxx.com/lottery/draw',
      //     method: 'POST',
      //     data: {},
      //     success: (res) => resolve(res.data.prize_id),
      //     fail: reject
      //   });
      // });
    }
  }
};
</script>

<style lang="scss" scoped>
.page {
  min-height: 100vh;
  background: #f7f8fa;
}

.header {
  padding: 20rpx 24rpx 10rpx;
  text-align: center;

  .title {
    font-size: 40rpx;
    font-weight: 800;
    color: #1f2329;
    letter-spacing: 2rpx;
  }
  .sub {
    margin-top: 8rpx;
    color: #6b7280;
    font-size: 26rpx;

    .strong {
      color: #f93110;
      font-weight: 700;
    }
  }
}

.wheel-wrap {
  position: relative;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0rpx 0 10rpx;
}

.ring {
  position: absolute;
  width: 620rpx;
  height: 620rpx;
  border-radius: 999rpx;
  background: radial-gradient(circle at center, #ffffff 0%, #fff1ef 55%, #ffd6d1 100%);
  box-shadow: 0 18rpx 40rpx rgba(0, 0, 0, 0.06);
}

.wheel-canvas {
  position: relative;
  z-index: 2;
  border-radius: 999rpx;
  background: #fff;
  box-shadow: 0 10rpx 26rpx rgba(0, 0, 0, 0.06);
}

.pointer {
  position: absolute;
  z-index: 3;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 220rpx;
  height: 220rpx;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pointer-tri {
  position: absolute;
  top: -18rpx;
  width: 0;
  height: 0;
  border-left: 18rpx solid transparent;
  border-right: 18rpx solid transparent;
  border-bottom: 36rpx solid #f93110;
  filter: drop-shadow(0 6rpx 10rpx rgba(0, 0, 0, 0.12));
}

.pointer-btn {
  width: 160rpx;
  height: 160rpx;
  border-radius: 999rpx;
  background: #f93110;
  box-shadow: 0 12rpx 24rpx rgba(249, 49, 16, 0.35);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 8rpx solid #ffffff;
}

.pointer-btn.disabled {
  opacity: 0.6;
}

.pointer-text {
  color: #fff;
  font-size: 34rpx;
  font-weight: 800;
}

.bulbs {
  position: absolute;
  z-index: 4;
  width: 660rpx;
  height: 660rpx;
  border-radius: 999rpx;
  pointer-events: none;
  display: flex;
  flex-wrap: wrap;
  align-content: space-between;
  justify-content: space-between;
  padding: 14rpx;
  box-sizing: border-box;
}

.bulb {
  width: 22rpx;
  height: 22rpx;
  border-radius: 999rpx;
  background: #ffe3df;
  box-shadow: inset 0 2rpx 4rpx rgba(0, 0, 0, 0.08);
}

.bulb.on {
  background: #f93110;
  box-shadow: 0 0 16rpx rgba(249, 49, 16, 0.6);
}

.actions {
  padding: 16rpx 24rpx 0;
  display: flex;
}

.prize-panel {
  margin: 18rpx 24rpx 24rpx;
  background: #fff;
  border-radius: 18rpx;
  padding: 18rpx;

  .panel-title {
    font-size: 30rpx;
    font-weight: 700;
    color: #1f2329;
    margin-bottom: 12rpx;
  }

  .prize-grid {
    display: flex;
    flex-wrap: wrap;
    margin: -10rpx;

    .prize-item {
      width: 50%;
      padding: 10rpx;

      .prize-name {
        background: #fff1ef;
        border: 1px solid #ffd6d1;
        color: #1f2329;
        border-radius: 14rpx;
        padding: 14rpx 14rpx 10rpx;
        font-size: 26rpx;
        font-weight: 600;
      }

      .prize-rate {
        margin-top: 8rpx;
        font-size: 22rpx;
        color: #9aa0a6;
        padding-left: 6rpx;
      }
    }
  }
}

.popup {
  padding: 24rpx;
  background: #fff;
  margin:20rpx;
  border-radius: 20rpx;

  .popup-title {
    font-size: 32rpx;
    font-weight: 800;
    color: #1f2329;
    margin-bottom: 12rpx;
    text-align: center;
  }
  .popup-text {
    white-space: pre-line;
    color: #6b7280;
    font-size: 26rpx;
    line-height: 40rpx;
    padding: 10rpx 10rpx 18rpx;
  }
}
</style>

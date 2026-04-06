<template>
	<view class="page">
		<!-- 顶部说明 -->
		<fa-navbar title="砸金蛋赢好礼"></fa-navbar>
		<!-- <view class="title">
			<text class="title-text">砸金蛋赢好礼</text>
		</view> -->

		<!-- 金蛋区域 -->
		<view class="egg-wrap">
			<view
				class="egg-item"
				v-for="(item, index) in eggList"
				:key="index"
				@click="hitEgg(index)"
			>
				<image
					:src="item.open ? openEggImg : eggImg"
					class="egg-img"
					:class="{ shake: item.shaking }"
				></image>
				<text v-if="item.open" class="prize-text">{{ item.prize }}</text>
			</view>
		</view>

		<!-- 规则 -->
		<view class="rule-box">
			<view class="rule-title">活动规则</view>
			<view class="rule-item">1. 每人每日可砸 1 次</view>
			<view class="rule-item">2. 奖品随机发放</view>
			<view class="rule-item">3. 奖品以实际到账为准</view>
		</view>

		<!-- 弹窗 -->
		<u-popup v-model="showPopup" mode="center" border-radius="12">
			<view class="popup">
				<view class="popup-title">恭喜中奖 🎉</view>
				<view class="popup-prize">{{ currentPrize }}</view>
				<u-button
					type="error"
					shape="circle"
					@click="showPopup = false"
				>
					我知道了
				</u-button>
			</view>
		</u-popup>
	</view>
</template>

<script>
export default {
	data() {
		return {
			eggImg: '/static/image/egg.png',        // 金蛋
			openEggImg: '/static/image/egg_open.png', // 砸开的蛋
			showPopup: false,
			currentPrize: '',
			eggList: [
				{ open: false, shaking: false, prize: '' },
				{ open: false, shaking: false, prize: '' },
				{ open: false, shaking: false, prize: '' },
				{ open: false, shaking: false, prize: '' },
				{ open: false, shaking: false, prize: '' },
				{ open: false, shaking: false, prize: '' }
			],
			prizePool: ['10积分', '5元红包', '谢谢参与']
		}
	},
	methods: {
		hitEgg(index) {
			const egg = this.eggList[index]
			if (egg.open) return

			// 摇动动画
			this.$set(this.eggList[index], 'shaking', true)

			setTimeout(() => {
				this.$set(this.eggList[index], 'shaking', false)
				this.$set(this.eggList[index], 'open', true)

				// 抽奖
				const prize =
					this.prizePool[Math.floor(Math.random() * this.prizePool.length)]
				this.$set(this.eggList[index], 'prize', prize)

				this.currentPrize = prize
				this.showPopup = true
			}, 600)
		}
	}
}
</script>

<style scoped>
.page {
	min-height: 100vh;
	background: #f7f8fa;
	padding: 30rpx;
	box-sizing: border-box;
}

/* 标题 */
.title {
	text-align: center;
	margin-bottom: 40rpx;
}
.title-text {
	color: #fff;
	font-size: 36rpx;
	font-weight: bold;
}

/* 金蛋 */
.egg-wrap {
	display: flex;
	justify-content: space-around;
	flex-wrap: wrap;
	margin-bottom: 50rpx;
}
.egg-item {
	text-align: center;
}
.egg-img {
	width: 180rpx;
	height: 220rpx;
}
.prize-text {
	margin-top: 10rpx;
	color: #ffd700;
	font-weight: bold;
}

/* 摇动动画 */
@keyframes shake {
	0% { transform: rotate(0); }
	25% { transform: rotate(10deg); }
	50% { transform: rotate(0); }
	75% { transform: rotate(-10deg); }
	100% { transform: rotate(0); }
}
.shake {
	animation: shake 0.6s;
}

/* 规则 */
.rule-box {
	background: #fff;
	border-radius: 12rpx;
	padding: 20rpx;
	color: #333;
}
.rule-title {
	font-weight: bold;
	margin-bottom: 10rpx;
}
.rule-item {
	font-size: 24rpx;
	line-height: 40rpx;
}

/* 弹窗 */
.popup {
	padding: 40rpx;
	text-align: center;
}
.popup-title {
	font-size: 32rpx;
	font-weight: bold;
	margin-bottom: 20rpx;
}
.popup-prize {
	color: #f93110;
	font-size: 30rpx;
	margin-bottom: 30rpx;
}
</style>

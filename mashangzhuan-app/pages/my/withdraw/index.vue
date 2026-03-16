<template>
	<view class="fui-wrap">
		<fa-navbar title="提现" :border-bottom="false"></fa-navbar>
		<view class="withdraw-page-header">
			<!-- 账户余额区域 -->
			<view class="balance-section-wrap">
				<view class="balance-section">
					<text class="balance-label">可提现余额</text>
					<view class="balance-amount">
						<text class="currency">¥</text>
						<text class="amount">{{ agentUser.income_money }}</text>
					</view>
				</view>
			</view>
		</view>
		
		<view class="withdraw-container">
			<!-- 提现表单 -->
			<view class="withdraw-form">
				<view class="form-item">
					<text class="label">提现金额</text>
					<view class="amount-options">
						<view class="amount-options-grid">
							<view 
								v-for="option in amountOptions" 
								:key="option"
								class="amount-option"
								:class="{ active: amount === option, primary: amount === option }"
								@click="selectAmount(option)"
							>
								<view class="amount-tag">每日一次</view>
								<text class="amount-value">{{option}}元</text>
								<text class="amount-desc">立即提现</text>
							</view>
						</view>
					</view>
					<view class="error-tip" v-if="amountError">{{amountError}}</view>
				</view>
				<view class="form-item">
					<view class="fee-flex-wrap">
						<view class="service-fee">
							<text class="service-fee-lab">手续费：</text>
							<text class="service-fee-num">¥0</text>
						</view>
					</view>
				</view>
			</view>
			<view class="withdraw-form">
				<view class="form-item">
					<text class="label">提现方式</text>
					<view class="picker">
						微信提现
					</view>
				</view>
			</view>
			
			<!-- 提现按钮 -->
			<view class="btn-wrapper">
				<button class="withdraw-btn" :disabled="!canSubmit" :class="{disabled: !canSubmit}" @click="submitWithdraw">
					立即提现
				</button>
			</view>
			
			<!-- 提现规则 -->
			<view class="rules">
				<view class="rules-title">提现规则</view>
				<view class="rule-item">1. 最低提现金额{{minAmount}}元</view>
				<view class="rule-item">2. 提现手续费{{feeRate}}%</view>
				<view class="rule-item">3. 提现申请将在1-3个工作日内处理</view>
			</view>
		</view>

		
	</view>
</template>

<script>
	export default {
		data() {
			return {
				scrollTop: 0,
				balance: 1000.50, // 可提现余额
				feeRate: 1, // 手续费百分比
				minAmount: 0.3, // 最低提现金额
				amount: '', // 提现金额
				amountError: '', // 金额错误提示

				// 提现方式
				selectedMethod: {
					id: 1,
					name: '微信提现',
					type: 'wechat'
				},

				// 金额选项
				amountOptions: [0.3, 1, 2, 5, 10, 20, 50, 100],

				agentUser:{}
			}
		},
		computed: {
			// 是否可以提交
			canSubmit() {
				return this.amount &&
					!this.amountError
			}
		},
		onLoad() {
			this.getUserInfo();
			this.getAgentUser();
		},
		methods: {
			async getUserInfo() {
				// 从服务器获取用户信息
				let res = await this.$api.getUserIndex();
				uni.stopPullDownRefresh();
				if (res.code == 1) {
					this.$u.vuex('vuex_user', res.data.userInfo || {});
				} else {
					this.$u.toast(res.msg);
					return;
				}
			},
			getAgentUser() {
				this.$u.get("/addons/coagent/api/get_agent_user")
					.then(res => {
						if (res.code === 1) {
							this.agentUser = res.data;
							this.$u.vuex("vuex_agent_user", this.agentUser);
						}
					})
					.catch(err => {
						console.error('获取代理用户信息失败：', err);
					});
			},
			back() {
				let canNavBack = getCurrentPages();
				if (canNavBack && canNavBack.length > 1) {
					uni.navigateBack()
				} else {
					// #ifdef H5
					history.back();
					// #endif
					uni.reLaunch({
						url: '/pages/index/index'
					})
				}
			
			},

			// 验证提现金额
			validateAmount() {
				const amount = parseFloat(this.amount)

				if (isNaN(amount)) {
					this.amountError = '请输入有效金额'
					return
				}

				if (amount < this.minAmount) {
					this.amountError = `提现金额不能低于${this.minAmount}元`
					return
				}

				const balance = this.agentUser.income_money || 0
				if (amount > balance) {
					this.amountError = '提现金额不能超过可提现余额'
					return
				}

				this.amountError = ''
			},

			// 选择金额选项
			selectAmount(option) {
				this.amount = option
				this.validateAmount()
			},



			// 提交提现申请
			submitWithdraw() {
				if (!this.canSubmit) return
				this.$u.post("/addons/cowithdraw/api/withdraw",{
					money:this.amount,
					payment:this.selectedMethod.type,
					account:'',
				}).then(res => {
					this.$u.toast(res.msg);
					if(res.code == 1){
						this.amount = ''
						this.getAgentUser();
					}
				})
			}
		},
		onPageScroll(e) {
			this.scrollTop = e.scrollTop;
		}
	}
</script>

<style lang="scss" scoped>
	.withdraw-page-header {
		// background: linear-gradient(#dae4f2, #ebeff3, #f9f9fc);
	}
	
	.balance-section-wrap {
		padding: 40rpx 40rpx;
	}
	
	.balance-section {
		 
	}
	
	.balance-label {
		display: block;
		font-size: 30rpx;
		color: #111;
		margin-bottom: 20rpx;
	}
	
	.balance-amount {
		display: flex;
		align-items: baseline;
	}
	
	.currency {
		font-size:30rpx;
		color: #111;
		font-weight: 500;
		margin-right: 8rpx;
	}
	
	.amount {
		font-size: 60rpx;
		font-weight: 500;
		color: #111;
	}

	 
	.balance-tip {
		font-size: 12px;
		opacity: 0.8;
	}
	
	.withdraw-container {
		margin-top:20rpx;
		padding:0rpx 30rpx 30rpx 30rpx;
	}
	

	.withdraw-form {
		background-color: #fff;
		border-radius: 30rpx;
		padding:6rpx 30rpx;
		margin-bottom: 24rpx;
		box-shadow: 0rpx 4rpx 16rpx 0rpx rgba(0, 0, 0, 0.05);
	}

	.form-item {
		padding:24rpx 0;
		border-bottom: 1px solid #f0f0f0;
	}

	.form-item:last-child {
		border-bottom: none;
	}

	.label {
		font-size: 28rpx;
		color: #666;
		display: block;
		margin-bottom: 20rpx;
	}

	.input-wrapper {
		padding:0 24rpx;
		display: flex;
		align-items: center;
		background-color: #f8f8f8;
		border-radius: 16rpx;
	}

	.prefix {
		font-size: 28rpx;
		color: #111;
		margin-right: 10rpx;
	}

	.form-input {
		flex: 1;
		font-size:30rpx;
		height: 80rpx;
	}

	.picker {
		height: 80rpx;
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size:30rpx;
		color: #111;
		padding:0 24rpx;
		background-color: #f8f8f9;
		border-radius: 16rpx;
	}

	.error-tip {
		color: #ff4d4f;
		font-size: 24rpx;
		margin-top:12rpx;
	}
	.amount-options {
		margin-top: 10rpx;
	}
	.amount-options-grid {
		display: flex;
		flex-wrap: wrap;
		gap: 6rpx;
	}
	.amount-option {
		flex: 0 0 calc(33.333% - 10rpx);
		position: relative;
		padding: 20rpx 16rpx;
		background-color: #f8f8f8;
		border-radius: 16rpx;
		border: 2rpx solid transparent;
		text-align: center;
		overflow: hidden;
	}
	.amount-option.primary {
		background-color: #ff4d4f;
		color: #fff;
		border: 2rpx solid #ff4d4f;
	}
	.amount-option.active {
		background-color: #ff4d4f;
		color: #fff;
		border: 2rpx solid #ff4d4f;
	}
	.amount-tag {
		position: absolute;
		top: 0;
		right: 0;
		background-color: #ffc107;
		color: #fff;
		font-size: 20rpx;
		padding: 4rpx 12rpx;
		border-bottom-left-radius: 16rpx;
		z-index: 1;
	}
	.amount-option.primary .amount-tag {
		background-color: rgba(255, 255, 255, 0.3);
	}
	.amount-option.active .amount-tag {
		background-color: rgba(255, 255, 255, 0.3);
	}
	.amount-value {
		display: block;
		font-size: 32rpx;
		font-weight: bold;
		margin: 12rpx 0 8rpx 0;
		color: #333;
	}
	.amount-option.primary .amount-value {
		color: #fff;
	}
	.amount-option.active .amount-value {
		color: #fff;
	}
	.amount-desc {
		display: block;
		font-size: 24rpx;
		color: #999;
	}
	.amount-option.primary .amount-desc {
		color: rgba(255, 255, 255, 0.8);
	}
	.amount-option.active .amount-desc {
		color: rgba(255, 255, 255, 0.8);
	}
	.fee-flex-wrap{
		display: flex;
		align-items: center;
		justify-content: space-between;
	}
	.service-fee-lab{
		color: #666;
		font-size: 26rpx;
	}
	.service-fee-num{
		color: #ff0000;
		font-size: 26rpx;
	}
	.all-withdraw{
		color: #ff0000;
		font-size: 26rpx;
	}

	.btn-wrapper {
		margin:80rpx 0;
	}

	.withdraw-btn {
		display: flex;
		align-items: center;
		justify-content: center;
		background: linear-gradient(to right, #90532f, #c87341);
		color: #fff;
		border-radius: 80rpx;
		height: 88rpx;
		line-height: 88rpx;
		font-size: 32rpx;
	}
	.withdraw-btn::after{
		content: none;
	}

	.withdraw-btn.disabled {
		opacity: 0.5;
	}

	.rules {
		padding:10rpx;
	}

	.rules-title {
		font-size:28rpx;
		font-weight: bold;
		margin-bottom:20rpx;
		color: #666;
	}

	.rule-item {
		font-size: 26rpx;
		color: #999;
		margin-bottom: 10rpx;
	}
</style>
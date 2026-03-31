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
						<text class="amount">{{ balance }}</text>
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
								:key="option.cash_amount"
								class="amount-option"
								:class="{ active: selectedAmount === option.cash_amount }"
								@click="selectAmount(option)"
							>
								<view class="amount-tag">{{ option.coin_amount }}金币</view>
								<text class="amount-value">{{ option.cash_amount }}元</text>
								<text class="amount-desc">立即提现</text>
							</view>
						</view>
					</view>
					<view class="error-tip" v-if="amountError">{{ amountError }}</view>
				</view>
				<view class="form-item">
					<view class="fee-flex-wrap">
						<view class="service-fee">
							<text class="service-fee-lab">手续费：</text>
							<text class="service-fee-num">{{ feeRate }}%</text>
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
				<button class="withdraw-btn" :disabled="!canSubmit || submitting" :class="{disabled: !canSubmit}" @click="submitWithdraw">
					{{ submitting ? '提交中...' : '立即提现' }}
				</button>
			</view>

			<!-- 提现规则 -->
			<view class="rules">
				<view class="rules-title">提现规则</view>
				<view class="rule-item">1. 最低提现金额{{ minAmount }}元</view>
				<view class="rule-item">2. 提现手续费{{ feeRate }}%</view>
				<view class="rule-item">3. 提现申请将在1-3个工作日内处理</view>
			</view>
		</view>
	</view>
</template>

<script>
	export default {
		data() {
			return {
				balance: '0.00',
				feeRate: 0,
				minAmount: 0,
				selectedAmount: null,
				selectedCoinAmount: 0,
				amountError: '',
				submitting: false,
				amountOptions: [],
				withdrawEnabled: true,
			}
		},
		computed: {
			canSubmit() {
				return this.selectedAmount && !this.amountError && this.withdrawEnabled;
			}
		},
		onLoad() {
			this.getWithdrawConfig();
		},
		methods: {
			getWithdrawConfig() {
				this.$api.withdrawConfig().then(res => {
					if (res && res.code == 1) {
						const data = res.data;
						this.balance = parseFloat(data.balance || 0).toFixed(2);
						this.feeRate = data.fee_rate || 0;
						this.minAmount = data.min_withdraw || 1;
						this.amountOptions = data.amount_options || [];
						this.withdrawEnabled = data.withdraw_enabled != 0;
					}
				}).catch(err => {
					console.error('[Withdraw] config接口异常:', err);
				});
			},

			selectAmount(option) {
				this.selectedAmount = option.cash_amount;
				this.selectedCoinAmount = option.coin_amount;
				// 校验余额
				if (parseFloat(this.balance) < option.cash_amount) {
					this.amountError = '提现金额不能超过可提现余额';
				} else {
					this.amountError = '';
				}
			},

			submitWithdraw() {
				if (!this.canSubmit || this.submitting) return;
				this.submitting = true;

				this.$api.withdrawApply({
					coin_amount: this.selectedCoinAmount,
					withdraw_type: 'wechat',
					withdraw_account: '',
					withdraw_name: ''
				}).then(res => {
					if (res && res.code == 1) {
						uni.showToast({ title: '提现申请已提交', icon: 'success' });
						this.selectedAmount = null;
						this.selectedCoinAmount = 0;
						this.amountError = '';
						// 刷新余额
						this.getWithdrawConfig();
					} else {
						uni.showToast({ title: res.msg || '提现失败', icon: 'none' });
					}
				}).catch(err => {
					console.error('[Withdraw] apply接口异常:', err);
					uni.showToast({ title: '提现失败', icon: 'none' });
				}).finally(() => {
					this.submitting = false;
				});
			}
		}
	}
</script>

<style lang="scss" scoped>
	.withdraw-page-header {}

	.balance-section-wrap {
		padding: 40rpx 40rpx;
	}

	.balance-section {}

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
		font-size: 30rpx;
		color: #111;
		font-weight: 500;
		margin-right: 8rpx;
	}

	.amount {
		font-size: 60rpx;
		font-weight: 500;
		color: #111;
	}

	.withdraw-container {
		margin-top: 20rpx;
		padding: 0rpx 30rpx 30rpx 30rpx;
	}

	.withdraw-form {
		background-color: #fff;
		border-radius: 30rpx;
		padding: 6rpx 30rpx;
		margin-bottom: 24rpx;
		box-shadow: 0rpx 4rpx 16rpx 0rpx rgba(0, 0, 0, 0.05);
	}

	.form-item {
		padding: 24rpx 0;
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

	.picker {
		height: 80rpx;
		display: flex;
		justify-content: space-between;
		align-items: center;
		font-size: 30rpx;
		color: #111;
		padding: 0 24rpx;
		background-color: #f8f8f9;
		border-radius: 16rpx;
	}

	.error-tip {
		color: #ff4d4f;
		font-size: 24rpx;
		margin-top: 12rpx;
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

	.amount-option.active .amount-value {
		color: #fff;
	}

	.amount-desc {
		display: block;
		font-size: 24rpx;
		color: #999;
	}

	.amount-option.active .amount-desc {
		color: rgba(255, 255, 255, 0.8);
	}

	.fee-flex-wrap {
		display: flex;
		align-items: center;
		justify-content: space-between;
	}

	.service-fee-lab {
		color: #666;
		font-size: 26rpx;
	}

	.service-fee-num {
		color: #ff0000;
		font-size: 26rpx;
	}

	.btn-wrapper {
		margin: 80rpx 0;
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

	.withdraw-btn::after {
		content: none;
	}

	.withdraw-btn.disabled {
		opacity: 0.5;
	}

	.rules {
		padding: 10rpx;
	}

	.rules-title {
		font-size: 28rpx;
		font-weight: bold;
		margin-bottom: 20rpx;
		color: #666;
	}

	.rule-item {
		font-size: 26rpx;
		color: #999;
		margin-bottom: 10rpx;
	}
</style>

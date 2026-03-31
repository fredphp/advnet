<template>
        <view class="fui-wrap">
                <fa-navbar title="提现" :border-bottom="false"></fa-navbar>

                <!-- 顶部余额卡片 -->
                <view class="balance-card">
                        <view class="balance-card-bg"></view>
                        <view class="balance-card-content">
                                <!-- 金币余额 -->
                                <view class="coin-row">
                                        <text class="coin-icon">🪙</text>
                                        <view class="coin-info">
                                                <text class="coin-label">金币余额</text>
                                                <view class="coin-amount-wrap">
                                                        <text class="coin-amount">{{ formatCoin(balance) }}</text>
                                                        <text class="coin-unit">金币</text>
                                                </view>
                                        </view>
                                </view>
                                <!-- 分割线 -->
                                <view class="divider"></view>
                                <!-- 可提现余额 -->
                                <view class="cash-row">
                                        <text class="cash-icon">💰</text>
                                        <view class="cash-info">
                                                <text class="cash-label">可提现余额</text>
                                                <view class="cash-amount-wrap">
                                                        <text class="cash-currency">¥</text>
                                                        <text class="cash-amount">{{ cashBalance }}</text>
                                                </view>
                                        </view>
                                        <!-- 汇率说明 -->
                                        <view class="rate-badge">
                                                <text class="rate-text">{{ exchangeRate }}金币=1元</text>
                                        </view>
                                </view>
                                <!-- 冻结金币 -->
                                <view class="frozen-row" v-if="frozen > 0">
                                        <text class="frozen-label">冻结金币：</text>
                                        <text class="frozen-value">{{ formatCoin(frozen) }}金币</text>
                                </view>
                        </view>
                </view>

                <view class="withdraw-container">
                        <!-- 提现金额选项 -->
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
                                                                <view class="amount-tag">{{ formatCoin(option.coin_amount) }}金币</view>
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
                                <view class="rule-item">1. {{ exchangeRate }}金币=1元，最低提现{{ minAmount }}元</view>
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
                                balance: 0,              // 金币余额（原始金币数）
                                frozen: 0,               // 冻结金币
                                exchangeRate: 10000,     // 金币汇率
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
                        // 可提现余额（元）= 金币余额 / 汇率
                        cashBalance() {
                                const rate = parseFloat(this.exchangeRate) || 10000;
                                return (parseFloat(this.balance) / rate).toFixed(2);
                        },
                        canSubmit() {
                                return this.selectedAmount && !this.amountError && this.withdrawEnabled;
                        }
                },
                onLoad() {
                        this.getWithdrawConfig();
                },
                methods: {
                        // 格式化金币数字（加千分位逗号）
                        formatCoin(num) {
                                const n = parseFloat(num) || 0;
                                return n.toLocaleString('en-US');
                        },

                        getWithdrawConfig() {
                                this.$api.withdrawConfig().then(res => {
                                        if (res && res.code == 1) {
                                                const data = res.data;
                                                this.balance = parseFloat(data.balance || 0);
                                                this.frozen = parseFloat(data.frozen || 0);
                                                this.exchangeRate = parseFloat(data.exchange_rate) || 10000;
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
                                // 校验余额（金币对比金币）
                                if (this.balance < option.coin_amount) {
                                        this.amountError = '金币余额不足，无法提现';
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
        /* ==================== 顶部余额卡片 ==================== */
        .balance-card {
                position: relative;
                margin: 20rpx 30rpx 0;
                border-radius: 24rpx;
                overflow: hidden;
                background: linear-gradient(160deg, #FFF5F5 0%, #FFEDED 100%);
                box-shadow: 0 4rpx 20rpx 0rpx rgba(230, 33, 41, 0.06);
        }

        .balance-card-bg {
                display: none;
        }

        .balance-card-content {
                position: relative;
                z-index: 1;
                padding: 40rpx 36rpx 36rpx;
        }

        /* 金币行 */
        .coin-row {
                display: flex;
                align-items: center;
        }

        .coin-icon {
                font-size: 48rpx;
                margin-right: 16rpx;
        }

        .coin-info {
                flex: 1;
        }

        .coin-label {
                display: block;
                font-size: 26rpx;
                color: #999;
                margin-bottom: 8rpx;
        }

        .coin-amount-wrap {
                display: flex;
                align-items: baseline;
        }

        .coin-amount {
                font-size: 52rpx;
                font-weight: 700;
                color: #333;
                letter-spacing: 1rpx;
        }

        .coin-unit {
                font-size: 24rpx;
                color: #999;
                margin-left: 8rpx;
        }

        /* 分割线 */
        .divider {
                height: 1rpx;
                background: rgba(230, 33, 41, 0.1);
                margin: 28rpx 0;
        }

        /* 可提现余额行 */
        .cash-row {
                display: flex;
                align-items: center;
        }

        .cash-icon {
                font-size: 48rpx;
                margin-right: 16rpx;
        }

        .cash-info {
                flex: 1;
        }

        .cash-label {
                display: block;
                font-size: 26rpx;
                color: #999;
                margin-bottom: 8rpx;
        }

        .cash-amount-wrap {
                display: flex;
                align-items: baseline;
        }

        .cash-currency {
                font-size: 28rpx;
                font-weight: 600;
                color: #E62129;
                margin-right: 4rpx;
        }

        .cash-amount {
                font-size: 56rpx;
                font-weight: 700;
                color: #E62129;
                letter-spacing: 1rpx;
        }

        /* 汇率徽章 */
        .rate-badge {
                background: rgba(230, 33, 41, 0.08);
                border-radius: 20rpx;
                padding: 8rpx 20rpx;
                margin-left: 20rpx;
        }

        .rate-text {
                font-size: 22rpx;
                color: #E62129;
                white-space: nowrap;
        }

        /* 冻结金币行 */
        .frozen-row {
                margin-top: 20rpx;
                padding-top: 20rpx;
                border-top: 1rpx solid rgba(230, 33, 41, 0.08);
        }

        .frozen-label {
                font-size: 24rpx;
                color: #bbb;
        }

        .frozen-value {
                font-size: 24rpx;
                color: #E62129;
        }

        /* ==================== 表单区域 ==================== */
        .withdraw-container {
                margin-top: 30rpx;
                padding: 0rpx 30rpx 30rpx 30rpx;
        }

        .withdraw-form {
                background-color: #fff;
                border-radius: 24rpx;
                padding: 6rpx 30rpx;
                margin-bottom: 24rpx;
                box-shadow: 0rpx 4rpx 20rpx 0rpx rgba(0, 0, 0, 0.05);
        }

        .form-item {
                padding: 24rpx 0;
                border-bottom: 1px solid #f5f5f5;
        }

        .form-item:last-child {
                border-bottom: none;
        }

        .label {
                font-size: 28rpx;
                color: #666;
                display: block;
                margin-bottom: 20rpx;
                font-weight: 500;
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
                color: #E62129;
                font-size: 24rpx;
                margin-top: 12rpx;
        }

        .amount-options {
                margin-top: 10rpx;
        }

        .amount-options-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 16rpx;
        }

        .amount-option {
                flex: 0 0 calc(33.333% - 12rpx);
                position: relative;
                padding: 24rpx 16rpx 20rpx;
                background-color: #f8f8f9;
                border-radius: 16rpx;
                border: 2rpx solid transparent;
                text-align: center;
                overflow: hidden;
                transition: all 0.2s ease;
        }

        .amount-option.active {
                background: linear-gradient(135deg, #FF4D4F, #E62129);
                color: #fff;
                border: 2rpx solid #E62129;
                transform: scale(1.02);
        }

        .amount-tag {
                position: absolute;
                top: 0;
                right: 0;
                background: linear-gradient(135deg, #ffc107, #ffb300);
                color: #fff;
                font-size: 20rpx;
                padding: 4rpx 14rpx;
                border-bottom-left-radius: 14rpx;
                z-index: 1;
                font-weight: 500;
        }

        .amount-option.active .amount-tag {
                background: rgba(255, 255, 255, 0.25);
        }

        .amount-value {
                display: block;
                font-size: 34rpx;
                font-weight: 700;
                margin: 16rpx 0 10rpx 0;
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
                color: #E62129;
                font-size: 26rpx;
                font-weight: 600;
        }

        /* ==================== 按钮 ==================== */
        .btn-wrapper {
                margin: 70rpx 0 40rpx;
        }

        .withdraw-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #E62129, #c41a22);
                color: #fff;
                border-radius: 80rpx;
                height: 96rpx;
                line-height: 96rpx;
                font-size: 34rpx;
                font-weight: 600;
                letter-spacing: 2rpx;
                box-shadow: 0 8rpx 24rpx rgba(230, 33, 41, 0.35);
        }

        .withdraw-btn::after {
                content: none;
        }

        .withdraw-btn.disabled {
                opacity: 0.45;
                box-shadow: none;
        }

        /* ==================== 规则 ==================== */
        .rules {
                padding: 10rpx;
        }

        .rules-title {
                font-size: 28rpx;
                font-weight: 600;
                margin-bottom: 20rpx;
                color: #666;
        }

        .rule-item {
                font-size: 26rpx;
                color: #999;
                margin-bottom: 10rpx;
                line-height: 1.6;
        }
</style>

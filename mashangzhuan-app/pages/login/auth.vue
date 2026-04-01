<template>
	<view>
		<!-- 顶部导航 -->
		<fa-navbar title="授权登录"></fa-navbar>
		<u-modal v-model="show" title="" :content="content" confirm-text="返回" @confirm="confirm">
			<view class="slot-content u-text-center u-m-b-30">
				<u-loading mode="flower" size="100"></u-loading>
				<view class="u-p-20">{{ content }}</view>
			</view>
		</u-modal>
	</view>
</template>

<script>
	import { loginfunc } from '@/common/fa.mixin.js';
	export default {
		mixins: [loginfunc],
		onLoad() {
			this.state = this.$util.getQueryString('state');
			this.code = this.$util.getQueryString('code');
			if (this.state && this.code) {
				this.goWxAuth();
			} else {
				this.content = '授权登录失败！未获取到授权参数';
			}
			
			this.si = setTimeout(() => {
				this.show = true;
			}, 1000);
		},
		data() {
			return {
				state: '',
				code: '',
				show: false,
				si: null,
				content: '授权登录中...'
			};
		},
		methods: {
			goWxAuth: async function() {
				try {
					let res = await this.$api.goOfficialLogin({
						code: this.code,
						state: this.state,
						invite_code: this.vuex_invitecode || ''
					});
					if (!res || !res.code) {
						clearTimeout(this.si);
						this.content = res && res.msg ? res.msg : '授权登录失败！';
						return;
					}
					clearTimeout(this.si);
					this.show = false;
					
					// 新接口返回 {user_id, token, userinfo, is_new}
					if (res.data.token) {
						this.$u.vuex('vuex_token', res.data.token);
						this.$u.vuex('vuex_user', res.data.userinfo || {});
						this.success();
						return;
					}
					this.content = '授权登录失败：未获取到登录凭证';
				} catch (e) {
					clearTimeout(this.si);
					console.error('微信授权登录异常:', e);
					this.content = '授权登录失败：网络异常';
				}
			},
			confirm() {
				window.history.go(-2);
			}
		}
	};
</script>

<style></style>

/**
 * 多端分享工具模块
 * 支持: APP (原生分享)、H5 (微信JSSDK/浏览器分享)、小程序 (原生转发)
 */

/**
 * 判断当前运行平台
 * @returns {'app' | 'h5' | 'mp-weixin' | 'mp-alipay' | 'unknown'}
 */
export function getPlatform() {
	// #ifdef APP-PLUS
	return 'app';
	// #endif
	// #ifdef H5
	return 'h5';
	// #endif
	// #ifdef MP-WEIXIN
	return 'mp-weixin';
	// #endif
	// #ifdef MP-ALIPAY
	return 'mp-alipay';
	// #endif
	return 'unknown';
}

/**
 * 判断H5环境是否在微信浏览器中
 * @returns {boolean}
 */
export function isWechatBrowser() {
	// #ifdef H5
	const ua = window.navigator.userAgent.toLowerCase();
	return ua.indexOf('micromessenger') !== -1;
	// #endif
	return false;
}

/**
 * H5端: 获取分享链接（邀请链接）
 * @param {object} userInfo
 * @returns {string}
 */
export function getShareLink(userInfo) {
	return userInfo.invite_link || window.location.href || '';
}

/**
 * H5端: 获取分享文案
 * @returns {object} { title, desc, link, imgUrl }
 */
export function getShareData(userInfo) {
	const link = getShareLink(userInfo);
	return {
		title: '马上赚 - 邀请你一起赚钱',
		desc: '我正在使用马上赚APP，邀请你一起赚钱！快来看看吧',
		link: link,
		imgUrl: userInfo.avatar || ''
	};
}

/**
 * APP端: 通过 uni.share 分享
 * @param {object} params - { provider, title, summary, href, imageUrl }
 * @returns {Promise}
 */
export function appShare(params) {
	// #ifdef APP-PLUS
	return new Promise((resolve, reject) => {
		uni.share({
			provider: params.provider,
			scene: params.scene || 'WXSceneSession', // WXSceneSession=好友, WXSenceTimeline=朋友圈
			type: params.type || 0, // 0=图文
			title: params.title,
			summary: params.summary,
			href: params.href,
			imageUrl: params.imageUrl,
			success: () => resolve(),
			fail: (err) => reject(err)
		});
	});
	// #endif
	// #ifndef APP-PLUS
	return Promise.reject(new Error('当前不支持APP原生分享'));
	// #endif
}

/**
 * APP端: 系统级分享
 * @param {object} params - { content }
 * @returns {Promise}
 */
export function appSystemShare(params) {
	// #ifdef APP-PLUS
	return new Promise((resolve, reject) => {
		plus.share.sendWithSystem({
			type: 'text',
			content: params.content
		}, () => {
			resolve();
		}, (err) => {
			reject(err);
		});
	});
	// #endif
	// #ifndef APP-PLUS
	return Promise.reject(new Error('当前不支持系统分享'));
	// #endif
}

/**
 * H5端: 通过微信JSSDK分享
 * @param {object} vm - Vue实例（需要引用 weixinShare mixin 或传入 getSigned API）
 * @param {object} shareData - { title, desc, link, imgUrl }
 * @param {string} scene - 'friend' | 'timeline'
 * @returns {Promise}
 */
export function h5WechatShare(vm, shareData, scene) {
	// #ifdef H5
	return new Promise((resolve, reject) => {
		if (typeof jweixin === 'undefined') {
			reject(new Error('微信JSSDK未加载'));
			return;
		}

		const url = window.location.href.split('#')[0];
		vm.$api.getSigned({ url: url }).then(res => {
			if (res && res.code == 1) {
				jweixin.config({
					debug: false,
					appId: res.data.appId,
					timestamp: res.data.timestamp,
					nonceStr: res.data.nonceStr,
					signature: res.data.signature,
					jsApiList: [
						'checkJsApi',
						'updateAppMessageShareData',
						'updateTimelineShareData'
					]
				});

				jweixin.ready(function() {
					const wxShareData = {
						title: shareData.title,
						desc: shareData.desc,
						link: shareData.link,
						imgUrl: shareData.imgUrl,
						success: function() {
							resolve();
						},
						cancel: function() {
							reject(new Error('用户取消分享'));
						}
					};

					if (scene === 'friend') {
						jweixin.updateAppMessageShareData(wxShareData);
					} else if (scene === 'timeline') {
						jweixin.updateTimelineShareData(wxShareData);
					}
					// JSSDK updateAppMessageShareData 不返回回调Promise
					// 分享成功需要延迟resolve（引导用户去点击右上角）
					setTimeout(() => resolve(), 500);
				});
			} else {
				reject(new Error(res.msg || '获取签名失败'));
			}
		}).catch(err => {
			reject(err);
		});
	});
	// #endif
	// #ifndef H5
	return Promise.reject(new Error('当前不是H5环境'));
	// #endif
}

/**
 * H5端: 复制链接到剪贴板
 * @param {string} text
 */
export function h5CopyToClipboard(text) {
	// #ifdef H5
	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(text).then(() => {
			uni.showToast({ title: '已复制到剪贴板', icon: 'success' });
		}).catch(() => {
			fallbackCopy(text);
		});
	} else {
		fallbackCopy(text);
	}
	// #endif
	// #ifndef H5
	uni.setClipboardData({
		data: text,
		success: () => {
			uni.showToast({ title: '已复制', icon: 'success' });
		}
	});
	// #endif
}

function fallbackCopy(text) {
	const textarea = document.createElement('textarea');
	textarea.value = text;
	textarea.style.position = 'fixed';
	textarea.style.left = '-9999px';
	document.body.appendChild(textarea);
	textarea.select();
	try {
		document.execCommand('copy');
		uni.showToast({ title: '已复制到剪贴板', icon: 'success' });
	} catch (e) {
		uni.showToast({ title: '复制失败，请手动复制', icon: 'none' });
	}
	document.body.removeChild(textarea);
}

/**
 * H5端: 下载图片（保存海报）
 * @param {string} base64 - 图片base64
 * @param {string} filename
 */
export function h5DownloadImage(base64, filename) {
	// #ifdef H5
	const link = document.createElement('a');
	link.href = base64;
	link.download = filename || 'invite-poster.png';
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
	// #endif
}

/**
 * 生成小程序码路径 (用于小程序分享卡片路径)
 * @param {string} inviteCode
 * @returns {string}
 */
export function getMiniProgramSharePath(inviteCode) {
	if (!inviteCode) return '/pages/index/index';
	return `/pages/index/index?invite_code=${inviteCode}`;
}

export default {
	getPlatform,
	isWechatBrowser,
	getShareLink,
	getShareData,
	appShare,
	appSystemShare,
	h5WechatShare,
	h5CopyToClipboard,
	h5DownloadImage,
	getMiniProgramSharePath
};

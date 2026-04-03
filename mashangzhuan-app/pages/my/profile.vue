<template>
        <view class="profile-container">
                <!-- 顶部导航 -->
                <fa-navbar title="个人资料" :border-bottom="false"></fa-navbar>

                <!-- 加载中 -->
                <view class="loading-wrapper" v-if="loading">
                        <u-loading mode="circle" size="60" color="#E62129"></u-loading>
                        <text class="loading-text">加载中...</text>
                </view>

                <!-- 表单内容 -->
                <view class="profile-content" v-if="!loading">
                        <!-- 头像区 -->
                        <view class="avatar-section">
                                <view class="section-title">头像</view>
                                <view class="avatar-row" @click="handleChooseAvatar">
                                        <u-avatar :src="form.avatar || defaultAvatar" size="120" shape="circle"></u-avatar>
                                        <view class="avatar-action">
                                                <text class="avatar-hint">点击更换头像</text>
                                                <u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
                                        </view>
                                </view>
                        </view>

                        <!-- 基本信息 -->
                        <view class="form-section">
                                <view class="section-title">基本信息</view>

                                <!-- 用户名 -->
                                <view class="form-item">
                                        <text class="form-label">用户名</text>
                                        <input
                                                class="form-input"
                                                v-model="form.username"
                                                placeholder="请输入用户名"
                                                maxlength="32"
                                        />
                                </view>

                                <!-- 昵称 -->
                                <view class="form-item required">
                                        <text class="form-label">昵称</text>
                                        <input
                                                class="form-input"
                                                v-model="form.nickname"
                                                placeholder="请输入昵称"
                                                maxlength="50"
                                        />
                                </view>

                                <!-- 性别 -->
                                <view class="form-item" @click="showGenderPicker = true">
                                        <text class="form-label">性别</text>
                                        <view class="form-value-row">
                                                <text class="form-value" :class="{ 'placeholder': !form.gender }">
                                                        {{ genderText || '请选择' }}
                                                </text>
                                                <u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
                                        </view>
                                </view>

                                <!-- 生日 -->
                                <view class="form-item" @click="showBirthdayPicker = true">
                                        <text class="form-label">生日</text>
                                        <view class="form-value-row">
                                                <text class="form-value" :class="{ 'placeholder': !form.birthday }">
                                                        {{ form.birthday || '请选择' }}
                                                </text>
                                                <u-icon name="arrow-right" size="28" color="#ccc"></u-icon>
                                        </view>
                                </view>

                                <!-- 个人简介 -->
                                <view class="form-item textarea-item">
                                        <text class="form-label">个人简介</text>
                                        <textarea
                                                class="form-textarea"
                                                v-model="form.bio"
                                                placeholder="介绍一下自己吧~"
                                                maxlength="100"
                                                :auto-height="false"
                                        />
                                        <text class="bio-count">{{ (form.bio || '').length }}/100</text>
                                </view>
                        </view>

                        <!-- 账号信息（只读） -->
                        <view class="form-section readonly-section">
                                <view class="section-title">账号信息</view>

                                <view class="form-item">
                                        <text class="form-label">手机号</text>
                                        <view class="form-value-row">
                                                <text class="form-value readonly-text">{{ maskedMobile }}</text>
                                                <view class="change-btn" @click="handleChangeMobile">
                                                        <text class="change-btn-text">修改</text>
                                                </view>
                                        </view>
                                </view>
                        </view>

                        <!-- 保存按钮 -->
                        <view class="submit-section">
                                <u-button
                                        type="error"
                                        hover-class="none"
                                        shape="circle"
                                        :loading="submitting"
                                        :disabled="submitting"
                                        :custom-style="{ backgroundColor: '#E62129', color: '#fff', fontSize: '32rpx', height: '88rpx' }"
                                        @click="submit"
                                >保存修改</u-button>
                        </view>
                </view>

                <!-- 性别选择器 -->
                <u-action-sheet
                        :list="genderList"
                        v-model="showGenderPicker"
                        :cancel-btn="true"
                        @click="onGenderSelect"
                ></u-action-sheet>

                <!-- 生日选择器 -->
                <u-picker
                        mode="time"
                        v-model="showBirthdayPicker"
                        :params="birthdayPickerParams"
                        :default-time="form.birthday || '2000-01-01'"
                        @confirm="onBirthdayConfirm"
                ></u-picker>

                <!-- 底部导航 -->
                <fa-tabbar></fa-tabbar>
        </view>
</template>

<script>
        import { avatar } from '@/common/fa.mixin.js';

        export default {
                mixins: [avatar],
                data() {
                        return {
                                loading: true,
                                submitting: false,
                                defaultAvatar: '',
                                showGenderPicker: false,
                                showBirthdayPicker: false,
                                form: {
                                        avatar: '',
                                        username: '',
                                        nickname: '',
                                        gender: 0,
                                        birthday: '',
                                        bio: ''
                                },
                                genderList: [
                                        { text: '男' },
                                        { text: '女' },
                                        { text: '保密' }
                                ],
                                birthdayPickerParams: {
                                        year: true,
                                        month: true,
                                        day: true,
                                        hour: false,
                                        minute: false,
                                        second: false,
                                        timestamp: false
                                }
                        };
                },
                computed: {
                        genderText() {
                                const map = { 1: '男', 2: '女', 0: '保密' };
                                return map[this.form.gender] || '';
                        },
                        maskedMobile() {
                                if (!this.vuex_user || !this.vuex_user.mobile) return '未绑定';
                                const m = this.vuex_user.mobile;
                                if (m.length >= 7) {
                                        return m.substring(0, 3) + '****' + m.substring(7);
                                }
                                return m;
                        }
                },
                onLoad() {
                        this.loadProfile();
                        this.defaultAvatar = this.$IMG_URL + '/images/default-avatar.png';
                },
                onShow() {
                        // 移除事件监听，防止重复绑定
                        uni.$off('uAvatarCropper', this.upload);
                },
                methods: {
                        // 加载个人资料
                        async loadProfile() {
                                try {
                                        const res = await this.$api.getProfileInfo();
                                        if (res.code == 1) {
                                                const data = res.data || {};
                                                this.form.avatar = data.avatar || '';
                                                this.form.username = data.username || '';
                                                this.form.nickname = data.nickname || '';
                                                this.form.gender = data.gender || 0;
                                                this.form.birthday = data.birthday || '';
                                                this.form.bio = data.bio || '';
                                        } else {
                                                this.$u.toast(res.msg || '加载失败');
                                        }
                                } catch (e) {
                                        console.error('loadProfile error:', e);
                                        this.$u.toast('加载失败，请重试');
                                } finally {
                                        this.loading = false;
                                }
                        },

                        // 头像选择
                        handleChooseAvatar() {
                                // #ifdef MP-WEIXIN
                                this.$u.toast('请在头像区域长按选择');
                                // #endif
                                // #ifndef MP-WEIXIN
                                this.chooseAvatar();
                                // #endif
                        },

                        // 微信小程序头像选择回调
                        onChooseAvatar(e) {
                                this.$api.goUpload({
                                        filePath: e.detail.avatarUrl,
                                        name: 'file'
                                }).then(res => {
                                        if (res.code) {
                                                this.form.avatar = res.data.fullurl;
                                        } else {
                                                this.$u.toast(res.msg || '上传失败');
                                        }
                                }).catch(() => {
                                        this.$u.toast('图片上传失败');
                                });
                        },

                        // 性别选择
                        onGenderSelect(index) {
                                const genderMap = [1, 2, 0]; // 男=1, 女=2, 保密=0
                                this.form.gender = genderMap[index] || 0;
                        },

                        // 生日选择确认
                        onBirthdayConfirm(e) {
                                if (e && e.year && e.month && e.day) {
                                        const month = String(e.month).padStart(2, '0');
                                        const day = String(e.day).padStart(2, '0');
                                        this.form.birthday = `${e.year}-${month}-${day}`;
                                }
                        },

                        // 修改手机号
                        handleChangeMobile() {
                                this.$u.toast('请在设置中修改手机号');
                        },

                        // 提交保存
                        async submit() {
                                // 表单校验
                                if (!this.form.nickname || !this.form.nickname.trim()) {
                                        this.$u.toast('请输入昵称');
                                        return;
                                }
                                if (this.form.nickname.trim().length < 2) {
                                        this.$u.toast('昵称至少2个字符');
                                        return;
                                }
                                if (this.form.username && this.form.username.trim().length < 3) {
                                        this.$u.toast('用户名至少3个字符');
                                        return;
                                }

                                this.submitting = true;
                                try {
                                        const params = {
                                                avatar: this.form.avatar || '',
                                                username: this.form.username || '',
                                                nickname: this.form.nickname.trim(),
                                                gender: this.form.gender,
                                                birthday: this.form.birthday || '',
                                                bio: this.form.bio || ''
                                        };

                                        const res = await this.$api.getUserProfile(params);
                                        if (res.code == 1) {
                                                this.$u.toast('保存成功');
                                                // 同步更新vuex用户信息
                                                const userInfo = this.vuex_user || {};
                                                const updatedData = res.data || {};
                                                if (updatedData.nickname || updatedData.avatar) {
                                                        this.$u.vuex('vuex_user', {
                                                                ...userInfo,
                                                                nickname: updatedData.nickname || userInfo.nickname,
                                                                avatar: updatedData.avatar || userInfo.avatar
                                                        });
                                                }
                                                // 延迟返回上一页
                                                setTimeout(() => {
                                                        uni.navigateBack();
                                                }, 800);
                                        } else {
                                                this.$u.toast(res.msg || '保存失败');
                                        }
                                } catch (e) {
                                        console.error('submit error:', e);
                                        this.$u.toast('保存失败，请重试');
                                } finally {
                                        this.submitting = false;
                                }
                        }
                }
        };
</script>

<style scoped lang="scss">
.profile-container {
        min-height: 100vh;
        background-color: #F7F8FA;
        padding-bottom: 120rpx;
}

// 加载中
.loading-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 200rpx 0;

        .loading-text {
                margin-top: 20rpx;
                font-size: 26rpx;
                color: #999;
        }
}

// 头像区
.avatar-section {
        background: #FFFFFF;
        margin: 20rpx 24rpx;
        border-radius: 16rpx;
        padding: 28rpx 30rpx;

        .section-title {
                font-size: 28rpx;
                font-weight: 500;
                color: #333;
                margin-bottom: 24rpx;
        }

        .avatar-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
        }

        .avatar-action {
                display: flex;
                align-items: center;
                flex: 1;
                margin-left: 30rpx;
                justify-content: flex-end;

                .avatar-hint {
                        font-size: 26rpx;
                        color: #999;
                        margin-right: 10rpx;
                }
        }
}

// 表单区块
.form-section {
        background: #FFFFFF;
        margin: 0 24rpx 20rpx;
        border-radius: 16rpx;
        padding: 28rpx 30rpx 0;

        .section-title {
                font-size: 28rpx;
                font-weight: 500;
                color: #333;
                margin-bottom: 20rpx;
        }
}

// 表单项
.form-item {
        display: flex;
                align-items: center;
                padding: 24rpx 0;
                border-bottom: 1rpx solid #f5f5f5;
                position: relative;

                &:last-child {
                        border-bottom: none;
                }
        }

.form-label {
        font-size: 28rpx;
        color: #333;
        width: 160rpx;
                flex-shrink: 0;
        }

.form-item.required .form-label::before {
        content: '*';
        color: #E62129;
        margin-right: 4rpx;
}

.form-input {
        flex: 1;
        font-size: 28rpx;
        color: #333;
        text-align: right;
                height: 48rpx;
                line-height: 48rpx;
}

.form-value-row {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: flex-end;
}

.form-value {
        font-size: 28rpx;
        color: #333;
        margin-right: 10rpx;

        &.placeholder {
                color: #ccc;
        }
}

.readonly-text {
        color: #666;
}

.change-btn {
        margin-left: 16rpx;
        padding: 6rpx 20rpx;
        border: 1rpx solid #E62129;
        border-radius: 24rpx;

        .change-btn-text {
                font-size: 22rpx;
                color: #E62129;
        }
}

// 多行文本
.textarea-item {
                flex-direction: column;
                align-items: stretch;
}

.textarea-item .form-label {
        margin-bottom: 16rpx;
}

.form-textarea {
        width: 100%;
        height: 160rpx;
        font-size: 28rpx;
        color: #333;
        line-height: 1.6;
        padding: 16rpx 20rpx;
        background: #F7F8FA;
        border-radius: 12rpx;
        box-sizing: border-box;
}

.bio-count {
        display: block;
        text-align: right;
        font-size: 22rpx;
        color: #ccc;
        margin-top: 8rpx;
        padding-bottom: 8rpx;
}

// 只读区块
.readonly-section {
        opacity: 0.9;
}

// 提交按钮
.submit-section {
        padding: 40rpx 60rpx;
}
</style>

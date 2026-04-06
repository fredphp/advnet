<template>
        <view>
                <!-- 顶部导航 -->
                <fa-navbar :title="info.title || '单页'" :border-bottom="false"></fa-navbar>
                <image class="page-imgae" v-if="info.image" :src="info.image" mode="aspectFill"></image>

                <view class="text-weight u-font-30 u-border-bottom u-p-30"><text v-text="info.title"></text></view>
                <view class="u-p-30">
                        <u-parse
                                :html="info.content"
                                :tag-style="vuex_parse_style"
                                :domain="vuex_config && vuex_config.upload && vuex_config.upload.cdnurl ? vuex_config.upload.cdnurl : ''"
                                @linkpress="diylinkpress"
                        ></u-parse>
                </view>
        </view>
</template>

<script>
export default {
        onLoad(e) {
                this.id = e.id || '';
                this.tpl = e.tpl || '';
                this.category = e.category || '';
                this.getPageDetail();
        },
        data() {
                return {
                        id: '',
                        tpl: '',
                        category: '',
                        info: {}
                };
        },
        methods: {
                getPageDetail() {
                        const params = {};
                        if (this.id) params.id = this.id;
                        if (this.tpl) params.tpl = this.tpl;
                        if (this.category) params.category = this.category;

                        this.$api
                                .singlepageDetail(params)
                                .then(res => {
                                        if (res.code) {
                                                this.info = res.data;
                                        } else {
                                                this.$u.toast(res.msg);
                                                setTimeout(() => {
                                                        uni.$u.route({
                                                                type: 'back'
                                                        });
                                                }, 1500);
                                        }
                                });
                }
        }
};
</script>

<style lang="scss" scoped>
.page-imgae {
        height: 400rpx;
        width: 100%;
}
</style>

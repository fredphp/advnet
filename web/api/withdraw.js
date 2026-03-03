/**
 * 提现系统 - 前端 API 封装
 * 文件位置: /web/api/withdraw.js
 */

import request from '@/utils/request';

/**
 * 获取提现配置
 * @returns {Promise}
 */
export function getWithdrawConfig() {
    return request({
        url: '/api/withdraw/config',
        method: 'GET'
    });
}

/**
 * 计算提现金额预览
 * @param {number} coinAmount - 提现金币数量
 * @returns {Promise}
 */
export function previewWithdraw(coinAmount) {
    return request({
        url: '/api/withdraw/preview',
        method: 'POST',
        data: {
            coin_amount: coinAmount
        }
    });
}

/**
 * 申请提现
 * @param {Object} data - 提现数据
 * @param {number} data.coin_amount - 提现金币数量
 * @param {string} data.withdraw_type - 提现方式 wechat/alipay/bank
 * @param {string} data.withdraw_account - 提现账号
 * @param {string} data.withdraw_name - 收款人姓名
 * @param {string} data.bank_name - 银行名称(银行卡提现)
 * @param {string} data.bank_branch - 开户行(银行卡提现)
 * @returns {Promise}
 */
export function applyWithdraw(data) {
    return request({
        url: '/api/withdraw/apply',
        method: 'POST',
        data: data
    });
}

/**
 * 获取提现记录列表
 * @param {Object} params - 参数
 * @param {number} params.status - 状态筛选
 * @param {number} params.page - 页码
 * @param {number} params.limit - 每页数量
 * @returns {Promise}
 */
export function getWithdrawList(params = {}) {
    return request({
        url: '/api/withdraw/list',
        method: 'GET',
        params: {
            status: params.status || '',
            page: params.page || 1,
            limit: params.limit || 20
        }
    });
}

/**
 * 获取提现详情
 * @param {number} id - 提现订单ID
 * @returns {Promise}
 */
export function getWithdrawDetail(id) {
    return request({
        url: '/api/withdraw/detail',
        method: 'GET',
        params: {
            id: id
        }
    });
}

/**
 * 取消提现
 * @param {number} id - 提现订单ID
 * @returns {Promise}
 */
export function cancelWithdraw(id) {
    return request({
        url: '/api/withdraw/cancel',
        method: 'POST',
        data: {
            id: id
        }
    });
}

/**
 * 获取提现统计
 * @returns {Promise}
 */
export function getWithdrawStat() {
    return request({
        url: '/api/withdraw/stat',
        method: 'GET'
    });
}

/**
 * 获取用户的提现账号列表
 * @returns {Promise}
 */
export function getWithdrawAccounts() {
    return request({
        url: '/api/withdraw/accounts',
        method: 'GET'
    });
}

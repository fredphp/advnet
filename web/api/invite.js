/**
 * 邀请分佣 - 前端 API 封装
 * 文件位置: /web/api/invite.js
 */

import request from '@/utils/request';

/**
 * 获取邀请统计概览
 * @returns {Promise}
 */
export function getInviteOverview() {
    return request({
        url: '/api/invite/overview',
        method: 'GET'
    });
}

/**
 * 获取我的邀请码
 * @returns {Promise}
 */
export function getMyInviteCode() {
    return request({
        url: '/api/invite/myCode',
        method: 'GET'
    });
}

/**
 * 绑定邀请关系
 * @param {string} inviteCode - 邀请码
 * @param {string} channel - 渠道
 * @returns {Promise}
 */
export function bindInvite(inviteCode, channel = 'link') {
    return request({
        url: '/api/invite/bind',
        method: 'POST',
        data: {
            invite_code: inviteCode,
            channel: channel
        }
    });
}

/**
 * 获取邀请列表
 * @param {Object} params - 参数
 * @param {number} params.level - 层级: 1=一级, 2=二级, 0=全部
 * @param {number} params.page - 页码
 * @param {number} params.limit - 每页数量
 * @returns {Promise}
 */
export function getInviteList(params = {}) {
    return request({
        url: '/api/invite/list',
        method: 'GET',
        params: {
            level: params.level || 0,
            page: params.page || 1,
            limit: params.limit || 20
        }
    });
}

/**
 * 获取佣金明细
 * @param {Object} params - 参数
 * @param {string} params.source_type - 来源类型: withdraw/video/red_packet/game
 * @param {number} params.level - 层级: 1=一级, 2=二级, 0=全部
 * @param {number} params.page - 页码
 * @param {number} params.limit - 每页数量
 * @returns {Promise}
 */
export function getCommissionList(params = {}) {
    return request({
        url: '/api/invite/commissionList',
        method: 'GET',
        params: {
            source_type: params.source_type || '',
            level: params.level || 0,
            page: params.page || 1,
            limit: params.limit || 20
        }
    });
}

/**
 * 获取佣金统计图表数据
 * @param {Object} params - 参数
 * @param {string} params.type - 类型: daily/source/level
 * @param {string} params.start_date - 开始日期
 * @param {string} params.end_date - 结束日期
 * @returns {Promise}
 */
export function getCommissionChart(params = {}) {
    return request({
        url: '/api/invite/chart',
        method: 'GET',
        params: {
            type: params.type || 'daily',
            start_date: params.start_date || '',
            end_date: params.end_date || ''
        }
    });
}

/**
 * 获取邀请排行
 * @param {Object} params - 参数
 * @param {string} params.type - 类型: invite/commission
 * @param {number} params.limit - 限制条数
 * @returns {Promise}
 */
export function getInviteRanking(params = {}) {
    return request({
        url: '/api/invite/ranking',
        method: 'GET',
        params: {
            type: params.type || 'invite',
            limit: params.limit || 50
        }
    });
}

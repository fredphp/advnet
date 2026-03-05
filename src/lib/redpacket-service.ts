/**
 * 红包服务类
 * 包含红包金额计算、配置获取等核心业务逻辑
 */

import { db } from '@/lib/db';
import {
  getClickData,
  setClickData,
  deleteClickData,
  getConfigCache,
  setConfigCache,
  clearConfigCache,
  getCurrentHour,
  isInTimeSegment,
  getCurrentTimeSegment,
  isTodayData,
  type RedPacketConfigCache,
  type RedPacketClickData,
} from './redpacket-cache';

/**
 * 从数据库获取所有启用的配置
 */
async function fetchConfigsFromDB(): Promise<RedPacketConfigCache[]> {
  const configs = await db.redPacketRewardConfig.findMany({
    where: { status: 1 },
    orderBy: [{ minAmount: 'asc' }, { startTime: 'asc' }],
  });
  return configs.map((c) => ({
    id: c.id,
    name: c.name,
    minAmount: c.minAmount,
    maxAmount: c.maxAmount,
    startTime: c.startTime,
    endTime: c.endTime,
    baseMin: c.baseMin,
    baseMax: c.baseMax,
    accumulateMin: c.accumulateMin,
    accumulateMax: c.accumulateMax,
    maxReward: c.maxReward,
    status: c.status,
  }));
}

/**
 * 获取配置（优先从缓存获取）
 */
export async function getConfigs(): Promise<RedPacketConfigCache[]> {
  let configs = getConfigCache();
  if (!configs || configs.length === 0) {
    configs = await fetchConfigsFromDB();
    setConfigCache(configs);
  }
  return configs;
}

/**
 * 更新配置缓存（配置变更时调用）
 */
export async function refreshConfigCache(): Promise<void> {
  clearConfigCache();
  await getConfigs();
}

/**
 * 生成指定范围内的随机整数
 */
function randomInt(min: number, max: number): number {
  return Math.floor(Math.random() * (max - min + 1)) + min;
}

/**
 * 根据今日已领取金额和当前时间段获取匹配的配置
 */
export async function getMatchingConfig(
  todayClaimedAmount: number
): Promise<RedPacketConfigCache | null> {
  const configs = await getConfigs();
  const currentHour = getCurrentHour();

  // 找到同时满足以下条件的配置：
  // 1. 今日已领取金额在 [minAmount, maxAmount] 范围内
  // 2. 当前时间在 [startTime, endTime) 范围内
  for (const config of configs) {
    if (
      todayClaimedAmount >= config.minAmount &&
      todayClaimedAmount <= config.maxAmount &&
      currentHour >= config.startTime &&
      currentHour < config.endTime
    ) {
      return config;
    }
  }

  // 如果没有精确匹配，尝试只匹配今日金额范围的配置（用于时间段判断）
  for (const config of configs) {
    if (
      todayClaimedAmount >= config.minAmount &&
      todayClaimedAmount <= config.maxAmount
    ) {
      return config;
    }
  }

  return null;
}

/**
 * 判断是否需要重新生成基础金额
 * 条件：时间段发生变化，且当前基础金额不在新时间段的范围内
 */
function shouldRegenerateBaseAmount(
  currentBaseAmount: number,
  currentSegment: string,
  config: RedPacketConfigCache
): boolean {
  const newSegment = getCurrentTimeSegment(config.startTime, config.endTime);
  
  // 如果时间段没变，不需要重新生成
  if (currentSegment === newSegment) {
    return false;
  }

  // 如果时间段变了，检查当前基础金额是否在新范围外
  if (
    currentBaseAmount < config.baseMin ||
    currentBaseAmount > config.baseMax
  ) {
    return true;
  }

  return false;
}

/**
 * 点击红包 - 核心逻辑
 * 返回点击后的红包数据
 */
export async function clickRedPacket(
  userId: string,
  todayClaimedAmount: number
): Promise<{
  success: boolean;
  amount: number;
  baseAmount: number;
  isBaseGenerated: boolean;
  message: string;
}> {
  // 获取匹配的配置
  const config = await getMatchingConfig(todayClaimedAmount);
  
  if (!config) {
    return {
      success: false,
      amount: 0,
      baseAmount: 0,
      isBaseGenerated: false,
      message: '当前时间段没有可用的红包配置',
    };
  }

  // 获取当前用户的红包点击数据
  let clickData = getClickData(userId);
  
  // 检查数据是否是今天的
  if (!isTodayData(clickData)) {
    clickData = null;
  }

  const currentSegment = getCurrentTimeSegment(config.startTime, config.endTime);
  let isBaseGenerated = false;
  let baseAmount = 0;
  let amount = 0;

  if (!clickData || clickData.amount === 0) {
    // 情况1：没有数据或金额为0，需要生成基础金额
    baseAmount = randomInt(config.baseMin, config.baseMax);
    amount = baseAmount;
    isBaseGenerated = true;
  } else {
    // 检查是否需要重新生成基础金额
    if (shouldRegenerateBaseAmount(clickData.baseAmount, clickData.lastTimeSegment, config)) {
      // 时间段变化且基础金额不在新范围内，重新生成基础金额
      baseAmount = randomInt(config.baseMin, config.baseMax);
      amount = baseAmount;
      isBaseGenerated = true;
    } else {
      // 正常累加
      baseAmount = clickData.baseAmount;
      const accumulateAmount = randomInt(config.accumulateMin, config.accumulateMax);
      amount = clickData.amount + accumulateAmount;
      
      // 检查是否超过封顶金额
      if (amount > config.maxReward) {
        amount = config.maxReward;
      }
    }
  }

  // 保存数据
  const newData: RedPacketClickData = {
    amount,
    baseAmount,
    lastTimeSegment: currentSegment,
    updatedAt: Date.now(),
  };
  setClickData(userId, newData);

  return {
    success: true,
    amount,
    baseAmount,
    isBaseGenerated,
    message: isBaseGenerated ? '生成基础红包金额' : '累加红包金额',
  };
}

/**
 * 获取当前红包金额
 */
export function getRedPacketAmount(userId: string): {
  amount: number;
  baseAmount: number;
  isValid: boolean;
} {
  const clickData = getClickData(userId);
  
  if (!clickData || !isTodayData(clickData)) {
    return { amount: 0, baseAmount: 0, isValid: false };
  }

  return {
    amount: clickData.amount,
    baseAmount: clickData.baseAmount,
    isValid: true,
  };
}

/**
 * 领取红包
 * 将红包金额转入用户余额，并清除红包数据
 */
export async function claimRedPacket(userId: string): Promise<{
  success: boolean;
  amount: number;
  message: string;
}> {
  const { amount, isValid } = getRedPacketAmount(userId);

  if (!isValid || amount <= 0) {
    return {
      success: false,
      amount: 0,
      message: '没有可领取的红包',
    };
  }

  try {
    // 更新用户余额
    await db.user.update({
      where: { id: userId },
      data: {
        balance: { increment: amount },
      },
    });

    // 记录领取
    await db.redPacketRecord.create({
      data: {
        userId,
        amount,
      },
    });

    // 清除红包数据
    deleteClickData(userId);

    return {
      success: true,
      amount,
      message: `成功领取 ${amount} 金币`,
    };
  } catch (error) {
    console.error('领取红包失败:', error);
    return {
      success: false,
      amount: 0,
      message: '领取红包失败，请稍后重试',
    };
  }
}

/**
 * 获取用户今日已领取金额
 */
export async function getTodayClaimedAmount(userId: string): Promise<number> {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  
  const records = await db.redPacketRecord.findMany({
    where: {
      userId,
      claimedAt: { gte: today },
    },
    select: { amount: true },
  });

  return records.reduce((sum, r) => sum + r.amount, 0);
}
